<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Session;

/**
 * TDS you have deducted and still owe the tax department, month by month.
 * Deducted = credits to the TDS Payable account; deposited = debits to it (the bank payment to the government, categorised to that account).
 * Deposits are applied to the oldest month first. A month's TDS is due on the 7th of the next month (30 April for March).
 */
class TdsDue
{
    public static function dueDate($ym)
    {
        [$y, $m] = array_map('intval', explode('-', $ym));
        if ($m === 3) { return sprintf('%04d-04-30', $y); }
        $m++; if ($m > 12) { $m = 1; $y++; }
        return sprintf('%04d-%02d-07', $y, $m);
    }

    public static function months()
    {
        $merchant = Session::get('merchant_id');
        $head = BillingSettings::get('tds_deducted');
        $rows = DB::table('statement')->where('merchant_id', $merchant)->where('status', 1)->where('head_id', $head)
            ->selectRaw("DATE_FORMAT(st_date,'%Y-%m') ym, COUNT(CASE WHEN credit NOT IN ('-','') AND credit+0>0 THEN 1 END) n,
                         SUM(CASE WHEN credit IN ('-','') THEN 0 ELSE credit+0 END) cr, SUM(CASE WHEN debit IN ('-','') THEN 0 ELSE debit+0 END) dr")
            ->groupBy('ym')->orderBy('ym')->get();
        $deposited = (float) $rows->sum('dr');
        $out = [];
        foreach ($rows as $r) {
            $ded = round((float) $r->cr, 2);
            if ($ded <= 0) { continue; }   // a month with only a deposit has nothing to show
            $applied = min($ded, round($deposited, 2)); $deposited = round($deposited - $applied, 2);
            $due = round($ded - $applied, 2);
            $out[] = ['ym' => $r->ym, 'label' => date('F Y', strtotime($r->ym . '-01')), 'count' => (int) $r->n, 'deducted' => $ded, 'deposited' => $applied, 'outstanding' => $due, 'due_date' => self::dueDate($r->ym)];
        }
        return $out;
    }

    /** The nearest TDS payment still to make: for the dashboard. */
    public static function next()
    {
        foreach (self::months() as $m) {
            if ($m['outstanding'] > 0.004) {
                $days = (int) floor((strtotime($m['due_date']) - strtotime(date('Y-m-d'))) / 86400);
                return $m + ['days' => $days, 'overdue' => $days < 0];
            }
        }
        return null;
    }

    /** The deduction entries behind one month. */
    public static function entries($ym)
    {
        $head = BillingSettings::get('tds_deducted');
        return DB::table('statement as s')->leftJoin('vouchers as v', 'v.id', '=', 's.voucher_id')->where('s.merchant_id', Session::get('merchant_id'))->where('s.status', 1)->where('s.head_id', $head)
            ->whereRaw("DATE_FORMAT(s.st_date,'%Y-%m') = ?", [$ym])->whereRaw("s.credit NOT IN ('-','')")->orderBy('s.st_date')->get(['s.st_date', 's.voucher_no', 's.credit', 'v.narration']);
    }
}
