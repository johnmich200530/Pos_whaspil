<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    /**
     * Returns the stored GCash QR path/URL, or null if not set.
     * Works for both Cloudinary URLs and local storage paths.
     */
    public static function gcashQrPath(): ?string
    {
        $pointer = 'settings/gcash_qr_path.txt';

        if (!Storage::disk('public')->exists($pointer)) {
            return null;
        }

        $path = trim(Storage::disk('public')->get($pointer));
        if ($path === '') {
            return null;
        }

        // Cloudinary URL — always valid, no local check needed
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        // Local path — verify it exists
        if (!Storage::disk('public')->exists($path)) {
            return null;
        }

        return $path;
    }

    public function updateGcashQr(Request $request)
    {
        $request->validate([
            'gcash_qr' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        // Delete old QR (Cloudinary or local)
        $old = static::gcashQrPath();
        if ($old) {
            if (str_starts_with($old, 'http')) {
                // Delete from Cloudinary
                try {
                    $urlPath  = parse_url($old, PHP_URL_PATH);
                    preg_match('#/upload/(?:v\d+/)?(.+)\.[a-z]+$#i', $urlPath, $matches);
                    $publicId = $matches[1] ?? null;
                    if ($publicId) {
                        app(\Cloudinary\Cloudinary::class)->uploadApi()->destroy($publicId);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Cloudinary QR delete failed: ' . $e->getMessage());
                }
            } else {
                Storage::disk('public')->delete($old);
            }
        }

        // Upload new QR
        $cloudinaryUrl = config('cloudinary.cloud_url');

        if (!empty($cloudinaryUrl)) {
            try {
                $result = app(\Cloudinary\Cloudinary::class)
                    ->uploadApi()
                    ->upload($request->file('gcash_qr')->getRealPath(), [
                        'folder'          => 'settings',
                        'public_id'       => 'gcash_qr',
                        'overwrite'       => true,
                        'resource_type'   => 'image',
                    ]);
                $newPath = $result['secure_url'];
            } catch (\Throwable $e) {
                Log::warning('Cloudinary QR upload failed, falling back to local: ' . $e->getMessage());
                $newPath = $request->file('gcash_qr')->store('settings', 'public');
            }
        } else {
            Log::warning('CLOUDINARY_URL not set — storing GCash QR locally.');
            $newPath = $request->file('gcash_qr')->store('settings', 'public');
        }

        // Save pointer
        Storage::disk('public')->put('settings/gcash_qr_path.txt', $newPath);

        return back()->with('success', 'GCash QR code updated.');
    }
}
