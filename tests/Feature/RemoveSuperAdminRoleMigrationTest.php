<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RemoveSuperAdminRoleMigrationTest extends TestCase
{
    public function test_super_admin_role_is_removed_without_sqlite_errors(): void
    {
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::dropIfExists('employees');
        Schema::create('employees', function (Blueprint $table) {
            $table->id('Employee_ID');
            $table->string('FNM');
            $table->string('LNM');
            $table->string('Username');
            $table->string('Role');
            $table->string('Password');
            $table->timestamps();
        });

        DB::table('employees')->insert([
            [
                'FNM' => 'Alice',
                'LNM' => 'Admin',
                'Username' => 'alice.admin',
                'Role' => 'super_admin',
                'Password' => 'secret',
            ],
            [
                'FNM' => 'Bob',
                'LNM' => 'Cashier',
                'Username' => 'bob.cashier',
                'Role' => 'cashier',
                'Password' => 'secret',
            ],
        ]);

        $migration = require base_path('database/migrations/2026_10_10_000000_remove_super_admin_role.php');

        $migration->up();

        $this->assertDatabaseMissing('employees', ['Role' => 'super_admin']);
        $this->assertDatabaseHas('employees', ['Role' => 'cashier']);

        Schema::dropIfExists('employees');
    }
}
