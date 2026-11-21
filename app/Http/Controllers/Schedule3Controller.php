<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Excel;
use App\Exports\Schedule3Export;
use App\Support\FinancialYears;
use App\Support\Schedule3;
use Session;

/** Financial statements in Schedule III (Division I) form: balance sheet, profit and loss, notes and ageing. Cash flow is not included. */
class Schedule3Controller extends Controller
{
    private function params(Request $request)
    {
        $years = DB::table('statement')->where('merchant_id', Session::get('merchant_id'))->whereNotNull('fyear')->distinct()->pluck('fyear')->all();
        $year = $request->input('year') ?: FinancialYears::current();
        $unit = array_key_exists($request->input('unit'), Schedule3::UNITS) ? $request->input('unit') : 'thousands';
        $dept = $request->input('dept');
        if ($dept === 'all' || $dept === '' || $dept === null) { $dept = null; }
        return [$year, $unit, FinancialYears::options($years, $year, true), $dept];
    }

    public function index(Request $request)
    {
        [$year, $unit, $yearOptions, $dept] = $this->params($request);
        $s = Schedule3::build($year, $unit, $dept);
        $entity = FinancialYears::entity();
        $end = $entity && $entity->financial_year === $year ? $entity->financial_year_end : (substr($year, -4) . '-03-31');
        $ageing = ['receivables' => Schedule3::ageing('sales', $end), 'payables' => Schedule3::ageing('purchase', $end)];
        $departments = DB::table('departments')->where('merchant_id', Session::get('merchant_id'))->orderBy('name')->get(['id', 'name']);
        $byDept = $request->input('tab') === 'dept' ? Schedule3::byDepartment($year, $unit) : null;
        $tb = $request->input('tab') === 'tb' ? Schedule3::trialBalance($year, $dept) : null;
        return view('schedule3.index', ['dept' => $dept, 'departments' => $departments, 'byDept' => $byDept, 'tb' => $tb, 's' => $s, 'tab' => $request->input('tab', 'bs'), 'year' => $year, 'unit' => $unit, 'year_options' => $yearOptions, 'entity' => $entity,
            'ageing' => $ageing, 'asat' => $end, 'units' => Schedule3::UNITS]);
    }

    public function export(Request $request)
    {
        [$year, $unit, , $dept] = $this->params($request);
        $s = Schedule3::build($year, $unit, $dept);
        return Excel::download(new Schedule3Export($s, FinancialYears::entity()), 'Schedule_III_' . $year . '.xlsx');
    }
}
