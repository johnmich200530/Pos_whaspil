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

    public function combined(Request $request)
    {
        $ids      = explode(',', $request->query('ids', ''));
        $receipts = Receipt::with('payment', 'order.orderItems.menu', 'order.employee')
            ->whereIn('Receipt_ID', $ids)
            ->get();

        if ($receipts->isEmpty()) {
            return redirect()->route('orders.active');
        }

        $combinedTotal    = $receipts->sum('Total_Amount');
        $amountTendered   = $receipts->sum(fn($r) => $r->payment->amount_tendered ?? 0);
        $method           = $receipts->first()->payment->Method ?? 'cash';
        $change           = max(0, $amountTendered - $combinedTotal);

        return view('pos.receipt-combined', compact('receipts', 'combinedTotal', 'amountTendered', 'method', 'change'));
    }
}
