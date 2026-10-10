<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('action');        // e.g. order.created
            $table->string('description');   // human-readable
            $table->string('subject')->nullable(); // e.g. "Table 3", "Coca-Cola"
            $table->timestamps();

            $table->foreign('employee_id')->references('Employee_ID')->on('employees')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
