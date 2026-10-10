<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id('Payment_ID');
            $table->unsignedBigInteger('Order_ID');
            $table->date('Date');
            $table->enum('Method', ['cash', 'gcash', 'card']);
            $table->decimal('amount_tendered', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('Order_ID')
                ->references('Order_ID')
                ->on('orders')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
