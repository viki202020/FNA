<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Session;

/** Outstanding receivables (open sales invoices) and payables (open purchase bills): by party, with due dates and ageing by days overdue. */
class Outstanding
{
    const BUCKETS = ['Not yet due', '1 to 30 days', '31 to 60 days', '61 to 90 days', 'Over 90 days'];

    private static function bucket($overdueDays)
    {
        if ($overdueDays <= 0) { return self::BUCKETS[0]; }
        if ($overdueDays <= 30) { return self::BUCKETS[1]; }
        if ($overdueDays <= 60) { return self::BUCKETS[2]; }
        if ($overdueDays <= 90) { return self::BUCKETS[3]; }
        return self::BUCKETS[4];
    }

    /** $kind: sales (receivables) or purchase (payables). */
    public static function report($kind, $asAt)
    {
        $docs = DB::table('billing_docs')->where('merchant_id', Session::get('merchant_id'))->where('kind', $kind)->whereIn('status', ['open', 'part'])
            ->where('doc_date', '<=', $asAt)->orderBy('due_date')->get();
        $parties = []; $totals = array_fill_keys(self::BUCKETS, 0.0); $grand = 0.0; $overdue = 0.0;
        foreach ($docs as $d) {
            $bal = round($d->total - $d->settled, 2);
            if ($bal <= 0) { continue; }
            $due = $d->due_date ?: $d->doc_date;
            $days = (int) floor((strtotime($asAt) - strtotime($due)) / 86400);
            $b = self::bucket($days);
            $key = $d->party_id ?: $d->party_name;
            if (!isset($parties[$key])) { $parties[$key] = ['name' => $d->party_name, 'docs' => [], 'buckets' => array_fill_keys(self::BUCKETS, 0.0), 'total' => 0.0, 'overdue' => 0.0]; }
            $parties[$key]['docs'][] = ['id' => $d->id, 'number' => $d->number, 'date' => $d->doc_date, 'due' => $due, 'amount' => (float) $d->total, 'balance' => $bal, 'overdue_days' => max(0, $days), 'bucket' => $b];
            $parties[$key]['buckets'][$b] += $bal; $parties[$key]['total'] += $bal;
            if ($days > 0) { $parties[$key]['overdue'] += $bal; $overdue += $bal; }
            $totals[$b] += $bal; $grand += $bal;
        }
        uasort($parties, fn($a, $b) => $b['total'] <=> $a['total']);
        return ['parties' => $parties, 'buckets' => $totals, 'total' => round($grand, 2), 'overdue' => round($overdue, 2), 'count' => count($docs), 'as_at' => $asAt];
    }

    /** Just the two figures for the dashboard. */
    public static function summary($kind)
    {
        $r = self::report($kind, date('Y-m-d'));
        return ['total' => $r['total'], 'overdue' => $r['overdue'], 'parties' => count($r['parties'])];
    }
}
