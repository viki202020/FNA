<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\Gstr2b;
use Session;

/** GSTR-2B upload and reconciliation against purchase bills; draft bills can be created for what is missing. */
class Gstr2bController extends Controller
{
    private function merchant() { return Session::get('merchant_id'); }

    public function index(Request $request)
    {
        $batches = DB::table('gstr2b_lines')->where('merchant_id', $this->merchant())->select('batch', 'period', DB::raw('COUNT(*) n'), DB::raw('MIN(created_at) at'))->groupBy('batch', 'period')->orderByDesc('batch')->get();
        $batch = $request->input('batch') ?: ($batches->first()->batch ?? null);
        $lines = $batch ? DB::table('gstr2b_lines')->where('merchant_id', $this->merchant())->where('batch', $batch)->orderBy('supplier_name')->orderBy('inv_date')->get() : collect();
        $notIn = $batch ? Gstr2b::notIn2b($batch) : collect();
        $counts = ['matched' => 0, 'mismatch' => 0, 'missing' => 0, 'drafted' => 0];
        foreach ($lines as $l) { $counts[$l->status] = ($counts[$l->status] ?? 0) + 1; }
        $itc = ['claimable' => 0.0, 'blocked' => 0.0];
        foreach ($lines as $l) { $t = $l->igst + $l->cgst + $l->sgst; if (in_array($l->status, ['matched'])) { if (strtoupper((string) $l->itc_available) === 'N') { $itc['blocked'] += $t; } else { $itc['claimable'] += $t; } } }
        return view('gstr2b.index', ['batches' => $batches, 'batch' => $batch, 'lines' => $lines, 'notIn' => $notIn, 'counts' => $counts, 'itc' => $itc]);
    }

    public function upload(Request $request)
    {
        $request->validate(['gstr_file' => 'required|file']);
        $file = $request->file('gstr_file');
        try {
            [$period, $lines, $creditNotes] = Gstr2b::parse($file->getRealPath(), $file->getClientOriginalExtension());
        } catch (\Throwable $e) {
            return back()->withErrors(['The file could not be read: ' . $e->getMessage()]);
        }
        if (!$lines) { return back()->withErrors(['No supplier invoices were found. Use the GSTR-2B JSON, or the portal\'s Excel file that has a B2B sheet.']); }
        $period = $request->input('period') ?: $period;
        $batch = Gstr2b::store($lines, $period);
        $msg = count($lines) . ' supplier invoice(s) read and matched.' . ($creditNotes ? ' ' . $creditNotes . ' credit note(s) in the file were not read (credit notes are not built yet).' : '');
        return redirect('merchant/gstr2b?batch=' . $batch)->with('success_msg', $msg);
    }

    public function draft(Request $request, $id)
    {
        try { $docId = Gstr2b::createDraft($id); }
        catch (\Throwable $e) { return back()->withErrors([$e->getMessage()]); }
        return redirect('merchant/bills/' . $docId)->with('success_msg', 'Draft bill created from GSTR-2B. Choose the expense account, then post it.');
    }

    public function draftAll(Request $request)
    {
        $ids = DB::table('gstr2b_lines')->where('merchant_id', $this->merchant())->where('batch', $request->input('batch'))->where('status', 'missing')->pluck('id');
        $n = 0; $err = 0;
        foreach ($ids as $id) { try { Gstr2b::createDraft($id); $n++; } catch (\Throwable $e) { $err++; } }
        return back()->with('success_msg', $n . ' draft bill(s) created' . ($err ? ', ' . $err . ' could not be created' : '') . '. They are drafts: choose the expense accounts, then post each.');
    }
}
