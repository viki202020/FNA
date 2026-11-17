<?php

namespace App\Support;

use App\Models\Entity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Session;

/**
 * Which financial years are open for entry.
 *
 *  - The company's CURRENT year is open.
 *  - Every other year is CLOSED: read only, hidden from the year boxes by default.
 *  - A closed year can be re-opened by an administrator (a row in financial_year_reopen,
 *    with a reason); it stays open until that row gets a closed_again_at date.
 *
 * Closing matters because the next year's opening balances are taken from the closed
 * year: changing a closed year silently would make those openings wrong.
 */
class FinancialYears
{
    private static function merchantId()
    {
        return Session::get('merchant_id');
    }

    public static function entity()
    {
        return Entity::where('status', 1)
            ->where('merchant_id', self::merchantId())
            ->orderBy('id', 'desc')
            ->first();
    }

    public static function current()
    {
        $entity = self::entity();
        return $entity ? $entity->financial_year : null;
    }

    /** Years re-opened by an administrator and not yet closed again. */
    public static function reopened()
    {
        if (!Schema::hasTable('financial_year_reopen')) {
            return [];
        }
        return DB::table('financial_year_reopen')
            ->where('merchant_id', self::merchantId())
            ->whereNull('closed_again_at')
            ->pluck('financial_year')
            ->all();
    }

    public static function openYears()
    {
        $years = array_merge([self::current()], self::reopened());
        return array_values(array_unique(array_filter($years)));
    }

    /** Lines with no year at all (old data) are not treated as closed. */
    public static function isOpen($financialYear)
    {
        if ($financialYear === null || $financialYear === '') {
            return true;
        }
        return in_array($financialYear, self::openYears(), true);
    }

    public static function isReopened($financialYear)
    {
        return in_array($financialYear, self::reopened(), true) && $financialYear !== self::current();
    }

    /** Is this date inside the company's current financial year? */
    public static function dateInCurrentYear($date)
    {
        $entity = self::entity();
        $time = strtotime((string) $date);
        if (!$entity || $time === false || !$entity->financial_year_start || !$entity->financial_year_end) {
            return false;
        }
        return $time >= strtotime($entity->financial_year_start)
            && $time <= strtotime($entity->financial_year_end . ' 23:59:59');
    }

    /**
     * The years to offer in a year box: open years always; closed years only when
     * asked for, or when the page is already showing one.
     */
    public static function options(array $knownYears, $selected, $showClosed)
    {
        $all = array_values(array_unique(array_filter(array_merge($knownYears, [self::current(), $selected]))));
        sort($all);
        return array_values(array_filter($all, function ($year) use ($selected, $showClosed) {
            return self::isOpen($year) || $showClosed || $year === $selected;
        }));
    }

    public static function message($financialYear)
    {
        return "Financial year " . $financialYear . " is closed, so its entries cannot be changed. "
             . "An administrator can re-open it (the next year's opening balances will then need to be checked).";
    }
}
