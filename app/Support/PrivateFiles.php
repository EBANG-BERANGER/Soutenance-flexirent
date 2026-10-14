<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Sensitive files (rental contracts, partner ID / business documents) live
 * on the private "local" disk (storage/app/private), which has no public
 * URL: they can only be reached through a controller that checks who is
 * asking. Older files were written to the public disk, where anyone could
 * open /storage/contracts/reservation-1.pdf, -2.pdf, ... directly; this
 * helper moves such a file to the private disk the first time it's used.
 */
class PrivateFiles
{
    public const DISK = 'local';

    /** Make sure $path is on the private disk; returns false if it exists nowhere. */
    public static function ensurePrivate(?string $path): bool
    {
        if (! $path) {
            return false;
        }

        $private = Storage::disk(self::DISK);
        if ($private->exists($path)) {
            return true;
        }

        $public = Storage::disk('public');
        if ($public->exists($path)) {
            $private->put($path, $public->get($path));
            $public->delete($path);

            return true;
        }

        return false;
    }

    public static function delete(?string $path): void
    {
        if (! $path) {
            return;
        }
        Storage::disk(self::DISK)->delete($path);
        Storage::disk('public')->delete($path);
    }
}
