<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->string('Category', 50)->nullable()->after('Name');
        });
    }

    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn('Category');
        });
    }
};
