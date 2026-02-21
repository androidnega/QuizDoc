<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class QuizBackupService
{
    /**
     * If a digest recipient is configured, generate a PDF of the quiz (class, level, date, Q&A) and send it.
     * Recipient is read from encrypted setting; do not log or expose.
     */
    public static function sendIfConfigured(Quiz $quiz): void
    {
        $to = self::recipient();
        if ($to === null || trim($to) === '') {
            return;
        }
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            Log::warning('QuizBackupService: digest recipient is not a valid email, skipping.', ['quiz_id' => $quiz->id]);
            return;
        }

        $quiz->load(['classGroup.level', 'level', 'academicClass', 'course', 'questions', 'questionPools']);
        $className = $quiz->classGroup?->name ?? $quiz->academicClass?->name ?? $quiz->course?->name ?? '—';
        $levelLabel = $quiz->level?->label ?? $quiz->classGroup?->level?->label ?? '—';
        $dateLabel = $quiz->created_at?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i');
        $quizTitle = $quiz->title ?? 'Quiz';
        $safeName = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $className);
        $safeTitle = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $quizTitle);
        $safeDate = $quiz->created_at?->format('Y-m-d') ?? now()->format('Y-m-d');
        $filename = $safeName . '_' . $safeTitle . '_' . $safeDate . '.pdf';

        $questionsForPdf = self::questionsForBackupPdf($quiz);

        try {
            $pdf = Pdf::loadView('admin.quizzes.backup-pdf', [
                'quizTitle' => $quizTitle,
                'className' => $className,
                'levelLabel' => $levelLabel,
                'dateLabel' => $dateLabel,
                'questions' => $questionsForPdf,
            ]);
            $pdfContent = $pdf->output();
        } catch (\Throwable $e) {
            Log::error('QuizBackupService: PDF generation failed.', ['quiz_id' => $quiz->id, 'message' => $e->getMessage()]);
            throw $e;
        }

        $appName = Setting::getValue(Setting::KEY_APP_NAME, config('app.name'));
        self::applyMailConfigFromSettings();

        try {
            Mail::raw('Please find the attached quiz backup (questions and marking scheme).', function ($message) use ($to, $filename, $pdfContent, $appName) {
                $message->to($to)
                    ->subject('[' . $appName . '] Quiz backup: ' . $filename)
                    ->attachData($pdfContent, $filename, ['mime' => 'application/pdf']);
            });
        } catch (\Throwable $e) {
            Log::error('QuizBackupService: sending digest email failed.', ['quiz_id' => $quiz->id, 'to' => $to, 'message' => $e->getMessage()]);
            throw $e;
        }

        Log::info('QuizBackupService: digest sent.', ['quiz_id' => $quiz->id, 'filename' => $filename]);
    }

    /**
     * Apply mail config from Settings so digest uses the same SMTP/from as the rest of the app.
     */
    private static function applyMailConfigFromSettings(): void
    {
        $mailer = Setting::getValue(Setting::KEY_MAIL_MAILER, config('mail.default'));
        $host = Setting::getValue(Setting::KEY_MAIL_HOST, config('mail.mailers.smtp.host'));
        $port = (int) Setting::getValue(Setting::KEY_MAIL_PORT, (string) (config('mail.mailers.smtp.port') ?? 587));
        $username = Setting::getValue(Setting::KEY_MAIL_USERNAME);
        $password = Setting::getValue(Setting::KEY_MAIL_PASSWORD);
        $encryption = Setting::getValue(Setting::KEY_MAIL_ENCRYPTION, 'tls');
        $fromAddress = Setting::getValue(Setting::KEY_MAIL_FROM_ADDRESS, config('mail.from.address'));
        $fromName = Setting::getValue(Setting::KEY_MAIL_FROM_NAME, config('mail.from.name'));

        Config::set('mail.default', $mailer);
        Config::set('mail.from.address', $fromAddress ?: 'noreply@quizsnap.local');
        Config::set('mail.from.name', $fromName ?: 'QuizSnap');
        Config::set('mail.mailers.smtp.host', $host);
        Config::set('mail.mailers.smtp.port', $port);
        Config::set('mail.mailers.smtp.username', $username);
        Config::set('mail.mailers.smtp.password', $password);
        Config::set('mail.mailers.smtp.encryption', $encryption ?: null);
    }

    /**
     * Build list of questions for backup PDF: use approved questions if any, else question pools (so digest gets content when quiz is just created).
     * Each item: text, options, correct_answer, points.
     */
    private static function questionsForBackupPdf(Quiz $quiz): \Illuminate\Support\Collection
    {
        $approved = $quiz->questions->map(fn ($q) => (object) [
            'text' => $q->text ?? '—',
            'options' => $q->options ?? [],
            'correct_answer' => $q->correct_answer,
            'points' => $q->points ?? 1,
        ]);

        // When approved questions exist, also include still-pending pools so digest reflects all generated/sent questions.
        $pendingPools = $quiz->questionPools
            ->filter(fn ($p) => !$p->is_approved)
            ->map(fn ($p) => (object) [
                'text' => $p->question_text ?? '—',
                'options' => $p->options ?? [],
                'correct_answer' => $p->correct_answer,
                'points' => 1,
            ]);

        if ($approved->isNotEmpty()) {
            return $approved->concat($pendingPools)->values();
        }

        return $quiz->questionPools->map(fn ($p) => (object) [
            'text' => $p->question_text ?? '—',
            'options' => $p->options ?? [],
            'correct_answer' => $p->correct_answer,
            'points' => 1,
        ])->values();
    }

    /**
     * Resolve digest recipient from encrypted store. Do not expose or log.
     */
    private static function recipient(): ?string
    {
        return Setting::getDigestRecipientValue();
    }
}
