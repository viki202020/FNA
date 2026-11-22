<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/** Planned (budget) versus actual, by account and month, for the income and expense accounts. */
class Budgets
{
    private static function m() { return Session::get('merchant_id'); }

    /** The twelve months of a financial year such as 2023-2024, in order, as YYYY-MM. Follows the company's own year start. */
    public static function months($fyear)
    {
        $e = FinancialYears::entity();
        $startMonth = $e && $e->financial_year_start ? (int) date('n', strtotime($e->financial_year_start)) : 4;
        $y = (int) substr($fyear, 0, 4);
        $out = [];
        for ($i = 0; $i < 12; $i++) { $out[] = date('Y-m', mktime(0, 0, 0, $startMonth + $i, 1, $y)); }
        return $out;
    }

    /** Income and expense accounts: [id => ['id','name','kind' => income|expense]]. */
    public static function heads()
    {
        $rows = DB::table('head as h')->join('account_type as t', 't.id', '=', 'h.account_type_id')->join('account_type_parent as p', 'p.id', '=', 't.parent_id')
            ->where('p.financial_statement_id', 2)->where('h.status', 1)->where(function ($q) { $q->where('h.merchant', self::m())->orWhereNull('h.merchant'); })
            ->orderBy('p.id', 'desc')->orderBy('h.name')->get(['h.id', 'h.name', 'p.id as pid']);
        $out = [];
        foreach ($rows as $r) { $out[$r->id] = ['id' => $r->id, 'name' => $r->name, 'kind' => (int) $r->pid === 3 ? 'income' : 'expense']; }
        return $out;
    }

    public static function planned($fyear)
    {
        $out = [];
        foreach (DB::table('budgets')->where('merchant_id', self::m())->where('fyear', $fyear)->get() as $b) { $out[$b->head_id][$b->ym] = (float) $b->amount; }
        return $out;
    }

    /** Actual by head and month, positive for the normal side (income: credits less debits; expense: debits less credits). */
    public static function actual($fyear)
    {
        $heads = self::heads();
        $rows = DB::table('statement')->where('merchant_id', self::m())->where('status', 1)->where('opening', 0)->where('fyear', $fyear)
            ->selectRaw("head_id, DATE_FORMAT(st_date,'%Y-%m') ym, SUM(CASE WHEN debit IN ('-','') THEN 0 ELSE debit+0 END) dr, SUM(CASE WHEN credit IN ('-','') THEN 0 ELSE credit+0 END) cr")
            ->groupBy('head_id', 'ym')->get();
        $out = [];
        foreach ($rows as $r) {
            $h = $heads[(int) $r->head_id] ?? null; if (!$h) { continue; }
            $out[$h['id']][$r->ym] = round($h['kind'] === 'income' ? $r->cr - $r->dr : $r->dr - $r->cr, 2);
        }
        return $out;
    }

    /** Saves one amount, logging the change. Zero or blank removes the plan for that cell by storing zero (never deletes). */
    public static function set($fyear, $headId, $ym, $amount, $source)
    {
        $old = DB::table('budgets')->where('merchant_id', self::m())->where('fyear', $fyear)->where('head_id', $headId)->where('ym', $ym)->first();
        $amount = round((float) $amount, 2);
        if (($old ? (float) $old->amount : 0.0) == $amount) { return false; }
        if ($old) { DB::table('budgets')->where('id', $old->id)->update(['amount' => $amount, 'updated_by' => Session::get('id'), 'updated_at' => now()]); }
        else { DB::table('budgets')->insert(['merchant_id' => self::m(), 'fyear' => $fyear, 'head_id' => $headId, 'ym' => $ym, 'amount' => $amount, 'updated_by' => Session::get('id'), 'created_at' => now(), 'updated_at' => now()]); }
        DB::table('budget_log')->insert(['merchant_id' => self::m(), 'fyear' => $fyear, 'head_id' => $headId, 'ym' => $ym, 'old_amount' => $old ? $old->amount : null, 'new_amount' => $amount, 'source' => $source, 'changed_by' => Session::get('id'), 'created_at' => now(), 'updated_at' => now()]);
        return true;
    }

    /** Variance rows up to and including month $upTo (YYYY-MM). Variance is favourable when positive. */
    public static function variance($fyear, $upTo)
    {
        $months = array_values(array_filter(self::months($fyear), fn($m) => $m <= $upTo));
        $plan = self::planned($fyear); $act = self::actual($fyear); $heads = self::heads();
        $rows = ['income' => [], 'expense' => []]; $tot = ['income' => ['b' => 0, 'a' => 0, 'fy' => 0], 'expense' => ['b' => 0, 'a' => 0, 'fy' => 0]];
        foreach ($heads as $h) {
            $b = array_sum(array_map(fn($m) => $plan[$h['id']][$m] ?? 0, $months));
            $a = array_sum(array_map(fn($m) => $act[$h['id']][$m] ?? 0, $months));
            $fy = array_sum($plan[$h['id']] ?? []);
            if (abs($b) < 0.005 && abs($a) < 0.005 && abs($fy) < 0.005) { continue; }
            $var = $h['kind'] === 'income' ? $a - $b : $b - $a;
            $rows[$h['kind']][] = ['name' => $h['name'], 'budget' => round($b, 2), 'actual' => round($a, 2), 'variance' => round($var, 2), 'pct' => abs($b) > 0.005 ? $var / $b * 100 : null, 'year' => round($fy, 2)];
            $tot[$h['kind']]['b'] += $b; $tot[$h['kind']]['a'] += $a; $tot[$h['kind']]['fy'] += $fy;
        }
        $pb = $tot['income']['b'] - $tot['expense']['b']; $pa = $tot['income']['a'] - $tot['expense']['a']; $pfy = $tot['income']['fy'] - $tot['expense']['fy'];
        return ['rows' => $rows, 'tot' => $tot, 'profit' => ['budget' => round($pb, 2), 'actual' => round($pa, 2), 'variance' => round($pa - $pb, 2), 'year' => round($pfy, 2)], 'months' => $months];
    }
}
