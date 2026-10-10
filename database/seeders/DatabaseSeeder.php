<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Employee::create([
            'FNM'      => 'Maria',
            'LNM'      => 'Santos',
            'Role'     => 'manager',
            'Username' => 'maria@username',
            'Password' => Hash::make('maria2026'),
        ]);
    }
}
