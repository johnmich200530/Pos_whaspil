<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;

class AuditController extends Controller
{
    public function index()
    {
        $search = request('search');
        $type   = request('type');

        $logs = AuditLog::with('employee')
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            }))
            ->when($type, fn($q) => $q->where('action', 'like', "{$type}.%"))
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        return view('pos.audit', compact('logs'));
    }
}
