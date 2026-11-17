<?php

namespace App\Support;

/**
 * Checks a company or customer tax number.
 *   India:      GSTIN, 15 characters, e.g. 27AAPFU0939F1ZV (format and check character)
 *   Australia:  ABN, 11 digits, e.g. 51 824 753 556 (ATO check-digit rule)
 * The number is case-insensitive and spaces are ignored.
 */
class TaxId
{
    public static function clean($value)
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $value));
    }

    public static function gstin($value)
    {
        $v = self::clean($value);
        if (!preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', $v)) {
            return false;
        }
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $sum = 0;
        for ($i = 0; $i < 14; $i++) {
            $product = strpos($chars, $v[$i]) * (($i % 2 == 0) ? 1 : 2);
            $sum += intdiv($product, 36) + ($product % 36);
        }
        return $v[14] === $chars[(36 - ($sum % 36)) % 36];
    }

    public static function abn($value)
    {
        $v = self::clean($value);
        if (!preg_match('/^[0-9]{11}$/', $v)) {
            return false;
        }
        $weights = [10, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19];
        $digits = str_split($v);
        $digits[0] = (int) $digits[0] - 1;
        $sum = 0;
        foreach ($digits as $i => $d) {
            $sum += ((int) $d) * $weights[$i];
        }
        return $sum % 89 === 0;
    }

    /** Which kind of number this is, or null if it is not a valid GSTIN or ABN. */
    public static function kind($value)
    {
        if (self::gstin($value)) {
            return 'GSTIN';
        }
        if (self::abn($value)) {
            return 'ABN';
        }
        return null;
    }

    /** An error message for the form, or null when the number is fine (or empty). */
    public static function error($value, $label = 'GSTIN / ABN')
    {
        if (trim((string) $value) === '') {
            return null;
        }
        if (self::kind($value) !== null) {
            return null;
        }
        $length = strlen(self::clean($value));
        return $label . " is not valid: it has " . $length . " characters. "
             . "A GSTIN has 15 characters (for example 27AAPFU0939F1ZV); an ABN has 11 digits (for example 51 824 753 556). "
             . "Check for a missing or mistyped character.";
    }
}
