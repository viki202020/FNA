<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\InventoriseLink;
use Session;

/** What Inventorise has sent: orders, drafts waiting for you, and suppliers/customers to confirm. */
class InventoriseScreenController extends Controller
{
    private function m() { return Session::get('merchant_id'); }

    public function index(Request $request)
    {
        $tab = in_array($request->input('tab'), ['held', 'sales', 'log']) ? $request->input('tab') : 'purchase';
        $d = ['tab' => $tab, 'heldCount' => DB::table('link_inbox')->where('merchant_id', $this->m())->whereIn('status', ['held', 'error'])->count()];
        if ($tab === 'held') {
            $d['held'] = DB::table('link_inbox')->where('merchant_id', $this->m())->whereIn('status', ['held', 'error'])->orderBy('id')->get()->map(function ($r) { $r->p = json_decode($r->payload, true); return $r; });
            $d['parties'] = DB::table('customer')->where('merchant', $this->m())->whereIn('type', [1, 4])->orderBy('display_name')->get(['id', 'display_name', 'first_name', 'last_name', 'type', 'gst']);
        } elseif ($tab === 'log') {
            $d['log'] = DB::table('link_inbox')->where('merchant_id', $this->m())->orderByDesc('updated_at')->limit(100)->get(['id', 'type', 'ref', 'status', 'note', 'hits', 'updated_at', 'order_id', 'doc_id']);
        } else {
            $d['orders'] = DB::table('orders')->where('merchant_id', $this->m())->where('kind', $tab)->orderByDesc('order_date')->orderByDesc('id')->limit(200)->get();
            $d['docs'] = DB::table('billing_docs')->where('merchant_id', $this->m())->whereNotNull('order_id')->where('source', 'inventorise')->get(['id', 'order_id', 'kind', 'number', 'status', 'total'])->groupBy('order_id');
        }
        return view('inventorise.index', $d);
    }

    public function confirm(Request $request)
    {
        $request->validate(['ext_key' => 'required|max:80', 'customer_id' => 'required|integer']);
        try { $n = InventoriseLink::confirmParty($this->m(), $request->input('ext_key'), (int) $request->input('customer_id'), Session::get('id')); }
        catch (\Throwable $e) { return back()->withErrors([$e->getMessage()]); }
        return back()->with('success_msg', 'Confirmed. ' . $n . ' message(s) filed.');
    }

    /** Creates the party from what Inventorise sent, then files the held messages. Only on your click. */
    public function createParty(Request $request)
    {
        $request->validate(['inbox_id' => 'required|integer']);
        $r = DB::table('link_inbox')->where('id', $request->input('inbox_id'))->where('merchant_id', $this->m())->first();
        abort_unless($r, 404);
        $p = json_decode($r->payload, true)['party'] ?? [];
        $type = str_starts_with($r->type, 'purchase') ? 4 : 1;
        $name = trim((string) ($p['name'] ?? ''));
        if ($name === '') { return back()->withErrors(['The message has no party name.']); }
        $gst = \App\Support\TaxId::clean($p['gstin'] ?? '');
        $id = DB::table('customer')->insertGetId(['type' => $type, 'first_name' => $name, 'display_name' => $name, 'gst' => $gst ?: null, 'gst_type' => $gst ? 'Registered' : 'Unregistered', 'email' => $p['email'] ?? null, 'phone' => $p['phone'] ?? null,
            'billing_state' => $p['state'] ?? null, 'pan' => strtoupper(substr((string) ($p['pan'] ?? ''), 0, 10)) ?: ($gst ? substr($gst, 2, 10) : null), 'merchant' => $this->m(), 'status' => 1, 'created_at' => date('d-m-Y H:i:s'), 'created_by' => Session::get('id') ?: 0]);
        $key = $r->party_key ?: ('name:' . mb_strtolower($name));
        $n = InventoriseLink::confirmParty($this->m(), $key, $id, Session::get('id'));
        return back()->with('success_msg', $name . ' created. ' . $n . ' message(s) filed.');
    }
}
