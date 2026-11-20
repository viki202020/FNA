<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Session;

class DepartmentController extends Controller
{
    private function merchant() { return Session::get('merchant_id'); }

    public function index()
    {
        $rows = DB::table('departments')->where('merchant_id', $this->merchant())->orderByDesc('active')->orderBy('name')->get();
        return view('settings.departments', ['rows' => $rows]);
    }

    public function store(Request $request)
    {
        $name = trim((string) $request->input('name'));
        if ($name === '' || mb_strlen($name) > 100) { return back()->withErrors(['name' => 'Enter a department name (up to 100 characters).']); }
        if (DB::table('departments')->where('merchant_id', $this->merchant())->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
            return back()->withErrors(['name' => 'That department already exists.']);
        }
        DB::table('departments')->insert(['merchant_id' => $this->merchant(), 'name' => $name, 'active' => 1, 'created_by' => Session::get('id'), 'created_at' => now(), 'updated_at' => now()]);
        return back()->with('success_msg', 'Added ' . $name . '.');
    }

    public function toggle($id)
    {
        $d = DB::table('departments')->where('merchant_id', $this->merchant())->where('id', $id)->first();
        abort_unless($d, 404);
        DB::table('departments')->where('id', $id)->update(['active' => $d->active ? 0 : 1, 'updated_at' => now()]);
        return back()->with('success_msg', $d->name . ($d->active ? ' switched off.' : ' switched on.'));
    }
}
