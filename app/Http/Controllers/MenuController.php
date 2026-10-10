<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MenuController extends Controller
{
    use \App\Http\Controllers\Concerns\LogsAudit;

    public function index()
    {
        $menus       = Menu::with('inventory')->orderBy('Category')->orderBy('Name')->get();
        $inventories = Inventory::orderBy('Name')->get();
        return view('pos.menu', compact('menus', 'inventories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'inventory_id' => 'nullable|exists:inventories,Inventory_ID',
            'name'         => 'required|string|max:255',
            'price'        => 'required|numeric|min:0',
            'category'     => 'required|string|max:100',
            'availability' => 'boolean',
            'image'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('menu-images', 'public');
        }

        $isDrinks = strcasecmp($validated['category'], 'Drinks') === 0;

        Menu::create([
            'Inventory_ID' => $isDrinks ? ($validated['inventory_id'] ?: null) : null,
            'Name'         => $validated['name'],
            'Price'        => $validated['price'],
            'Category'     => $validated['category'],
            'Availability' => $validated['availability'] ?? true,
            'Image'        => $imagePath,
        ]);

        $this->audit('menu.created', 'Menu item added: ' . $validated['name'], $validated['name']);
        return back()->with('success', 'Menu item added.');
    }

    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'inventory_id' => 'nullable|exists:inventories,Inventory_ID',
            'name'         => 'sometimes|string|max:255',
            'price'        => 'sometimes|numeric|min:0',
            'category'     => 'sometimes|string|max:100',
            'availability' => 'sometimes|boolean',
            'image'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $data = [];
        if (isset($validated['name']))         $data['Name']         = $validated['name'];
        if (isset($validated['price']))        $data['Price']        = $validated['price'];
        if (isset($validated['category']))     $data['Category']     = $validated['category'];
        if (isset($validated['availability'])) $data['Availability'] = $validated['availability'];

        $category = $data['Category'] ?? $menu->Category;
        $isDrinks = strcasecmp($category, 'Drinks') === 0;
        $data['Inventory_ID'] = $isDrinks ? (($validated['inventory_id'] ?? null) ?: null) : null;

        if ($request->hasFile('image')) {
            if ($menu->Image) {
                Storage::disk('public')->delete($menu->Image);
            }
            $data['Image'] = $request->file('image')->store('menu-images', 'public');
        }

        $menu->update($data);
        $this->audit('menu.updated', 'Menu item updated: ' . $menu->Name, $menu->Name);
        return back()->with('success', 'Menu item updated.');
    }

    public function destroy(Menu $menu)
    {
        $name = $menu->Name;
        if ($menu->Image) {
            Storage::disk('public')->delete($menu->Image);
        }
        $menu->delete();
        $this->audit('menu.deleted', 'Menu item deleted: ' . $name, $name);
        return back()->with('success', 'Menu item removed.');
    }
}
