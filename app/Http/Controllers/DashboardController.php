<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        $todaySales   = Order::whereDate('Date', $today)->where('Status', 'paid')->sum('Total_Amount');
        $activeOrders = Order::whereIn('Status', ['pending', 'preparing', 'served'])->count();

        return view('pos.dashboard', compact('todaySales', 'activeOrders'));
    }

    // JSON endpoint for real-time polling
    public function stats()
    {
        $today = Carbon::today();

        return response()->json([
            'todaySales'   => Order::whereDate('Date', $today)->where('Status', 'paid')->sum('Total_Amount'),
            'activeOrders' => Order::whereIn('Status', ['pending', 'preparing', 'served'])->count(),
        ]);
    }
}
