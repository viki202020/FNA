<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\BalanceSheetReconciliation;
use App\Support\FinancialYears;
use Session;

/** Data Analytics: checks and views built on the books. First tab: balance sheet reconciliation. */
class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $years = DB::table('statement')->where('merchant_id', Session::get('merchant_id'))->whereNotNull('fyear')->distinct()->pluck('fyear')->all();
        $year = $request->input('year') ?: FinancialYears::current();
        $tab = in_array($request->input('tab'), ['receivables', 'payables', 'ratios']) ? $request->input('tab') : 'recon';
        $asAt = $request->input('as_at') ?: date('Y-m-d');
        $r = $tab === 'recon' ? BalanceSheetReconciliation::run($year) : null;
        $out = in_array($tab, ['receivables', 'payables']) ? \App\Support\Outstanding::report($tab === 'receivables' ? 'sales' : 'purchase', $asAt) : null;
        $ratios = $tab === 'ratios' ? \App\Support\Ratios::compute($year) : null;
        return view('analytics.index', ['tab' => $tab, 'out' => $out, 'ratios' => $ratios, 'as_at' => $asAt, 'r' => $r, 'year' => $year, 'year_options' => FinancialYears::options($years, $year, true), 'entity' => FinancialYears::entity()]);
    }
}
