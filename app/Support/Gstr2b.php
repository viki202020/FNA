<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Session;

/**
 * Reads a GSTR-2B file downloaded from the GST portal (JSON, or the portal's Excel with a B2B sheet) and matches every supplier invoice
 * to the purchase bills booked in FNA, by supplier GSTIN + invoice number, then amounts.
 *   matched   the bill is in FNA and the taxable value and tax agree (within 1 rupee)
 *   mismatch  the bill is in FNA but the amounts differ (the differences are listed)
 *   missing   the supplier filed it but it is not in FNA yet: a draft bill can be created from it
 */
class Gstr2b
{
    const STATE_CODES = ['01'=>'Jammu and Kashmir','02'=>'Himachal Pradesh','03'=>'Punjab','04'=>'Chandigarh','05'=>'Uttarakhand','06'=>'Haryana','07'=>'Delhi','08'=>'Rajasthan','09'=>'Uttar Pradesh','10'=>'Bihar','11'=>'Sikkim','12'=>'Arunachal Pradesh','13'=>'Nagaland','14'=>'Manipur','15'=>'Mizoram','16'=>'Tripura','17'=>'Meghalaya','18'=>'Assam','19'=>'West Bengal','20'=>'Jharkhand','21'=>'Odisha','22'=>'Chhattisgarh','23'=>'Madhya Pradesh','24'=>'Gujarat','26'=>'Dadra and Nagar Haveli and Daman and Diu','27'=>'Maharashtra','29'=>'Karnataka','30'=>'Goa','31'=>'Lakshadweep','32'=>'Kerala','33'=>'Tamil Nadu','34'=>'Puducherry','35'=>'Andaman and Nicobar Islands','36'=>'Telangana','37'=>'Andhra Pradesh','38'=>'Ladakh'];

    public static function norm($s) { return preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $s)); }

    private static function num($v)
    {
        $v = str_replace([',', ' '], '', (string) $v);
        return is_numeric($v) ? round((float) $v, 2) : 0.0;
    }

    private static function date($v)
    {
        if ($v instanceof \DateTimeInterface) { return $v->format('Y-m-d'); }
        if (is_numeric($v) && $v > 20000) { return date('Y-m-d', (int) (($v - 25569) * 86400)); }
        $t = strtotime(str_replace('/', '-', (string) $v));
        return $t ? date('Y-m-d', $t) : null;
    }

    /** @return array [period, lines[], skippedCreditNotes] */
    public static function parse($path, $ext)
    {
        $ext = strtolower($ext);
        if ($ext === 'json') {
            $j = json_decode(file_get_contents($path), true);
            $d = $j['data'] ?? $j;
            $period = $d['rtnprd'] ?? ($j['rtnprd'] ?? null);
            $lines = [];
            foreach (($d['docdata']['b2b'] ?? $d['b2b'] ?? []) as $sup) {
                foreach (($sup['inv'] ?? []) as $inv) {
                    $lines[] = ['gstin' => strtoupper($sup['ctin'] ?? ''), 'name' => $sup['trdnm'] ?? '', 'inv_no' => (string) ($inv['inum'] ?? ''), 'inv_date' => self::date($inv['dt'] ?? null),
                        'value' => self::num($inv['val'] ?? 0), 'taxable' => self::num($inv['txval'] ?? 0), 'igst' => self::num($inv['igst'] ?? 0), 'cgst' => self::num($inv['cgst'] ?? 0),
                        'sgst' => self::num($inv['sgst'] ?? 0), 'cess' => self::num($inv['cess'] ?? 0), 'itc' => $inv['itcavl'] ?? null];
                }
            }
            $cn = 0; foreach (($d['docdata']['cdnr'] ?? $d['cdnr'] ?? []) as $sup) { $cn += count($sup['nt'] ?? []); }
            return [$period, $lines, $cn];
        }
        // Excel / CSV: the portal's B2B sheet. Columns are found by their headings.
        $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $sheet = null;
        foreach ($book->getAllSheets() as $s) { if (strtoupper(trim($s->getTitle())) === 'B2B') { $sheet = $s; break; } }
        $sheet = $sheet ?: $book->getSheet(0);
        $rows = $sheet->toArray(null, true, true, false);
        $hdr = null; $map = [];
        foreach ($rows as $i => $r) {
            $txt = array_map(fn($c) => strtolower(trim((string) $c)), $r);
            if (in_array('invoice number', $txt, true) || in_array('invoice no', $txt, true)) { $hdr = $i; $map = array_flip(array_filter($txt, fn($x) => $x !== '')); break; }
        }
        if ($hdr === null) { return [null, [], 0]; }
        $col = function (array $names) use ($map) { foreach ($map as $label => $idx) { foreach ($names as $n) { if (str_contains($label, $n)) { return $idx; } } } return null; };
        $c = ['gstin' => $col(['gstin of supplier', 'supplier gstin']), 'name' => $col(['trade/legal', 'trade name', 'legal name']), 'inv' => $col(['invoice number', 'invoice no']), 'date' => $col(['invoice date']),
              'val' => $col(['invoice value']), 'tx' => $col(['taxable value']), 'ig' => $col(['integrated tax']), 'cg' => $col(['central tax']), 'sg' => $col(['state/ut tax', 'state tax']), 'ce' => $col(['cess']), 'itc' => $col(['itc availability'])];
        $lines = [];
        for ($i = $hdr + 1; $i < count($rows); $i++) {
            $r = $rows[$i];
            $inv = trim((string) ($c['inv'] !== null ? ($r[$c['inv']] ?? '') : ''));
            $gstin = strtoupper(trim((string) ($c['gstin'] !== null ? ($r[$c['gstin']] ?? '') : '')));
            if ($inv === '' || strlen($gstin) < 15) { continue; }
            $lines[] = ['gstin' => $gstin, 'name' => $c['name'] !== null ? (string) ($r[$c['name']] ?? '') : '', 'inv_no' => $inv, 'inv_date' => self::date($r[$c['date']] ?? null),
                'value' => self::num($r[$c['val']] ?? 0), 'taxable' => self::num($r[$c['tx']] ?? 0), 'igst' => self::num($r[$c['ig']] ?? 0), 'cgst' => self::num($r[$c['cg']] ?? 0),
                'sgst' => self::num($r[$c['sg']] ?? 0), 'cess' => self::num($c['ce'] !== null ? ($r[$c['ce']] ?? 0) : 0), 'itc' => $c['itc'] !== null ? (string) ($r[$c['itc']] ?? '') : null];
        }
        return [null, $lines, 0];
    }

    /** Matches parsed lines to the purchase bills in FNA and stores them as one batch. Returns the batch id. */
    public static function store(array $lines, $period)
    {
        $m = Session::get('merchant_id'); $batch = date('YmdHis') . substr((string) mt_rand(100, 999), 0, 3);
        $bills = DB::table('billing_docs as d')->leftJoin('customer as c', 'c.id', '=', 'd.party_id')->where('d.merchant_id', $m)->where('d.kind', 'purchase')->where('d.status', '<>', 'void')
            ->get(['d.*', 'c.gst as p_gst']);
        $index = [];
        foreach ($bills as $b) { if ($b->reference) { $index[strtoupper(trim((string) $b->p_gst)) . '|' . self::norm($b->reference)][] = $b; } }
        foreach ($lines as $l) {
            $key = $l['gstin'] . '|' . self::norm($l['inv_no']);
            $status = 'missing'; $note = null; $docId = null;
            if (isset($index[$key])) {
                $b = $index[$key][0]; $docId = $b->id; $diff = [];
                $fileTax = round($l['igst'] + $l['cgst'] + $l['sgst'], 2); $billTax = round($b->cgst + $b->sgst + $b->igst, 2);
                if (abs($b->subtotal - $l['taxable']) > 1.0) { $diff[] = 'taxable value: bill ' . number_format($b->subtotal, 2) . ' against 2B ' . number_format($l['taxable'], 2); }
                if (abs($billTax - $fileTax) > 1.0) { $diff[] = 'tax: bill ' . number_format($billTax, 2) . ' against 2B ' . number_format($fileTax, 2); }
                $status = $diff ? 'mismatch' : 'matched'; $note = $diff ? implode('; ', $diff) : null;
            }
            DB::table('gstr2b_lines')->insert(['merchant_id' => $m, 'batch' => $batch, 'period' => $period, 'supplier_gstin' => $l['gstin'], 'supplier_name' => $l['name'], 'inv_no' => $l['inv_no'],
                'inv_date' => $l['inv_date'], 'inv_value' => $l['value'], 'taxable' => $l['taxable'], 'igst' => $l['igst'], 'cgst' => $l['cgst'], 'sgst' => $l['sgst'], 'cess' => $l['cess'],
                'itc_available' => $l['itc'], 'status' => $status, 'note' => $note, 'doc_id' => $docId, 'uploaded_by' => Session::get('id'), 'created_at' => now(), 'updated_at' => now()]);
        }
        return $batch;
    }

    /** Purchase bills booked in FNA (with a supplier GSTIN) that are not in the 2B batch: the supplier has not filed them (yet). */
    public static function notIn2b($batch)
    {
        $m = Session::get('merchant_id');
        $in = DB::table('gstr2b_lines')->where('merchant_id', $m)->where('batch', $batch)->whereNotNull('doc_id')->pluck('doc_id')->all();
        $period = DB::table('gstr2b_lines')->where('merchant_id', $m)->where('batch', $batch)->value('period');
        $q = DB::table('billing_docs as d')->leftJoin('customer as c', 'c.id', '=', 'd.party_id')->where('d.merchant_id', $m)->where('d.kind', 'purchase')->whereIn('d.status', ['open', 'part', 'paid'])
            ->whereNotNull('d.number')->whereNotIn('d.id', $in ?: [0]);
        if ($period && strlen($period) === 6) { $y = substr($period, 2); $mo = substr($period, 0, 2); $q->whereYear('d.doc_date', $y)->whereMonth('d.doc_date', (int) $mo); }
        return $q->get(['d.*', 'c.gst as p_gst'])->filter(fn($d) => trim((string) $d->p_gst) !== '')->values();
    }

    /** Creates a DRAFT purchase bill from a 2B line that is missing in FNA. The supplier is found by GSTIN or created. Never posted automatically. */
    public static function createDraft($lineId)
    {
        $m = Session::get('merchant_id'); $me = Session::get('id');
        return DB::transaction(function () use ($lineId, $m, $me) {
            $l = DB::table('gstr2b_lines')->where('merchant_id', $m)->where('id', $lineId)->lockForUpdate()->first();
            if (!$l || $l->status !== 'missing') { throw new \RuntimeException('Only a line missing in FNA can be turned into a draft.'); }
            $party = DB::table('customer')->where('merchant', $m)->where('type', 4)->whereRaw('UPPER(gst) = ?', [$l->supplier_gstin])->first();
            if (!$party) {
                $state = self::STATE_CODES[substr($l->supplier_gstin, 0, 2)] ?? null;
                $pid = DB::table('customer')->insertGetId(['type' => 4, 'first_name' => $l->supplier_name ?: $l->supplier_gstin, 'display_name' => $l->supplier_name ?: $l->supplier_gstin, 'gst' => $l->supplier_gstin,
                    'gst_type' => 'Registered', 'billing_state' => $state, 'pan' => substr($l->supplier_gstin, 2, 10), 'merchant' => $m, 'status' => 1, 'created_at' => date('d-m-Y H:i:s'), 'created_by' => $me ?: 0]);
                $party = DB::table('customer')->where('id', $pid)->first();
            }
            $tax = round($l->igst + $l->cgst + $l->sgst, 2);
            $date = $l->inv_date ?: date('Y-m-d');
            $docId = DB::table('billing_docs')->insertGetId(['merchant_id' => $m, 'kind' => 'purchase', 'doc_date' => $date, 'due_date' => $date, 'party_id' => $party->id,
                'party_name' => $party->display_name ?: $party->first_name, 'supply' => $l->igst > 0 ? 'inter' : 'intra', 'subtotal' => $l->taxable, 'cgst' => $l->cgst, 'sgst' => $l->sgst, 'igst' => $l->igst,
                'total' => round($l->taxable + $tax, 2), 'status' => 'draft', 'reference' => $l->inv_no, 'notes' => 'Created from GSTR-2B (period ' . ($l->period ?: 'unknown') . '). Choose the expense account, then post.',
                'created_by' => $me, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('billing_doc_lines')->insert(['doc_id' => $docId, 'description' => 'Supplier invoice ' . $l->inv_no . ' (from GSTR-2B)', 'qty' => 1, 'rate' => $l->taxable,
                'tax_rate' => $l->taxable > 0 ? round($tax / $l->taxable * 100, 2) : 0, 'amount' => $l->taxable, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('gstr2b_lines')->where('id', $l->id)->update(['status' => 'drafted', 'doc_id' => $docId, 'updated_at' => now()]);
            return $docId;
        });
    }
}
