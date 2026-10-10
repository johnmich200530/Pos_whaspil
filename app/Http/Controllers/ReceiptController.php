<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_id'   => 'required|exists:payments,id',
            'order_id'     => 'required|exists:orders,id',
            'date'         => 'required|date',
            'total_amount' => 'required|numeric|min:0',
        ]);

        return Receipt::create($validated + ['status' => 'issued']);
    }

    public function show(Receipt $receipt)
    {
        $receipt->load('payment.order.orderItems.menu', 'order.employee');
        return view('pos.receipt', compact('receipt'));
    }
}
