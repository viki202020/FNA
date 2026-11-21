<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Session;

/**
 * Money that may belong to an open sales invoice or purchase bill.
 * Rule: a bank line whose amount equals the open balance of a sales invoice (money in) or a purchase
 * bill (money out) is never categorised by a rule. It waits in "Receipts to match" for a person to choose.
 */
class OpenDocuments
{
    private static $cache = [];

    private static function balances($kind)
    {
        $m = Session::get('merchant_id');
        $key = $m . '|' . $kind;
        if (!isset(self::$cache[$key])) {
            self::$cache[$key] = [];
            if (Schema::hasTable('billing_docs')) {
                $rows = DB::table('billing_docs as d')->leftJoin('customer as c', 'c.id', '=', 'd.party_id')->where('d.merchant_id', $m)->where('d.kind', $kind)
                    ->whereIn('d.status', ['open', 'part'])->get(['d.total', 'd.settled', 'd.subtotal', 'c.tds_status', 'c.tds_rate']);
                foreach ($rows as $r) {
                    $bal = $r->total - $r->settled;
                    self::$cache[$key][number_format($bal, 2, '.', '')] = true;
                    if ((int) $r->tds_status === 1 && is_numeric($r->tds_rate) && (float) $r->tds_rate > 0) {   // the amount left after the party deducts TDS
                        self::$cache[$key][number_format($bal - round($r->subtotal * (float) $r->tds_rate / 100, 2), 2, '.', '')] = true;
                    }
                }
            }
        }
        return self::$cache[$key];
    }

    private static function chequeAmounts($kind)
    {
        $m = Session::get('merchant_id');
        $key = $m . '|chq|' . $kind;
        if (!isset(self::$cache[$key])) {
            self::$cache[$key] = [];
            if (Schema::hasTable('payment_notes')) {
                $rows = DB::table('payment_notes as n')->join('billing_docs as d', 'd.id', '=', 'n.doc_id')
                    ->where('n.merchant_id', $m)->where('n.mode', 'Cheque')->where('n.status', 'outstanding')->where('d.kind', $kind)->pluck('n.amount');
                foreach ($rows as $a) { self::$cache[$key][number_format($a, 2, '.', '')] = true; }
            }
        }
        return self::$cache[$key];
    }

    public static function forget() { self::$cache = []; }

    /** True when the amount equals the open balance of an invoice (credit) or bill (debit). */
    public static function reserved($debit, $credit)
    {
        $d = (float) preg_replace('/[^0-9.\-]/', '', (string) $debit);
        $c = (float) preg_replace('/[^0-9.\-]/', '', (string) $credit);
        $k = number_format(max($c, $d), 2, '.', '');
        if ($c > 0) { return isset(self::balances('sales')[$k]) || isset(self::chequeAmounts('sales')[$k]); }
        if ($d > 0) { return isset(self::balances('purchase')[$k]) || isset(self::chequeAmounts('purchase')[$k]); }
        return false;
    }
}
