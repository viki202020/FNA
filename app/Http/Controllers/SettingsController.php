<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\BillingSettings;
use Session;

/** The accounts that invoices, bills, payments and tax post to. Changes apply to future postings only. */
class SettingsController extends Controller
{
    public function index()
    {
        $heads = DB::table('head')->where('status', 1)->orderBy('name')->get(['id', 'name']);
        $values = [];
        foreach (BillingSettings::LABELS as $k => $l) { $values[$k] = BillingSettings::get($k); }
        $log = DB::table('fna_settings_log')->where('merchant_id', Session::get('merchant_id'))->orderByDesc('id')->limit(10)->get();
        $names = DB::table('head')->pluck('name', 'id');
        $users = DB::table('merchant')->pluck('name', 'id');
        return view('settings.posting', ['heads' => $heads, 'values' => $values, 'labels' => BillingSettings::LABELS, 'defaults' => BillingSettings::DEFAULTS, 'log' => $log, 'names' => $names, 'users' => $users]);
    }

    public function save(Request $request)
    {
        $m = Session::get('merchant_id');
        $changed = 0;
        foreach (BillingSettings::LABELS as $k => $l) {
            $new = (int) $request->input($k);
            if (!$new) { continue; }
            if (!DB::table('head')->where('id', $new)->where('status', 1)->exists()) { return back()->withErrors([$k => 'Choose an active account for ' . $l[0] . '.']); }
            $old = BillingSettings::get($k);
            if ($old === $new) { continue; }
            DB::table('fna_settings')->updateOrInsert(['merchant_id' => $m, 'skey' => $k], ['svalue' => (string) $new]);
            DB::table('fna_settings_log')->insert(['merchant_id' => $m, 'skey' => $k, 'old_head' => $old, 'new_head' => $new, 'changed_by' => Session::get('id'), 'created_at' => now(), 'updated_at' => now()]);
            $changed++;
        }
        return back()->with('success_msg', $changed ? $changed . ' setting(s) saved. They apply to what you post from now on; nothing already posted changes.' : 'Nothing changed.');
    }
}
