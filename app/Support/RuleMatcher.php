<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * One place that decides whether a rule fits a bank line. Used by the Apply Rule buttons.
 * (Amounts are as the bank shows them: debit = money out, credit = money in.)
 * A rule may start on the Amount or on the Description; the second condition covers the other column.
 */
class RuleMatcher
{
    public static function clean($v)
    {
        $v = trim((string) $v);
        return ($v === '-' ) ? '' : $v;
    }

    public static function text($mode, $wanted, $text)
    {
        $wanted = trim((string) $wanted);
        if ($mode === 'Exact') {
            return strcasecmp(trim((string) $text), $wanted) === 0;
        }
        if ($wanted === '') { return true; }
        return Str::contains(strtoupper((string) $text), strtoupper($wanted));
    }

    public static function amount($cond, $wanted, $amount)
    {
        if ($cond === null || $cond === '' || $cond === 'Any' || $cond === 'Any Value') { return true; }
        $a = (float) $amount; $w = (float) $wanted;
        switch ($cond) {
            case 'Exact': return abs($a - $w) < 0.005;
            case '>=': return $a >= $w;
            case '<=': return $a <= $w;
            case '>':  return $a > $w;
            case '<':  return $a < $w;
        }
        return false;
    }

    /** Does $rule fit a line with this remark and these debit / credit values? */
    public static function fits($rule, $remark, $debit, $credit)
    {
        $debit = self::clean($debit); $credit = self::clean($credit);
        $isDebit = $debit !== '' && (float) $debit > 0;
        $isCredit = $credit !== '' && (float) $credit > 0;
        if (!(($rule->rule_for === 'Debit' && $isDebit) || ($rule->rule_for === 'Credit' && $isCredit))) {
            return false;
        }
        $lineAmount = $rule->rule_for === 'Debit' ? $debit : $credit;
        if ($rule->col_type === 'Amount') {
            return self::amount($rule->rule_condition, $rule->content, $lineAmount)
                && ($rule->rule_condition1 === 'Any' || $rule->rule_condition1 === null || $rule->rule_condition1 === ''
                    ? true : self::text($rule->rule_condition1 === 'Exact' ? 'Exact' : 'like', $rule->content1, $remark));
        }
        if ($rule->col_type === 'Description') {
            return self::text($rule->rule_condition, $rule->content, $remark)
                && self::amount($rule->rule_condition1, $rule->content1, $lineAmount);
        }
        return false;
    }
}
