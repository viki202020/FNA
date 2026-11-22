<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Working papers for the quarterly TDS return on payments to residents other than salary
 * (Form 26Q; Form 140 for deductions on or after 1 April 2026) and for the challans that paid the tax.
 * Built from the TDS Payable account: credits are deductions, debits are deposits. Reversed entries are left out.
 */
class TdsReturn
{
    public static function quarterRange($fyStart, $q)
    {
        $starts = [1 => [$fyStart, 4], 2 => [$fyStart, 7], 3 => [$fyStart, 10], 4 => [$fyStart + 1, 1]];
        [$y, $m] = $starts[$q];
        $from = sprintf('%04d-%02d-01', $y, $m);
        return [$from, date('Y-m-t', strtotime($from . ' +2 months'))];
    }

    public static function quarterOf($date)
    {
        $m = (int) date('n', strtotime($date)); $y = (int) date('Y', strtotime($date));
        $fy = $m >= 4 ? $y : $y - 1;
        return [$fy, $m >= 4 ? intdiv($m - 4, 3) + 1 : 4];
    }

    public static function filingDue($fyStart, $q)
    {
        return [1 => $fyStart . '-07-31', 2 => $fyStart . '-10-31', 3 => ($fyStart + 1) . '-01-31', 4 => ($fyStart + 1) . '-05-31'][$q];
    }

    public static function formName($fyStart) { return $fyStart >= 2026 ? 'Form 140' : 'Form 26Q'; }

    /** Quarters that have any TDS deducted, newest first: [[fyStart, q, label], ...]. */
    public static function quarters()
    {
        $head = BillingSettings::get('tds_deducted');
        $dates = DB::table('statement')->where('merchant_id', Session::get('merchant_id'))->where('status', 1)->where('head_id', $head)->where('opening', 0)
            ->whereRaw("credit NOT IN ('-','')")->selectRaw("DISTINCT DATE_FORMAT(st_date,'%Y-%m-01') d")->pluck('d')->all();
        $seen = [];
        foreach ($dates as $d) { [$fy, $q] = self::quarterOf($d); $seen[$fy * 10 + $q] = [$fy, $q]; }
        krsort($seen);
        return array_map(fn($x) => ['fy' => $x[0], 'q' => $x[1], 'key' => $x[0] . '-' . $x[1], 'label' => 'Q' . $x[1] . ' of ' . $x[0] . '-' . substr($x[0] + 1, 2) . ' (' . self::formName($x[0]) . ')'], array_values($seen));
    }

    /** Every TDS deduction in the range, with the deductee where the entry can be traced to a bill. */
    public static function deductions($from, $to)
    {
        $m = Session::get('merchant_id'); $head = BillingSettings::get('tds_deducted');
        $rows = DB::table('statement as s')->leftJoin('vouchers as v', 'v.id', '=', 's.voucher_id')
            ->where('s.merchant_id', $m)->where('s.status', 1)->where('s.head_id', $head)->where('s.opening', 0)
            ->whereRaw("s.credit NOT IN ('-','')")->whereBetween('s.st_date', [$from, $to])
            ->where(function ($q) { $q->whereNull('v.status')->orWhere('v.status', '!=', 'reversed'); })->where('s.voucher_no', 'not like', 'RJV%')
            ->orderBy('s.st_date')->orderBy('s.id')->get(['s.id', 's.st_date', 's.voucher_no', 's.voucher_id', 's.credit', 'v.narration']);
        $out = [];
        foreach ($rows as $r) {
            $docId = null; $payDate = null;
            if ($r->voucher_id) {
                $docId = DB::table('billing_settlements')->where('tds_voucher_id', $r->voucher_id)->value('doc_id');
                if (!$docId) { $docId = DB::table('payment_notes')->where('voucher_id', $r->voucher_id)->value('doc_id'); }
            }
            $doc = $docId ? DB::table('billing_docs')->where('id', $docId)->where('merchant_id', $m)->first() : null;
            $party = $doc && $doc->party_id ? DB::table('customer')->where('id', $doc->party_id)->first() : null;
            $tds = round((float) $r->credit, 2);
            $rate = $party && is_numeric($party->tds_rate) && (float) $party->tds_rate > 0 ? (float) $party->tds_rate : null;
            $base = $rate ? round($tds * 100 / $rate, 2) : null;
            $pan = $party ? strtoupper(trim((string) $party->pan)) : '';
            $issues = [];
            if (!$doc) { $issues[] = 'Not linked to a bill: deductee unknown'; }
            else {
                if (!preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $pan)) { $issues[] = 'PAN missing or invalid'; }
                if (!$party || trim((string) $party->tds_section) === '') { $issues[] = 'Section missing'; }
                if (!$rate) { $issues[] = 'Rate missing: base amount unknown'; }
                if (date('Y-m', strtotime($doc->doc_date)) < date('Y-m', strtotime($r->st_date))) { $issues[] = 'Bill dated ' . date('d/m/Y', strtotime($doc->doc_date)) . ' but TDS booked later. Tax is deductible at the earlier of credit and payment'; }
            }
            $out[] = ['id' => $r->id, 'date' => $r->st_date, 'voucher_no' => $r->voucher_no, 'tds' => $tds, 'rate' => $rate, 'base' => $base, 'pan' => $pan,
                'name' => $doc ? $doc->party_name : '', 'section' => $party ? trim((string) $party->tds_section) : '', 'doc' => $doc, 'credit_date' => $doc ? $doc->doc_date : null, 'issues' => $issues];
        }
        return $out;
    }

    /** All deposits to the tax department in the ledger (debits to TDS Payable), with challan details if entered. */
    public static function deposits()
    {
        $m = Session::get('merchant_id'); $head = BillingSettings::get('tds_deducted');
        $rows = DB::table('statement as s')->leftJoin('tds_challans as c', function ($j) { $j->on('c.statement_id', '=', 's.id'); })
            ->where('s.merchant_id', $m)->where('s.status', 1)->where('s.head_id', $head)->where('s.opening', 0)->whereRaw("s.debit NOT IN ('-','')")
            ->where('s.voucher_no', 'not like', 'RJV%')->orderBy('s.st_date')->orderBy('s.id')
            ->get(['s.id', 's.st_date', 's.voucher_no', 's.debit', 's.remark', 'c.for_month', 'c.bsr_code', 'c.challan_serial', 'c.interest', 'c.fee', 'c.note']);
        // a deposit not yet assigned to a month is offered the oldest month still unpaid
        $months = TdsDue::months();
        return $rows->map(function ($r) use ($months) {
            $r->amount = round((float) $r->debit, 2);
            $r->suggested = $r->for_month ?: ($months[0]['ym'] ?? date('Y-m', strtotime($r->st_date)));
            $r->due = $r->for_month ? TdsDue::dueDate($r->for_month) : null;
            $r->late_days = ($r->for_month && $r->st_date > $r->due) ? (int) floor((strtotime($r->st_date) - strtotime($r->due)) / 86400) : 0;
            return $r;
        });
    }

    public static function saveChallan($statementId, $forMonth, $bsr, $serial, $interest, $fee, $note)
    {
        $m = Session::get('merchant_id'); $head = BillingSettings::get('tds_deducted');
        $line = DB::table('statement')->where('id', $statementId)->where('merchant_id', $m)->where('head_id', $head)->where('status', 1)->whereRaw("debit NOT IN ('-','')")->first();
        if (!$line) { throw new \RuntimeException('That deposit was not found.'); }
        $data = ['for_month' => $forMonth, 'bsr_code' => $bsr ?: null, 'challan_serial' => $serial ?: null, 'interest' => max(0, (float) $interest), 'fee' => max(0, (float) $fee), 'note' => $note ?: null, 'updated_by' => Session::get('id'), 'updated_at' => now()];
        $has = DB::table('tds_challans')->where('merchant_id', $m)->where('statement_id', $statementId)->exists();
        if ($has) { DB::table('tds_challans')->where('merchant_id', $m)->where('statement_id', $statementId)->update($data); }
        else { DB::table('tds_challans')->insert($data + ['merchant_id' => $m, 'statement_id' => $statementId, 'created_at' => now()]); }
    }

    /** Month by month for a quarter: deducted, deposited (by the month each challan is for), difference. */
    public static function monthly($fyStart, $q, $deductions)
    {
        [$from] = self::quarterRange($fyStart, $q);
        $out = [];
        for ($i = 0; $i < 3; $i++) {
            $ym = date('Y-m', strtotime($from . " +$i months"));
            $ded = round(array_sum(array_map(fn($d) => date('Y-m', strtotime($d['date'])) === $ym ? $d['tds'] : 0, $deductions)), 2);
            $dep = round((float) DB::table('tds_challans as c')->join('statement as s', 's.id', '=', 'c.statement_id')->where('c.merchant_id', Session::get('merchant_id'))->where('c.for_month', $ym)
                ->selectRaw("SUM(CASE WHEN s.debit IN ('-','') THEN 0 ELSE s.debit+0 END) t")->value('t'), 2);
            $out[] = ['ym' => $ym, 'label' => date('F Y', strtotime($ym . '-01')), 'deducted' => $ded, 'deposited' => $dep, 'diff' => round($ded - $dep, 2), 'due' => TdsDue::dueDate($ym)];
        }
        return $out;
    }
}
