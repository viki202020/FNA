<?php

namespace App\Http\Controllers;

use App\Models\EntityFinancialYear;
use App\Support\FinancialYears;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Session;

/**
 * Rule clashes found at upload time, the user's choices (saved in bulk), and the rule audit.
 */
class RuleConflictController extends Controller
{
    /** A rule described in plain words, e.g. 'description contains "Razor"'. */
    public static function sentence($r)
    {
        $word = ['Exact' => 'is exactly', 'like' => 'contains', 'Any' => 'contains', '>=' => 'is at least', '<=' => 'is at most', '>' => 'is more than', '<' => 'is less than'];
        $part = function ($col, $cond, $val) use ($word) {
            if ($col === null || $col === '') { return null; }
            if ($col === 'Amount') {
                if ($cond === 'Any' || $cond === 'Any Value' || $cond === null || $cond === '') { return null; }
                return 'amount ' . ($cond === 'Exact' ? 'is exactly' : ($word[$cond] ?? $cond)) . ' ' . $val;
            }
            if (trim((string) $val) === '') { return null; }
            return 'description ' . ($cond === 'Exact' ? 'is exactly' : 'contains') . ' "' . $val . '"';
        };
        $bits = array_filter([$part($r->col_type, $r->rule_condition, $r->content), $part($r->col_type1, $r->rule_condition1, $r->content1)]);
        return $bits ? implode(' and ', $bits) : 'any line';
    }

    private function rulesById(array $ids)
    {
        $rules = DB::table('rules as r')->leftJoin('head as h', 'h.id', '=', 'r.head_id')
            ->whereIn('r.id', $ids ?: [0])->get(['r.*', 'h.name as head_name'])->keyBy('id');
        return $rules;
    }

    public function conflicts(Request $request)
    {
        $merchant = Session::get('merchant_id');
        $rows = DB::table('rule_audit as a')
            ->join('statement as s', function ($j) {
                // explicit collation: the tables may have been created with different ones
                $j->whereRaw('s.voucher_no = a.voucher_no COLLATE utf8mb4_general_ci')->where('s.status', 0);
            })
            ->where('a.merchant_id', $merchant)->where('a.outcome', 'conflict')
            ->orderBy('a.line_date')->orderBy('a.id')
            ->get(['a.*', 's.id as statement_id']);
        $rows = $rows->filter(function ($r) { return FinancialYears::isOpen($r->fyear); })->values();

        $ids = [];
        foreach ($rows as $r) {
            foreach (json_decode($r->matched, true) ?: [] as $m) { $ids[] = $m['rule']; }
        }
        $rules = $this->rulesById(array_unique($ids));
        $groups = $rows->groupBy('matched_key')->map->count();
        foreach ($rows as $r) {
            $seen = []; $cands = [];
            foreach (json_decode($r->matched, true) ?: [] as $m) {
                if (isset($seen[$m['rule']]) || !isset($rules[$m['rule']])) { continue; }
                $seen[$m['rule']] = true;
                $rule = $rules[$m['rule']];
                $cands[] = (object) ['id' => $rule->id, 'name' => $rule->rule_name, 'text' => self::sentence($rule), 'head' => $rule->head_name];
            }
            $r->candidates = $cands;
            $r->group_size = $groups[$r->matched_key] ?? 1;
        }
        return view('rules.conflicts', ['rows' => $rows]);
    }

    public function resolve(Request $request)
    {
        $merchant = Session::get('merchant_id');
        $choices = $request->input('choice', []);   // audit id => rule id (blank = leave)
        $same = $request->input('same', []);        // audit id => 1
        $done = 0; $skipped = 0; $closed = [];

        $apply = function ($audit, $ruleId) use ($merchant, &$done, &$skipped, &$closed) {
            $rule = DB::table('rules')->where('id', $ruleId)->first();
            if (!$rule || $audit->outcome !== 'conflict') { return; }
            $line = DB::table('statement')->where('voucher_no', $audit->voucher_no)->where('status', 0)->first();
            if (!$line) { return; }
            if (!FinancialYears::isOpen($line->fyear)) { $skipped++; $closed[$line->fyear] = true; return; }
            DB::table('statement')->where('id', $line->id)->update([
                'head_id' => $rule->head_id, 'status' => 1,
                'updated_at' => date('d-m-Y H:i:s'), 'updated_by' => Session::get('id'),
            ]);
            DB::table('rule_audit')->where('id', $audit->id)->update([
                'outcome' => 'resolved', 'chosen_rule_id' => $rule->id, 'head_id' => $rule->head_id,
                'decided_by' => Session::get('id'), 'decided_at' => now(), 'updated_at' => now(),
            ]);
            $done++;
        };

        foreach ($choices as $auditId => $ruleId) {
            if ($ruleId === '' || $ruleId === null) { continue; }
            $audit = DB::table('rule_audit')->where('id', $auditId)->where('merchant_id', $merchant)->first();
            if (!$audit || $audit->outcome !== 'conflict') { continue; }
            $apply($audit, $ruleId);
            if (!empty($same[$auditId])) {
                // every other open clash between exactly the same rules gets the same answer
                $others = DB::table('rule_audit')->where('merchant_id', $merchant)->where('outcome', 'conflict')
                    ->where('matched_key', $audit->matched_key)->where('id', '!=', $audit->id)->get();
                foreach ($others as $o) {
                    if (isset($choices[$o->id]) && $choices[$o->id] !== '') { continue; } // an explicit choice wins
                    $apply($o, $ruleId);
                }
            }
        }
        $msg = $done . ' line(s) categorised.';
        if ($skipped) {
            $msg .= ' ' . $skipped . ' skipped: closed year (' . implode(', ', array_keys($closed)) . ').';
        }
        return redirect('merchant/rule-conflicts')->with('rule_msg', $msg);
    }

    public function audit(Request $request)
    {
        $merchant = Session::get('merchant_id');
        $year = $request->input('year') ?: FinancialYears::current();
        $outcome = $request->input('outcome', '');
        $q = DB::table('rule_audit as a')->where('a.merchant_id', $merchant)
            ->leftJoin('head as h', 'h.id', '=', 'a.head_id')
            ->leftJoin('users as u', 'u.id', '=', 'a.decided_by')
            ->select('a.*', 'h.name as head_name', 'u.name as decided_name');
        if ($year !== 'all') { $q->where('a.fyear', $year); }
        if ($outcome !== '') { $q->where('a.outcome', $outcome); }
        $rows = $q->orderByDesc('a.id')->paginate(50)->withQueryString();
        $ids = [];
        foreach ($rows as $r) {
            foreach (json_decode($r->matched, true) ?: [] as $m) { $ids[] = $m['rule']; }
            if ($r->applied_rule_id) { $ids[] = $r->applied_rule_id; }
            if ($r->chosen_rule_id) { $ids[] = $r->chosen_rule_id; }
        }
        $rules = $this->rulesById(array_unique($ids));
        $years = EntityFinancialYear::where('merchant_id', $merchant)->where('status', 1)->pluck('financial_year')->all();
        $year_options = FinancialYears::options($years, $year === 'all' ? FinancialYears::current() : $year, true);
        return view('rules.audit', ['rows' => $rows, 'rules' => $rules, 'year_options' => $year_options, 'selected_year' => $year, 'outcome' => $outcome]);
    }
}
