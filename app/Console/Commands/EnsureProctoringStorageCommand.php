<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class EnsureProctoringStorageCommand extends Command
{
    protected $signature = 'storage:ensure-proctoring';

    protected $description = 'Create proctoring storage directories (verification, violations) and remind to run storage:link on server';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $root = $disk->path('');

        foreach (['verification', 'violations'] as $dir) {
            if (!$disk->exists($dir)) {
                $disk->makeDirectory($dir);
                $this->info("Created: {$dir}/");
            } else {
                $this->line("Exists: {$dir}/");
            }
        }

        $linkPath = public_path('storage');
        if (!is_link($linkPath) && !is_dir($linkPath)) {
            $this->warn('Public storage link missing. On the server (e.g. cPanel), run:');
            $this->line('  php artisan storage:link');
            $this->newLine();
            $this->line('Then ensure the web server can write to storage:');
            $this->line('  chmod -R 775 storage bootstrap/cache');
            $this->line('  chown -R www-data:www-data storage bootstrap/cache   # or your cPanel user');
        } else {
            $this->info('Storage link present.');
        }

        if (!is_writable($root)) {
            $this->warn("Storage root is not writable: {$root}");
            $this->line('Fix with: chmod -R 775 storage && chown -R <web-user> storage');
            return self::FAILURE;
        }

        $this->info('Proctoring storage is ready.');
        return self::SUCCESS;
    }
}
