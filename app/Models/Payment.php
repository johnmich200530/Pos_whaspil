<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $primaryKey = 'Payment_ID';
    public function getRouteKeyName(): string { return 'Payment_ID'; }

    protected $fillable = ['Order_ID', 'Date', 'Method', 'amount_tendered'];

    public function order()
    {
        return $this->belongsTo(Order::class, 'Order_ID', 'Order_ID');
    }

    public function receipt()
    {
        return $this->hasOne(Receipt::class, 'Payment_ID', 'Payment_ID');
    }
}
