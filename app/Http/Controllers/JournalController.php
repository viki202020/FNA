<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\FinancialYears;
use App\Support\VoucherNumber;
use Session;

/** The manual journal voucher (series MJV). A draft may be unbalanced; a posted voucher always balances. */
class JournalController extends Controller
{
    private function merchant() { return Session::get('merchant_id'); }

    private function lookups()
    {
        return [
            'heads' => DB::table('head')->where('status', 1)->orderBy('name')->get(['id', 'name', 'department_required']),
            'departments' => DB::table('departments')->where('merchant_id', $this->merchant())->where('active', 1)->orderBy('name')->get(['id', 'name']),
            'parties' => DB::table('customer')->where('merchant', $this->merchant())->where('status', 1)->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'display_name']),
        ];
    }

    public function create()
    {
        $next = 'MJV' . str_pad((string) (((int) DB::table('vouchers')->where('voucher_no', 'like', 'MJV%')->selectRaw('MAX(CAST(SUBSTRING(voucher_no,4) AS UNSIGNED)) m')->value('m')) + 1), 4, '0', STR_PAD_LEFT);
        return view('journal.form', $this->lookups() + ['voucher' => null, 'lines' => [], 'next' => $next, 'defaultDate' => PaymentReversalController::defaultDate()]);
    }

    public function edit($id)
    {
        $v = DB::table('vouchers')->where('merchant_id', $this->merchant())->where('id', $id)->where('status', 'draft')->first();
        abort_unless($v, 404);
        $lines = DB::table('voucher_draft_lines')->where('voucher_id', $id)->orderBy('line_no')->get();
        return view('journal.form', $this->lookups() + ['voucher' => $v, 'lines' => $lines, 'next' => $v->voucher_no, 'defaultDate' => $v->voucher_date]);
    }

    /** Reads and checks the rows. Returns [rows, errors, debit, credit]. */
    private function rows(Request $r, $forPost)
    {
        $heads = DB::table('head')->pluck('department_required', 'id');
        $rows = []; $errors = []; $dr = 0; $cr = 0;
        foreach ((array) $r->input('head_id') as $i => $h) {
            $d = round((float) str_replace(',', '', (string) ($r->input('debit')[$i] ?? 0)), 2);
            $c = round((float) str_replace(',', '', (string) ($r->input('credit')[$i] ?? 0)), 2);
            $narr = trim((string) ($r->input('narration')[$i] ?? ''));
            $dept = $r->input('department_id')[$i] ?? null; $party = $r->input('party_id')[$i] ?? null;
            if (!$h && $d == 0 && $c == 0 && $narr === '') { continue; }   // an empty row
            $n = count($rows) + 1;
            if (!$h) { $errors[] = "Row $n: choose an account."; }
            elseif (!isset($heads[$h])) { $errors[] = "Row $n: that account does not exist."; }
            if ($d > 0 && $c > 0) { $errors[] = "Row $n: a row has either a debit or a credit, not both."; }
            if ($d < 0 || $c < 0) { $errors[] = "Row $n: amounts cannot be negative."; }
            if ($forPost) {
                if ($d == 0 && $c == 0) { $errors[] = "Row $n: enter a debit or a credit."; }
                if ($narr === '') { $errors[] = "Row $n: narration is required."; }
                if ($h && !empty($heads[$h]) && !$dept) { $errors[] = "Row $n: this account needs a department."; }
            }
            $dr += $d; $cr += $c;
            $rows[] = ['head_id' => $h ?: null, 'narration' => $narr, 'department_id' => $dept ?: null, 'party_id' => $party ?: null, 'debit' => $d, 'credit' => $c];
        }
        if (!$rows) { $errors[] = 'Add at least one row.'; }
        if ($forPost) {
            if (count($rows) < 2) { $errors[] = 'A journal needs at least two rows.'; }
            if (abs($dr - $cr) > 0.004) { $errors[] = 'Debits and credits differ by ' . number_format(abs($dr - $cr), 2) . '. A voucher can be posted only when they are equal. You can save it as a draft.'; }
            if ($dr <= 0) { $errors[] = 'The voucher has no amount.'; }
        }
        return [$rows, $errors, $dr, $cr];
    }

    public function save(Request $request, $id = null)
    {
        $post = $request->input('action') === 'post';
        $date = $request->input('voucher_date');
        $errs = [];
        if (!$date || !strtotime($date)) { $errs[] = 'Enter the voucher date.'; }
        elseif (!FinancialYears::dateInCurrentYear($date)) { $errs[] = 'The date must be inside the open financial year.'; }
        [$rows, $rowErrs] = $this->rows($request, $post);
        $errs = array_merge($errs, $rowErrs);
        if ($errs) { return back()->withInput()->withErrors($errs); }

        $me = Session::get('id');
        try {
            $vid = DB::transaction(function () use ($request, $id, $post, $date, $rows, $me) {
                $fyear = FinancialYears::current();
                if ($id) {
                    $v = DB::table('vouchers')->where('merchant_id', $this->merchant())->where('id', $id)->where('status', 'draft')->lockForUpdate()->first();
                    if (!$v) { throw new \RuntimeException('Only a draft can be changed.'); }
                    DB::table('vouchers')->where('id', $id)->update(['voucher_date' => $date, 'reference' => $request->input('reference'), 'narration' => $rows[0]['narration'] ?? null, 'updated_by' => $me, 'updated_at' => now()]);
                    $vid = (int) $id;
                    // keep what the draft looked like before this edit (history), then replace its working lines
                    $old = DB::table('voucher_draft_lines')->where('voucher_id', $vid)->orderBy('line_no')->get();
                    DB::table('voucher_versions')->insert(['voucher_id' => $vid, 'version' => $v->version, 'action' => 'edited', 'snapshot' => json_encode($old), 'changed_by' => $me, 'created_at' => now(), 'updated_at' => now()]);
                    DB::table('voucher_draft_lines')->where('voucher_id', $vid)->delete();
                    $number = $v->voucher_no;
                } else {
                    $number = VoucherNumber::sequence('MJV', 4)->next();
                    $vid = DB::table('vouchers')->insertGetId([
                        'merchant_id' => $this->merchant(), 'voucher_no' => $number, 'series' => 'MJV', 'fyear' => $fyear, 'voucher_date' => $date, 'status' => 'draft', 'version' => 1,
                        'narration' => $rows[0]['narration'] ?? null, 'reference' => $request->input('reference'), 'created_by' => $me, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    DB::table('voucher_versions')->insert(['voucher_id' => $vid, 'version' => 1, 'action' => 'created', 'changed_by' => $me, 'created_at' => now(), 'updated_at' => now()]);
                }
                foreach ($rows as $n => $r) {
                    DB::table('voucher_draft_lines')->insert($r + ['voucher_id' => $vid, 'line_no' => $n + 1, 'created_at' => now(), 'updated_at' => now()]);
                }
                if ($post) { $this->postDraft($vid, $number, $date, $fyear, $rows, $me); }
                return $vid;
            });
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['Not saved: ' . $e->getMessage()]);
        }
        $no = DB::table('vouchers')->where('id', $vid)->value('voucher_no');
        return redirect('merchant/voucher/' . $no)->with('success_msg', $post ? 'Posted as ' . $no . '.' : 'Saved as a draft (' . $no . ').');
    }

    private function postDraft($vid, $number, $date, $fyear, $rows, $me)
    {
        foreach ($rows as $r) {
            DB::table('statement')->insert([
                'voucher_no' => $number, 'voucher_id' => $vid,
                'debit' => $r['debit'] > 0 ? number_format($r['debit'], 2, '.', '') : '-', 'credit' => $r['credit'] > 0 ? number_format($r['credit'], 2, '.', '') : '-',
                'remark' => $r['narration'], 'head_id' => $r['head_id'], 'st_date' => $date, 'fyear' => $fyear, 'status' => 1, 'opening' => 0,
                'department_id' => $r['department_id'], 'party_id' => $r['party_id'],
                'merchant_id' => $this->merchant(), 'created_at' => date('Y-m-d H:i:s'), 'created_by' => $me ?: 0,
            ]);
        }
        DB::table('vouchers')->where('id', $vid)->update(['status' => 'posted', 'voucher_date' => $date, 'fyear' => $fyear, 'updated_at' => now()]);
        DB::table('voucher_versions')->insert(['voucher_id' => $vid, 'version' => 1, 'action' => 'posted', 'changed_by' => $me, 'created_at' => now(), 'updated_at' => now()]);
    }
}
