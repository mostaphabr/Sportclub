<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

/**
 * One place that knows where uploaded images live.
 *
 * Folders: "pitches", "post", "profile".
 * The database only stores the file name (e.g. "664cfc2c-montazah.jpg").
 */
class Uploads
{
    public const DEFAULT_AVATAR = 'images/profile/Default.jpg';

    /** Public URL of an uploaded image, or $fallback (an asset path) when there is none. */
    public static function url(string $folder, ?string $file, ?string $fallback = null): ?string
    {
        if ($file) {
            return asset("images/{$folder}/".basename($file));
        }

        return $fallback ? asset($fallback) : null;
    }

    /** Save an uploaded file and return the stored file name. */
    public static function store(UploadedFile $upload, string $folder, string $name): string
    {
        $upload->move(public_path("images/{$folder}"), $name);

        return $name;
    }

    /** Delete an uploaded file if it exists. */
    public static function delete(string $folder, ?string $file): void
    {
        if ($file && File::exists(self::path($folder, $file))) {
            File::delete(self::path($folder, $file));
        }
    }

    public static function path(string $folder, string $file): string
    {
        return public_path("images/{$folder}/".basename($file));
    }
}
