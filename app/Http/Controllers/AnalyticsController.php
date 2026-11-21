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
        $r = BalanceSheetReconciliation::run($year);
        return view('analytics.index', ['r' => $r, 'year' => $year, 'year_options' => FinancialYears::options($years, $year, true), 'entity' => FinancialYears::entity()]);
    }
}
