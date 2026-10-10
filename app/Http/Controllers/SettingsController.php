<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public static function gcashQrPath(): ?string
    {
        $pointer = 'settings/gcash_qr_path.txt';
        if (!Storage::disk('public')->exists($pointer)) {
            return null;
        }

        $path = trim(Storage::disk('public')->get($pointer));
        if ($path === '' || !Storage::disk('public')->exists($path)) {
            return null;
        }

        return $path;
    }

    public function updateGcashQr(Request $request)
    {
        $request->validate([
            'gcash_qr' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        $old = static::gcashQrPath();
        if ($old) {
            Storage::disk('public')->delete($old);
        }

        $path = $request->file('gcash_qr')->store('settings', 'public');
        Storage::disk('public')->put('settings/gcash_qr_path.txt', $path);

        return back()->with('success', 'GCash QR code updated.');
    }
}
