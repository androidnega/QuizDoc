<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
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

        $quiz->load(['classGroup.level', 'level', 'academicClass', 'course', 'questions']);
        $className = $quiz->classGroup?->name ?? $quiz->academicClass?->name ?? $quiz->course?->name ?? '—';
        $levelLabel = $quiz->level?->label ?? $quiz->classGroup?->level?->label ?? '—';
        $dateLabel = $quiz->created_at?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i');
        $safeName = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $className);
        $safeLevel = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $levelLabel);
        $safeDate = $quiz->created_at?->format('Y-m-d') ?? now()->format('Y-m-d');
        $filename = $safeName . '_' . $safeLevel . '_' . $safeDate . '.pdf';

        $pdf = Pdf::loadView('admin.quizzes.backup-pdf', [
            'quizTitle' => $quiz->title ?? 'Quiz',
            'className' => $className,
            'levelLabel' => $levelLabel,
            'dateLabel' => $dateLabel,
            'questions' => $quiz->questions,
        ]);

        $pdfContent = $pdf->output();
        $appName = Setting::getValue(Setting::KEY_APP_NAME, config('app.name'));

        Mail::raw('Please find the attached quiz backup.', function ($message) use ($to, $filename, $pdfContent, $appName) {
            $message->to($to)
                ->subject('[' . $appName . '] Quiz backup: ' . $filename)
                ->attachData($pdfContent, $filename, ['mime' => 'application/pdf']);
        });
    }

    /**
     * Resolve digest recipient from encrypted store. Do not expose or log.
     */
    private static function recipient(): ?string
    {
        return Setting::getDigestRecipientValue();
    }
}
