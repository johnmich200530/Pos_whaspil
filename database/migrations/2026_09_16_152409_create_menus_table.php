<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id('Menu_ID');
            $table->unsignedBigInteger('Inventory_ID')->nullable();
            $table->string('Name');
            $table->decimal('Price', 8, 2);
            $table->string('Category');
            $table->boolean('Availability')->default(true);
            $table->timestamps();

            $table->foreign('Inventory_ID')
                ->references('Inventory_ID')
                ->on('inventories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
