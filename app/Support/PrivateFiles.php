<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Contracts and partner documents contain personal data (ID numbers,
 * driving licences), so they are saved on the private disk
 * (storage/app/private) instead of the public one. Files on the private
 * disk have no URL: they can only be downloaded through a controller that
 * checks who is asking.
 */
class PrivateFiles
{
    // Old files were saved on the public disk: move them to the private disk.
    public static function ensurePrivate(?string $path): bool
    {
        if (! $path) {
            return false;
        }

        if (Storage::disk('local')->exists($path)) {
            return true;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('local')->put($path, Storage::disk('public')->get($path));
            Storage::disk('public')->delete($path);

            return true;
        }

        return false;
    }

    public static function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('local')->delete($path);
            Storage::disk('public')->delete($path);
        }
    }
}
