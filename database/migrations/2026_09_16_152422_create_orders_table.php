<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id('Order_ID');
            $table->unsignedBigInteger('Employee_ID');
            $table->string('Table_number');
            $table->date('Date');
            $table->decimal('Total_Amount', 10, 2)->default(0);
            $table->enum('Status', ['pending', 'preparing', 'served', 'paid', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->foreign('Employee_ID')
                ->references('Employee_ID')
                ->on('employees')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
