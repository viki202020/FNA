<?php

namespace App\Support;

/** Financial ratios from the Schedule III figures of a year and the year before it. */
class Ratios
{
    private static function inputs($f)
    {
        $l = $f['lines'];
        $g = fn(array $k) => array_sum(array_map(fn($x) => $l[$x] ?? 0, $k));
        $ca = $g(['c_inv', 'inventories', 'trade_rec', 'cash', 'st_loans', 'other_ca']);
        $cl = $g(['st_borrow', 'trade_pay', 'other_cl', 'st_prov']);
        $assets = $ca + $g(['ppe', 'nc_inv', 'dta', 'lt_loans', 'other_nca']);
        $equity = $g(['share_capital', 'reserves']);
        $borrow = $g(['lt_borrow', 'st_borrow']);
        $revenue = $l['revenue'] ?? 0; $income = $revenue + ($l['other_income'] ?? 0);
        $expenses = $g(['employee', 'finance', 'other_exp']);
        return ['ca' => $ca, 'cl' => $cl, 'assets' => $assets, 'equity' => $equity, 'borrow' => $borrow, 'revenue' => $revenue, 'income' => $income, 'expenses' => $expenses,
                'profit' => $f['profit'], 'inv' => $l['inventories'] ?? 0, 'rec' => $l['trade_rec'] ?? 0, 'pay' => $l['trade_pay'] ?? 0, 'cash' => $l['cash'] ?? 0, 'finance' => $l['finance'] ?? 0];
    }

    private static function div($a, $b) { return abs($b) < 0.005 ? null : $a / $b; }

    public static function compute($fyear)
    {
        $cur = self::inputs(Schedule3::rawFigures($fyear));
        $py = Schedule3::previousYear($fyear);
        $hasPrev = \Illuminate\Support\Facades\DB::table('statement')->where('merchant_id', \Session::get('merchant_id'))->where('status', 1)->where('fyear', $py)->exists();
        $prev = $hasPrev ? self::inputs(Schedule3::rawFigures($py)) : null;
        $defs = [
            ['Liquidity', 'Current ratio', 'Current assets / current liabilities', fn($x) => self::div($x['ca'], $x['cl']), 'x', 'Can short-term assets cover short-term dues? Above 1 is comfortable.'],
            ['Liquidity', 'Quick ratio', '(Current assets - inventories) / current liabilities', fn($x) => self::div($x['ca'] - $x['inv'], $x['cl']), 'x', 'The same test without stock, which may take time to sell.'],
            ['Liquidity', 'Cash ratio', 'Cash and bank / current liabilities', fn($x) => self::div($x['cash'], $x['cl']), 'x', 'How much of the dues could be paid today.'],
            ['Profitability', 'Net profit margin', 'Profit for the year / revenue from operations', fn($x) => ($v = self::div($x['profit'], $x['revenue'])) === null ? null : $v * 100, '%', 'Profit kept from each 100 of sales.'],
            ['Profitability', 'Expense ratio', 'Operating expenses / total income', fn($x) => ($v = self::div($x['expenses'], $x['income'])) === null ? null : $v * 100, '%', 'Share of income used up by running costs.'],
            ['Profitability', 'Return on equity', 'Profit for the year / shareholders\' funds', fn($x) => ($v = self::div($x['profit'], $x['equity'])) === null ? null : $v * 100, '%', 'Profit earned on the owners\' money.'],
            ['Leverage', 'Debt to equity', 'Borrowings / shareholders\' funds', fn($x) => self::div($x['borrow'], $x['equity']), 'x', 'How much of the business is financed by loans against owners\' money.'],
            ['Leverage', 'Liabilities to assets', 'Total liabilities / total assets', fn($x) => ($v = self::div($x['assets'] - $x['equity'], $x['assets'])) === null ? null : $v * 100, '%', 'Share of everything owned that is owed to others.'],
            ['Efficiency', 'Receivable days', 'Trade receivables / revenue x 365', fn($x) => ($v = self::div($x['rec'], $x['revenue'])) === null ? null : $v * 365, 'days', 'On average, how long customers take to pay.'],
            ['Efficiency', 'Payable days', 'Trade payables / operating expenses x 365', fn($x) => ($v = self::div($x['pay'], $x['expenses'])) === null ? null : $v * 365, 'days', 'On average, how long you take to pay suppliers.'],
        ];
        $rows = [];
        foreach ($defs as [$group, $name, $formula, $fn, $unit, $means]) {
            $c = $fn($cur); $p = $prev ? $fn($prev) : null;
            $rows[] = ['group' => $group, 'name' => $name, 'formula' => $formula, 'cur' => $c, 'prev' => $p, 'unit' => $unit, 'means' => $means];
        }
        return ['rows' => $rows, 'year' => $fyear, 'prev_year' => $hasPrev ? $py : null];
    }
}
