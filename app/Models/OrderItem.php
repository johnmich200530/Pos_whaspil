<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $primaryKey = 'OrderItem_ID';
    public function getRouteKeyName(): string { return 'OrderItem_ID'; }

    protected $fillable = ['Order_ID', 'Menu_ID', 'Quantity', 'Subtotal'];

    public function order()
    {
        return $this->belongsTo(Order::class, 'Order_ID', 'Order_ID');
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'Menu_ID', 'Menu_ID');
    }
}
