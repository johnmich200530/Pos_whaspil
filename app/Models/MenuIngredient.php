<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuIngredient extends Model
{
    protected $fillable = ['Menu_ID', 'Inventory_ID', 'quantity_used'];

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'Menu_ID', 'Menu_ID');
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'Inventory_ID', 'Inventory_ID');
    }
}
