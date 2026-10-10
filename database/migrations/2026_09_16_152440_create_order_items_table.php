<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id('OrderItem_ID');
            $table->unsignedBigInteger('Order_ID');
            $table->unsignedBigInteger('Menu_ID');
            $table->integer('Quantity');
            $table->decimal('Subtotal', 10, 2);
            $table->timestamps();

            $table->foreign('Order_ID')
                ->references('Order_ID')
                ->on('orders')
                ->onDelete('cascade');

            $table->foreign('Menu_ID')
                ->references('Menu_ID')
                ->on('menus')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
