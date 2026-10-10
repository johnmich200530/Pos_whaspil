<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $primaryKey = 'staff_id';

    protected $fillable = ['employee_id', 'pin'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
