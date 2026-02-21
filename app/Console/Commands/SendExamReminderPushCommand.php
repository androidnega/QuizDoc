<?php

namespace App\Console\Commands;

use App\Models\ClassGroupStudent;
use App\Models\ExamCalendar;
use App\Models\PushSubscription;
use App\Models\Student;
use Illuminate\Console\Command;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class SendExamReminderPushCommand extends Command
{
    protected $signature = 'exam:send-reminder-push {--window=55,65 : Minutes from now for exam start (min,max)}';
    protected $description = 'Send browser push notifications 1 hour before exam calendar entries. Run via cron every 10–15 min.';

    public function handle(): int
    {
        $publicKey = config('services.webpush.vapid_public');
        $privateKey = config('services.webpush.vapid_private');
        if (!$publicKey || !$privateKey) {
            $this->warn('VAPID keys not set. Add VAPID_PUBLIC_KEY and VAPID_PRIVATE_KEY to .env. Generate with: php artisan exam:generate-vapid');
            return self::FAILURE;
        }

        $window = $this->option('window');
        [$minMin, $maxMin] = array_map('intval', explode(',', $window));
        $from = now()->addMinutes($minMin);
        $to = now()->addMinutes($maxMin);

        $entries = ExamCalendar::with('course')
            ->whereBetween('scheduled_at', [$from, $to])
            ->get();

        if ($entries->isEmpty()) {
            return self::SUCCESS;
        }

        $auth = [
            'VAPID' => [
                'subject' => config('app.url'),
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ];

        $webPush = new WebPush($auth);
        $sent = 0;

        foreach ($entries as $entry) {
            $courseName = $entry->course_display;
            $title = 'Exam in about 1 hour';
            $body = $courseName . ' · ' . $entry->scheduled_at->format('g:i A');
            $payload = json_encode(['title' => $title, 'body' => $body]);

            $indexNumbers = ClassGroupStudent::where('class_group_id', $entry->class_group_id)
                ->pluck('index_number')
                ->unique()
                ->filter();
            $normalized = $indexNumbers->map(fn ($i) => strtolower(trim($i ?? '')))->unique()->filter()->values();
            $studentIds = $normalized->isEmpty()
                ? collect()
                : Student::whereRaw('LOWER(TRIM(index_number)) IN (' . $normalized->map(fn () => '?')->join(',') . ')', $normalized->toArray())->pluck('id');

            $subscriptions = PushSubscription::whereIn('student_id', $studentIds)->get();
            foreach ($subscriptions as $sub) {
                try {
                    $subscription = Subscription::create([
                        'endpoint' => $sub->endpoint,
                        'keys' => [
                            'p256dh' => $sub->public_key,
                            'auth' => $sub->auth_token,
                        ],
                    ]);
                    $webPush->queue($subscription, $payload);
                    $sent++;
                } catch (\Throwable $e) {
                    $this->warn("Push failed for subscription {$sub->id}: " . $e->getMessage());
                }
            }
        }

        $reported = $webPush->flush();
        if ($sent > 0) {
            $this->info("Queued {$sent} exam reminder push(es).");
        }
        return self::SUCCESS;
    }
}
