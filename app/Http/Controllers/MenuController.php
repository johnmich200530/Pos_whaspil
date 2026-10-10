<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class MenuController extends Controller
{
    use \App\Http\Controllers\Concerns\LogsAudit;

    /**
     * Upload an image to Cloudinary (preferred) or local disk as fallback.
     */
    private function uploadImage($file): string
    {
        $cloudinaryUrl = config('cloudinary.cloud_url');

        if (!empty($cloudinaryUrl)) {
            $uploaded = Cloudinary::upload($file->getRealPath(), [
                'folder' => 'menu-images',
            ]);
            return $uploaded->getSecurePath();
        }

        // Fallback: local public disk
        Log::warning('CLOUDINARY_URL not set — storing image locally.');
        return $file->store('menu-images', 'public');
    }

    /**
     * Delete an image from wherever it was stored.
     */
    private function deleteImage(string $path): void
    {
        if (str_contains($path, 'cloudinary.com')) {
            try {
                $publicId = pathinfo(parse_url($path, PHP_URL_PATH), PATHINFO_FILENAME);
                Cloudinary::destroy('menu-images/' . $publicId);
            } catch (\Throwable $e) {
                Log::warning('Cloudinary delete failed: ' . $e->getMessage());
            }
        } else {
            Storage::disk('public')->delete($path);
        }
    }

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
            $imagePath = $this->uploadImage($request->file('image'));
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
                $this->deleteImage($menu->Image);
            }
            $data['Image'] = $this->uploadImage($request->file('image'));
        }

        $menu->update($data);
        $this->audit('menu.updated', 'Menu item updated: ' . $menu->Name, $menu->Name);
        return back()->with('success', 'Menu item updated.');
    }

    public function destroy(Menu $menu)
    {
        $name = $menu->Name;
        if ($menu->Image) {
            $this->deleteImage($menu->Image);
        }
        $menu->delete();
        $this->audit('menu.deleted', 'Menu item deleted: ' . $name, $name);
        return back()->with('success', 'Menu item removed.');
    }
}
