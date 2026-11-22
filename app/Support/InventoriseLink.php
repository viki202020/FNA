<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Receives what Inventorise sends and files it in FNA.
 *   purchase_order / sales_order  -> an Order (posts nothing)
 *   purchase_bill / sales_invoice -> a DRAFT bill or invoice, linked to its order; never posted automatically
 * A supplier or customer FNA does not know is not created on its own: the message is held until you confirm the party.
 * Nothing goes back to Inventorise. Payments there come from Xero.
 */
class InventoriseLink
{
    public const TYPES = ['purchase_order', 'sales_order', 'purchase_bill', 'sales_invoice'];

    private static function kind($type) { return str_starts_with($type, 'purchase') ? 'purchase' : 'sales'; }

    /** Inventorise writes a rate as a fraction (0.1800); FNA keeps percent (18). */
    private static function pct($r) { $r = (float) $r; return round($r <= 1 ? $r * 100 : $r, 2); }

    /** Stores the message (and its history) and tries to file it. Returns the inbox row. */
    public static function receive($merchant, array $p)
    {
        $type = $p['type']; $ref = trim((string) $p['ref']);
        $json = json_encode($p, JSON_UNESCAPED_UNICODE);
        $key = trim((string) ($p['party']['key'] ?? ''));
        $row = DB::table('link_inbox')->where('merchant_id', $merchant)->where('type', $type)->where('ref', $ref)->first();
        if ($row) {
            DB::table('link_inbox')->where('id', $row->id)->update(['payload' => $json, 'party_key' => $key ?: null, 'hits' => $row->hits + 1, 'updated_at' => now()]);
            $id = $row->id;
        } else {
            $id = DB::table('link_inbox')->insertGetId(['merchant_id' => $merchant, 'type' => $type, 'ref' => $ref, 'payload' => $json, 'party_key' => $key ?: null, 'status' => 'held', 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('link_history')->insert(['inbox_id' => $id, 'payload' => $json, 'received_at' => now()]);
        return self::apply($id);
    }

    /**
     * Finds the FNA party for a message:
     *   1. a match you confirmed before (Inventorise's own id for the party);
     *   2. the same GSTIN (a different spelling of the name is fine);
     *   3. no GSTIN in the message: the same name, ignoring capitals and extra spaces, if exactly one party has it.
     * Null when nothing matches. With a GSTIN the caller then creates the party; without one the message is held for approval.
     */
    public static function findParty($merchant, $kind, array $party)
    {
        $type = $kind === 'purchase' ? 4 : 1;
        $key = trim((string) ($party['key'] ?? ''));
        if ($key !== '') {
            $cid = DB::table('party_links')->where('merchant_id', $merchant)->where('ext_key', $key)->value('customer_id');
            if ($cid) { $c = DB::table('customer')->where('id', $cid)->where('merchant', $merchant)->first(); if ($c) { return $c; } }
        }
        $gst = TaxId::clean($party['gstin'] ?? '');
        if ($gst !== '') {
            return DB::table('customer')->where('merchant', $merchant)->where('type', $type)->whereRaw('UPPER(REPLACE(gst, " ", "")) = ?', [$gst])->orderBy('id')->first();
        }
        $name = mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) ($party['name'] ?? ''))));
        if ($name !== '') {
            $same = DB::table('customer')->where('merchant', $merchant)->where('type', $type)->whereRaw("LOWER(TRIM(REPLACE(display_name, '  ', ' '))) = ?", [$name])->orderBy('id')->limit(2)->get();
            return $same->count() === 1 ? $same[0] : null;   // two parties with one name: a person decides
        }
        return null;
    }

    /** Creates the supplier or customer from what Inventorise sent, and remembers which Inventorise party it is. */
    public static function createParty($merchant, $kind, array $p)
    {
        $name = trim(preg_replace('/\s+/', ' ', (string) ($p['name'] ?? '')));
        if ($name === '') { throw new \RuntimeException('The message has no party name, so a party cannot be created.'); }
        $gst = TaxId::clean($p['gstin'] ?? '');
        $state = trim((string) ($p['state'] ?? '')) ?: ($gst !== '' ? (Gstr2b::STATE_CODES[substr($gst, 0, 2)] ?? null) : null);
        $id = DB::table('customer')->insertGetId(['type' => $kind === 'purchase' ? 4 : 1, 'first_name' => $name, 'display_name' => $name, 'gst' => $gst ?: null, 'gst_type' => $gst ? 'Registered' : 'Unregistered',
            'email' => $p['email'] ?? null, 'phone' => mb_substr((string) ($p['phone'] ?? ''), 0, 20) ?: null, 'billing_state' => $state,
            'pan' => strtoupper(substr((string) ($p['pan'] ?? ''), 0, 10)) ?: ($gst !== '' ? substr($gst, 2, 10) : null), 'merchant' => $merchant, 'status' => 1, 'created_at' => date('d-m-Y H:i:s'), 'created_by' => 0]);
        $key = trim((string) ($p['key'] ?? ''));
        if ($key !== '') { DB::table('party_links')->updateOrInsert(['merchant_id' => $merchant, 'ext_key' => $key], ['customer_id' => $id, 'confirmed_by' => 0, 'updated_at' => now(), 'created_at' => now()]); }
        return DB::table('customer')->where('id', $id)->first();
    }

    public static function apply($inboxId)
    {
        $row = DB::table('link_inbox')->where('id', $inboxId)->first();
        $p = json_decode($row->payload, true);
        $m = $row->merchant_id; $kind = self::kind($row->type);
        try {
            $made = null;
            $party = self::findParty($m, $kind, (array) ($p['party'] ?? []));
            if (!$party && TaxId::clean($p['party']['gstin'] ?? '') === '') {
                // GSTIN decides. Without one, a party FNA does not already know is never created on its own: a person approves it.
                DB::table('link_inbox')->where('id', $inboxId)->update(['status' => 'held', 'note' => 'No GSTIN was sent and "' . ($p['party']['name'] ?? '?') . '" is not a ' . ($kind === 'purchase' ? 'supplier' : 'customer') . ' FNA knows. Approval awaited: match it to an existing party or create it.', 'updated_at' => now()]);
                return DB::table('link_inbox')->where('id', $inboxId)->first();
            }
            if (!$party) {
                try { $party = self::createParty($m, $kind, (array) ($p['party'] ?? [])); $made = 'New ' . ($kind === 'purchase' ? 'supplier' : 'customer') . ' "' . ($party->display_name) . '" was created in FNA from this message. Add its TDS details under Customers if needed.'; }
                catch (\RuntimeException $e) {
                    DB::table('link_inbox')->where('id', $inboxId)->update(['status' => 'held', 'note' => $e->getMessage(), 'updated_at' => now()]);
                    return DB::table('link_inbox')->where('id', $inboxId)->first();
                }
            }
            DB::transaction(function () use ($row, $p, $party, $m, $kind, $made) {
                if (str_ends_with($row->type, '_order')) { $oid = self::saveOrder($m, $kind, $p, $party); $upd = ['order_id' => $oid, 'note' => $made]; }
                else { [$did, $note] = self::saveDoc($m, $kind, $p, $party); $upd = ['doc_id' => $did, 'note' => $note ?: $made]; }
                DB::table('link_inbox')->where('id', $row->id)->update($upd + ['status' => 'applied', 'updated_at' => now()]);
            });
        } catch (\Throwable $e) {
            DB::table('link_inbox')->where('id', $inboxId)->update(['status' => 'error', 'note' => mb_substr($e->getMessage(), 0, 480), 'updated_at' => now()]);
        }
        return DB::table('link_inbox')->where('id', $inboxId)->first();
    }

    private static function lines(array $p)
    {
        $out = [];
        foreach ((array) ($p['lines'] ?? []) as $l) {
            $qty = round((float) ($l['qty'] ?? 0), 3); $rate = round((float) ($l['unit_price'] ?? 0), 2);
            if ($qty <= 0 && $rate <= 0) { continue; }
            $out[] = ['description' => mb_substr((string) ($l['description'] ?? ($l['sku'] ?? '')), 0, 500), 'sku' => mb_substr((string) ($l['sku'] ?? ''), 0, 60), 'hsn' => mb_substr((string) ($l['hsn'] ?? ''), 0, 20),
                'qty' => $qty, 'rate' => $rate, 'tax_rate' => self::pct($l['gst_rate'] ?? 0), 'amount' => round($qty * $rate, 2)];
        }
        if (!$out) { throw new \RuntimeException('The message has no lines.'); }
        return $out;
    }

    private static function date($v) { $t = $v ? strtotime((string) $v) : false; return $t ? date('Y-m-d', $t) : null; }

    private static function saveOrder($m, $kind, array $p, $party)
    {
        $lines = self::lines($p);
        $sub = round(array_sum(array_column($lines, 'amount')), 2);
        $tax = round(array_sum(array_map(fn($l) => $l['amount'] * $l['tax_rate'] / 100, $lines)), 2);
        $data = ['status' => $p['status'] ?? null, 'party_id' => $party->id, 'party_name' => $party->display_name ?: trim($party->first_name . ' ' . $party->last_name), 'order_date' => self::date($p['date'] ?? null),
            'expected_date' => self::date($p['expected'] ?? null), 'notes' => $p['notes'] ?? null, 'subtotal' => $sub, 'tax' => $tax, 'total' => round($sub + $tax, 2), 'updated_at' => now()];
        $o = DB::table('orders')->where('merchant_id', $m)->where('kind', $kind)->where('ref', $p['ref'])->first();
        if ($o) { DB::table('orders')->where('id', $o->id)->update($data); $oid = $o->id; DB::table('order_lines')->where('order_id', $oid)->delete(); }   // an order is a mirror of what Inventorise holds; every version is kept in link_history
        else { $oid = DB::table('orders')->insertGetId($data + ['merchant_id' => $m, 'kind' => $kind, 'ref' => $p['ref'], 'created_at' => now()]); }
        foreach ($lines as $l) { DB::table('order_lines')->insert(['order_id' => $oid] + $l + ['created_at' => now(), 'updated_at' => now()]); }
        return $oid;
    }

    private static function saveDoc($m, $kind, array $p, $party)
    {
        $lines = self::lines($p);
        $entity = DB::table('entity')->where('merchant_id', $m)->where('status', 1)->orderByDesc('id')->first();
        $placeState = $kind === 'sales' ? ($p['place_of_supply'] ?? $party->billing_state) : $party->billing_state;
        $supply = BillingPosting::supplyType($entity ? $entity->state : '', $placeState);
        $t = BillingPosting::totals(array_map(fn($l) => ['qty' => $l['qty'], 'rate' => $l['rate'], 'tax_rate' => $l['tax_rate']], $lines), $supply);
        $order = !empty($p['order_ref']) ? DB::table('orders')->where('merchant_id', $m)->where('kind', $kind)->where('ref', $p['order_ref'])->first() : null;
        $number = trim((string) ($p['number'] ?? $p['ref']));
        $doc = DB::table('billing_docs')->where('merchant_id', $m)->where('kind', $kind)->where('source', 'inventorise')->where('ext_ref', $p['ref'])->first();
        if ($doc && $doc->status !== 'draft') { return [$doc->id, 'Already posted in FNA as ' . $doc->number . '; a later change in Inventorise was not applied.']; }
        $date = self::date($p['date'] ?? null) ?: date('Y-m-d');
        $head = ['party_id' => $party->id, 'party_name' => $party->display_name ?: trim($party->first_name . ' ' . $party->last_name), 'supply' => $supply, 'doc_date' => $date, 'due_date' => self::date($p['due_date'] ?? null) ?: $date,
            'subtotal' => $t['subtotal'], 'cgst' => $t['cgst'], 'sgst' => $t['sgst'], 'igst' => $t['igst'], 'total' => $t['total'], 'reference' => $kind === 'purchase' ? $number : null,
            'notes' => 'From Inventorise ' . $p['ref'] . '.' . ($kind === 'purchase' ? ' Choose the expense account on each line, then post.' : ''), 'order_id' => $order->id ?? null, 'updated_at' => now()];
        if ($doc) { DB::table('billing_docs')->where('id', $doc->id)->update($head); $id = $doc->id; DB::table('billing_doc_lines')->where('doc_id', $id)->delete(); }   // a draft only; every version is kept in link_history
        else { $id = DB::table('billing_docs')->insertGetId($head + ['merchant_id' => $m, 'kind' => $kind, 'status' => 'draft', 'source' => 'inventorise', 'ext_ref' => $p['ref'], 'created_by' => 0, 'created_at' => now()]); }
        foreach ($lines as $l) { DB::table('billing_doc_lines')->insert(['doc_id' => $id, 'description' => $l['description'], 'hsn' => $l['hsn'] ?: null, 'qty' => $l['qty'], 'rate' => $l['rate'], 'tax_rate' => $l['tax_rate'], 'amount' => $l['amount'], 'created_at' => now(), 'updated_at' => now()]); }
        return [$id, null];
    }

    /** You confirmed which FNA party a held message's supplier/customer is. Remembers it and files every held message of that party. */
    public static function confirmParty($merchant, $extKey, $customerId, $user)
    {
        $c = DB::table('customer')->where('id', $customerId)->where('merchant', $merchant)->first();
        if (!$c) { throw new \RuntimeException('Choose a party from the list.'); }
        DB::table('party_links')->updateOrInsert(['merchant_id' => $merchant, 'ext_key' => $extKey], ['customer_id' => $c->id, 'confirmed_by' => $user, 'updated_at' => now(), 'created_at' => now()]);
        $n = 0;
        foreach (DB::table('link_inbox')->where('merchant_id', $merchant)->where('party_key', $extKey)->whereIn('status', ['held', 'error'])->orderBy('id')->get() as $r) {
            // orders first, so a bill finds its order
            if (str_ends_with($r->type, '_order')) { self::apply($r->id); $n++; }
        }
        foreach (DB::table('link_inbox')->where('merchant_id', $merchant)->where('party_key', $extKey)->whereIn('status', ['held', 'error'])->orderBy('id')->get() as $r) { self::apply($r->id); $n++; }
        return $n;
    }
}
