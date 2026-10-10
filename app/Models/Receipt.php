<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    protected $primaryKey = 'Receipt_ID';
    public function getRouteKeyName(): string { return 'Receipt_ID'; }

    protected $fillable = ['Payment_ID', 'Order_ID', 'Date', 'Total_Amount', 'Status'];

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'Payment_ID', 'Payment_ID');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'Order_ID', 'Order_ID');
    }
}
