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
        // what to search in: all fields, number, amount or narration
        $in = $request->input('in', 'all');
        if ($q !== '') {
            $query->where(function ($w) use ($q, $in) {
                $first = true;
                $add = function ($cb) use ($w, &$first) { $first ? $w->where($cb) : $w->orWhere($cb); $first = false; };
                if (in_array($in, ['all', 'number'])) {
                    $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
                    $digits = ltrim(preg_replace('/\D/', '', $q), '0');
                    $add(function ($n) use ($like, $digits, $q) {
                        $n->where('v.voucher_no', 'like', $like)->orWhere('v.reference', 'like', $like);
                        // "33" finds MJV0033 and SBV-00033: digits at the end, ignoring leading zeros
                        if ($digits !== '' && ctype_digit(preg_replace('/\D/', '', $q)) && $q === preg_replace('/\D/', '', $q)) {
                            $n->orWhereRaw("v.voucher_no REGEXP ?", ['(^|[^0-9])0*' . $digits . '$']);
                        }
                    });
                }
                if (in_array($in, ['all', 'amount'])) {
                    $num = str_replace([',', ' '], '', $q);
                    if (preg_match('/^(\d+(?:\.\d+)?)-(\d+(?:\.\d+)?)$/', $num, $m)) {
                        $add(function ($n) use ($m) { $n->whereBetween('a.total_debit', [(float) $m[1], (float) $m[2]]); });
                    } elseif (is_numeric($num)) {
                        $add(function ($n) use ($num) { $n->whereRaw('ABS(a.total_debit - ?) < 0.005', [(float) $num]); });
                    }
                }
                if (in_array($in, ['all', 'narration'])) {
                    $like2 = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
                    $add(function ($n) use ($like2) {
                        $n->where('v.narration', 'like', $like2)
                          ->orWhereExists(function ($e) use ($like2) {
                              $e->select(DB::raw(1))->from('statement as x')
                                ->whereColumn('x.voucher_id', 'v.id')->where('x.remark', 'like', $like2);
                          });
                    });
                }
                if ($first) { $w->whereRaw('1 = 0'); } // nothing searchable in the text (e.g. letters in Amount)
            });
        }
        if ($series !== '') {
            if ($series === 'OLD') { $query->where(function ($w) { $w->whereNull('v.series')->orWhere('v.series', ''); }); }
            else { $query->where('v.series', $series); }
        }
        if ($status !== '') {
            $query->where('v.status', $status);
        }
        $vouchers = $query->orderByDesc('v.id')->paginate(50)->withQueryString();

        $years = EntityFinancialYear::where('merchant_id', $merchant)->where('status', 1)->pluck('financial_year')->all();
        $year_options = FinancialYears::options($years, $year === 'all' ? FinancialYears::current() : $year, true);
        $series_options = DB::table('vouchers')->where('merchant_id', $merchant)->whereNotNull('series')->distinct()->pluck('series');

        $base = DB::table('vouchers')->where('merchant_id', $merchant);
        if ($year !== 'all') { $base->where('fyear', $year); }
        $summary = ['total' => (clone $base)->count(), 'drafts' => (clone $base)->where('status', 'draft')->count()];

        return view('voucher.index', [
            'summary' => $summary,
            'vouchers' => $vouchers, 'year_options' => $year_options, 'selected_year' => $year,
            'q' => $q, 'in' => $in, 'series' => $series, 'status' => $status, 'series_options' => $series_options,
        ]);
    }

    public function show(Request $request, $voucherNo)
    {
        $merchant = Session::get('merchant_id');
        $voucher = DB::table('vouchers')->where('merchant_id', $merchant)->where('voucher_no', $voucherNo)->first();
        abort_unless($voucher, 404);

        $lines = DB::table('statement as s')
            ->leftJoin('head as h', 'h.id', '=', 's.head_id')
            ->leftJoin('departments as dp', 'dp.id', '=', 's.department_id')
            ->where('s.voucher_id', $voucher->id)
            ->orderBy('s.id')
            ->get(['s.*', 'h.name as head_name', 'dp.name as department_name']);
        if ($voucher->status === 'draft') {
            // a draft is not in the books: show its working lines
            $lines = DB::table('voucher_draft_lines as d')->leftJoin('head as h', 'h.id', '=', 'd.head_id')->where('d.voucher_id', $voucher->id)->orderBy('d.line_no')
                ->leftJoin('departments as dp', 'dp.id', '=', 'd.department_id')->get(['d.*', 'h.name as head_name', 'dp.name as department_name'])->map(function ($d) use ($voucher) {
                    $d->st_date = $voucher->voucher_date; $d->remark = $d->narration; $d->fyear = $voucher->fyear; $d->status = 0; return $d;
                });
        }
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
