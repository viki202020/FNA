<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Session;

/**
 * Recognises a payment or receipt that the bank has taken back (a returned or dishonoured payment) and reverses it.
 * A bank line counts as a return only when ALL of these agree:
 *   1. its wording says so (RETURN, REVERSAL, REFUND, BOUNCE, DISHONOUR, UNPAID ...) and is not a bank charge;
 *   2. it is for the same amount as an earlier payment (or receipt) that was matched to an invoice or bill, in the opposite direction,
 *      within 30 days before it;
 *   3. the party's name, or a reference of 8 or more characters, appears in both the return wording and the original wording;
 *   4. exactly ONE earlier payment fits. If two or more fit, nothing is done and the line is left for a person.
 * Then the return line is categorised to the same account, the document is unpaid again, and the reversal is logged.
 * The original bank line stays as it was: both lines are real bank entries.
 */
class ReturnDetector
{
    const WORDS = '/\b(RETURN(ED)?|RTN|REVERSAL|REVERSED|REFUND(ED)?|BOUNCED?|DISHONOU?R(ED)?|UNPAID|CHQ\s*RET(URN)?)\b/i';
    const NOT = '/(CHARGE|CHRG|FEE|COMMISSION|PENALTY)/i';
    const WINDOW_DAYS = 30;

    private static function merchant() { return Session::get('merchant_id'); }

    private static function tokens($text)
    {
        preg_match_all('/[A-Z0-9]{8,}/', strtoupper((string) $text), $m);
        return array_unique($m[0]);
    }

    private static function partyAgrees($partyName, $returnRemark, $originalRemark)
    {
        foreach (array_filter(preg_split('/\W+/', strtoupper((string) $partyName)), fn($w) => strlen($w) >= 4) as $w) {
            if (str_contains(strtoupper((string) $returnRemark), $w)) { return true; }
        }
        return count(array_intersect(self::tokens($returnRemark), self::tokens($originalRemark))) > 0;
    }

    /** Looks at every uncategorised bank line and reverses the clear-cut returns. Returns how many it dealt with. */
    public static function scan()
    {
        $merchant = self::merchant();
        $lines = DB::table('statement')->where('merchant_id', $merchant)->where('status', 0)->where('opening', 0)->whereNotNull('voucher_no')->whereNotNull('bank_id')
            ->where(function ($q) { $q->whereNull('head_id')->orWhere('head_id', ''); })->whereIn('fyear', FinancialYears::openYears())->orderBy('st_date')->orderBy('id')->get();
        $done = 0;
        foreach ($lines as $l) {
            if (!preg_match(self::WORDS, (string) $l->remark) || preg_match(self::NOT, (string) $l->remark)) { continue; }
            $in = (float) preg_replace('/[^0-9.]/', '', (string) $l->credit) > 0;     // money coming back in: a payment was returned
            $amount = round((float) preg_replace('/[^0-9.]/', '', (string) ($in ? $l->credit : $l->debit)), 2);
            if ($amount <= 0) { continue; }
            try { if (DB::transaction(fn() => self::handle($l, $in, $amount))) { $done++; } } catch (\Throwable $e) { /* leave the line for a person */ }
        }
        if ($done) { OpenDocuments::forget(); }
        return $done;
    }

    private static function handle($l, $in, $amount)
    {
        $merchant = self::merchant();
        $from = date('Y-m-d', strtotime($l->st_date . ' -' . self::WINDOW_DAYS . ' days'));
        $to = date('Y-m-d', strtotime($l->st_date));
        $kind = $in ? 'purchase' : 'sales';     // money back in reverses a payment to a supplier; money going out reverses a receipt

        // (a) payments matched from a bank line
        $found = [];
        $rows = DB::table('billing_settlements as s')->join('statement as o', 'o.id', '=', 's.statement_id')->join('billing_docs as d', 'd.id', '=', 's.doc_id')
            ->where('d.merchant_id', $merchant)->where('d.kind', $kind)->whereNull('s.reversed_at')->whereBetween('o.st_date', [$from, $to])
            ->where('o.voucher_no', 'not like', 'PYV-%')->where('o.voucher_no', 'not like', 'RCV-%')->where('o.id', '<>', $l->id)
            ->get(['s.id as sid', 's.statement_id', 's.amount', 's.method', 'o.remark as o_remark', 'd.party_name', 'd.id as doc_id']);
        foreach ($rows->groupBy('statement_id') as $stId => $g) {
            $bankPart = $g->where('method', 'manual')->sum('amount');
            if (abs($bankPart - $amount) > 0.004) { continue; }
            if (!self::partyAgrees($g->first()->party_name, $l->remark, $g->first()->o_remark)) { continue; }
            $found[] = ['type' => 'line', 'rows' => $g, 'statement_id' => $stId];
        }
        // (b) cheques that had cleared
        $chq = DB::table('payment_notes as n')->join('billing_docs as d', 'd.id', '=', 'n.doc_id')->join('statement as o', 'o.id', '=', 'n.statement_id')
            ->where('n.merchant_id', $merchant)->where('n.mode', 'Cheque')->where('n.status', 'cleared')->where('d.kind', $kind)->whereBetween('o.st_date', [$from, $to])
            ->get(['n.*', 'd.party_name', 'o.remark as o_remark']);
        foreach ($chq as $n) {
            if (abs($n->amount - $amount) > 0.004 || !self::partyAgrees($n->party_name, $l->remark, $n->o_remark)) { continue; }
            $found[] = ['type' => 'cheque', 'note' => $n];
        }
        if (count($found) !== 1) { return false; }       // nothing, or ambiguous: a person decides
        $f = $found[0];
        $reason = 'Returned by the bank: ' . substr((string) $l->remark, 0, 80);
        $pr = new \App\Http\Controllers\PaymentReversalController();

        if ($f['type'] === 'line') {
            $head = BillingSettings::get($in ? 'payable_head' : 'receivable_head');
            LineCategoriser::set($l->id, $head, 'return');
            foreach ($f['rows'] as $s) {
                $full = DB::table('billing_settlements')->where('id', $s->sid)->first();
                if (!empty($full->tds_voucher_id)) { $pr->reverseVoucherLegs($full->tds_voucher_id, $reason, 0, $to); }
                $pr->reduceDoc($s->doc_id, $s->amount);
                DB::table('billing_settlements')->where('id', $s->sid)->update(['reversed_at' => now(), 'reversed_by' => 0]);
                DB::table('payment_reversals')->insert(['merchant_id' => $merchant, 'doc_id' => $s->doc_id, 'statement_id' => $l->id, 'amount' => $s->amount,
                    'reason_code' => 'RETURNED_PAYMENT', 'reason_text' => $reason, 'reversed_by' => 0, 'created_at' => now(), 'updated_at' => now()]);
            }
            return true;
        }
        $n = $f['note'];
        $doc = DB::table('billing_docs')->where('id', $n->doc_id)->first();
        $pr->reverseCheque($doc, $n, 'CHEQUE_DISHONOURED', $reason, 0, $to);
        LineCategoriser::set($l->id, BillingSettings::transitHead($in ? 'chq_out' : 'chq_in'), 'return');
        return true;
    }
}
