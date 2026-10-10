<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id('Receipt_ID');
            $table->unsignedBigInteger('Payment_ID');
            $table->unsignedBigInteger('Order_ID');
            $table->date('Date');
            $table->decimal('Total_Amount', 10, 2);
            $table->enum('Status', ['issued', 'voided'])->default('issued');
            $table->timestamps();

            $table->foreign('Payment_ID')
                ->references('Payment_ID')
                ->on('payments')
                ->onDelete('cascade');

            $table->foreign('Order_ID')
                ->references('Order_ID')
                ->on('orders')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
