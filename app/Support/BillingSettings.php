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
        'tds_deducted' => 49,    // TDS Payable (tax we deduct when we pay)
        'tds_recoverable' => 107,// TDS recoverable (tax a customer deducted when paying us)
        'cash_head' => 2,        // Cash and Cash Equivalents
    ];

    /** key => [label shown on the settings screen, what it is used for] */
    const LABELS = [
        'sales_head' => ['Sales', 'Credited on every sales invoice, for the amount before tax'],
        'receivable_head' => ['Trade Receivables', 'Debited on a sales invoice; credited when the customer pays'],
        'cgst_out' => ['CGST on sales', 'Credited on a sales invoice (same state)'],
        'sgst_out' => ['SGST on sales', 'Credited on a sales invoice (same state)'],
        'igst_out' => ['IGST on sales', 'Credited on a sales invoice (other state)'],
        'payable_head' => ['Accounts Payable', 'Credited on a purchase bill; debited when you pay it'],
        'cgst_in' => ['CGST on purchases', 'Debited on a purchase bill (same state)'],
        'sgst_in' => ['SGST on purchases', 'Debited on a purchase bill (same state)'],
        'igst_in' => ['IGST on purchases', 'Debited on a purchase bill (other state)'],
        'tds_deducted' => ['TDS deducted (payable)', 'Tax you deduct from a supplier payment'],
        'tds_recoverable' => ['TDS recoverable', 'Tax a customer deducts from a receipt'],
        'cash_head' => ['Cash in hand', 'Cash payments and receipts'],
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

    /**
     * The two "cheques in transit" heads (created the first time they are needed):
     *   chq_out: cheques issued, not yet presented (credit balance, reduces the bank to the book balance)
     *   chq_in:  cheques received, not yet cleared (debit balance)
     * They sit in an account type of their own, so they never appear in the bank lists.
     */
    public static function transitHead($key)
    {
        $existing = null;
        if (Schema::hasTable('fna_settings')) {
            $existing = DB::table('fna_settings')->where('merchant_id', Session::get('merchant_id'))->where('skey', $key)->value('svalue');
        }
        if ($existing) { return (int) $existing; }
        $bankType = DB::table('account_type')->where('id', 3)->first();
        $typeId = DB::table('account_type')->where('name', 'Cheques in transit')->value('id');
        if (!$typeId) {
            $typeId = DB::table('account_type')->insertGetId([
                'parent_id' => $bankType ? $bankType->parent_id : 1, 'ac_type_parent_id' => $bankType->ac_type_parent_id ?? null,
                'name' => 'Cheques in transit', 'status' => 1, 'sequence' => '3',
            ]);
        }
        $name = $key === 'chq_out' ? 'Cheques issued, not yet presented' : 'Cheques received, not yet cleared';
        $headId = DB::table('head')->insertGetId([
            'account_type_id' => $typeId, 'name' => $name, 'status' => 1, 'merchant' => Session::get('merchant_id'),
            'created_at' => date('d-m-Y H:i:s'), 'created_by' => Session::get('id') ?: 0,
        ]);
        DB::table('fna_settings')->updateOrInsert(['merchant_id' => Session::get('merchant_id'), 'skey' => $key], ['svalue' => (string) $headId]);
        return $headId;
    }
}
