<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Support\FinancialYears;
use App\Support\PasswordCheck;
use Session;

/** Your own password, the company's logins (admin / accountant) and re-opening the year before the current one. */
class AccountController extends Controller
{
    private function company() { return Session::get('merchant_id'); }
    private function me() { return Session::get('id'); }

    // ---- own password
    public function passwordForm() { return view('login.change-password'); }

    public function passwordSave(Request $request)
    {
        $request->validate(['old_password' => 'required', 'new_password' => 'required|string|min:10|max:100|confirmed']);
        $user = DB::table('merchant')->where('id', $this->me())->first();
        if (!$user || !PasswordCheck::user($user->email, $request->input('old_password'))) {
            return back()->withErrors(['old_password' => 'The old password is not right.']);
        }
        DB::table('merchant')->where('id', $user->id)->update(['password' => Hash::make($request->input('new_password')), 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => $this->me()]);
        return back()->with('success_msg', 'Password changed.');
    }

    // ---- logins of this company
    private function logins()
    {
        return DB::table('merchant')->where(function ($q) { $q->where('id', $this->company())->orWhere('company_id', $this->company()); })->orderBy('id')->get(['id', 'name', 'email', 'role', 'status', 'company_id', 'totp_enabled']);
    }

    public function users() { return view('account_admin.users', ['rows' => $this->logins()]); }

    public function userAdd(Request $request)
    {
        $request->validate(['name' => 'required|max:100', 'email' => 'required|email|max:200', 'role' => 'required|in:admin,accountant', 'password' => 'required|string|min:10|max:100']);
        if ($this->logins()->count() >= 3) {
            return back()->withInput()->withErrors(['A company can have at most 3 logins. Switch one off and it still counts, so ask me if you need to replace one.']);
        }
        if (DB::table('merchant')->whereRaw('LOWER(email)=?', [strtolower($request->input('email'))])->exists()) {
            return back()->withInput()->withErrors(['email' => 'That email already has a login.']);
        }
        DB::table('merchant')->insert([
            'name' => $request->input('name'), 'email' => $request->input('email'), 'mobile' => '', 'password' => Hash::make($request->input('password')),
            'status' => 1, 'role' => $request->input('role'), 'company_id' => $this->company(), 'created_by' => $this->me(), 'created_at' => date('Y-m-d H:i:s'), 'updated_by' => 0, 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return back()->with('success_msg', 'Login created for ' . $request->input('email') . '. They set up their authenticator at the first sign-in.');
    }

    private function mine($id)
    {
        $r = DB::table('merchant')->where('id', $id)->where(function ($q) { $q->where('id', $this->company())->orWhere('company_id', $this->company()); })->first();
        abort_unless($r, 404);
        return $r;
    }

    private function otherAdmins($id)
    {
        return $this->logins()->where('role', 'admin')->where('status', 1)->where('id', '!=', $id)->count();
    }

    public function userToggle($id)
    {
        $r = $this->mine($id);
        if ((int) $r->id === (int) $this->me()) { return back()->withErrors(['You cannot switch off your own login.']); }
        if ($r->status && $r->role === 'admin' && !$this->otherAdmins($r->id)) { return back()->withErrors(['The company needs at least one active admin.']); }
        DB::table('merchant')->where('id', $id)->update(['status' => $r->status ? 0 : 1]);
        return back()->with('success_msg', ($r->status ? 'Switched off ' : 'Switched on ') . $r->email . '.');
    }

    public function userRole(Request $request, $id)
    {
        $request->validate(['role' => 'required|in:admin,accountant']);
        $r = $this->mine($id);
        if ($r->role === 'admin' && $request->input('role') !== 'admin' && !$this->otherAdmins($r->id)) { return back()->withErrors(['The company needs at least one active admin.']); }
        DB::table('merchant')->where('id', $id)->update(['role' => $request->input('role')]);
        return back()->with('success_msg', $r->email . ' is now ' . $request->input('role') . '.');
    }

    // ---- re-open the year before the current one
    private function previousYear()
    {
        $cur = FinancialYears::current();
        if (!$cur || !preg_match('/^(\d{4})-(\d{4})$/', $cur, $m)) { return null; }
        $p = ($m[1] - 1) . '-' . ($m[2] - 1);
        $has = DB::table('statement')->where('merchant_id', $this->company())->where('fyear', $p)->exists();
        return $has ? $p : null;
    }

    public function reopen()
    {
        $prev = $this->previousYear();
        $open = FinancialYears::reopened();
        $log = DB::table('financial_year_reopen as r')->leftJoin('merchant as m', 'm.id', '=', 'r.reopened_by')->where('r.merchant_id', $this->company())->orderByDesc('r.id')->limit(30)->get(['r.*', 'm.email as who']);
        $blockers = in_array($prev, $open) ? FinancialYears::closeBlockers($prev) : [];
        return view('account_admin.reopen', ['current' => FinancialYears::current(), 'prev' => $prev, 'open' => $open, 'log' => $log, 'blockers' => $blockers]);
    }

    public function reopenSave(Request $request)
    {
        $request->validate(['reason' => 'required|string|min:10|max:500']);
        $prev = $this->previousYear();
        if (!$prev) { return back()->withErrors(['There is no earlier year to re-open.']); }
        if (in_array($prev, FinancialYears::reopened())) { return back()->withErrors([$prev . ' is already re-opened.']); }
        DB::table('financial_year_reopen')->insert(['merchant_id' => $this->company(), 'financial_year' => $prev, 'reason' => $request->input('reason'), 'reopened_by' => $this->me(), 'reopened_at' => now()]);
        return back()->with('success_msg', $prev . ' is open again. After your changes, close it again here and check the opening balances of the year after it.');
    }

    public function closeAgain()
    {
        $prev = $this->previousYear();
        $open = FinancialYears::reopened();
        if (!$prev || !in_array($prev, $open)) { return back()->withErrors(['Nothing to close again.']); }
        $blockers = FinancialYears::closeBlockers($prev);
        if (count($blockers)) { return back()->withErrors(array_merge(['Cannot close ' . $prev . ' yet:'], array_column($blockers, 'message'))); }
        DB::table('financial_year_reopen')->where('merchant_id', $this->company())->where('financial_year', $prev)->whereNull('closed_again_at')->update(['closed_again_at' => now(), 'closed_again_by' => $this->me()]);
        return back()->with('success_msg', $prev . ' is closed again.');
    }
}
