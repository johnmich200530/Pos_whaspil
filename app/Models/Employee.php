<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $primaryKey = 'Employee_ID';
    public function getRouteKeyName(): string { return 'Employee_ID'; }

    protected $fillable = ['FNM', 'LNM', 'Username', 'Role', 'Password'];

    protected $hidden = ['Password'];

    public function orders()
    {
        return $this->hasMany(Order::class, 'Employee_ID', 'Employee_ID');
    }

    public function isManager(): bool
    {
        return $this->Role === 'manager';
    }
}
