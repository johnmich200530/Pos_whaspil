<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = ['employee_id', 'action', 'description', 'subject'];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'Employee_ID');
    }
}
