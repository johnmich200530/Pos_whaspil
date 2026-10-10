<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AuditLog;

trait LogsAudit
{
    protected function audit(string $action, string $description, string $subject = null): void
    {
        $employee = session('pos_employee');
        AuditLog::create([
            'employee_id' => $employee?->Employee_ID,
            'action'      => $action,
            'description' => $description,
            'subject'     => $subject,
        ]);
    }
}
