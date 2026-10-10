<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $primaryKey = 'Order_ID';
    public function getRouteKeyName(): string { return 'Order_ID'; }

    protected $fillable = ['Employee_ID', 'Table_number', 'Date', 'Total_Amount', 'Status'];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'Employee_ID', 'Employee_ID');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'Order_ID', 'Order_ID');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'Order_ID', 'Order_ID');
    }

    public function receipt()
    {
        return $this->hasOne(Receipt::class, 'Order_ID', 'Order_ID');
    }
}
