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
                $rows = DB::table('billing_docs')->where('merchant_id', $m)->where('kind', $kind)
                    ->whereIn('status', ['open', 'part'])->get(['total', 'settled']);
                foreach ($rows as $r) {
                    self::$cache[$key][number_format($r->total - $r->settled, 2, '.', '')] = true;
                }
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
        if ($c > 0) { return isset(self::balances('sales')[number_format($c, 2, '.', '')]); }
        if ($d > 0) { return isset(self::balances('purchase')[number_format($d, 2, '.', '')]); }
        return false;
    }
}
