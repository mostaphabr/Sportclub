<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * One place that knows where uploaded images live.
 *
 * Folders: "pitches", "post", "profile". The database only stores the file name.
 *
 * New uploads are saved in storage/app/public/{folder} (not in Git).
 * Older uploads still in public/images/{folder} keep working, and
 * `php artisan sportclub:move-uploads` moves them to storage.
 */
class Uploads
{
    public const FOLDERS = ['pitches', 'post', 'profile'];

    public const DEFAULT_AVATAR = 'SportClub/assets/img/default-avatar.jpg';

    /** Public URL of an uploaded image, or $fallback (an asset path) when there is none. */
    public static function url(string $folder, ?string $file, ?string $fallback = null): ?string
    {
        if (! $file) {
            return $fallback ? asset($fallback) : null;
        }

        $file = basename($file);

        // Old location (before uploads moved to storage)
        if (! self::disk()->exists("{$folder}/{$file}") && File::exists(self::legacyPath($folder, $file))) {
            return asset("images/{$folder}/{$file}");
        }

        // `php artisan storage:link` makes storage/app/public available at /storage
        if (File::exists(public_path('storage'))) {
            return asset("storage/{$folder}/{$file}");
        }

        // No link yet: Laravel serves the file itself (see the "media" route).
        return route('media', ['folder' => $folder, 'file' => $file]);
    }

    /** Save an uploaded file and return the stored file name. */
    public static function store(UploadedFile $upload, string $folder, string $name): string
    {
        $name = basename($name);
        $upload->storeAs($folder, $name, 'public');

        return $name;
    }

    /** Delete an uploaded file wherever it is. */
    public static function delete(string $folder, ?string $file): void
    {
        if (! $file) {
            return;
        }

        $file = basename($file);
        self::disk()->delete("{$folder}/{$file}");

        if (File::exists(self::legacyPath($folder, $file))) {
            File::delete(self::legacyPath($folder, $file));
        }
    }

    public static function legacyPath(string $folder, string $file = ''): string
    {
        $root = rtrim(config('sportclub.legacy_uploads_path', public_path('images')), '/\\');

        return $root.DIRECTORY_SEPARATOR.$folder.($file !== '' ? DIRECTORY_SEPARATOR.basename($file) : '');
    }

    public static function disk()
    {
        return Storage::disk('public');
    }
}
