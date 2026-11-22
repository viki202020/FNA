<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\TdsReturn;
use App\Support\TdsDue;
use Session;

/** TDS deposits with their challan details, and the quarterly return working papers. */
class TdsController extends Controller
{
    private function quarter(Request $r)
    {
        $qs = TdsReturn::quarters();
        $key = (string) $r->input('q');
        foreach ($qs as $x) { if ($x['key'] === $key) { return [$x, $qs]; } }
        return [$qs[0] ?? null, $qs];
    }

    public function index(Request $request)
    {
        $tab = $request->input('tab') === 'return' ? 'return' : 'deposits';
        [$cur, $qs] = $this->quarter($request);
        $data = ['tab' => $tab, 'quarters' => $qs, 'cur' => $cur, 'months' => TdsDue::months()];
        if ($tab === 'deposits') {
            $data['deposits'] = TdsReturn::deposits();
        } elseif ($cur) {
            [$from, $to] = TdsReturn::quarterRange($cur['fy'], $cur['q']);
            $ded = TdsReturn::deductions($from, $to);
            $data += ['ded' => $ded, 'monthly' => TdsReturn::monthly($cur['fy'], $cur['q'], $ded), 'challans' => $this->challans($cur), 'due' => TdsReturn::filingDue($cur['fy'], $cur['q']), 'form' => TdsReturn::formName($cur['fy'])];
        }
        return view('tds.index', $data);
    }

    private function challans($cur)
    {
        [$from] = TdsReturn::quarterRange($cur['fy'], $cur['q']);
        $months = [date('Y-m', strtotime($from)), date('Y-m', strtotime($from . ' +1 month')), date('Y-m', strtotime($from . ' +2 months'))];
        return DB::table('tds_challans as c')->join('statement as s', 's.id', '=', 'c.statement_id')->where('c.merchant_id', Session::get('merchant_id'))->whereIn('c.for_month', $months)
            ->orderBy('c.for_month')->orderBy('s.st_date')->get(['c.*', 's.st_date', 's.debit']);
    }

    public function save(Request $request)
    {
        $request->validate(['statement_id' => 'required|integer', 'for_month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'], 'bsr_code' => ['nullable', 'regex:/^\d{7}$/'], 'challan_serial' => ['nullable', 'regex:/^\d{1,5}$/'],
            'interest' => 'nullable|numeric|min:0', 'fee' => 'nullable|numeric|min:0', 'note' => 'nullable|max:300'], ['bsr_code.regex' => 'The BSR code is 7 digits.', 'challan_serial.regex' => 'The challan serial number is up to 5 digits.']);
        try {
            TdsReturn::saveChallan($request->input('statement_id'), $request->input('for_month'), $request->input('bsr_code'), $request->input('challan_serial'), $request->input('interest'), $request->input('fee'), $request->input('note'));
        } catch (\Throwable $e) { return back()->withErrors([$e->getMessage()]); }
        return back()->with('success_msg', 'Challan details saved.');
    }

    public function csv(Request $request, $part)
    {
        abort_unless(in_array($part, ['challans', 'deductees']), 404);
        [$cur] = $this->quarter($request);
        abort_unless($cur, 404);
        [$from, $to] = TdsReturn::quarterRange($cur['fy'], $cur['q']);
        $f = fn($v) => number_format((float) $v, 2, '.', '');
        $rows = [];
        $chal = $this->challans($cur);
        if ($part === 'challans') {
            $rows[] = ['Deduction month', 'Date of deposit', 'BSR code', 'Challan serial no', 'TDS deposited', 'Interest', 'Fee', 'Total'];
            foreach ($chal as $c) { $t = (float) $c->debit; $rows[] = [$c->for_month, date('d-m-Y', strtotime($c->st_date)), $c->bsr_code, $c->challan_serial, $f($t), $f($c->interest), $f($c->fee), $f($t + $c->interest + $c->fee)]; }
        } else {
            $rows[] = ['Deductee name', 'PAN', 'Section', 'Date of payment/credit', 'Amount paid/credited', 'Date of deduction', 'Rate', 'TDS deducted', 'Challan BSR', 'Challan serial'];
            foreach (TdsReturn::deductions($from, $to) as $d) {
                $ym = date('Y-m', strtotime($d['date'])); $c = $chal->firstWhere('for_month', $ym);
                $rows[] = [$d['name'], $d['pan'], $d['section'], $d['credit_date'] ? date('d-m-Y', strtotime($d['credit_date'])) : '', $d['base'] !== null ? $f($d['base']) : '', date('d-m-Y', strtotime($d['date'])), $d['rate'], $f($d['tds']), $c->bsr_code ?? '', $c->challan_serial ?? ''];
            }
        }
        $h = fopen('php://temp', 'w+');
        foreach ($rows as $r) { fputcsv($h, array_map(fn($c) => (is_string($c) && preg_match('/^[=+@]/', $c)) ? "'" . $c : $c, $r)); }
        rewind($h); $csv = stream_get_contents($h); fclose($h);
        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="tds-' . $part . '-' . $cur['key'] . '.csv"']);
    }
}
