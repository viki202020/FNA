<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Session;

/**
 * Turns a saved invoice (sales) or bill (purchase) into a posted voucher.
 *   Sales:    Dr Trade Receivables (total)   Cr Sales (subtotal)   Cr CGST+SGST or IGST output tax
 *   Purchase: Dr expense heads (each line)   Dr CGST+SGST or IGST input tax   Cr Accounts Payable (total)
 * The voucher always balances, because the total is the sum of the other lines.
 */
class BillingPosting
{
    /** Numbers like SIV-2425-001; the counter starts again each financial year. */
    public static function nextNumber($kind, $fyear, $merchant)
    {
        $prefix = $kind === 'sales' ? 'SIV' : 'PBV';
        [$a, $b] = array_pad(explode('-', (string) $fyear), 2, '');
        $stem = $prefix . '-' . substr($a, -2) . substr($b, -2) . '-';
        $max = DB::table('billing_docs')->where('merchant_id', $merchant)->where('kind', $kind)
            ->where('number', 'like', $stem . '%')
            ->selectRaw('MAX(CAST(SUBSTRING(number, ?) AS UNSIGNED)) m', [strlen($stem) + 1])->value('m');
        return $stem . str_pad((string) (((int) $max) + 1), 3, '0', STR_PAD_LEFT);
    }

    /** Works out the tax from the lines and the supply type; returns totals. */
    public static function totals(array $lines, $supply)
    {
        $sub = 0; $tax = 0;
        foreach ($lines as $l) {
            $amt = round($l['qty'] * $l['rate'], 2);
            $sub += $amt;
            $tax += round($amt * $l['tax_rate'] / 100, 2);
        }
        if ($supply === 'inter') {
            return ['subtotal' => $sub, 'cgst' => 0, 'sgst' => 0, 'igst' => round($tax, 2), 'total' => round($sub + $tax, 2)];
        }
        $half = round($tax / 2, 2);
        return ['subtotal' => $sub, 'cgst' => $half, 'sgst' => round($tax - $half, 2), 'igst' => 0, 'total' => round($sub + $tax, 2)];
    }

    /** Intra-state (CGST+SGST) when the party and the company are in the same state, otherwise IGST. */
    public static function supplyType($companyState, $partyState)
    {
        $a = strtolower(trim((string) $companyState));
        $b = strtolower(trim((string) $partyState));
        return ($a !== '' && $a === $b) ? 'intra' : 'inter';
    }

    /** Posts the document. Returns the voucher number or throws. */
    public static function post($docId)
    {
        return DB::transaction(function () use ($docId) {
            $doc = DB::table('billing_docs')->where('id', $docId)->lockForUpdate()->first();
            if (!$doc || $doc->status !== 'draft') {
                throw new \RuntimeException('Only a draft can be posted.');
            }
            $fyear = FinancialYears::current();
            if (!FinancialYears::isOpen($fyear) || !FinancialYears::dateInCurrentYear($doc->doc_date)) {
                throw new \RuntimeException('The date is outside the open financial year.');
            }
            $lines = DB::table('billing_doc_lines')->where('doc_id', $doc->id)->get();
            if ($lines->isEmpty()) {
                throw new \RuntimeException('Add at least one line.');
            }
            $sales = $doc->kind === 'sales';
            $number = self::nextNumber($doc->kind, $fyear, $doc->merchant_id);
            $voucherId = DB::table('vouchers')->insertGetId([
                'merchant_id' => $doc->merchant_id, 'voucher_no' => $number, 'series' => $sales ? 'SIV' : 'PBV',
                'fyear' => $fyear, 'voucher_date' => $doc->doc_date, 'status' => 'posted', 'version' => 1,
                'narration' => ($sales ? 'Sales invoice ' : 'Purchase bill ') . $number . ' ' . $doc->party_name,
                'reference' => $doc->reference, 'created_by' => Session::get('id'), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $out = [];
            $add = function ($head, $dr, $cr) use (&$out) {
                if ((float) $dr == 0 && (float) $cr == 0) { return; }
                $out[] = ['head' => $head, 'dr' => $dr, 'cr' => $cr];
            };
            if ($sales) {
                $add(BillingSettings::get('receivable_head'), $doc->total, 0);
                $add(BillingSettings::get('sales_head'), 0, $doc->subtotal);
                $add(BillingSettings::get('cgst_out'), 0, $doc->cgst);
                $add(BillingSettings::get('sgst_out'), 0, $doc->sgst);
                $add(BillingSettings::get('igst_out'), 0, $doc->igst);
            } else {
                foreach ($lines as $l) { $add($l->head_id, $l->amount, 0); }
                $add(BillingSettings::get('cgst_in'), $doc->cgst, 0);
                $add(BillingSettings::get('sgst_in'), $doc->sgst, 0);
                $add(BillingSettings::get('igst_in'), $doc->igst, 0);
                $add(BillingSettings::get('payable_head'), 0, $doc->total);
            }
            $dr = array_sum(array_map(fn($o) => round($o['dr'], 2), $out));
            $cr = array_sum(array_map(fn($o) => round($o['cr'], 2), $out));
            if (abs($dr - $cr) > 0.004) {
                throw new \RuntimeException('The voucher does not balance (' . number_format($dr, 2) . ' against ' . number_format($cr, 2) . '). Nothing was posted.');
            }
            foreach ($out as $o) {
                DB::table('statement')->insert([
                    'voucher_no' => $number, 'voucher_id' => $voucherId,
                    'debit' => $o['dr'] > 0 ? number_format($o['dr'], 2, '.', '') : '-',
                    'credit' => $o['cr'] > 0 ? number_format($o['cr'], 2, '.', '') : '-',
                    'remark' => ($sales ? 'Invoice ' : 'Bill ') . $number . ' ' . $doc->party_name,
                    'head_id' => $o['head'], 'st_date' => $doc->doc_date, 'fyear' => $fyear,
                    'status' => 1, 'opening' => 0, 'merchant_id' => $doc->merchant_id,
                    'created_at' => date('Y-m-d H:i:s'), 'created_by' => Session::get('id') ?: 0,
                ]);
            }
            DB::table('voucher_versions')->insert([
                'voucher_id' => $voucherId, 'version' => 1, 'action' => 'created',
                'changed_by' => Session::get('id'), 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('billing_docs')->where('id', $doc->id)->update([
                'number' => $number, 'fyear' => $fyear, 'status' => 'open', 'voucher_id' => $voucherId, 'updated_at' => now(),
            ]);
            return $number;
        });
    }
}
