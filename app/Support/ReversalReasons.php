<?php

namespace App\Support;

/**
 * The reasons offered when a payment (to a supplier) or a receipt (from a customer) is reversed.
 * "Cheque dishonoured" is offered only when the payment mode is Cheque.
 * "Others" needs a typed reason that reads like a real sentence.
 */
class ReversalReasons
{
    public static function list($isReceipt, $mode)
    {
        $l = ['CHEQUE_DISHONOURED' => 'Cheque dishonoured'];
        if (strtolower((string) $mode) !== 'cheque') { unset($l['CHEQUE_DISHONOURED']); }
        $l += $isReceipt
            ? ['FRAUD' => 'Fraud discovered', 'COURT_CASE' => 'Court case', 'WRONG_AMOUNT' => 'Wrong amount entered', 'WRONG_DATE' => 'Wrong payment date',
               'WRONG_MODE' => 'Incorrect payment mode', 'WITHHELD' => 'Payment withheld by customer']
            : ['FRAUD' => 'Supplier fraud', 'WRONG_AMOUNT' => 'Wrong amount entered', 'WRONG_DATE' => 'Wrong payment date',
               'WRONG_MODE' => 'Incorrect payment mode', 'WITHHELD' => 'Payment withheld by management'];
        $l['OTHERS'] = 'Others';
        return $l;
    }

    /** One list for voiding an invoice or a bill. */
    public static function voidList()
    {
        return ['CHEQUE_DISHONOURED' => 'Cheque dishonoured', 'FRAUD' => 'Fraud discovered', 'COURT_CASE' => 'Court case',
                'WRONG_AMOUNT' => 'Wrong amount entered', 'WRONG_DATE' => 'Wrong payment date', 'WRONG_MODE' => 'Incorrect payment mode',
                'WITHHELD' => 'Payment withheld by customer', 'OTHERS' => 'Others'];
    }

    /** Returns an error message, or null when the reason is acceptable. */
    public static function error($code, $text, $isReceipt, $mode, $lastReason = null)
    {
        $allowed = $mode === 'VOID' ? self::voidList() : self::list($isReceipt, $mode);
        if (!isset($allowed[$code])) { return 'Choose a reason from the list.'; }
        if ($code !== 'OTHERS') { return null; }
        $t = trim((string) $text);
        if (mb_strlen($t) < 15) { return 'Please explain in at least 15 characters.'; }
        $words = preg_split('/\s+/u', $t);
        if (count($words) < 3) { return 'Please explain in at least three words.'; }
        if (preg_match('/(.)\1{4,}/u', $t)) { return 'That does not read like a real reason.'; }
        $letters = preg_replace('/[^\p{L}]/u', '', mb_strtolower($t));
        if (mb_strlen($letters) < 10 || count(array_unique(preg_split('//u', $letters, -1, PREG_SPLIT_NO_EMPTY))) < 5) { return 'That does not read like a real reason.'; }
        $junk = ['test', 'testing', 'na', 'n/a', 'abc', 'asdf', 'xyz', 'wrong', 'error', 'mistake', 'none', 'nil', 'other', 'others', 'misc', 'reason'];
        if (in_array(mb_strtolower($t), $junk, true)) { return 'That does not read like a real reason.'; }
        $vowels = preg_match_all('/[aeiouAEIOU\x{0900}-\x{097F}]/u', $t);
        if ($vowels < 3) { return 'That does not read like a real reason.'; }
        if ($lastReason !== null && mb_strtolower(trim($lastReason)) === mb_strtolower($t)) { return 'Please give a reason specific to this reversal.'; }
        return null;
    }
}
