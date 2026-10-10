<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $primaryKey = 'Inventory_ID';

    public function getRouteKeyName(): string { return 'Inventory_ID'; }

    protected $fillable = ['Name', 'Quantity', 'Unit', 'Status'];

    public function menus()
    {
        return $this->hasMany(Menu::class, 'Inventory_ID', 'Inventory_ID');
    }
}
