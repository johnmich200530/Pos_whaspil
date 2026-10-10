<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PosAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('pos_employee')) {
            return redirect()->route('login');
        }
        return $next($request);
    }
}
