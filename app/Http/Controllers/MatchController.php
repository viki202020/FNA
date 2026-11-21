<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\BillingSettings;
use App\Support\FinancialYears;
use Session;

/** "Receipts to match": bank lines whose amount fits an open invoice (money in) or bill (money out). A person always confirms. */
class MatchController extends Controller
{
    private function merchant() { return Session::get('merchant_id'); }

    private function lineAmount($l, $in) { return round((float) preg_replace('/[^0-9.]/', '', $in ? $l->credit : $l->debit), 2); }

    /** Open documents with a balance, with a simple score for how well they fit this bank line. */
    private function candidates($line, $in)
    {
        $kind = $in ? 'sales' : 'purchase';
        $amount = $this->lineAmount($line, $in);
        $docs = DB::table('billing_docs')->where('merchant_id', $this->merchant())->where('kind', $kind)
            ->whereIn('status', ['open', 'part'])->get();
        $notes = DB::table('payment_notes')->where('merchant_id', $this->merchant())->where('status', 'sent')->get()->groupBy('doc_id');
        $text = strtoupper((string) $line->remark);
        $out = [];
        foreach ($docs as $d) {
            $bal = round($d->total - $d->settled, 2);
            $score = 0; $why = [];
            if ($d->number && str_contains($text, strtoupper($d->number))) { $score += 5; $why[] = 'document number is in the bank wording'; }
            $words = array_filter(preg_split('/\W+/', strtoupper((string) $d->party_name)), fn($w) => strlen($w) >= 4);
            foreach ($words as $w) { if (str_contains($text, $w)) { $score += 2; $why[] = 'party name is in the bank wording'; break; } }
            if (abs($bal - $amount) < 0.005) { $score += 3; $why[] = 'same amount as the balance'; }
            if (isset($notes[$d->id])) {
                $n = $notes[$d->id]->first();
                $score += 2; $why[] = ($in ? 'payment received' : 'payment sent') . ' noted on ' . date('d/m/Y', strtotime($n->pay_date));
                if (abs($n->amount - $amount) < 0.005) { $score += 2; }
                if ($n->reference && str_contains($text, strtoupper($n->reference))) { $score += 3; $why[] = 'reference ' . $n->reference . ' is in the bank wording'; }
            }
            if ($score === 0) { continue; }
            $out[] = ['id' => $d->id, 'number' => $d->number, 'party' => $d->party_name, 'due' => $d->due_date ? date('d/m/Y', strtotime($d->due_date)) : '',
                'balance' => $bal, 'score' => $score, 'why' => implode('; ', array_unique($why)), 'exact' => abs($bal - $amount) < 0.005];
        }
        usort($out, fn($a, $b) => $b['score'] <=> $a['score']);
        return $out;
    }

    /** Outstanding cheques that this bank line may be clearing (same direction, same amount; the cheque number in the wording ranks first). */
    private function chequeCandidates($line, $in)
    {
        $amount = $this->lineAmount($line, $in);
        $text = strtoupper((string) $line->remark);
        $rows = DB::table('payment_notes as n')->join('billing_docs as d', 'd.id', '=', 'n.doc_id')
            ->where('n.merchant_id', $this->merchant())->where('n.mode', 'Cheque')->where('n.status', 'outstanding')
            ->where('d.kind', $in ? 'sales' : 'purchase')->get(['n.*', 'd.party_name', 'd.number as doc_number']);
        $out = [];
        foreach ($rows as $n) {
            $byNo = $n->cheque_no && str_contains($text, strtoupper($n->cheque_no));
            $byAmt = abs($n->amount - $amount) < 0.005;
            if (!$byAmt) { continue; }
            $out[] = ['id' => $n->id, 'cheque_no' => $n->cheque_no, 'party' => $n->party_name, 'doc' => $n->doc_number, 'date' => date('d/m/Y', strtotime($n->pay_date)),
                'amount' => (float) $n->amount, 'by_number' => $byNo, 'why' => $byNo ? 'cheque number is in the bank wording; same amount' : 'same amount as the cheque'];
        }
        usort($out, fn($a, $b) => (int) $b['by_number'] <=> (int) $a['by_number']);
        return $out;
    }

    public function index(Request $request)
    {
        $tab = $request->input('tab') === 'out' ? 'out' : 'in';
        $open = FinancialYears::openYears();
        $q = DB::table('statement')->where('merchant_id', $this->merchant())->where('status', 0)
            ->where(function ($w) use ($open) { $w->whereNull('fyear')->orWhereIn('fyear', $open); });
        $lines = $q->orderByDesc('id')->get();
        $rows = ['in' => [], 'out' => []];
        foreach ($lines as $l) {
            foreach (['in', 'out'] as $dir) {
                $amt = $this->lineAmount($l, $dir === 'in');
                if ($amt <= 0) { continue; }
                $c = $this->candidates($l, $dir === 'in');
                $k = $this->chequeCandidates($l, $dir === 'in');
                if (!$c && !$k) { continue; }
                $best = $k ? ['number' => 'Cheque ' . $k[0]['cheque_no'], 'party' => $k[0]['party']] : $c[0];
                $rows[$dir][] = ['line' => $l, 'amount' => $amt, 'best' => $best, 'count' => count($c) + count($k)];
            }
        }
        return view('billing.match', ['tab' => $tab, 'rows' => $rows]);
    }

    public function candidatesJson($id)
    {
        $line = DB::table('statement')->where('merchant_id', $this->merchant())->where('id', $id)->first();
        abort_unless($line, 404);
        $in = $this->lineAmount($line, true) > 0;
        return response()->json(['line_id' => $line->id, 'in' => $in, 'amount' => $this->lineAmount($line, $in), 'date' => $line->st_date, 'remark' => $line->remark, 'candidates' => $this->candidates($line, $in), 'cheques' => $this->chequeCandidates($line, $in)]);
    }

    public function confirm(Request $request)
    {
        $line = DB::table('statement')->where('merchant_id', $this->merchant())->where('id', $request->input('statement_id'))->first();
        if ($line && $request->input('cheque_id')) { return $this->clearCheque($line, $request->input('cheque_id')); }
        $ids = array_filter((array) $request->input('doc_ids'));
        if (!$line || !$ids) { return response()->json(['success' => false, 'message' => 'Choose at least one invoice or bill.'], 422); }
        if ((int) $line->status !== 0) { return response()->json(['success' => false, 'message' => 'This line is already categorised.'], 422); }
        if (!FinancialYears::isOpen($line->fyear)) { return response()->json(['success' => false, 'message' => FinancialYears::message($line->fyear)], 422); }
        $in = $this->lineAmount($line, true) > 0;
        $amount = $this->lineAmount($line, $in);
        try {
            DB::transaction(function () use ($line, $ids, $in, $amount) {
                $docs = DB::table('billing_docs')->whereIn('id', $ids)->where('merchant_id', $this->merchant())
                    ->where('kind', $in ? 'sales' : 'purchase')->whereIn('status', ['open', 'part'])->orderBy('due_date')->lockForUpdate()->get();
                if ($docs->count() !== count($ids)) { throw new \RuntimeException('One of the chosen documents is no longer open.'); }
                $remaining = $amount;
                foreach ($docs as $d) {
                    if ($remaining <= 0) { break; }
                    $bal = round($d->total - $d->settled, 2);
                    $alloc = min($bal, $remaining);
                    $remaining = round($remaining - $alloc, 2);
                    $settled = round($d->settled + $alloc, 2);
                    DB::table('billing_docs')->where('id', $d->id)->update(['settled' => $settled, 'status' => $settled + 0.004 >= $d->total ? 'paid' : 'part', 'updated_at' => now()]);
                    DB::table('billing_settlements')->insert(['doc_id' => $d->id, 'statement_id' => $line->id, 'amount' => $alloc, 'method' => 'manual', 'chosen_by' => Session::get('id'), 'created_at' => now(), 'updated_at' => now()]);
                    DB::table('payment_notes')->where('doc_id', $d->id)->where('status', 'sent')->update(['status' => 'matched', 'updated_at' => now()]);
                }
                if ($remaining > 0.004) { throw new \RuntimeException('The chosen documents are worth less than the money (' . number_format($remaining, 2) . ' left over). Choose more, or use Assign manually.'); }
                \App\Support\LineCategoriser::set($line->id, BillingSettings::get($in ? 'receivable_head' : 'payable_head'), 'person');
            });
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
        \App\Support\OpenDocuments::forget();
        return response()->json(['success' => true]);
    }

    /** The bank line clears a cheque already posted: the line's other side goes to the in-transit head, which nets that cheque to zero. */
    private function clearCheque($line, $chequeId)
    {
        if ((int) $line->status !== 0) { return response()->json(['success' => false, 'message' => 'This line is already categorised.'], 422); }
        if (!FinancialYears::isOpen($line->fyear)) { return response()->json(['success' => false, 'message' => FinancialYears::message($line->fyear)], 422); }
        $in = $this->lineAmount($line, true) > 0;
        $amount = $this->lineAmount($line, $in);
        try {
            DB::transaction(function () use ($line, $chequeId, $in, $amount) {
                $n = DB::table('payment_notes')->where('id', $chequeId)->where('merchant_id', $this->merchant())->where('mode', 'Cheque')->where('status', 'outstanding')->lockForUpdate()->first();
                if (!$n) { throw new \RuntimeException('That cheque is no longer outstanding.'); }
                if (abs($n->amount - $amount) > 0.004) { throw new \RuntimeException('The cheque amount differs from the bank line.'); }
                \App\Support\LineCategoriser::set($line->id, BillingSettings::transitHead($in ? 'chq_in' : 'chq_out'), 'person');
                DB::table('payment_notes')->where('id', $n->id)->update(['status' => 'cleared', 'cleared_on' => date('Y-m-d', strtotime($line->st_date)), 'statement_id' => $line->id, 'updated_at' => now()]);
            });
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
        \App\Support\OpenDocuments::forget();
        return response()->json(['success' => true]);
    }

    /**
     * "Payment sent" / "Payment received".
     *   Cheque: posts the payment on the date handed over (Dr payable / Cr cheques issued not presented;
     *           for a receipt Dr cheques received not cleared / Cr receivable) and adds a cheque register row.
     *   Other modes: only a note. The bank line books the payment when it arrives.
     */
    public function note(Request $request, $id)
    {
        $doc = DB::table('billing_docs')->where('merchant_id', $this->merchant())->where('id', $id)->first();
        abort_unless($doc && in_array($doc->status, ['open', 'part']), 404);
        $request->validate(['pay_date' => 'required|date', 'amount' => 'required|numeric|gt:0',
            'mode' => 'required|in:Cheque,NEFT,RTGS,IMPS,UPI,Cash,Other']);
        $mode = $request->input('mode');
        $amount = round((float) $request->input('amount'), 2);
        $balance = round($doc->total - $doc->settled, 2);
        if ($amount > $balance + 0.004) { return back()->withErrors(['amount' => 'The amount is more than the balance (' . number_format($balance, 2) . ').']); }
        $base = [
            'merchant_id' => $this->merchant(), 'doc_id' => $id, 'pay_date' => $request->input('pay_date'), 'amount' => $amount,
            'bank_name' => $request->input('bank_name'), 'reference' => $request->input('reference'), 'mode' => $mode,
            'created_by' => Session::get('id'), 'created_at' => now(), 'updated_at' => now(),
        ];
        if ($mode !== 'Cheque') {
            DB::table('payment_notes')->insert($base + ['status' => 'sent']);
            return back()->with('success_msg', 'Noted. Nothing is booked until the bank line arrives and you match it.');
        }
        $request->validate(['cheque_no' => 'required|string|max:30', 'bank_head_id' => 'required|integer']);
        $chq = trim($request->input('cheque_no'));
        if (DB::table('payment_notes')->where('merchant_id', $this->merchant())->where('mode', 'Cheque')->where('cheque_no', $chq)
            ->where('bank_head_id', $request->input('bank_head_id'))->whereIn('status', ['outstanding', 'cleared'])->exists()) {
            return back()->withErrors(['cheque_no' => 'Cheque ' . $chq . ' is already in the register for this bank.']);
        }
        try {
            DB::transaction(function () use ($doc, $base, $chq, $amount, $request, $id) {
                $fyear = \App\Support\FinancialYears::yearForDate($base['pay_date']);
                if (!\App\Support\FinancialYears::dateInCurrentYear($base['pay_date'])) { throw new \RuntimeException('The cheque date is outside the open financial year.'); }
                $in = $doc->kind === 'sales';
                $series = $in ? 'RCV' : 'PYV';
                $number = \App\Support\BillingPosting::nextVoucherNumber($series, $fyear);
                $voucherId = DB::table('vouchers')->insertGetId([
                    'merchant_id' => $this->merchant(), 'voucher_no' => $number, 'series' => $series, 'fyear' => $fyear, 'voucher_date' => $base['pay_date'],
                    'status' => 'posted', 'version' => 1, 'narration' => ($in ? 'Cheque received ' : 'Cheque issued ') . $chq . ' ' . $doc->party_name,
                    'reference' => $chq, 'created_by' => Session::get('id'), 'created_at' => now(), 'updated_at' => now(),
                ]);
                $transit = BillingSettings::transitHead($in ? 'chq_in' : 'chq_out');
                $ledger = BillingSettings::get($in ? 'receivable_head' : 'payable_head');
                $legs = $in ? [[$transit, $amount, 0], [$ledger, 0, $amount]] : [[$ledger, $amount, 0], [$transit, 0, $amount]];
                $apId = null;
                foreach ($legs as $g) {
                    $rid = DB::table('statement')->insertGetId([
                        'voucher_no' => $number, 'voucher_id' => $voucherId, 'debit' => $g[1] > 0 ? number_format($g[1], 2, '.', '') : '-',
                        'credit' => $g[2] > 0 ? number_format($g[2], 2, '.', '') : '-', 'remark' => 'Cheque ' . $chq . ' ' . $doc->party_name,
                        'head_id' => $g[0], 'st_date' => $base['pay_date'], 'fyear' => $fyear, 'status' => 1, 'opening' => 0,
                        'merchant_id' => $this->merchant(), 'created_at' => date('Y-m-d H:i:s'), 'created_by' => Session::get('id') ?: 0,
                    ]);
                    if ($g[0] == $ledger) { $apId = $rid; }
                }
                DB::table('voucher_versions')->insert(['voucher_id' => $voucherId, 'version' => 1, 'action' => 'created', 'changed_by' => Session::get('id'), 'created_at' => now(), 'updated_at' => now()]);
                $settled = round($doc->settled + $amount, 2);
                DB::table('billing_docs')->where('id', $id)->update(['settled' => $settled, 'status' => $settled + 0.004 >= $doc->total ? 'paid' : 'part', 'updated_at' => now()]);
                DB::table('billing_settlements')->insert(['doc_id' => $id, 'statement_id' => $apId, 'amount' => $amount, 'method' => 'manual', 'chosen_by' => Session::get('id'), 'created_at' => now(), 'updated_at' => now()]);
                DB::table('payment_notes')->insert($base + ['status' => 'outstanding', 'cheque_no' => $chq, 'bank_head_id' => $request->input('bank_head_id'), 'voucher_id' => $voucherId]);
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['cheque_no' => $e->getMessage()]);
        }
        \App\Support\OpenDocuments::forget();
        return back()->with('success_msg', 'Cheque ' . $chq . ' recorded and posted. It stays Outstanding in the cheque register until the bank line is matched.');
    }

    /** The cheque register: a view of the payments made by cheque. */
    public function register(Request $request)
    {
        $tab = $request->input('tab') === 'in' ? 'in' : 'out';
        $rows = DB::table('payment_notes as n')->join('billing_docs as d', 'd.id', '=', 'n.doc_id')
            ->where('n.merchant_id', $this->merchant())->where('n.mode', 'Cheque')->where('d.kind', $tab === 'in' ? 'sales' : 'purchase')
            ->orderByDesc('n.pay_date')->orderByDesc('n.id')
            ->get(['n.*', 'd.number as doc_number', 'd.party_name', 'd.id as doc_id2']);
        $banks = DB::table('head')->where('account_type_id', 3)->pluck('name', 'id');
        return view('billing.register', ['tab' => $tab, 'rows' => $rows, 'banks' => $banks]);
    }

    /** Bank reconciliation statement: statement balance, less cheques issued not presented, plus cheques received not cleared. */
    public function reconciliation(Request $request)
    {
        $asat = $request->input('asat', date('Y-m-d'));
        $banks = DB::table('head')->where('account_type_id', 3)->where('status', 1)->orderBy('name')->get(['id', 'name']);
        $out = [];
        foreach ($banks as $b) {
            // The bank's own balance counts every bank entry, categorised or not.
            $bal = DB::table('statement')->where('merchant_id', $this->merchant())->whereIn('status', [0, 1])->where('head_id', $b->id)
                ->whereDate('st_date', '<=', $asat)->selectRaw("ROUND(SUM(IF(debit IN ('-',''),0,debit+0)) - SUM(IF(credit IN ('-',''),0,credit+0)),2) AS net")->value('net');
            // Bank lines not yet categorised are not in the accounts.
            $uncat = DB::table('statement')->where('merchant_id', $this->merchant())->where('status', 0)->where('head_id', $b->id)
                ->whereDate('st_date', '<=', $asat)->selectRaw("ROUND(SUM(IF(debit IN ('-',''),0,debit+0)) - SUM(IF(credit IN ('-',''),0,credit+0)),2) AS net")->value('net');
            $chq = DB::table('payment_notes as n')->join('billing_docs as d', 'd.id', '=', 'n.doc_id')
                ->where('n.merchant_id', $this->merchant())->where('n.mode', 'Cheque')->where('n.bank_head_id', $b->id)
                ->whereDate('n.pay_date', '<=', $asat)
                ->where(function ($w) use ($asat) { $w->where('n.status', 'outstanding')->orWhere(function ($x) use ($asat) { $x->where('n.status', 'cleared')->whereDate('n.cleared_on', '>', $asat); }); })
                ->get(['n.*', 'd.kind', 'd.party_name']);
            $issued = $chq->where('kind', 'purchase'); $received = $chq->where('kind', 'sales');
            $out[] = ['bank' => $b->name, 'statement' => (float) $bal, 'issued' => $issued, 'received' => $received,
                'uncat' => (float) $uncat, 'issuedTotal' => (float) $issued->sum('amount'), 'receivedTotal' => (float) $received->sum('amount')];
        }
        return view('billing.reconciliation', ['asat' => $asat, 'banks' => $out]);
    }
}
