<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\Entity;
use App\Support\BillingPosting;
use App\Support\FinancialYears;
use Session;

/** Sales invoices (kind = sales) and purchase bills (kind = purchase): the same screens, mirrored. */
class BillingController extends Controller
{
    private function kind(Request $r)
    {
        return in_array($r->segment(2), ['bills','manage-bill']) || $r->input('kind') === 'purchase' ? 'purchase' : 'sales';
    }

    private function merchant() { return Session::get('merchant_id'); }

    public function index(Request $request)
    {
        $kind = $this->kind($request);
        $filter = $request->input('show', 'all');
        $q = DB::table('billing_docs')->where('merchant_id', $this->merchant())->where('kind', $kind);
        $years = DB::table('billing_docs')->where('merchant_id', $this->merchant())->where('kind', $kind)->whereNotNull('fyear')->distinct()->pluck('fyear')->all();
        $year = $request->input('year', FinancialYears::current());
        $yearOptions = FinancialYears::options($years, $year, (bool) $request->input('closed'));
        if ($year !== 'all') { $q->where(function ($w) use ($year) { $w->where('fyear', $year)->orWhereNull('fyear'); }); }
        $today = date('Y-m-d');
        if ($filter === 'draft') { $q->where('status', 'draft'); }
        elseif ($filter === 'unpaid') { $q->whereIn('status', ['open', 'part']); }
        elseif ($filter === 'overdue') { $q->whereIn('status', ['open', 'part'])->where('due_date', '<', $today); }
        elseif ($filter === 'paid') { $q->where('status', 'paid'); }
        $docs = $q->orderByDesc('doc_date')->orderByDesc('id')->get();
        $all = DB::table('billing_docs')->where('merchant_id', $this->merchant())->where('kind', $kind)->whereIn('status', ['open', 'part'])->whereNotNull('due_date')->where('due_date', '<', $today)->get();
        return view('billing.index', [
            'kind' => $kind, 'docs' => $docs, 'filter' => $filter, 'year' => $year, 'year_options' => $yearOptions,
            'overdue_count' => $all->count(), 'overdue_total' => $all->sum(fn($d) => $d->total - $d->settled),
        ]);
    }

    public function create(Request $request)
    {
        $kind = $this->kind($request);
        $parties = Customer::where('merchant', $this->merchant())->where('status', 1)
            ->where('type', $kind === 'sales' ? 1 : 4)->orderBy('first_name')->get();
        $heads = DB::table('head')->where('status', 1)->orderBy('name')->get(['id', 'name']);
        $products = DB::table('products')->where('merchant_id', $this->merchant())->where('status', 1)->orderBy('name')->get();
        $entity = FinancialYears::entity();
        $issues = []; foreach ($parties as $pp) { $issues[$pp->id] = \App\Support\PartyChecks::issues($pp, $kind); }
        return view('billing.form', ['issues' => $issues, 'kind' => $kind, 'parties' => $parties, 'heads' => $heads, 'products' => $products, 'entity' => $entity]);
    }

    public function store(Request $request)
    {
        $kind = $this->kind($request);
        $request->validate([
            'doc_date' => 'required|date', 'party_id' => 'required|integer',
            'description' => 'required|array|min:1',
        ]);
        $party = Customer::where('merchant', $this->merchant())->find($request->input('party_id'));
        if (!$party) { return back()->withInput()->withErrors(['party_id' => 'Choose a party from the list.']); }
        $entity = FinancialYears::entity();
        $supply = BillingPosting::supplyType($entity ? $entity->state : '', $party->billing_state);
        $lines = [];
        foreach ($request->input('description') as $i => $desc) {
            $qty = (float) ($request->input('qty')[$i] ?? 0); $rate = (float) ($request->input('rate')[$i] ?? 0);
            if (trim((string) $desc) === '' && $qty * $rate == 0) { continue; }
            $lines[] = [
                'description' => $desc, 'qty' => $qty, 'rate' => $rate, 'tax_rate' => (float) ($request->input('tax_rate')[$i] ?? 0),
                'head_id' => $request->input('head_id')[$i] ?? null, 'hsn' => $request->input('hsn')[$i] ?? null,
                'product_id' => $request->input('product_id')[$i] ?? null,
            ];
        }
        if (!$lines) { return back()->withInput()->withErrors(['description' => 'Add at least one line with an amount.']); }
        if ($kind === 'purchase') {
            foreach ($lines as $l) { if (empty($l['head_id'])) { return back()->withInput()->withErrors(['description' => 'Choose an expense account for every bill line.']); } }
        }
        $t = BillingPosting::totals($lines, $supply);
        $name = $party->display_name ?: trim($party->first_name . ' ' . $party->last_name);
        $id = DB::transaction(function () use ($request, $kind, $party, $name, $supply, $t, $lines) {
            $id = DB::table('billing_docs')->insertGetId([
                'merchant_id' => $this->merchant(), 'kind' => $kind, 'doc_date' => $request->input('doc_date'),
                'due_date' => $request->input('due_date') ?: $request->input('doc_date'),
                'party_id' => $party->id, 'party_name' => $name, 'supply' => $supply, 'status' => 'draft',
                'reference' => $request->input('reference'), 'notes' => $request->input('notes'),
                'created_by' => Session::get('id'), 'created_at' => now(), 'updated_at' => now(),
            ] + $t);
            foreach ($lines as $l) {
                DB::table('billing_doc_lines')->insert([
                    'doc_id' => $id, 'product_id' => $l['product_id'] ?: null, 'head_id' => $l['head_id'] ?: null,
                    'description' => $l['description'], 'hsn' => $l['hsn'], 'qty' => $l['qty'], 'rate' => $l['rate'],
                    'tax_rate' => $l['tax_rate'], 'amount' => round($l['qty'] * $l['rate'], 2), 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            return $id;
        });
        $warn = collect(\App\Support\PartyChecks::issues($party, $kind))->where('level', 'warn')->pluck('text')->implode(' ');
        if ($warn) { session()->flash('warn_msg', $party->display_name . ': ' . $warn); }
        if ($request->input('action') === 'post') {
            try {
                $no = BillingPosting::post($id);
                return redirect('merchant/' . ($kind === 'sales' ? 'invoices' : 'bills') . '/' . $id)->with('success_msg', 'Posted as ' . $no . '.');
            } catch (\Throwable $e) {
                return redirect('merchant/' . ($kind === 'sales' ? 'invoices' : 'bills') . '/' . $id)->with('error_msg', 'Saved as a draft. Not posted: ' . $e->getMessage());
            }
        }
        return redirect('merchant/' . ($kind === 'sales' ? 'invoices' : 'bills') . '/' . $id)->with('success_msg', 'Saved as a draft.');
    }

    public function show(Request $request, $id)
    {
        $doc = DB::table('billing_docs')->where('merchant_id', $this->merchant())->where('id', $id)->first();
        abort_unless($doc, 404);
        $lines = DB::table('billing_doc_lines')->where('doc_id', $id)->get();
        $settlements = DB::table('billing_settlements as s')->join('statement as st', 'st.id', '=', 's.statement_id')
            ->where('s.doc_id', $id)->get(['s.*', 'st.st_date', 'st.remark', 'st.voucher_no']);
        $heads = DB::table('head')->pluck('name', 'id');
        $notes = DB::table('payment_notes')->where('doc_id', $id)->whereIn('mode', ['Cheque', 'Cash'])->orderBy('id')->get();
        $lineSettlements = DB::table('billing_settlements as s')->join('statement as st', 'st.id', '=', 's.statement_id')->where('s.doc_id', $id)
            ->where('st.voucher_no', 'not like', 'PYV-%')->where('st.voucher_no', 'not like', 'RCV-%')->get(['s.*', 'st.st_date', 'st.remark', 'st.voucher_no']);
        $banks = DB::table('head')->where('account_type_id', 3)->where('status', 1)->orderBy('name')->get(['id', 'name']);
        $party = DB::table('customer')->where('id', $doc->party_id)->first();
        $tdsSuggest = ($party && (int) $party->tds_status === 1 && is_numeric($party->tds_rate) && (float) $party->tds_rate > 0) ? ['rate' => (float) $party->tds_rate, 'amount' => round($doc->subtotal * (float) $party->tds_rate / 100, 2)] : null;
        $partyIssues = \App\Support\PartyChecks::issues($party, $doc->kind);
        return view('billing.show', ['partyIssues' => $partyIssues, 'tdsSuggest' => $tdsSuggest, 'notes' => $notes, 'lineSettlements' => $lineSettlements, 'me' => Session::get('id'), 'banks' => $banks, 'doc' => $doc, 'lines' => $lines, 'settlements' => $settlements, 'heads' => $heads]);
    }

    public function post(Request $request, $id)
    {
        $doc = DB::table('billing_docs')->where('merchant_id', $this->merchant())->where('id', $id)->first();
        abort_unless($doc, 404);
        $base = 'merchant/' . ($doc->kind === 'sales' ? 'invoices' : 'bills') . '/' . $id;
        try {
            $no = BillingPosting::post($id);
            return redirect($base)->with('success_msg', 'Posted as ' . $no . '.');
        } catch (\Throwable $e) {
            return redirect($base)->with('error_msg', 'Not posted: ' . $e->getMessage());
        }
    }
}
