<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_ingredients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('Menu_ID');
            $table->unsignedBigInteger('Inventory_ID');
            $table->decimal('quantity_used', 10, 3)->default(1); // amount deducted per serving
            $table->timestamps();

            $table->foreign('Menu_ID')->references('Menu_ID')->on('menus')->onDelete('cascade');
            $table->foreign('Inventory_ID')->references('Inventory_ID')->on('inventories')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_ingredients');
    }
};
