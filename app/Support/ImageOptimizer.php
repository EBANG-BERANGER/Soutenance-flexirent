<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Resizes and re-encodes an uploaded photo before it ever touches disk, so
 * a partner uploading a 10 MB phone photo doesn't serve that same 10 MB to
 * every catalogue visitor. Pure GD (bundled with PHP, no Composer package):
 * this project otherwise has zero image-processing dependencies, and GD
 * covers everything these forms need (JPEG/PNG/WEBP input, JPEG output).
 *
 * Deliberately NOT used for partner supporting documents (documents_path),
 * which can legitimately be a PDF — re-encoding those as JPEG would corrupt
 * them.
 */
class ImageOptimizer
{
    /**
     * Resize + compress an upload and store it, mirroring
     * UploadedFile::store()'s signature/return value (a path relative to
     * the disk) so call sites barely change.
     */
    public static function store(UploadedFile $file, string $directory, string $disk = 'public', int $maxDimension = 1600, int $quality = 82): string
    {
        $filename = Str::random(40) . '.jpg';
        $path = trim($directory, '/') . '/' . $filename;

        return self::storeAs($file, $path, $disk, $maxDimension, $quality);
    }

    /**
     * Same, but at a caller-chosen path (mirrors UploadedFile::storeAs()) —
     * used for the homepage cover photo, which always saves as "cover.*".
     */
    public static function storeAs(UploadedFile $file, string $path, string $disk = 'public', int $maxDimension = 1600, int $quality = 82): string
    {
        $encoded = self::resizeAndEncode($file->getRealPath(), $maxDimension, $quality);

        // If GD couldn't decode it (corrupt file, unsupported format slipping
        // past the `image` validation rule), fall back to storing the
        // original untouched rather than losing the upload entirely.
        $content = $encoded ?? file_get_contents($file->getRealPath());
        Storage::disk($disk)->put($path, $content);

        return $path;
    }

    private static function resizeAndEncode(string $realPath, int $maxDimension, int $quality): ?string
    {
        $data = @file_get_contents($realPath);
        if ($data === false) {
            return null;
        }

        $source = @imagecreatefromstring($data);
        if ($source === false) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, $maxDimension / max($width, $height));
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        // Flatten onto white first: a transparent PNG re-encoded straight to
        // JPEG (which has no alpha channel) would otherwise turn black.
        $white = imagecolorallocate($resized, 255, 255, 255);
        imagefill($resized, 0, 0, $white);
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($source);

        ob_start();
        imagejpeg($resized, null, $quality);
        $encoded = ob_get_clean();
        imagedestroy($resized);

        return $encoded ?: null;
    }
}
