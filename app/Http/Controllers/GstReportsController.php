<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Support\GstReports;

/** GST reports: GSTR-1 tables and a GSTR-3B summary, month by month. */
class GstReportsController extends Controller
{
    private function month(Request $r)
    {
        $ym = (string) $r->input('month');
        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $ym) ? $ym : date('Y-m');
    }

    public function index(Request $request)
    {
        $ym = $this->month($request);
        $tab = in_array($request->input('tab'), ['gstr1', 'hsn']) ? $request->input('tab') : '3b';
        $data = $tab === '3b' ? GstReports::gstr3b($ym) : GstReports::gstr1($ym);
        return view('gst.index', ['ym' => $ym, 'tab' => $tab, 'd' => $data, 'months' => GstReports::months(), 'entity' => \App\Support\FinancialYears::entity()]);
    }

    /** CSV of one GSTR-1 table, to copy into the portal's offline tool or the CA's own workbook. */
    public function csv(Request $request, $table)
    {
        abort_unless(in_array($table, ['b2b', 'b2c', 'hsn']), 404);
        $ym = $this->month($request);
        $g = GstReports::gstr1($ym);
        $f = fn($v) => $v === null ? '' : (is_float($v) ? number_format($v, 2, '.', '') : $v);
        $rows = [];
        if ($table === 'b2b') {
            $rows[] = ['GSTIN of recipient', 'Receiver name', 'Invoice number', 'Invoice date', 'Invoice value', 'Rate', 'Taxable value', 'IGST', 'CGST', 'SGST'];
            foreach ($g['b2b'] as $r) { foreach ($r['rates'] as $x) { $rows[] = [$r['gstin'], $r['party'], $r['doc']->number, date('d-m-Y', strtotime($r['doc']->doc_date)), $f((float) $r['doc']->total), $x['rate'], $f($x['taxable']), $f($x['igst']), $f($x['cgst']), $f($x['sgst'])]; } }
        } elseif ($table === 'b2c') {
            $rows[] = ['Type', 'Place of supply', 'Invoice number', 'Invoice date', 'Invoice value', 'Rate', 'Taxable value', 'IGST', 'CGST', 'SGST'];
            foreach ($g['b2c'] as $r) { foreach ($r['rates'] as $x) { $rows[] = [$r['large'] ? 'B2C Large' : 'B2C Small', $r['pos'], $r['doc']->number, date('d-m-Y', strtotime($r['doc']->doc_date)), $f((float) $r['doc']->total), $x['rate'], $f($x['taxable']), $f($x['igst']), $f($x['cgst']), $f($x['sgst'])]; } }
        } else {
            $rows[] = ['HSN / SAC', 'Description', 'Rate', 'Quantity', 'Taxable value', 'IGST', 'CGST', 'SGST'];
            foreach ($g['hsn'] as $h) { $rows[] = [$h['hsn'], $h['desc'], $h['rate'], $h['qty'], $f($h['taxable']), $f($h['igst']), $f($h['cgst']), $f($h['sgst'])]; }
        }
        $h = fopen('php://temp', 'w+');
        foreach ($rows as $r) {
            // a cell starting with = + - @ would run as a formula when opened in Excel
            fputcsv($h, array_map(fn($c) => (is_string($c) && preg_match('/^[=+@]/', $c)) ? "'" . $c : $c, $r));
        }
        rewind($h); $csv = stream_get_contents($h); fclose($h);
        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="gstr1-' . $table . '-' . $ym . '.csv"']);
    }
}
