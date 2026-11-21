<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Session;

/**
 * Balance sheet and statement of profit and loss in the Companies Act 2013 Schedule III (Division I) layout,
 * built from the accountant-approved mapping of account heads to Schedule III lines (database/data/schedule3_mapping.json).
 * Only categorised entries are used (status 1), exactly like every other report. Amounts are shown in the chosen unit.
 */
class Schedule3
{
    const UNITS = ['rupees' => [1, 'rupees'], 'hundreds' => [100, 'hundreds'], 'thousands' => [1000, 'thousands'], 'lakhs' => [100000, 'lakhs'], 'crores' => [10000000, 'crores']];

    /** line key => [label, side]  side: L liability/equity (credit positive), A asset (debit positive), I income, E expense */
    const LINES = [
        'share_capital' => ['Share capital', 'L'], 'reserves' => ['Reserves and surplus', 'L'],
        'lt_borrow' => ['Long-term borrowings', 'L'], 'dtl' => ['Deferred tax liabilities (net)', 'L'], 'other_lt_liab' => ['Other long-term liabilities', 'L'], 'lt_prov' => ['Long-term provisions', 'L'],
        'st_borrow' => ['Short-term borrowings', 'L'], 'trade_pay' => ['Trade payables', 'L'], 'other_cl' => ['Other current liabilities', 'L'], 'st_prov' => ['Short-term provisions', 'L'],
        'ppe' => ['Property, plant and equipment', 'A'], 'nc_inv' => ['Non-current investments', 'A'], 'dta' => ['Deferred tax assets (net)', 'A'], 'lt_loans' => ['Long-term loans and advances', 'A'], 'other_nca' => ['Other non-current assets', 'A'],
        'c_inv' => ['Current investments', 'A'], 'inventories' => ['Inventories', 'A'], 'trade_rec' => ['Trade receivables', 'A'], 'cash' => ['Cash and cash equivalents', 'A'], 'st_loans' => ['Short-term loans and advances', 'A'], 'other_ca' => ['Other current assets', 'A'],
        'revenue' => ['Revenue from operations', 'I'], 'other_income' => ['Other income', 'I'],
        'employee' => ['Employee benefit expense', 'E'], 'finance' => ['Finance costs', 'E'], 'other_exp' => ['Other expenses', 'E'], 'tax' => ['Tax expense', 'E'],
    ];

    private static $base = [
        'Share capital' => 'share_capital', 'Reserves and surplus' => 'reserves', 'Long-term borrowings' => 'lt_borrow', 'Deferred tax liabilities (net)' => 'dtl',
        'Other long-term liabilities' => 'other_lt_liab', 'Long-term provisions' => 'lt_prov', 'Short-term borrowings' => 'st_borrow', 'Trade payables' => 'trade_pay',
        'Other current liabilities' => 'other_cl', 'Short-term provisions' => 'st_prov', 'Property, plant and equipment' => 'ppe', 'Non-current investments' => 'nc_inv',
        'Deferred tax assets (net)' => 'dta', 'Long-term loans and advances' => 'lt_loans', 'Other non-current assets' => 'other_nca', 'Current investments' => 'c_inv',
        'Inventories' => 'inventories', 'Trade receivables' => 'trade_rec', 'Cash and cash equivalents' => 'cash', 'Short-term loans and advances' => 'st_loans',
        'Other current assets' => 'other_ca', 'Revenue from operations' => 'revenue', 'Other income' => 'other_income', 'Employee benefit expense' => 'employee',
        'Finance costs' => 'finance', 'Other expenses' => 'other_exp', 'Tax expense' => 'tax',
    ];

    /** Mapping rows keyed by head id: [key, sub-heading]. */
    public static function mapping()
    {
        $json = json_decode(file_get_contents(base_path('database/data/schedule3_mapping.json')), true);
        $map = [];
        foreach ($json['rows'] as $r) {
            if ($r['statement'] === 'CLEAR') {
                $map[$r['head_id']] = ['clear', $r['name'], $r['head_id'] == 65 ? 'reserves' : 'other_cl'];
                continue;
            }
            $line = $r['line']; $sub = '';
            if (str_contains($line, '(less returns)')) { $line = 'Revenue from operations'; $sub = 'Less: sales returns and allowances'; }
            elseif (str_contains($line, ' - ')) { [$line, $sub] = explode(' - ', $line, 2); }
            if (!isset(self::$base[$line])) { continue; }
            $map[$r['head_id']] = [self::$base[$line], $sub];
        }
        return $map;
    }

    /** Net balance (debit minus credit) of every head for the year, opening entries included. */
    private static function balances($fyear)
    {
        $rows = DB::table('statement')->where('merchant_id', Session::get('merchant_id'))->where('status', 1)->where('fyear', $fyear)
            ->selectRaw("head_id, SUM(CAST(NULLIF(NULLIF(REPLACE(debit,',',''),'-'),'') AS DECIMAL(16,2))) AS dr, SUM(CAST(NULLIF(NULLIF(REPLACE(credit,',',''),'-'),'') AS DECIMAL(16,2))) AS cr")
            ->groupBy('head_id')->get();
        $out = [];
        foreach ($rows as $r) { if ($r->head_id !== null && $r->head_id !== '') { $out[(int) $r->head_id] = round((float) $r->dr - (float) $r->cr, 2); } }
        return $out;
    }

    /** All the figures for one year: line totals, per-head detail, unmapped accounts and the profit. */
    private static function figures($fyear, $map)
    {
        $bal = self::balances($fyear);
        $heads = DB::table('head')->join('account_type as t', 't.id', '=', 'head.account_type_id')->get(['head.id', 'head.name', 't.name as type', 't.parent_id'])->keyBy('id');
        $lines = []; $detail = []; $unmapped = []; $clear = [];
        foreach ($bal as $hid => $net) {
            if (abs($net) < 0.005) { continue; }
            $h = $heads[$hid] ?? null;
            $name = $h ? $h->name : 'Account ' . $hid;
            $m = $map[$hid] ?? null;
            if ($m && $m[0] === 'clear') { $clear[$hid] = ['name' => $name, 'amount' => $net]; $key = $m[2]; $sub = '(to be cleared) ' . $name; }
            elseif ($m) { [$key, $sub] = $m; }
            elseif ($h && in_array($h->type, ['Bank', 'Cheques in transit'], true)) { $key = 'cash'; $sub = ''; }
            else {
                // an account with a balance that the approved mapping does not cover: kept visible on its own side, never dropped
                $side = $h ? $h->parent_id : 1;
                $key = $side == 1 ? 'other_ca' : ($side == 4 ? 'other_cl' : ($side == 3 ? 'other_income' : 'other_exp'));
                $sub = 'UNMAPPED: ' . $name; $unmapped[$hid] = ['name' => $name, 'amount' => $net];
            }
            $sign = in_array(self::LINES[$key][1], ['L', 'I'], true) ? -1 : 1;
            $amt = round($net * $sign, 2);
            $lines[$key] = round(($lines[$key] ?? 0) + $amt, 2);
            $detail[$key][$sub === '' ? '_' : $sub][] = ['head' => $name, 'amount' => $amt];
        }
        $plNet = 0;   // profit = income - expenses (credit minus debit over income and expense lines)
        foreach (['revenue', 'other_income'] as $k) { $plNet += $lines[$k] ?? 0; }
        foreach (['employee', 'finance', 'other_exp', 'tax'] as $k) { $plNet -= $lines[$k] ?? 0; }
        return ['lines' => $lines, 'detail' => $detail, 'unmapped' => $unmapped, 'clear' => $clear, 'profit' => round($plNet, 2)];
    }

    /** Rounds a list so that it adds up exactly to $target (largest remainder method). */
    private static function allocate(array $values, int $target)
    {
        $floors = array_map(fn($v) => (int) floor($v), $values);
        $diff = $target - array_sum($floors);
        $order = array_keys($values);
        usort($order, fn($a, $b) => ($values[$b] - floor($values[$b])) <=> ($values[$a] - floor($values[$a])));
        $n = count($order);
        for ($i = 0; $i < abs($diff) && $n > 0; $i++) { $floors[$order[$diff > 0 ? $i % $n : ($n - 1 - ($i % $n))]] += $diff > 0 ? 1 : -1; }
        return $floors;
    }

    public static function previousYear($fyear)
    {
        $start = (int) substr($fyear, 0, 4);
        return ($start - 1) . '-' . $start;
    }

    public static function build($fyear, $unit = 'thousands')
    {
        $map = self::mapping();
        $cur = self::figures($fyear, $map);
        $prevYear = self::previousYear($fyear);
        $hasPrev = DB::table('statement')->where('merchant_id', Session::get('merchant_id'))->where('status', 1)->where('fyear', $prevYear)->exists();
        $prev = $hasPrev ? self::figures($prevYear, $map) : null;
        [$factor, $unitName] = self::UNITS[$unit] ?? self::UNITS['thousands'];
        $r = fn($v) => $v === null ? null : (int) round($v / $factor);

        $val = function ($fig, $key) { return $fig ? ($fig['lines'][$key] ?? 0) : null; };
        // reserves and surplus include the year's profit (the statement of profit and loss balance)
        $valWithProfit = function ($fig, $key) use ($val) { return $fig === null ? null : round(($fig['lines'][$key] ?? 0) + ($key === 'reserves' ? $fig['profit'] : 0), 2); };
        $get = fn($fig, $key) => $key === 'reserves' ? $valWithProfit($fig, $key) : $val($fig, $key);

        $note = 0; $notes = [];
        $item = function ($key, $label = null) use (&$note, &$notes, $cur, $prev, $get, $r) {
            $c = $get($cur, $key); $p = $get($prev, $key);
            $label = $label ?: self::LINES[$key][0];
            $no = null;
            if (abs($c) > 0.004 || ($p !== null && abs($p) > 0.004)) { $no = ++$note; $notes[] = ['no' => $no, 'key' => $key, 'label' => $label]; }
            return ['key' => $key, 'label' => $label, 'note' => $no, 'cur' => $r($c), 'prev' => $r($p), 'cur_raw' => $c, 'prev_raw' => $p];
        };
        $sum = function (array $items, $f) { $t = 0; foreach ($items as $i) { $t += $i[$f] ?? 0; } return $t; };
        $section = function ($title, array $keys) use ($item, $sum, $prev) {
            $items = array_map(fn($k) => $item($k), $keys);
            return ['title' => $title, 'items' => $items, 'cur' => $sum($items, 'cur'), 'prev' => $prev === null ? null : $sum($items, 'prev'),
                'cur_raw' => $sum($items, 'cur_raw'), 'prev_raw' => $prev === null ? null : $sum($items, 'prev_raw')];
        };
        $bs = [
            'equity' => [$section("Shareholders' funds", ['share_capital', 'reserves']),
                         $section('Non-current liabilities', ['lt_borrow', 'dtl', 'other_lt_liab', 'lt_prov']),
                         $section('Current liabilities', ['st_borrow', 'trade_pay', 'other_cl', 'st_prov'])],
            'assets' => [$section('Non-current assets', ['ppe', 'nc_inv', 'dta', 'lt_loans', 'other_nca']),
                         $section('Current assets', ['c_inv', 'inventories', 'trade_rec', 'cash', 'st_loans', 'other_ca'])],
        ];
        $tot = fn($secs, $f) => array_sum(array_map(fn($s) => $s[$f] ?? 0, $secs));
        $bs['equity_total'] = ['cur' => $tot($bs['equity'], 'cur'), 'prev' => $prev === null ? null : $tot($bs['equity'], 'prev'), 'cur_raw' => $tot($bs['equity'], 'cur_raw'), 'prev_raw' => $prev === null ? null : $tot($bs['equity'], 'prev_raw')];
        $bs['assets_total'] = ['cur' => $tot($bs['assets'], 'cur'), 'prev' => $prev === null ? null : $tot($bs['assets'], 'prev'), 'cur_raw' => $tot($bs['assets'], 'cur_raw'), 'prev_raw' => $prev === null ? null : $tot($bs['assets'], 'prev_raw')];

        $rev = $item('revenue'); $oth = $item('other_income');
        $exp = [$item('employee'), $item('finance'), $item('other_exp')];
        $tax = $item('tax');
        $totalIncome = ['cur' => $rev['cur'] + $oth['cur'], 'prev' => $prev === null ? null : $rev['prev'] + $oth['prev'], 'cur_raw' => $rev['cur_raw'] + $oth['cur_raw'], 'prev_raw' => $prev === null ? null : $rev['prev_raw'] + $oth['prev_raw']];
        $totalExp = ['cur' => $sum($exp, 'cur'), 'prev' => $prev === null ? null : $sum($exp, 'prev'), 'cur_raw' => $sum($exp, 'cur_raw'), 'prev_raw' => $prev === null ? null : $sum($exp, 'prev_raw')];
        $pbt = ['cur' => $totalIncome['cur'] - $totalExp['cur'], 'prev' => $prev === null ? null : $totalIncome['prev'] - $totalExp['prev'], 'cur_raw' => $totalIncome['cur_raw'] - $totalExp['cur_raw'], 'prev_raw' => $prev === null ? null : $totalIncome['prev_raw'] - $totalExp['prev_raw']];
        $profit = ['cur' => $pbt['cur'] - $tax['cur'], 'prev' => $prev === null ? null : $pbt['prev'] - $tax['prev'], 'cur_raw' => $pbt['cur_raw'] - $tax['cur_raw'], 'prev_raw' => $prev === null ? null : $pbt['prev_raw'] - $tax['prev_raw']];
        $pl = ['revenue' => $rev, 'other_income' => $oth, 'total_income' => $totalIncome, 'expenses' => $exp, 'total_expenses' => $totalExp, 'pbt' => $pbt, 'tax' => $tax, 'profit' => $profit];

        // Rounding: every line is rounded so that the lines add up to their section, and the sections to the side total,
        // which is the rounded true total. The two sides of the balance sheet therefore always agree on screen.
        foreach (['cur', 'prev'] as $f) {
            if ($f === 'prev' && $prev === null) { continue; }
            $raw = $f . '_raw';
            foreach (['equity' => 'equity_total', 'assets' => 'assets_total'] as $side => $totKey) {
                $T = (int) round($bs[$totKey][$raw] / $factor);
                $secInts = self::allocate(array_map(fn($sec) => $sec[$raw] / $factor, $bs[$side]), $T);
                foreach ($bs[$side] as $si => $sec) {
                    $bs[$side][$si][$f] = $secInts[$si];
                    $itemInts = self::allocate(array_map(fn($it) => $it[$raw] / $factor, $sec['items']), $secInts[$si]);
                    foreach ($sec['items'] as $ii => $it) { $bs[$side][$si]['items'][$ii][$f] = $itemInts[$ii]; }
                }
                $bs[$totKey][$f] = $T;
            }
            $Ti = (int) round($pl['total_income'][$raw] / $factor);
            $inc = self::allocate([$pl['revenue'][$raw] / $factor, $pl['other_income'][$raw] / $factor], $Ti);
            $pl['revenue'][$f] = $inc[0]; $pl['other_income'][$f] = $inc[1]; $pl['total_income'][$f] = $Ti;
            $Te = (int) round($pl['total_expenses'][$raw] / $factor);
            $exp = self::allocate(array_map(fn($e) => $e[$raw] / $factor, $pl['expenses']), $Te);
            foreach ($pl['expenses'] as $ei => $e) { $pl['expenses'][$ei][$f] = $exp[$ei]; }
            $pl['total_expenses'][$f] = $Te;
            $pl['tax'][$f] = (int) round($pl['tax'][$raw] / $factor);
            $pl['pbt'][$f] = $Ti - $Te;
            $pl['profit'][$f] = $pl['pbt'][$f] - $pl['tax'][$f];
        }

        // note detail: each line broken down into its sub-headings and accounts
        $noteDetail = [];
        foreach ($notes as $n) {
            $subs = [];
            foreach (($cur['detail'][$n['key']] ?? []) as $sub => $rows) { $subs[$sub]['cur'] = $rows; }
            if ($prev) { foreach (($prev['detail'][$n['key']] ?? []) as $sub => $rows) { $subs[$sub]['prev'] = $rows; } }
            if ($n['key'] === 'reserves') { $subs['Surplus: profit for the year (statement of profit and loss)']['cur'] = [['head' => 'Profit for the year', 'amount' => $cur['profit']]]; if ($prev) { $subs['Surplus: profit for the year (statement of profit and loss)']['prev'] = [['head' => 'Profit for the year', 'amount' => $prev['profit']]]; } }
            $noteDetail[] = $n + ['subs' => $subs];
        }
        $checks = [
            'bs_difference' => round($bs['assets_total']['cur_raw'] - $bs['equity_total']['cur_raw'], 2),
            'unmapped' => $cur['unmapped'], 'clear' => $cur['clear'],
            'uncategorised' => (int) DB::table('statement')->where('merchant_id', Session::get('merchant_id'))->where('fyear', $fyear)->where('status', 0)->where('opening', 0)->count(),
        ];
        return ['year' => $fyear, 'prev_year' => $hasPrev ? $prevYear : null, 'unit' => $unit, 'unit_name' => $unitName, 'factor' => $factor,
                'bs' => $bs, 'pl' => $pl, 'notes' => $noteDetail, 'checks' => $checks];
    }

    /** Ageing of open invoices (receivables) or bills (payables) as at a date, in the Schedule III bands. */
    public static function ageing($kind, $asAt)
    {
        $bands = ['Less than 6 months' => 0, '6 months to 1 year' => 0, '1 to 2 years' => 0, '2 to 3 years' => 0, 'More than 3 years' => 0];
        $docs = DB::table('billing_docs')->where('merchant_id', Session::get('merchant_id'))->where('kind', $kind)->whereIn('status', ['open', 'part'])->where('doc_date', '<=', $asAt)->get();
        $total = 0;
        foreach ($docs as $d) {
            $bal = round($d->total - $d->settled, 2);
            $days = (int) floor((strtotime($asAt) - strtotime($d->due_date ?: $d->doc_date)) / 86400);
            $band = $days < 183 ? 'Less than 6 months' : ($days < 365 ? '6 months to 1 year' : ($days < 730 ? '1 to 2 years' : ($days < 1095 ? '2 to 3 years' : 'More than 3 years')));
            $bands[$band] += $bal; $total += $bal;
        }
        return ['bands' => $bands, 'total' => round($total, 2), 'count' => count($docs)];
    }
}
