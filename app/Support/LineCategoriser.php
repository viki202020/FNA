<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Session;

/**
 * The one place that categorises (or un-categorises) a bank line.
 * A bank line has two entries: the bank's own side and the side that waits for an account.
 * They always move together: both count in the accounts, or neither does.
 */
class LineCategoriser
{
    /** Gives the waiting side its account and brings both entries into the accounts. $source: 'rule' or 'person'. */
    public static function set($lineId, $headId, $source, $ruleId = null)
    {
        $user = Session::get('id');
        $line = DB::table('statement')->where('id', $lineId)->first();
        if (!$line) { return false; }
        $update = [
            'head_id' => $headId, 'status' => 1, 'cat_source' => $source, 'cat_rule_id' => $ruleId, 'cat_by' => $user,
            'updated_at' => date('d-m-Y H:i:s'), 'updated_by' => $user,
        ];
        // Old lines have no financial year; without one they would be left out of every report.
        if (empty($line->fyear) && ($fy = FinancialYears::yearForDate($line->st_date))) { $update['fyear'] = $fy; }
        DB::table('statement')->where('id', $lineId)->update($update);
        self::siblings($line)->update(['status' => 1]);
        return true;
    }

    /** Puts a line back to Uncategorised: both entries leave the accounts. */
    public static function clear($lineId)
    {
        $user = Session::get('id');
        $line = DB::table('statement')->where('id', $lineId)->first();
        if (!$line) { return false; }
        DB::table('statement')->where('id', $lineId)->update([
            'head_id' => null, 'status' => 0, 'cat_source' => null, 'cat_rule_id' => null, 'cat_by' => null,
            'updated_at' => date('d-m-Y H:i:s'), 'updated_by' => $user,
        ]);
        self::siblings($line)->update(['status' => 0]);
        return true;
    }

    /** The bank's own entry that belongs to the same bank line. */
    private static function siblings($line)
    {
        $q = DB::table('statement')->where('id', '<>', $line->id)->whereRaw('1 = 0');
        if (!empty($line->voucher_no)) {
            $q = DB::table('statement')->where('merchant_id', $line->merchant_id)->where('voucher_no', $line->voucher_no)
                ->where('id', '<>', $line->id)->whereNotNull('bank_id')->whereColumn('head_id', 'bank_id');
        }
        return $q;
    }
}
