<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;

class AuditController extends Controller
{
    public function index()
    {
        $logs = AuditLog::with('employee')
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('pos.audit', compact('logs'));
    }
}
