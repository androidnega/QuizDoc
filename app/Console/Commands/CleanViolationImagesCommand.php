<?php

namespace App\Console\Commands;

use App\Models\QuizViolation;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanViolationImagesCommand extends Command
{
    protected $signature = 'violations:clean-old-images {--days= : Override retention days (default from settings)}';

    protected $description = 'Delete violation images older than configured retention days and clear DB references';

    public function handle(): int
    {
        $days = $this->option('days');
        if ($days !== null) {
            $days = max(1, min(365, (int) $days));
        } else {
            $days = max(1, min(365, (int) Setting::getValue(Setting::KEY_VIOLATION_RETENTION_DAYS_PRIMARY, '21')));
        }

        $cutoff = now()->subDays($days)->timestamp;
        $disk = Storage::disk('public');

        if (! $disk->exists('violations')) {
            return Command::SUCCESS;
        }

        $deleted = 0;
        $cleared = 0;

        $files = $disk->allFiles('violations');
        foreach ($files as $path) {
            try {
                $lastModified = $disk->lastModified($path);
                if ($lastModified < $cutoff) {
                    $disk->delete($path);
                    $deleted++;

                    // Clear image_url for violations that pointed to this path
                    $pathForUrl = 'storage/' . $path;
                    $updated = QuizViolation::whereNotNull('image_url')
                        ->where(function ($q) use ($pathForUrl, $path) {
                            $q->where('image_url', 'like', '%' . $pathForUrl . '%')
                                ->orWhere('image_url', 'like', '%/' . $path . '%');
                        })
                        ->update(['image_url' => null]);
                    $cleared += $updated;
                }
            } catch (\Throwable $e) {
                $this->warn("Failed to process {$path}: " . $e->getMessage());
            }
        }

        if ($deleted > 0 || $cleared > 0) {
            $this->info("Deleted {$deleted} violation image(s), cleared {$cleared} DB reference(s) (older than {$days} days).");
        }

        return Command::SUCCESS;
    }
}
