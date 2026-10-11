<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Inventory;
use App\Models\MenuIngredient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MenuController extends Controller
{
    use \App\Http\Controllers\Concerns\LogsAudit;

    private function uploadImage($file): string
    {
        $cloudinaryUrl = config('cloudinary.cloud_url');
        if (!empty($cloudinaryUrl)) {
            $uploaded = app(\Cloudinary\Cloudinary::class)
                ->uploadApi()
                ->upload($file->getRealPath(), ['folder' => 'menu-images']);
            return $uploaded['secure_url'];
        }
        Log::warning('CLOUDINARY_URL not set — storing image locally.');
        return $file->store('menu-images', 'public');
    }

    private function deleteImage(string $path): void
    {
        if (str_contains($path, 'cloudinary.com')) {
            try {
                $publicId = pathinfo(parse_url($path, PHP_URL_PATH), PATHINFO_FILENAME);
                app(\Cloudinary\Cloudinary::class)->uploadApi()->destroy('menu-images/' . $publicId);
            } catch (\Throwable $e) {
                Log::warning('Cloudinary delete failed: ' . $e->getMessage());
            }
        } else {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Sync ingredients for a menu item.
     * $ingredients = array of ['inventory_id' => X, 'quantity_used' => Y]
     */
    private function syncIngredients(Menu $menu, array $ingredients): void
    {
        $menu->ingredients()->delete();
        foreach ($ingredients as $ing) {
            if (!empty($ing['inventory_id']) && isset($ing['quantity_used']) && $ing['quantity_used'] > 0) {
                MenuIngredient::create([
                    'Menu_ID'       => $menu->Menu_ID,
                    'Inventory_ID'  => $ing['inventory_id'],
                    'quantity_used' => $ing['quantity_used'],
                ]);
            }
        }
    }

    public function index()
    {
        $menus       = Menu::with('inventory', 'ingredients.inventory')->orderBy('Category')->orderBy('Name')->get();
        $inventories = Inventory::orderBy('Name')->get();
        return view('pos.menu', compact('menus', 'inventories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'inventory_id'              => 'nullable|exists:inventories,Inventory_ID',
            'name'                      => 'required|string|max:255',
            'price'                     => 'required|numeric|min:0',
            'category'                  => 'required|string|max:100',
            'availability'              => 'boolean',
            'image'                     => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'ingredients'               => 'nullable|array',
            'ingredients.*.inventory_id'=> 'required|exists:inventories,Inventory_ID',
            'ingredients.*.quantity_used'=> 'required|numeric|min:0.001',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->uploadImage($request->file('image'));
        }

        $isDrinks = strcasecmp($validated['category'], 'Drinks') === 0;

        $menu = Menu::create([
            'Inventory_ID' => $isDrinks ? ($validated['inventory_id'] ?: null) : null,
            'Name'         => $validated['name'],
            'Price'        => $validated['price'],
            'Category'     => $validated['category'],
            'Availability' => $validated['availability'] ?? true,
            'Image'        => $imagePath,
        ]);

        // Save ingredients (for all non-drinks categories)
        if (!$isDrinks && !empty($validated['ingredients'])) {
            $this->syncIngredients($menu, $validated['ingredients']);
        }

        $this->audit('menu.created', 'Menu item added: ' . $validated['name'], $validated['name']);
        return back()->with('success', 'Menu item added.');
    }

    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'inventory_id'               => 'nullable|exists:inventories,Inventory_ID',
            'name'                       => 'sometimes|string|max:255',
            'price'                      => 'sometimes|numeric|min:0',
            'category'                   => 'sometimes|string|max:100',
            'availability'               => 'sometimes|boolean',
            'image'                      => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'ingredients'                => 'nullable|array',
            'ingredients.*.inventory_id' => 'required|exists:inventories,Inventory_ID',
            'ingredients.*.quantity_used'=> 'required|numeric|min:0.001',
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
            if ($menu->Image) $this->deleteImage($menu->Image);
            $data['Image'] = $this->uploadImage($request->file('image'));
        }

        $menu->update($data);

        // Sync ingredients
        if (!$isDrinks) {
            $this->syncIngredients($menu, $validated['ingredients'] ?? []);
        } else {
            $menu->ingredients()->delete();
        }

        $this->audit('menu.updated', 'Menu item updated: ' . $menu->Name, $menu->Name);
        return back()->with('success', 'Menu item updated.');
    }

    public function destroy(Menu $menu)
    {
        $name = $menu->Name;
        if ($menu->Image) $this->deleteImage($menu->Image);
        $menu->delete();
        $this->audit('menu.deleted', 'Menu item deleted: ' . $name, $name);
        return back()->with('success', 'Menu item removed.');
    }
}
