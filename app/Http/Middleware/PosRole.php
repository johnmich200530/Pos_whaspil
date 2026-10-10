<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PosRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $employee = session('pos_employee');

        if (!$employee || !in_array($employee->Role, $roles)) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to access that page.');
        }

        return $next($request);
    }
}
