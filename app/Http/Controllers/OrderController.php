<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Menu;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrderController extends Controller
{
    use \App\Http\Controllers\Concerns\LogsAudit;

    public function tables()
    {
        $activeTables = Order::whereIn('Status', ['pending', 'preparing', 'served'])
            ->get(['Table_number', 'Status', 'Order_ID', 'Total_Amount'])
            ->keyBy('Table_number');

        return view('pos.tables', compact('activeTables'));
    }

    public function index()
    {
        return Order::with('employee', 'orderItems.menu')->get();
    }

    public function create()
    {
        $menus = Menu::where('Availability', true)->get()
            ->groupBy('Category')
            ->sortKeys(SORT_NATURAL | SORT_FLAG_CASE);
        $categories = $menus->keys();

        $activeTables = Order::whereIn('Status', ['pending', 'preparing', 'served'])
            ->get(['Table_number', 'Status'])
            ->keyBy('Table_number');

        $takeoutCount = Order::whereDate('Date', Carbon::today())
            ->where('Table_number', 'like', 'Takeout #%')
            ->count();
        $nextTakeout = 'Takeout #' . ($takeoutCount + 1);

        return view('pos.new-order', compact('menus', 'categories', 'activeTables', 'nextTakeout'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'table_number'         => 'required|string|max:50',
            'items'                => 'required|array|min:1',
            'items.*.menu_id'      => 'required|exists:menus,Menu_ID',
            'items.*.quantity'     => 'required|integer|min:1',
        ]);

        $employee = session('pos_employee');

        $order = DB::transaction(function () use ($validated, $employee) {
            $order = Order::create([
                'Employee_ID'  => $employee->Employee_ID,
                'Table_number' => $validated['table_number'],
                'Date'         => Carbon::today(),
                'Total_Amount' => 0,
                'Status'       => 'pending',
            ]);

            $total = 0;

            foreach ($validated['items'] as $item) {
                $menu     = Menu::findOrFail($item['menu_id']);
                $subtotal = $menu->Price * $item['quantity'];
                $total   += $subtotal;

                OrderItem::create([
                    'Order_ID' => $order->Order_ID,
                    'Menu_ID'  => $menu->Menu_ID,
                    'Quantity' => $item['quantity'],
                    'Subtotal' => $subtotal,
                ]);

                // Auto-deduct inventory
                $inventory = $menu->inventory;
                if ($inventory) {
                    $newQty = max(0, $inventory->Quantity - $item['quantity']);
                    $inventory->update([
                        'Quantity' => $newQty,
                        'Status'   => $newQty <= 0 ? 'out_of_stock'
                            : ($newQty < 10 ? 'low_stock' : 'in_stock'),
                    ]);

                    if ($newQty <= 0) {
                        Menu::where('Inventory_ID', $inventory->Inventory_ID)
                            ->update(['Availability' => false]);
                    }
                }
            }

            $order->update(['Total_Amount' => $total]);
            return $order;
        });

        $this->audit('order.created', 'New order placed for ' . $validated['table_number'], $validated['table_number']);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'order_id' => $order->Order_ID]);
        }

        return redirect()->route('orders.create')->with('success', 'Order sent to kitchen!');
    }

    public function active()
    {
        $orders = Order::with('employee', 'orderItems.menu')
            ->whereIn('Status', ['pending', 'preparing', 'served'])
            ->orderByDesc('updated_at')
            ->get();

        return view('pos.active-orders', compact('orders'));
    }

    // JSON endpoint for real-time polling
    public function poll()
    {
        $orders = Order::with('orderItems.menu')
            ->whereIn('Status', ['pending', 'preparing', 'served'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn($o) => [
                'id'          => $o->Order_ID,
                'table'       => $o->Table_number,
                'status'      => $o->Status,
                'total'       => $o->Total_Amount,
                'updated_at'  => $o->updated_at?->diffForHumans(),
                'time'        => $o->created_at?->format('h:i A'),
                'items_count' => $o->orderItems->count(),
                'items'       => $o->orderItems->take(3)->map(fn($i) => [
                    'qty'  => $i->Quantity,
                    'name' => $i->menu->Name ?? '—',
                ]),
                'items_extra' => max(0, $o->orderItems->count() - 3),
                'is_takeout'  => str_starts_with($o->Table_number, 'Takeout'),
            ]);

        return response()->json(['orders' => $orders]);
    }

    public function show(Order $order)
    {
        if ($order->Status === 'cancelled') {
            return redirect()->route('orders.active')->with('success', 'Order cancelled.');
        }

        $order->load('employee', 'orderItems.menu', 'payment', 'receipt');
        $gcashQr = SettingsController::gcashQrPath();
        return view('pos.order-detail', compact('order', 'gcashQr'));
    }

    public function statusJson(Order $order)
    {
        return response()->json(['status' => $order->Status]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,preparing,served,paid,cancelled',
        ]);

        $order->update(['Status' => $validated['status']]);

        if ($validated['status'] === 'cancelled') {
            $this->audit('order.cancelled', 'Order cancelled for ' . $order->Table_number, $order->Table_number);
            return redirect()->route('orders.active')->with('success', 'Order cancelled.');
        }

        $this->audit('order.status', 'Order status updated to ' . $validated['status'] . ' for ' . $order->Table_number, $order->Table_number);
        return back()->with('success', 'Order status updated.');
    }

    public function updateItem(Request $request, OrderItem $orderItem)
    {
        $validated = $request->validate(['quantity' => 'required|integer|min:1']);

        $order  = $orderItem->order;
        $menu   = $orderItem->menu;
        $oldQty = $orderItem->Quantity;
        $newQty = $validated['quantity'];
        $diff   = $newQty - $oldQty;

        $orderItem->update([
            'Quantity' => $newQty,
            'Subtotal' => $menu->Price * $newQty,
        ]);

        $order->update(['Total_Amount' => $order->orderItems()->sum('Subtotal')]);

        if ($diff !== 0 && $menu->inventory) {
            $inv    = $menu->inventory;
            $invQty = $diff > 0 ? max(0, $inv->Quantity - $diff) : $inv->Quantity + abs($diff);
            $inv->update([
                'Quantity' => $invQty,
                'Status'   => $invQty <= 0 ? 'out_of_stock' : ($invQty < 10 ? 'low_stock' : 'in_stock'),
            ]);
            Menu::where('Inventory_ID', $inv->Inventory_ID)
                ->update(['Availability' => $invQty > 0]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Item updated.');
    }

    public function destroyItem(OrderItem $orderItem)
    {
        $order = $orderItem->order;
        $menu  = $orderItem->menu;
        $qty   = $orderItem->Quantity;

        $orderItem->delete();

        // Restore inventory
        if ($menu->inventory) {
            $inv    = $menu->inventory;
            $invQty = $inv->Quantity + $qty;
            $inv->update([
                'Quantity' => $invQty,
                'Status'   => $invQty <= 0 ? 'out_of_stock' : ($invQty < 10 ? 'low_stock' : 'in_stock'),
            ]);
            Menu::where('Inventory_ID', $inv->Inventory_ID)
                ->update(['Availability' => $invQty > 0]);
        }

        // Delete order if no items left
        if ($order->orderItems()->count() === 0) {
            $order->delete();
            return response()->json(['success' => true, 'order_deleted' => true]);
        }

        $order->update(['Total_Amount' => $order->orderItems()->sum('Subtotal')]);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'order_deleted' => false]);
        }
        return back()->with('success', 'Item removed.');
    }

    public function destroy(Order $order)
    {
        $this->audit('order.deleted', 'Order deleted for ' . $order->Table_number, $order->Table_number);
        $order->delete();
        return response()->noContent();
    }
}
