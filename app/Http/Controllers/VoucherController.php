<?php

namespace App\Http\Controllers;

use App\Models\EntityFinancialYear;
use App\Support\FinancialYears;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Session;

/**
 * Stage 2 - voucher lookup (read only): find a voucher, see its lines, status and history.
 */
class VoucherController extends Controller
{
    public static $seriesNames = [
        'SBV' => 'Bank statement line',
        'UJV' => 'Uploaded journal',
        'MJV' => 'Manual journal',
        'RJV' => 'Reversal',
        'SRV' => 'Auto reversal',
        'SIV' => 'Sales invoice',
    ];

    public static function seriesLabel($series)
    {
        return self::$seriesNames[$series] ?? 'Journal entry';
    }

    public function index(Request $request)
    {
        $merchant = Session::get('merchant_id');
        $year = $request->input('year') ?: FinancialYears::current();
        $q = trim((string) $request->input('q', ''));
        $series = $request->input('series', '');
        $status = $request->input('status', '');

        $agg = DB::table('statement')
            ->whereNotNull('voucher_id')
            ->select('voucher_id',
                DB::raw('COUNT(*) as line_count'),
                DB::raw('SUM(CASE WHEN status=0 THEN 1 ELSE 0 END) as open_lines'),
                DB::raw('ROUND(SUM(debit+0),2) as total_debit'),
                DB::raw('ROUND(SUM(credit+0),2) as total_credit'),
                DB::raw('MIN(remark) as first_remark'))
            ->groupBy('voucher_id');
        $query = DB::table('vouchers as v')
            ->leftJoinSub($agg, 'a', 'a.voucher_id', '=', 'v.id')
            ->where('v.merchant_id', $merchant)
            ->select('v.*',
                DB::raw('COALESCE(a.line_count,0) as line_count'),
                DB::raw('COALESCE(a.open_lines,0) as open_lines'),
                DB::raw('COALESCE(a.total_debit,0) as total_debit'),
                DB::raw('COALESCE(a.total_credit,0) as total_credit'),
                'a.first_remark');
        if ($year !== 'all') {
            $query->where('v.fyear', $year);
        }
        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $query->where(function ($w) use ($like) {
                $w->where('v.voucher_no', 'like', $like)
                  ->orWhere('v.narration', 'like', $like)
                  ->orWhere('v.reference', 'like', $like)
                  ->orWhereExists(function ($e) use ($like) {
                      $e->select(DB::raw(1))->from('statement as x')
                        ->whereColumn('x.voucher_id', 'v.id')->where('x.remark', 'like', $like);
                  });
            });
        }
        if ($series !== '') {
            $query->where('v.series', $series);
        }
        if ($status !== '') {
            $query->where('v.status', $status);
        }
        $vouchers = $query->orderByDesc('v.id')->paginate(50)->withQueryString();

        $years = EntityFinancialYear::where('merchant_id', $merchant)->where('status', 1)->pluck('financial_year')->all();
        $year_options = FinancialYears::options($years, $year === 'all' ? FinancialYears::current() : $year, true);
        $series_options = DB::table('vouchers')->where('merchant_id', $merchant)->whereNotNull('series')->distinct()->pluck('series');

        return view('voucher.index', [
            'vouchers' => $vouchers, 'year_options' => $year_options, 'selected_year' => $year,
            'q' => $q, 'series' => $series, 'status' => $status, 'series_options' => $series_options,
        ]);
    }

    public function show(Request $request, $voucherNo)
    {
        $merchant = Session::get('merchant_id');
        $voucher = DB::table('vouchers')->where('merchant_id', $merchant)->where('voucher_no', $voucherNo)->first();
        abort_unless($voucher, 404);

        $lines = DB::table('statement as s')
            ->leftJoin('head as h', 'h.id', '=', 's.head_id')
            ->where('s.voucher_id', $voucher->id)
            ->orderBy('s.id')
            ->get(['s.*', 'h.name as head_name']);
        $totalDebit = 0; $totalCredit = 0;
        foreach ($lines as $l) {
            $totalDebit += (float) $l->debit;
            $totalCredit += (float) $l->credit;
        }
        $versions = DB::table('voucher_versions')->where('voucher_id', $voucher->id)->orderByDesc('version')->get();
        $related = DB::table('vouchers')->where('merchant_id', $merchant)
            ->whereIn('id', array_filter([$voucher->reverses_id, $voucher->reversed_by_id]))->get();

        return view('voucher.show', [
            'voucher' => $voucher, 'lines' => $lines,
            'total_debit' => round($totalDebit, 2), 'total_credit' => round($totalCredit, 2),
            'versions' => $versions, 'related' => $related,
            'year_is_closed' => !FinancialYears::isOpen($voucher->fyear),
        ]);
    }
}
