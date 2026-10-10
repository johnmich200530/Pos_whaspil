<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    protected $primaryKey = 'admin_id';

    protected $fillable = ['employee_id', 'username', 'password'];

    protected $hidden = ['password'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
