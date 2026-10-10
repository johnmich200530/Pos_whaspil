<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id('Employee_ID');
            $table->string('FNM');
            $table->string('LNM');
            $table->string('Username')->unique();
            $table->enum('Role', ['super_admin', 'manager', 'cashier', 'waiter', 'chef']);
            $table->string('Password');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
