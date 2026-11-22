<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Lets only an admin login through: closing books, re-opening years and changing setup. */
class AdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        if (session('role', 'admin') !== 'admin') {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'data' => '', 'message' => 'Only an admin can do this.'], 200);
            }
            return response()->view('errors.admin_only', [], 403);
        }
        return $next($request);
    }
}
