<?php

namespace App\Http\Controllers;

use App\Support\Uploads;

/**
 * Serves uploaded images from storage/app/public when the
 * `php artisan storage:link` link has not been created yet.
 */
class MediaController extends Controller
{
    public function show(string $folder, string $file)
    {
        abort_unless(in_array($folder, Uploads::FOLDERS, true), 404);

        $path = $folder.'/'.basename($file);
        abort_unless(Uploads::disk()->exists($path), 404);

        return response()->file(Uploads::disk()->path($path), [
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
