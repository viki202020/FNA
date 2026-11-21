<?php

namespace App\Support;

/** What is missing from a customer's or supplier's record that matters when an invoice or bill is booked. */
class PartyChecks
{
    /** @return array of ['level' => warn|info, 'text' => ...] */
    public static function issues($party, $kind)
    {
        if (!$party) { return []; }
        $out = [];
        $isSale = $kind === 'sales';
        if (trim((string) $party->gst) === '') {
            $out[] = ['level' => 'warn', 'text' => $isSale ? 'No GSTIN: this will be treated as a sale to an unregistered customer.' : 'No GSTIN: input tax credit will not be available on this bill.'];
        }
        if (trim((string) $party->billing_state) === '') {
            $out[] = ['level' => 'warn', 'text' => 'No state: CGST + SGST or IGST cannot be decided properly, so IGST is used.'];
        }
        $applicable = $party->tds_status;
        if ($applicable === null || $applicable === '') {
            $out[] = ['level' => 'info', 'text' => 'TDS applicability is not set for this ' . ($isSale ? 'customer' : 'supplier') . '.'];
        }
        if ($applicable === null || $applicable === '' || (int) $applicable === 1) {
            if (trim((string) $party->pan) === '') {
                $out[] = ['level' => 'warn', 'text' => $isSale ? 'No PAN: the customer\'s TDS certificate cannot be matched.' : 'No PAN: if TDS is deducted it must be at the higher rate (20%).'];
            }
        }
        if ((int) $applicable === 1) {
            if (trim((string) $party->tds_section) === '') { $out[] = ['level' => 'warn', 'text' => 'TDS is applicable but the section (for example 194J) is not set.']; }
            if (!is_numeric($party->tds_rate) || (float) $party->tds_rate <= 0) { $out[] = ['level' => 'warn', 'text' => 'TDS is applicable but the rate is not set.']; }
        }
        return $out;
    }
}
