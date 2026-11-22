<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * GST working papers built from posted invoices and bills (drafts and voided documents are left out).
 * Tax on each line is rate x amount, split into CGST+SGST (same state) or IGST (other state), as on the document.
 * Figures are for preparing GSTR-1 and GSTR-3B; filing is still done on the GST portal.
 */
class GstReports
{
    private static function docs($kind, $from, $to)
    {
        return DB::table('billing_docs')->where('merchant_id', Session::get('merchant_id'))->where('kind', $kind)
            ->whereIn('status', ['open', 'part', 'paid'])->whereBetween('doc_date', [$from, $to])->orderBy('doc_date')->orderBy('id')->get();
    }

    /** Tax on one line, split by the document's supply type. */
    public static function lineTax($line, $supply)
    {
        $tax = round($line->amount * $line->tax_rate / 100, 2);
        if ($supply === 'inter') { return ['igst' => $tax, 'cgst' => 0.0, 'sgst' => 0.0]; }
        $half = round($tax / 2, 2);
        return ['igst' => 0.0, 'cgst' => $half, 'sgst' => round($tax - $half, 2)];
    }

    public static function range($ym)
    {
        $from = $ym . '-01';
        return [$from, date('Y-m-t', strtotime($from))];
    }

    public static function gstr1($ym)
    {
        [$from, $to] = self::range($ym);
        $docs = self::docs('sales', $from, $to);
        $ids = $docs->pluck('id')->all();
        $lines = $ids ? DB::table('billing_doc_lines')->whereIn('doc_id', $ids)->get()->groupBy('doc_id') : collect();
        $parties = DB::table('customer')->whereIn('id', $docs->pluck('party_id')->filter()->all())->get()->keyBy('id');
        $entity = FinancialYears::entity();
        $b2b = []; $b2c = []; $hsn = []; $missingHsn = 0; $missingGstin = [];
        foreach ($docs as $d) {
            $p = $parties[$d->party_id] ?? null;
            $gstin = $p ? TaxId::clean($p->gst) : '';
            $valid = $gstin !== '' && TaxId::gstin($gstin);
            $byRate = [];
            foreach ($lines[$d->id] ?? [] as $l) {
                $t = self::lineTax($l, $d->supply);
                $r = (float) $l->tax_rate;
                $x = $byRate[(string) $r] ?? ['rate' => $r, 'taxable' => 0.0, 'igst' => 0.0, 'cgst' => 0.0, 'sgst' => 0.0];
                $x['taxable'] += $l->amount; $x['igst'] += $t['igst']; $x['cgst'] += $t['cgst']; $x['sgst'] += $t['sgst'];
                $byRate[(string) $r] = $x;
                $code = trim((string) $l->hsn);
                if ($code === '') { $missingHsn++; }
                $hk = $code . '|' . $r;
                $h = $hsn[$hk] ?? ['hsn' => $code, 'desc' => $l->description, 'rate' => $r, 'qty' => 0.0, 'taxable' => 0.0, 'igst' => 0.0, 'cgst' => 0.0, 'sgst' => 0.0];
                $h['qty'] += $l->qty; $h['taxable'] += $l->amount; $h['igst'] += $t['igst']; $h['cgst'] += $t['cgst']; $h['sgst'] += $t['sgst'];
                $hsn[$hk] = $h;
            }
            $row = ['doc' => $d, 'gstin' => $gstin, 'rates' => array_values($byRate), 'party' => $d->party_name];
            if ($valid) { $b2b[] = $row; }
            else {
                if ($gstin !== '') { $missingGstin[$d->id] = $d->number . ': GSTIN "' . $gstin . '" is not valid'; }
                $row['large'] = $d->supply === 'inter' && $d->total > 250000;
                $row['pos'] = $p ? $p->billing_state : '';
                $b2c[] = $row;
            }
        }
        ksort($hsn);
        $nums = $docs->pluck('number')->all();
        $void = DB::table('billing_docs')->where('merchant_id', Session::get('merchant_id'))->where('kind', 'sales')->where('status', 'void')->whereBetween('doc_date', [$from, $to])->count();
        return ['docs' => $docs, 'b2b' => $b2b, 'b2c' => $b2c, 'hsn' => array_values($hsn), 'missing_hsn' => $missingHsn, 'bad_gstin' => $missingGstin,
            'doc_from' => $nums ? reset($nums) : null, 'doc_to' => $nums ? end($nums) : null, 'doc_count' => count($nums), 'cancelled' => $void];
    }

    public static function gstr3b($ym)
    {
        [$from, $to] = self::range($ym);
        $sales = self::docs('sales', $from, $to); $purch = self::docs('purchase', $from, $to);
        $sum = fn($docs) => ['igst' => (float) $docs->sum('igst'), 'cgst' => (float) $docs->sum('cgst'), 'sgst' => (float) $docs->sum('sgst'), 'taxable' => (float) $docs->sum('subtotal')];
        $nil = 0.0;
        $lines = $sales->count() ? DB::table('billing_doc_lines')->whereIn('doc_id', $sales->pluck('id')->all())->get() : collect();
        foreach ($lines as $l) { if ((float) $l->tax_rate == 0.0) { $nil += $l->amount; } }
        $out = $sum($sales); $out['taxable'] -= $nil;
        $in = $sum($purch);
        $b = DB::table('gstr2b_lines')->where('merchant_id', Session::get('merchant_id'))->where('inv_date', '>=', $from)->where('inv_date', '<=', $to);
        $twoB = ['igst' => (float) (clone $b)->where('itc_available', '!=', 'N')->sum('igst'), 'cgst' => (float) (clone $b)->where('itc_available', '!=', 'N')->sum('cgst'), 'sgst' => (float) (clone $b)->where('itc_available', '!=', 'N')->sum('sgst'), 'n' => (clone $b)->count()];
        $payable = ['igst' => max(0, $out['igst'] - $in['igst']), 'cgst' => max(0, $out['cgst'] - $in['cgst']), 'sgst' => max(0, $out['sgst'] - $in['sgst'])];
        return ['out' => $out, 'nil' => $nil, 'itc' => $in, 'two_b' => $twoB, 'net' => ['igst' => $out['igst'] - $in['igst'], 'cgst' => $out['cgst'] - $in['cgst'], 'sgst' => $out['sgst'] - $in['sgst']],
            'sales_count' => $sales->count(), 'purchase_count' => $purch->count()];
    }

    /** Months that have any posted document, newest first (for the month picker). */
    public static function months()
    {
        $m = DB::table('billing_docs')->where('merchant_id', Session::get('merchant_id'))->whereIn('status', ['open', 'part', 'paid'])
            ->selectRaw("DATE_FORMAT(doc_date,'%Y-%m') ym")->distinct()->orderByDesc('ym')->pluck('ym')->all();
        $now = date('Y-m');
        if (!in_array($now, $m)) { array_unshift($m, $now); }
        return $m;
    }
}
