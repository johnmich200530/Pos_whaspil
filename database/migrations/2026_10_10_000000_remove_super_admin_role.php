<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('employees')->where('Role', 'super_admin')->delete();

        DB::statement("ALTER TABLE employees MODIFY Role ENUM('manager', 'cashier', 'waiter', 'chef') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE employees MODIFY Role ENUM('super_admin', 'manager', 'cashier', 'waiter', 'chef') NOT NULL");
    }
};
