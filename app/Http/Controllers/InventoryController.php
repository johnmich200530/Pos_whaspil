<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    use \App\Http\Controllers\Concerns\LogsAudit;

    public function index()
    {
        $search   = request('search');
        $status   = request('status', 'all');
        $category = request('category', 'all');

        $inventories = Inventory::when($search, fn($q) => $q->where('Name', 'like', "%{$search}%"))
            ->when($status && $status !== 'all', fn($q) => $q->where('Status', $status))
            ->when($category && $category !== 'all', fn($q) => $q->where('Category', $category))
            ->orderBy('Status')
            ->orderBy('Name')
            ->paginate(8)
            ->withQueryString();

        $lowCount  = Inventory::where('Status', 'low_stock')->count();
        $outCount  = Inventory::where('Status', 'out_of_stock')->count();
        $categories = Inventory::whereNotNull('Category')->where('Category', '!=', '')
            ->distinct()->orderBy('Category')->pluck('Category');

        return view('pos.inventory', compact('inventories', 'lowCount', 'outCount', 'search', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'category' => 'nullable|string|max:50',
            'quantity' => 'required|integer|min:0',
            'unit'     => 'required|string|max:50',
        ]);

        $status = $validated['quantity'] <= 0 ? 'out_of_stock'
                : ($validated['quantity'] < 10 ? 'low_stock' : 'in_stock');

        Inventory::create([
            'Name'     => $validated['name'],
            'Category' => $validated['category'] ?: null,
            'Quantity' => $validated['quantity'],
            'Unit'     => $validated['unit'],
            'Status'   => $status,
        ]);

        $this->audit('inventory.created', 'Stock item added: ' . $validated['name'] . ' (' . $validated['quantity'] . ' ' . $validated['unit'] . ')', $validated['name']);

        return back()->with('success', 'Item added to inventory.');
    }

    public function update(Request $request, Inventory $inventory)
    {
        $validated = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'category' => 'sometimes|nullable|string|max:50',
            'quantity' => 'sometimes|integer|min:0',
            'unit'     => 'sometimes|string|max:50',
        ]);

        $data = [];
        if (isset($validated['name']))     $data['Name']     = $validated['name'];
        if (array_key_exists('category', $validated)) $data['Category'] = $validated['category'] ?: null;
        if (isset($validated['unit']))     $data['Unit']     = $validated['unit'];
        if (isset($validated['quantity'])) {
            $qty = $validated['quantity'];
            $data['Quantity'] = $qty;
            $data['Status']   = $qty <= 0 ? 'out_of_stock' : ($qty < 10 ? 'low_stock' : 'in_stock');

            // Sync linked menu availability
            \App\Models\Menu::where('Inventory_ID', $inventory->Inventory_ID)
                ->update(['Availability' => $qty > 0]);
        }

        $inventory->update($data);

        $this->audit('inventory.updated', 'Stock updated: ' . $inventory->Name . ' → ' . ($data['Quantity'] ?? $inventory->Quantity) . ' ' . $inventory->Unit, $inventory->Name);

        return back()->with('success', 'Inventory updated.');
    }

    public function destroy(Inventory $inventory)
    {
        $name = $inventory->Name;
        $inventory->delete();
        $this->audit('inventory.deleted', 'Stock item removed: ' . $name, $name);
        return back()->with('success', 'Item removed.');
    }
}
