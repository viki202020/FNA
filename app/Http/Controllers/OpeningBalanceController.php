<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Excel;
use App\Imports\RawRows;
use App\Support\BillingPosting;
use App\Support\FinancialYears;
use Session;

/**
 * Upload of opening balances: the same way the bank and journal uploads work. You say which row is the header, which is the last,
 * and which column holds what; FNA shows a preview and only posts when every row matched an account and debits equal credits.
 * One voucher (OBV) holds all the opening entries of the year. A year that already has opening entries is never overwritten.
 */
class OpeningBalanceController extends Controller
{
    private function merchant() { return Session::get('merchant_id'); }

    public function form()
    {
        return view('opening.form');
    }

    public function template()
    {
        $csv = "Account name,Debit,Credit,Party (optional),Schedule or note (optional)\nBank-AU SFB-5398,64520.00,,,\nTrade Receivables,221338.14,,Analytix Business Solutions,Schedule: Debtors\nAccounts Payable,,35000.00,Razorpay Software,Schedule: Creditors\nShare capital,,100000.00,,\n";
        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="opening_balances_template.csv"']);
    }

    private static function num($v)
    {
        $v = trim(str_replace([',', ' ', "\u{20B9}"], '', (string) $v));
        return is_numeric($v) ? round((float) $v, 2) : 0.0;
    }

    public function preview(Request $request)
    {
        $request->validate(['csv_file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240', 'header_row' => 'required|integer|min:0', 'last_row' => 'required|integer|min:1', 'as_at' => 'required|date',
            'layout' => 'required|in:two,one', 'name_col' => 'required|integer|min:1']);
        $sheets = Excel::toArray(new RawRows, $request->file('csv_file'));
        $rows = $sheets[0] ?? [];
        $layout = $request->input('layout');
        $nameCol = (int) $request->input('name_col') - 1;
        $drCol = (int) $request->input('debit_col') - 1; $crCol = (int) $request->input('credit_col') - 1;
        $amtCol = (int) $request->input('amount_col') - 1; $sideCol = (int) $request->input('side_col') - 1;
        $partyCol = $request->input('party_col') ? (int) $request->input('party_col') - 1 : null;
        $noteCol = $request->input('note_col') ? (int) $request->input('note_col') - 1 : null;
        $asAt = $request->input('as_at');
        $fyear = FinancialYears::yearForDate($asAt);

        $heads = DB::table('head')->where('status', 1)->get(['id', 'name']);
        $byName = [];
        foreach ($heads as $h) { $byName[self::norm($h->name)][] = $h->id; }
        $parties = DB::table('customer')->where('merchant', $this->merchant())->get(['id', 'first_name', 'last_name', 'display_name']);
        $partyByName = [];
        foreach ($parties as $p) { foreach ([$p->display_name, trim($p->first_name . ' ' . $p->last_name)] as $n) { if ($n) { $partyByName[self::norm($n)] = $p->id; } } }

        $out = []; $issues = []; $dr = 0; $cr = 0;
        $from = (int) $request->input('header_row'); $to = (int) $request->input('last_row');
        for ($i = $from; $i < min($to, count($rows)); $i++) {     // header_row is 1-based; the data starts on the row after it
            $r = $rows[$i];
            $name = trim((string) ($r[$nameCol] ?? ''));
            if ($name === '') { continue; }
            if ($layout === 'two') { $d = self::num($r[$drCol] ?? 0); $c = self::num($r[$crCol] ?? 0); }
            else {
                $amt = self::num($r[$amtCol] ?? 0); $side = strtoupper(trim((string) ($r[$sideCol] ?? '')));
                $d = str_starts_with($side, 'D') ? $amt : 0; $c = str_starts_with($side, 'C') ? $amt : 0;
                if ($amt > 0 && $d == 0 && $c == 0) { $issues[] = 'Row ' . ($i + 1) . ' (' . $name . '): the Dr/Cr column must say Dr or Cr.'; continue; }
            }
            if ($d == 0 && $c == 0) { continue; }
            if ($d > 0 && $c > 0) { $issues[] = 'Row ' . ($i + 1) . ' (' . $name . '): has both a debit and a credit.'; continue; }
            $match = $byName[self::norm($name)] ?? [];
            $headId = null;
            if (count($match) === 1) { $headId = $match[0]; }
            elseif (count($match) > 1) { $issues[] = 'Row ' . ($i + 1) . ' (' . $name . '): more than one account has this name.'; }
            else { $issues[] = 'Row ' . ($i + 1) . ' (' . $name . '): no account with this name. Add it under Account heads or correct the name, then upload again.'; }
            $party = $partyCol !== null ? trim((string) ($r[$partyCol] ?? '')) : '';
            $out[] = ['row' => $i + 1, 'name' => $name, 'head_id' => $headId, 'debit' => $d, 'credit' => $c,
                'party' => $party, 'party_id' => $party !== '' ? ($partyByName[self::norm($party)] ?? null) : null, 'note' => $noteCol !== null ? trim((string) ($r[$noteCol] ?? '')) : ''];
            $dr += $d; $cr += $c;
        }
        $dr = round($dr, 2); $cr = round($cr, 2);
        if (!$out) { $issues[] = 'No rows with an amount were found. Check the header row, last row and the columns you chose.'; }
        if (abs($dr - $cr) > 0.004) { $issues[] = 'Debits ' . number_format($dr, 2) . ' and credits ' . number_format($cr, 2) . ' differ by ' . number_format(abs($dr - $cr), 2) . '. Opening balances must balance.'; }
        if (!FinancialYears::acceptsDate($asAt)) { $issues[] = 'The financial year of ' . $asAt . ' (' . $fyear . ') is closed or not open yet.'; }
        if (DB::table('statement')->where('merchant_id', $this->merchant())->where('fyear', $fyear)->where('opening', 1)->exists()) {
            $issues[] = 'Financial year ' . $fyear . ' already has opening entries. They are never overwritten; correct them with a journal voucher.';
        }
        Session::put('ob_import', ['rows' => $out, 'as_at' => $asAt, 'fyear' => $fyear, 'ok' => !$issues]);
        return view('opening.preview', ['rows' => $out, 'issues' => $issues, 'dr' => $dr, 'cr' => $cr, 'as_at' => $asAt, 'fyear' => $fyear]);
    }

    private static function norm($s) { return preg_replace('/\s+/', ' ', strtolower(trim((string) $s))); }

    public function confirm(Request $request)
    {
        $data = Session::get('ob_import');
        if (!$data || empty($data['ok'])) { return redirect('merchant/opening-balance')->withErrors(['Upload the file again: there is nothing ready to post.']); }
        $me = Session::get('id'); $m = $this->merchant();
        try {
            $number = DB::transaction(function () use ($data, $me, $m) {
                $fy = $data['fyear'];
                if (DB::table('statement')->where('merchant_id', $m)->where('fyear', $fy)->where('opening', 1)->lockForUpdate()->exists()) { throw new \RuntimeException('This year already has opening entries.'); }
                $no = BillingPosting::nextVoucherNumber('OBV', $fy);
                $vid = DB::table('vouchers')->insertGetId([
                    'merchant_id' => $m, 'voucher_no' => $no, 'series' => 'OBV', 'fyear' => $fy, 'voucher_date' => $data['as_at'], 'status' => 'posted', 'version' => 1,
                    'narration' => 'Opening balances as at ' . $data['as_at'], 'created_by' => $me, 'created_at' => now(), 'updated_at' => now(),
                ]);
                foreach ($data['rows'] as $r) {
                    DB::table('statement')->insert([
                        'voucher_no' => $no, 'voucher_id' => $vid, 'debit' => $r['debit'] > 0 ? number_format($r['debit'], 2, '.', '') : '-', 'credit' => $r['credit'] > 0 ? number_format($r['credit'], 2, '.', '') : '-',
                        'remark' => 'Opening balance: ' . $r['name'] . ($r['note'] ? ' (' . $r['note'] . ')' : ''), 'head_id' => $r['head_id'], 'party_id' => $r['party_id'],
                        'st_date' => $data['as_at'], 'fyear' => $fy, 'status' => 1, 'opening' => 1, 'merchant_id' => $m, 'created_at' => date('Y-m-d H:i:s'), 'created_by' => $me ?: 0,
                    ]);
                }
                DB::table('voucher_versions')->insert(['voucher_id' => $vid, 'version' => 1, 'action' => 'created', 'reason' => 'Opening balance upload', 'changed_by' => $me, 'created_at' => now(), 'updated_at' => now()]);
                return $no;
            });
        } catch (\Throwable $e) {
            return redirect('merchant/opening-balance')->withErrors(['Not posted: ' . $e->getMessage()]);
        }
        Session::forget('ob_import');
        return redirect('merchant/voucher/' . $number)->with('success_msg', 'Opening balances posted as ' . $number . '.');
    }
}
