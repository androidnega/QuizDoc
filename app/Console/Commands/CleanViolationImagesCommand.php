<?php

namespace App\Console\Commands;

use App\Models\QuizSession;
use App\Models\QuizViolation;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanViolationImagesCommand extends Command
{
    protected $signature = 'violations:clean-old-images {--days= : Override retention days (default from settings)}';

    protected $description = 'Delete violation and verification images older than configured retention days and clear DB references';

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
        $deleted = 0;
        $cleared = 0;
        $verificationCleared = 0;

        if ($disk->exists('violations')) {
            $files = $disk->allFiles('violations');
            foreach ($files as $path) {
                try {
                    $lastModified = $disk->lastModified($path);
                    if ($lastModified < $cutoff) {
                        $disk->delete($path);
                        $deleted++;
                        $pathForUrl = 'storage/' . $path;
                        $updated = QuizViolation::whereNotNull('image_url')
                            ->where(function ($q) use ($pathForUrl, $path) {
                                $q->where('image_url', 'like', '%' . $pathForUrl . '%')
                                    ->orWhere('image_url', 'like', '%/' . $path . '%')
                                    ->orWhere('image_url', 'like', '%' . $path . '%');
                            })
                            ->update(['image_url' => null]);
                        $cleared += $updated;
                    }
                } catch (\Throwable $e) {
                    $this->warn("Failed to process {$path}: " . $e->getMessage());
                }
            }
        }

        if ($disk->exists('verification')) {
            $files = $disk->allFiles('verification');
            foreach ($files as $path) {
                try {
                    if ($disk->lastModified($path) < $cutoff) {
                        $disk->delete($path);
                        $deleted++;
                        $pre = QuizSession::where('pre_face_image', $path)->update(['pre_face_image' => null]);
                        $post = QuizSession::where('post_face_image', $path)->update(['post_face_image' => null]);
                        $verificationCleared += $pre + $post;
                    }
                } catch (\Throwable $e) {
                    $this->warn("Failed to process verification {$path}: " . $e->getMessage());
                }
            }
        }

        if ($deleted > 0 || $cleared > 0 || $verificationCleared > 0) {
            $this->info("Deleted {$deleted} image(s), cleared {$cleared} violation ref(s), {$verificationCleared} verification ref(s) (older than {$days} days).");
        }

        return Command::SUCCESS;
    }
}
