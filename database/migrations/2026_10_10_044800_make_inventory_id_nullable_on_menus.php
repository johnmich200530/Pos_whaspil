<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropForeign(['Inventory_ID']);
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->unsignedBigInteger('Inventory_ID')->nullable()->change();
            $table->foreign('Inventory_ID')
                ->references('Inventory_ID')
                ->on('inventories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropForeign(['Inventory_ID']);
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->unsignedBigInteger('Inventory_ID')->nullable(false)->change();
            $table->foreign('Inventory_ID')
                ->references('Inventory_ID')
                ->on('inventories')
                ->cascadeOnDelete();
        });
    }
};
