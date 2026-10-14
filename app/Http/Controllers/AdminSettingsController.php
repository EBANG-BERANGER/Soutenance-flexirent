<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use App\Support\ImageOptimizer;
use App\Support\SiteCover;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Site-wide settings that aren't tied to any single vehicle, category or
 * user: currently just the homepage cover photo.
 */
class AdminSettingsController extends Controller
{
    private const COVER_DIR = 'site';

    public function edit()
    {
        return view('admin.settings', ['coverPath' => SiteCover::path()]);
    }

    public function updateCoverPhoto(Request $request)
    {
        $request->validate([
            'cover_photo' => ['required', 'image', 'max:5120'],
        ]);

        // Remove any previous cover (whatever extension it had) before
        // storing the new one, so stale files don't pile up.
        $this->deleteExistingCover();

        // ImageOptimizer always re-encodes to JPEG, so the stored filename
        // is always "cover.jpg" now regardless of what was uploaded.
        ImageOptimizer::storeAs($request->file('cover_photo'), self::COVER_DIR . '/cover.jpg', 'public', 1920);

        AdminActivityLog::record('updated', auth()->user(), 'Updated the homepage cover photo');

        return back()->with('status', 'Homepage cover photo updated.');
    }

    public function destroyCoverPhoto()
    {
        $this->deleteExistingCover();
        AdminActivityLog::record('updated', auth()->user(), 'Removed the homepage cover photo');

        return back()->with('status', 'Homepage cover photo removed. The homepage will fall back to a vehicle photo.');
    }

    private function deleteExistingCover(): void
    {
        foreach (Storage::disk('public')->files(self::COVER_DIR) as $file) {
            if (str_starts_with(basename($file), 'cover.')) {
                Storage::disk('public')->delete($file);
            }
        }
    }
}
