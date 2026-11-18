<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Session;

/** Which account heads invoices and bills post to. Defaults can be changed per company. */
class BillingSettings
{
    const DEFAULTS = [
        'sales_head' => 71,      // Sales
        'receivable_head' => 56, // Trade Receivables
        'cgst_out' => 80,        // CGST Output tax Payable
        'sgst_out' => 81,        // SGST Output tax Payable
        'igst_out' => 82,        // IGST Output tax Payable
        'payable_head' => 37,    // Accounts Payable
        'cgst_in' => 86,         // CGST Input Tax
        'sgst_in' => 87,         // SGST Input Tax
        'igst_in' => 104,        // IGST Input tax
    ];

    public static function get($key)
    {
        if (Schema::hasTable('fna_settings')) {
            $v = DB::table('fna_settings')->where('merchant_id', Session::get('merchant_id'))->where('skey', $key)->value('svalue');
            if ($v !== null && $v !== '') {
                return (int) $v;
            }
        }
        return self::DEFAULTS[$key];
    }
}
