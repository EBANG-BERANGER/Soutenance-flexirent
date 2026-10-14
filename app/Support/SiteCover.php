<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Locates the admin-uploaded homepage cover photo, if one has been set.
 * Stored as a fixed file (site/cover.{ext}) rather than a settings table,
 * since there's only ever one of it. Shared between AdminSettingsController
 * (which writes it) and VehicleController (which reads it for the homepage).
 */
class SiteCover
{
    private const DIR = 'site';

    public static function path(): ?string
    {
        foreach (Storage::disk('public')->files(self::DIR) as $file) {
            if (str_starts_with(basename($file), 'cover.')) {
                return $file;
            }
        }

        return null;
    }
}
