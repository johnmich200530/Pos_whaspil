<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    use \App\Http\Controllers\Concerns\LogsAudit;

    public function index()
    {
        $search = request('search');

        $inventories = Inventory::when($search, fn($q) => $q->where('Name', 'like', "%{$search}%"))
            ->orderBy('Status')
            ->orderBy('Name')
            ->get();

        $lowCount = Inventory::whereIn('Status', ['low_stock', 'out_of_stock'])->count();

        return view('pos.inventory', compact('inventories', 'lowCount', 'search'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'unit'     => 'required|string|max:50',
        ]);

        $status = $validated['quantity'] <= 0 ? 'out_of_stock'
                : ($validated['quantity'] < 10 ? 'low_stock' : 'in_stock');

        Inventory::create([
            'Name'     => $validated['name'],
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
            'quantity' => 'sometimes|integer|min:0',
            'unit'     => 'sometimes|string|max:50',
        ]);

        $data = [];
        if (isset($validated['name']))     $data['Name']     = $validated['name'];
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
