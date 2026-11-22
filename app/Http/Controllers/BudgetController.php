<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Excel;
use App\Imports\RawRows;
use App\Support\Budgets;
use App\Support\FinancialYears;
use Session;

/** Budgets by account and month, and planned-versus-actual. */
class BudgetController extends Controller
{
    private function year(Request $r)
    {
        $y = (string) $r->input('year');
        return preg_match('/^\d{4}-\d{4}$/', $y) ? $y : (FinancialYears::current() ?: date('Y') . '-' . (date('Y') + 1));
    }

    public function index(Request $request)
    {
        $year = $this->year($request);
        $tab = in_array($request->input('tab'), ['variance', 'upload']) ? $request->input('tab') : 'plan';
        $months = Budgets::months($year);
        $known = DB::table('statement')->where('merchant_id', Session::get('merchant_id'))->whereNotNull('fyear')->distinct()->pluck('fyear')->all();
        $data = ['year' => $year, 'tab' => $tab, 'months' => $months, 'year_options' => FinancialYears::options($known, $year, true)];
        if ($tab === 'plan') { $data += ['heads' => Budgets::heads(), 'plan' => Budgets::planned($year)]; }
        if ($tab === 'variance') {
            $default = min(end($months), max($months[0], date('Y-m')));
            $upTo = preg_match('/^\d{4}-\d{2}$/', (string) $request->input('upto')) ? $request->input('upto') : $default;
            $data += ['upTo' => $upTo, 'v' => Budgets::variance($year, $upTo)];
        }
        if ($tab === 'upload') {
            $data['log'] = DB::table('budget_log as l')->join('head as h', 'h.id', '=', 'l.head_id')->leftJoin('merchant as u', 'u.id', '=', 'l.changed_by')->where('l.merchant_id', Session::get('merchant_id'))->where('l.fyear', $year)
                ->orderByDesc('l.id')->limit(25)->get(['l.*', 'h.name as head', 'u.email as who']);
        }
        return view('budget.index', $data);
    }

    public function save(Request $request)
    {
        $year = $this->year($request);
        $months = Budgets::months($year); $heads = Budgets::heads(); $n = 0; $todo = [];
        foreach ((array) $request->input('b', []) as $hid => $cells) {
            if (!isset($heads[(int) $hid])) { continue; }
            foreach ((array) $cells as $ym => $v) {
                if (!in_array($ym, $months, true)) { continue; }
                $v = str_replace([',', ' '], '', (string) $v);
                if ($v === '') { $v = '0'; }
                if (!is_numeric($v) || abs((float) $v) > 9999999999) { return back()->withErrors(['"' . $v . '" is not an amount (' . $heads[(int) $hid]['name'] . ', ' . $ym . '). Nothing was saved.']); }
                $todo[] = [(int) $hid, $ym, $v];
            }
        }
        DB::transaction(function () use ($todo, $year, &$n) { foreach ($todo as [$hid, $ym, $v]) { if (Budgets::set($year, $hid, $ym, $v, 'screen')) { $n++; } } });
        return redirect('merchant/budgets?tab=plan&year=' . $year)->with('success_msg', $n ? $n . ' amount(s) saved.' : 'Nothing had changed.');
    }

    public function template(Request $request)
    {
        $year = $this->year($request);
        $months = Budgets::months($year);
        $h = fopen('php://temp', 'w+');
        fputcsv($h, array_merge(['Account name'], array_map(fn($m) => date('M Y', strtotime($m . '-01')), $months)));
        $plan = Budgets::planned($year);
        foreach (Budgets::heads() as $x) { fputcsv($h, array_merge([$x['name']], array_map(fn($m) => isset($plan[$x['id']][$m]) ? number_format($plan[$x['id']][$m], 2, '.', '') : '', $months))); }
        rewind($h); $csv = stream_get_contents($h); fclose($h);
        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="budget-' . $year . '.csv"']);
    }

    public function upload(Request $request)
    {
        $request->validate(['csv_file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240']);
        $year = $this->year($request);
        $months = Budgets::months($year);
        $rows = (Excel::toArray(new RawRows, $request->file('csv_file'))[0] ?? []);
        $byName = [];
        foreach (Budgets::heads() as $x) { $byName[mb_strtolower(trim(preg_replace('/\s+/', ' ', $x['name'])))][] = $x['id']; }
        $done = 0; $cells = 0; $unknown = []; $ambiguous = [];
        DB::transaction(function () use ($rows, $byName, $months, $year, &$done, &$cells, &$unknown, &$ambiguous) {
            foreach (array_slice($rows, 1) as $i => $r) {
                $name = trim(preg_replace('/\s+/', ' ', (string) ($r[0] ?? '')));
                if ($name === '') { continue; }
                $ids = $byName[mb_strtolower($name)] ?? [];
                if (!$ids) { $unknown[] = 'Row ' . ($i + 2) . ': ' . $name; continue; }
                if (count($ids) > 1) { $ambiguous[] = 'Row ' . ($i + 2) . ': ' . $name; continue; }
                $any = false;
                foreach ($months as $k => $ym) {
                    $v = str_replace([',', ' ', "\u{20B9}"], '', (string) ($r[$k + 1] ?? ''));
                    if ($v === '' || !is_numeric($v)) { continue; }
                    if (Budgets::set($year, $ids[0], $ym, $v, 'upload')) { $cells++; }
                    $any = true;
                }
                if ($any) { $done++; }
            }
        });
        $msg = $done . ' account(s) read, ' . $cells . ' amount(s) changed.';
        $res = redirect('merchant/budgets?tab=upload&year=' . $year)->with('success_msg', $msg);
        if ($unknown || $ambiguous) { $res->with('warn_list', array_merge(array_map(fn($x) => 'Not an income or expense account: ' . $x, array_slice($unknown, 0, 30)), array_map(fn($x) => 'Name matches more than one account: ' . $x, array_slice($ambiguous, 0, 30)))); }
        return $res;
    }
}
