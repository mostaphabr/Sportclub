<?php

namespace App\Console\Commands;

use App\Support\Uploads;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * One-time move of old uploads from public/images/{pitches,post,profile}
 * to storage/app/public/{pitches,post,profile}.
 *
 *   php artisan sportclub:move-uploads          (copy, then delete the old files)
 *   php artisan sportclub:move-uploads --keep   (copy only)
 */
class MoveUploads extends Command
{
    protected $signature = 'sportclub:move-uploads {--keep : Copy only, keep the old files in public/images}';

    protected $description = 'Move uploaded images from public/images to storage/app/public';

    public function handle(): int
    {
        $rows = [];

        foreach (Uploads::FOLDERS as $folder) {
            $dir = Uploads::legacyPath($folder);
            $copied = $skipped = 0;

            if (File::isDirectory($dir)) {
                foreach (File::files($dir) as $file) {
                    $name = $file->getFilename();

                    if ($name === 'Default.jpg' || str_starts_with($name, '.')) {
                        continue;
                    }

                    if (! Uploads::disk()->exists("{$folder}/{$name}")) {
                        Uploads::disk()->put("{$folder}/{$name}", File::get($file->getPathname()));
                        $copied++;
                    } else {
                        $skipped++;
                    }

                    if (! $this->option('keep')) {
                        File::delete($file->getPathname());
                    }
                }
            }

            $rows[] = [$folder, $copied, $skipped];
        }

        $this->table(['Folder', 'Moved', 'Already in storage'], $rows);

        if (! File::exists(public_path('storage'))) {
            $this->warn('Run "php artisan storage:link" so the web server can show these images directly.');
        }

        $this->info('Done. Images now live in storage/app/public.');

        return self::SUCCESS;
    }
}
