<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $primaryKey = 'Menu_ID';

    public function getRouteKeyName(): string { return 'Menu_ID'; }

    protected $fillable = ['Inventory_ID', 'Name', 'Price', 'Category', 'Availability', 'Image'];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'Inventory_ID', 'Inventory_ID');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'Menu_ID', 'Menu_ID');
    }
}
