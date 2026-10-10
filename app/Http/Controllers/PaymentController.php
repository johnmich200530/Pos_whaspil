<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Order;
use App\Models\Receipt;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PaymentController extends Controller
{
    use \App\Http\Controllers\Concerns\LogsAudit;

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id'        => 'required|exists:orders,Order_ID',
            'method'          => 'required|in:cash,gcash',
            'amount_tendered' => 'nullable|numeric|min:0',
        ]);

        $order = Order::findOrFail($validated['order_id']);

        if (in_array($order->Status, ['paid', 'cancelled'])) {
            return back()->withErrors(['order_id' => 'This order has already been paid or cancelled.']);
        }

        $tendered = $validated['method'] === 'gcash'
            ? $order->Total_Amount
            : ($validated['amount_tendered'] ?? 0);

        if ($validated['method'] === 'cash' && $tendered < $order->Total_Amount) {
            return back()->withErrors(['amount_tendered' => 'Amount tendered must cover the order total.']);
        }

        $payment = Payment::create([
            'Order_ID'        => $order->Order_ID,
            'Date'            => Carbon::today(),
            'Method'          => $validated['method'],
            'amount_tendered' => $tendered,
        ]);

        $order->update(['Status' => 'paid']);

        $receipt = Receipt::create([
            'Payment_ID'   => $payment->Payment_ID,
            'Order_ID'     => $order->Order_ID,
            'Date'         => Carbon::today(),
            'Total_Amount' => $order->Total_Amount,
            'Status'       => 'issued',
        ]);

        $this->audit(
            'payment.processed',
            'Payment via ' . $validated['method'] . ' for ' . $order->Table_number . ' — ₱' . $order->Total_Amount,
            $order->Table_number
        );

        return redirect()->route('receipt.show', $receipt)->with('success', 'Payment processed!');
    }

    public function show(Payment $payment)
    {
        return $payment->load('order');
    }
}
