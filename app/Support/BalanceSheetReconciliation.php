<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Session;

/**
 * Balance sheet reconciliation: verifies that what the balance sheet shows is accurate.
 *  1. Every component's closing balance, listed as a trial balance (debits must equal credits).
 *  2. The same components regrouped as assets against liabilities (they must match).
 *  3. Each component tied back to its own records: receivables and payables to the open invoices and bills, bank to the bank statement,
 *     this year's opening balances to last year's closing balances.
 * Any difference is shown with the amount, so it can be traced and fixed.
 */
class BalanceSheetReconciliation
{
    public static function run($fyear)
    {
        $merchant = Session::get('merchant_id');
        $f = Schedule3::rawFigures($fyear);
        $lines = $f['lines'];

        // 1. components as closing debit / credit balances
        $components = []; $dr = 0; $cr = 0;
        $order = ['share_capital', 'reserves', 'lt_borrow', 'dtl', 'other_lt_liab', 'lt_prov', 'st_borrow', 'trade_pay', 'other_cl', 'st_prov',
                  'ppe', 'nc_inv', 'dta', 'lt_loans', 'other_nca', 'c_inv', 'inventories', 'trade_rec', 'cash', 'st_loans', 'other_ca'];
        foreach ($order as $k) {
            $v = round($lines[$k] ?? 0, 2);
            if (abs($v) < 0.005) { continue; }
            $isLiab = Schedule3::LINES[$k][1] === 'L';
            $debit = $isLiab ? ($v < 0 ? -$v : 0) : ($v > 0 ? $v : 0);
            $credit = $isLiab ? ($v > 0 ? $v : 0) : ($v < 0 ? -$v : 0);
            $components[] = ['key' => $k, 'label' => Schedule3::LINES[$k][0], 'debit' => $debit, 'credit' => $credit];
            $dr += $debit; $cr += $credit;
        }
        $trial = ['components' => $components, 'debit' => round($dr, 2), 'credit' => round($cr, 2), 'difference' => round($dr - $cr, 2)];

        // 2. assets against liabilities
        $g = fn(array $keys, $sign = 1) => round($sign * array_sum(array_map(fn($k) => $lines[$k] ?? 0, $keys)), 2);
        $assets = [
            ['Net fixed assets', $g(['ppe'])], ['Net investments', $g(['nc_inv', 'c_inv'])], ['Other non-current assets', $g(['dta', 'lt_loans', 'other_nca'])],
            ['Net current assets', $g(['inventories', 'trade_rec', 'cash', 'st_loans', 'other_ca'])],
        ];
        $liab = [
            ['Share capital', $g(['share_capital'])], ['Reserves and surplus (with the year\'s profit)', $g(['reserves'])], ['Borrowings', $g(['lt_borrow', 'st_borrow'])],
            ['Other non-current liabilities', $g(['dtl', 'other_lt_liab', 'lt_prov'])], ['Net current liabilities', $g(['trade_pay', 'other_cl', 'st_prov'])],
        ];
        $ta = round(array_sum(array_column($assets, 1)), 2); $tl = round(array_sum(array_column($liab, 1)), 2);
        $summary = ['assets' => $assets, 'liabilities' => $liab, 'total_assets' => $ta, 'total_liabilities' => $tl, 'difference' => round($ta - $tl, 2)];

        // 3. tie-outs
        $ties = [];
        $entity = FinancialYears::entity();
        $asAt = $entity && $entity->financial_year === $fyear ? $entity->financial_year_end : (substr($fyear, -4) . '-03-31');
        foreach ([['Trade receivables', 'trade_rec', 'sales', 'open sales invoices'], ['Trade payables', 'trade_pay', 'purchase', 'open purchase bills']] as [$label, $key, $kind, $what]) {
            $docs = DB::table('billing_docs')->where('merchant_id', $merchant)->where('kind', $kind)->whereIn('status', ['open', 'part'])->where('doc_date', '<=', $asAt)->get();
            $docTotal = round($docs->sum(fn($d) => $d->total - $d->settled), 2);
            $ledger = round($lines[$key] ?? 0, 2);
            $ties[] = ['name' => $label . ' against ' . $what, 'a_label' => 'Balance sheet', 'a' => $ledger, 'b_label' => 'Open documents', 'b' => $docTotal, 'diff' => round($ledger - $docTotal, 2),
                'note' => 'A difference is expected where balances were brought forward without invoices or bills. The rest should be traced.'];
        }
        // bank accounts: books against statement less items not in the accounts
        foreach (DB::table('head')->where('account_type_id', 3)->where('status', 1)->where('merchant', $merchant)->get(['id', 'name']) as $b) {
            $stmt = (float) (DB::table('statement')->where('merchant_id', $merchant)->whereIn('status', [0, 1])->where('head_id', $b->id)->where('fyear', $fyear)
                ->selectRaw("SUM(CAST(NULLIF(NULLIF(REPLACE(debit,',',''),'-'),'') AS DECIMAL(16,2))) - SUM(CAST(NULLIF(NULLIF(REPLACE(credit,',',''),'-'),'') AS DECIMAL(16,2))) AS n")->value('n') ?? 0);
            $uncat = (float) (DB::table('statement')->where('merchant_id', $merchant)->where('status', 0)->where('head_id', $b->id)->where('fyear', $fyear)
                ->selectRaw("SUM(CAST(NULLIF(NULLIF(REPLACE(debit,',',''),'-'),'') AS DECIMAL(16,2))) - SUM(CAST(NULLIF(NULLIF(REPLACE(credit,',',''),'-'),'') AS DECIMAL(16,2))) AS n")->value('n') ?? 0);
            $books = (float) (DB::table('statement')->where('merchant_id', $merchant)->where('status', 1)->where('head_id', $b->id)->where('fyear', $fyear)
                ->selectRaw("SUM(CAST(NULLIF(NULLIF(REPLACE(debit,',',''),'-'),'') AS DECIMAL(16,2))) - SUM(CAST(NULLIF(NULLIF(REPLACE(credit,',',''),'-'),'') AS DECIMAL(16,2))) AS n")->value('n') ?? 0);
            if (abs($stmt) < 0.005 && abs($books) < 0.005) { continue; }
            $ties[] = ['name' => $b->name . ': books against the bank statement', 'a_label' => 'In the books', 'a' => round($books, 2), 'b_label' => 'Statement less lines not yet categorised', 'b' => round($stmt - $uncat, 2),
                'diff' => round($books - ($stmt - $uncat), 2), 'note' => 'Uncategorised bank lines of ' . number_format($uncat, 2) . ' are not in the books yet.'];
        }
        // this year's opening balances against last year's closing balances
        $prevYear = Schedule3::previousYear($fyear);
        $opening = DB::table('statement')->where('merchant_id', $merchant)->where('status', 1)->where('fyear', $fyear)->where('opening', 1)
            ->selectRaw("head_id, SUM(CAST(NULLIF(NULLIF(REPLACE(debit,',',''),'-'),'') AS DECIMAL(16,2))) - SUM(CAST(NULLIF(NULLIF(REPLACE(credit,',',''),'-'),'') AS DECIMAL(16,2))) AS n")->groupBy('head_id')->pluck('n', 'head_id');
        $carry = ['checked' => false, 'differences' => [], 'prev_year' => $prevYear];
        if ($opening->count() && DB::table('statement')->where('merchant_id', $merchant)->where('status', 1)->where('fyear', $prevYear)->exists()) {
            $prevBal = Schedule3::headBalances($prevYear);
            $isBs = DB::table('head')->join('account_type as t', 't.id', '=', 'head.account_type_id')->join('account_type_parent as p', 'p.id', '=', 't.parent_id')->where('p.financial_statement_id', 1)->pluck('head.id')->flip();
            $names = DB::table('head')->pluck('name', 'id');
            foreach ($isBs as $hid => $_) {
                $o = round((float) ($opening[$hid] ?? 0), 2); $c = round((float) ($prevBal[$hid] ?? 0), 2);
                if (abs($o - $c) > 0.004) { $carry['differences'][] = ['head' => $names[$hid] ?? $hid, 'opening' => $o, 'closing' => $c, 'diff' => round($o - $c, 2)]; }
            }
            $carry['checked'] = true;
        }
        $extras = [
            'uncategorised' => (int) DB::table('statement')->where('merchant_id', $merchant)->where('fyear', $fyear)->where('status', 0)->where('opening', 0)->count(),
            'unmapped' => $f['unmapped'], 'clear' => $f['clear'], 'profit' => $f['profit'],
        ];
        return ['year' => $fyear, 'trial' => $trial, 'summary' => $summary, 'ties' => $ties, 'carry' => $carry, 'extras' => $extras];
    }
}
