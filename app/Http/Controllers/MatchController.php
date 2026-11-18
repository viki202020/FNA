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
                if (!$c) { continue; }
                $rows[$dir][] = ['line' => $l, 'amount' => $amt, 'best' => $c[0], 'count' => count($c)];
            }
        }
        return view('billing.match', ['tab' => $tab, 'rows' => $rows]);
    }

    public function candidatesJson($id)
    {
        $line = DB::table('statement')->where('merchant_id', $this->merchant())->where('id', $id)->first();
        abort_unless($line, 404);
        $in = $this->lineAmount($line, true) > 0;
        return response()->json(['line_id' => $line->id, 'in' => $in, 'amount' => $this->lineAmount($line, $in), 'date' => $line->st_date, 'remark' => $line->remark, 'candidates' => $this->candidates($line, $in)]);
    }

    public function confirm(Request $request)
    {
        $line = DB::table('statement')->where('merchant_id', $this->merchant())->where('id', $request->input('statement_id'))->first();
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
                DB::table('statement')->where('id', $line->id)->update([
                    'head_id' => BillingSettings::get($in ? 'receivable_head' : 'payable_head'), 'status' => 1,
                    'updated_at' => date('d-m-Y H:i:s'), 'updated_by' => Session::get('id'),
                ]);
            });
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
        \App\Support\OpenDocuments::forget();
        return response()->json(['success' => true]);
    }

    /** "Payment sent" / "Payment received": a note against a document. Posts nothing. */
    public function note(Request $request, $id)
    {
        $doc = DB::table('billing_docs')->where('merchant_id', $this->merchant())->where('id', $id)->first();
        abort_unless($doc && in_array($doc->status, ['open', 'part']), 404);
        $request->validate(['pay_date' => 'required|date', 'amount' => 'required|numeric|gt:0']);
        DB::table('payment_notes')->insert([
            'merchant_id' => $this->merchant(), 'doc_id' => $id, 'pay_date' => $request->input('pay_date'), 'amount' => $request->input('amount'),
            'bank_name' => $request->input('bank_name'), 'reference' => $request->input('reference'), 'status' => 'sent',
            'created_by' => Session::get('id'), 'created_at' => now(), 'updated_at' => now(),
        ]);
        return back()->with('success_msg', 'Noted. Nothing is booked until the bank line arrives and you match it.');
    }
}
