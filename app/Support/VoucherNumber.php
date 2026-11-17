<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Hands out voucher numbers in a series, never reusing one:
 *   SBV-00001  System Bank Voucher       (one per bank statement line)
 *   UJV0001    Uploaded Journal Voucher  (one per voucher in an uploaded journal file)
 *   MJV0001    Manual Journal Voucher    (booked by hand)
 * The next number is one more than the highest already stored for that prefix.
 */
class VoucherNumber
{
    private $prefix;
    private $width;
    private $last;

    public function __construct($prefix, $width)
    {
        $this->prefix = $prefix;
        $this->width = $width;
        $row = DB::table('statement')
            ->where('voucher_no', 'like', $prefix . '%')
            ->selectRaw('MAX(CAST(SUBSTRING(voucher_no, ?) AS UNSIGNED)) AS m', [strlen($prefix) + 1])
            ->first();
        $this->last = (int) ($row->m ?? 0);
    }

    public static function sequence($prefix, $width)
    {
        return new self($prefix, $width);
    }

    public function next()
    {
        $this->last++;
        return $this->prefix . str_pad((string) $this->last, $this->width, '0', STR_PAD_LEFT);
    }
}
