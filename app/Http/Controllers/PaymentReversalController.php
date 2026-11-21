<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\FinancialYears;
use App\Support\ReversalReasons;
use App\Support\VoucherNumber;
use Session;

/**
 * Reverse a payment or receipt on an invoice or bill. Nothing is deleted.
 *   Cheque still outstanding: a reversing voucher (RJV) with the legs swapped; the cheque shows Reversed or Dishonoured.
 *   Payment booked from a bank line: the bank line goes back to Uncategorised (the bank's own entry is untouched).
 * Only the person who recorded the payment may reverse it, and a reason is always required.
 */
class PaymentReversalController extends Controller
{
    private function merchant() { return Session::get('merchant_id'); }

    public function reverse(Request $request, $id)
    {
        $doc = DB::table('billing_docs')->where('merchant_id', $this->merchant())->where('id', $id)->first();
        abort_unless($doc, 404);
        $isReceipt = $doc->kind === 'sales';
        $base = url('merchant/' . ($isReceipt ? 'invoices' : 'bills') . '/' . $id);
        $type = $request->input('type');                 // note | line
        $ref = (int) $request->input('ref');
        $code = $request->input('reason_code');
        $text = $request->input('reason_text');
        $me = Session::get('id');
        $note = null; $sets = null;

        if ($type === 'note') {
            $note = DB::table('payment_notes')->where('merchant_id', $this->merchant())->where('doc_id', $id)->where('id', $ref)->first();
            if (!$note || !in_array($note->mode, ['Cheque', 'Cash'], true)) { return redirect($base)->with('error_msg', 'That payment cannot be reversed here.'); }
            $live = $note->mode === 'Cheque' ? 'outstanding' : 'posted';
            if ($note->status !== $live) { return redirect($base)->with('error_msg', $note->status === 'cleared'
                ? 'This cheque has already cleared the bank. If the bank returns it, the returned line is recognised when you upload the bank file.' : 'This payment has already been reversed.'); }
            if ((int) $note->created_by !== (int) $me) { return redirect($base)->with('error_msg', 'Only the person who recorded this payment can reverse it.'); }
            $mode = $note->mode;
        } elseif ($type === 'line') {
            $sets = DB::table('billing_settlements')->where('doc_id', $id)->where('statement_id', $ref)->whereNull('reversed_at')->get();
            if ($sets->isEmpty()) { return redirect($base)->with('error_msg', 'That payment was already reversed or does not exist.'); }
            if ($sets->contains(fn($s) => (int) $s->chosen_by !== (int) $me)) { return redirect($base)->with('error_msg', 'Only the person who matched this payment can reverse it.'); }
            $mode = 'Bank';
        } else {
            return redirect($base)->with('error_msg', 'Choose a payment to reverse.');
        }

        $last = DB::table('payment_reversals')->where('merchant_id', $this->merchant())->where('reversed_by', $me)->orderByDesc('id')->value('reason_text');
        if ($err = ReversalReasons::error($code, $text, $isReceipt, $mode, $last)) {
            return redirect($base)->with('error_msg', $err)->withInput();
        }
        $label = ReversalReasons::list($isReceipt, $mode)[$code];
        $reasonText = $code === 'OTHERS' ? trim($text) : $label;

        try {
            DB::transaction(function () use ($type, $ref, $doc, $note, $sets, $code, $reasonText, $me, $request) {
                if ($type === 'note') { $this->reverseCheque($doc, $note, $code, $reasonText, $me, $request->input('rev_date')); }
                else { $this->returnLine($doc, $ref, $sets, $code, $reasonText, $me); }
            });
        } catch (\Throwable $e) {
            return redirect($base)->with('error_msg', 'Not reversed: ' . $e->getMessage());
        }
        \App\Support\OpenDocuments::forget();
        return redirect($base)->with('success_msg', 'Reversed. The reason is saved with your name and the time.');
    }

    /** Today, or the last day of the open year when the year has ended but is not yet closed. */
    public static function defaultDate()
    {
        $e = FinancialYears::entity();
        $today = date('Y-m-d');
        if (FinancialYears::acceptsDate($today)) { return $today; }
        if ($e && $e->financial_year_end && $today > $e->financial_year_end) { return $e->financial_year_end; }
        if ($e && $e->financial_year_start && $today < $e->financial_year_start) { return $e->financial_year_start; }
        return $today;
    }

    public function reduceDoc($docId, $amount)
    {
        $d = DB::table('billing_docs')->where('id', $docId)->lockForUpdate()->first();
        $settled = max(0, round($d->settled - $amount, 2));
        DB::table('billing_docs')->where('id', $docId)->update(['settled' => $settled, 'status' => $settled > 0.004 ? 'part' : 'open', 'updated_at' => now()]);
    }

    public function reverseCheque($doc, $note, $code, $reasonText, $me, $revDate)
    {
        $date = $revDate ?: self::defaultDate();
        if (!FinancialYears::dateInCurrentYear($date)) { throw new \RuntimeException('The reversal date is outside the open financial year.'); }
        $orig = DB::table('vouchers')->where('id', $note->voucher_id)->first();
        if (!$orig || !FinancialYears::isOpen($orig->fyear)) { throw new \RuntimeException('The year of this cheque is closed.'); }
        $fyear = FinancialYears::yearForDate($date);
        $number = VoucherNumber::sequence('RJV', 4)->next();
        $vid = DB::table('vouchers')->insertGetId([
            'merchant_id' => $this->merchant(), 'voucher_no' => $number, 'series' => 'RJV', 'fyear' => $fyear, 'voucher_date' => $date, 'status' => 'posted',
            'version' => 1, 'narration' => 'Reversal of ' . $orig->voucher_no . ': ' . $reasonText, 'reference' => $note->cheque_no, 'reverses_id' => $orig->id,
            'created_by' => $me, 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach (DB::table('statement')->where('voucher_id', $orig->id)->where('status', 1)->get() as $l) {
            DB::table('statement')->insert([
                'voucher_no' => $number, 'voucher_id' => $vid, 'debit' => $l->credit, 'credit' => $l->debit,
                'remark' => 'Reversal of ' . $orig->voucher_no . ($note->cheque_no ? ' (cheque ' . $note->cheque_no . ')' : ''), 'head_id' => $l->head_id, 'st_date' => $date, 'fyear' => $fyear,
                'status' => 1, 'opening' => 0, 'merchant_id' => $this->merchant(), 'created_at' => date('Y-m-d H:i:s'), 'created_by' => $me ?: 0,
            ]);
        }
        DB::table('vouchers')->where('id', $orig->id)->update(['status' => 'reversed', 'reversed_by_id' => $vid, 'updated_at' => now()]);
        DB::table('voucher_versions')->insert(['voucher_id' => $orig->id, 'version' => $orig->version, 'action' => 'reversed', 'reason' => $reasonText, 'changed_by' => $me, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('voucher_versions')->insert(['voucher_id' => $vid, 'version' => 1, 'action' => 'created', 'changed_by' => $me, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('billing_settlements')->where('doc_id', $doc->id)->whereIn('statement_id', DB::table('statement')->where('voucher_id', $orig->id)->pluck('id'))
            ->update(['reversed_at' => now(), 'reversed_by' => $me]);
        $this->reduceDoc($doc->id, $note->amount + $note->tds_amount);
        DB::table('payment_notes')->where('id', $note->id)->update(['status' => $code === 'CHEQUE_DISHONOURED' ? 'dishonoured' : 'reversed', 'reason' => $reasonText, 'updated_at' => now()]);
        DB::table('payment_reversals')->insert(['merchant_id' => $this->merchant(), 'doc_id' => $doc->id, 'note_id' => $note->id, 'amount' => $note->amount + $note->tds_amount,
            'reason_code' => $code, 'reason_text' => $reasonText, 'reversal_voucher_id' => $vid, 'reversed_by' => $me, 'created_at' => now(), 'updated_at' => now()]);
    }

    /** Posts a reversing voucher (RJV) for a posted voucher, legs swapped, and marks the original Reversed. Returns the new voucher id. */
    public function reverseVoucherLegs($origId, $reasonText, $me, $date = null)
    {
        $date = $date ?: self::defaultDate();
        $orig = DB::table('vouchers')->where('id', $origId)->first();
        if (!$orig || !FinancialYears::isOpen($orig->fyear) || !FinancialYears::acceptsDate($date)) { throw new \RuntimeException('The year of this entry is closed.'); }
        $fyear = FinancialYears::yearForDate($date);
        $number = VoucherNumber::sequence('RJV', 4)->next();
        $vid = DB::table('vouchers')->insertGetId([
            'merchant_id' => $this->merchant(), 'voucher_no' => $number, 'series' => 'RJV', 'fyear' => $fyear, 'voucher_date' => $date, 'status' => 'posted', 'version' => 1,
            'narration' => 'Reversal of ' . $orig->voucher_no . ': ' . $reasonText, 'reverses_id' => $orig->id, 'created_by' => $me, 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach (DB::table('statement')->where('voucher_id', $orig->id)->where('status', 1)->get() as $l) {
            DB::table('statement')->insert([
                'voucher_no' => $number, 'voucher_id' => $vid, 'debit' => $l->credit, 'credit' => $l->debit, 'remark' => 'Reversal of ' . $orig->voucher_no, 'head_id' => $l->head_id,
                'st_date' => $date, 'fyear' => $fyear, 'status' => 1, 'opening' => 0, 'merchant_id' => $this->merchant(), 'created_at' => date('Y-m-d H:i:s'), 'created_by' => $me ?: 0,
            ]);
        }
        DB::table('vouchers')->where('id', $orig->id)->update(['status' => 'reversed', 'reversed_by_id' => $vid, 'updated_at' => now()]);
        DB::table('voucher_versions')->insert(['voucher_id' => $orig->id, 'version' => $orig->version, 'action' => 'reversed', 'reason' => $reasonText, 'changed_by' => $me, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('voucher_versions')->insert(['voucher_id' => $vid, 'version' => 1, 'action' => 'created', 'changed_by' => $me, 'created_at' => now(), 'updated_at' => now()]);
        return $vid;
    }

    private function returnLine($doc, $statementId, $sets, $code, $reasonText, $me)
    {
        $line = DB::table('statement')->where('id', $statementId)->where('merchant_id', $this->merchant())->first();
        if (!$line || !FinancialYears::isOpen($line->fyear)) { throw new \RuntimeException('The year of this bank line is closed.'); }
        \App\Support\LineCategoriser::clear($statementId);
        foreach ($sets as $s) {
            if (!empty($s->tds_voucher_id)) { $this->reverseVoucherLegs($s->tds_voucher_id, $reasonText, $me); }   // the TDS entry goes back too
            $this->reduceDoc($s->doc_id, $s->amount);
            DB::table('billing_settlements')->where('id', $s->id)->update(['reversed_at' => now(), 'reversed_by' => $me]);
            DB::table('payment_reversals')->insert(['merchant_id' => $this->merchant(), 'doc_id' => $s->doc_id, 'statement_id' => $statementId, 'amount' => $s->amount,
                'reason_code' => $code, 'reason_text' => $reasonText, 'reversed_by' => $me, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    /**
     * Void an invoice or bill. The document stays visible. A posted one gets a reversing voucher so the reports net to zero.
     * It cannot be voided while it still has payments: reverse those first. Only the person who created it may void it.
     */
    public function void(Request $request, $id)
    {
        $doc = DB::table('billing_docs')->where('merchant_id', $this->merchant())->where('id', $id)->first();
        abort_unless($doc, 404);
        $base = url('merchant/' . ($doc->kind === 'sales' ? 'invoices' : 'bills') . '/' . $id);
        $me = Session::get('id');
        if ($doc->status === 'void') { return redirect($base)->with('error_msg', 'It is already void.'); }
        if ((int) $doc->created_by !== (int) $me) { return redirect($base)->with('error_msg', 'Only the person who created this ' . ($doc->kind === 'sales' ? 'invoice' : 'bill') . ' can void it.'); }
        if ($doc->settled > 0.004) { return redirect($base)->with('error_msg', 'It has payments against it. Reverse the payments first, then void it.'); }
        $code = $request->input('reason_code'); $text = $request->input('reason_text');
        $last = DB::table('billing_docs')->where('merchant_id', $this->merchant())->where('voided_by', $me)->orderByDesc('voided_at')->value('void_reason');
        if ($err = ReversalReasons::error($code, $text, $doc->kind === 'sales', 'VOID', $last)) { return redirect($base)->with('error_msg', $err); }
        $reason = $code === 'OTHERS' ? trim($text) : ReversalReasons::voidList()[$code];
        try {
            DB::transaction(function () use ($doc, $code, $reason, $me, $request) {
                $vid = null;
                if ($doc->voucher_id) {
                    $date = $request->input('void_date') ?: self::defaultDate();
                    if (!FinancialYears::dateInCurrentYear($date)) { throw new \RuntimeException('The void date is outside the open financial year.'); }
                    $orig = DB::table('vouchers')->where('id', $doc->voucher_id)->first();
                    if (!$orig || !FinancialYears::isOpen($orig->fyear)) { throw new \RuntimeException('The year of this document is closed.'); }
                    $fyear = FinancialYears::yearForDate($date);
                    $number = VoucherNumber::sequence('RJV', 4)->next();
                    $vid = DB::table('vouchers')->insertGetId([
                        'merchant_id' => $this->merchant(), 'voucher_no' => $number, 'series' => 'RJV', 'fyear' => $fyear, 'voucher_date' => $date, 'status' => 'posted', 'version' => 1,
                        'narration' => 'Void of ' . $doc->number . ': ' . $reason, 'reference' => $doc->number, 'reverses_id' => $orig->id, 'created_by' => $me, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    foreach (DB::table('statement')->where('voucher_id', $orig->id)->where('status', 1)->get() as $l) {
                        DB::table('statement')->insert([
                            'voucher_no' => $number, 'voucher_id' => $vid, 'debit' => $l->credit, 'credit' => $l->debit, 'remark' => 'Void of ' . $doc->number, 'head_id' => $l->head_id,
                            'st_date' => $date, 'fyear' => $fyear, 'status' => 1, 'opening' => 0, 'merchant_id' => $this->merchant(), 'created_at' => date('Y-m-d H:i:s'), 'created_by' => $me ?: 0,
                        ]);
                    }
                    DB::table('vouchers')->where('id', $orig->id)->update(['status' => 'void', 'reversed_by_id' => $vid, 'updated_at' => now()]);
                    DB::table('voucher_versions')->insert(['voucher_id' => $orig->id, 'version' => $orig->version, 'action' => 'voided', 'reason' => $reason, 'changed_by' => $me, 'created_at' => now(), 'updated_at' => now()]);
                    DB::table('voucher_versions')->insert(['voucher_id' => $vid, 'version' => 1, 'action' => 'created', 'changed_by' => $me, 'created_at' => now(), 'updated_at' => now()]);
                }
                DB::table('billing_docs')->where('id', $doc->id)->update([
                    'status' => 'void', 'void_code' => $code, 'void_reason' => $reason, 'voided_by' => $me, 'voided_at' => now(), 'void_voucher_id' => $vid, 'updated_at' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            return redirect($base)->with('error_msg', 'Not voided: ' . $e->getMessage());
        }
        \App\Support\OpenDocuments::forget();
        return redirect($base)->with('success_msg', 'Voided. The reason is saved with your name and the time.');
    }
}
