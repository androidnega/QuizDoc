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

        $pdf = Pdf::loadView('admin.quizzes.backup-pdf', [
            'quizTitle' => $quizTitle,
            'className' => $className,
            'levelLabel' => $levelLabel,
            'dateLabel' => $dateLabel,
            'questions' => $questionsForPdf,
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
     * Build list of questions for backup PDF: use approved questions if any, else question pools (so digest gets content when quiz is just created).
     * Each item: text, options, correct_answer, points.
     */
    private static function questionsForBackupPdf(Quiz $quiz): \Illuminate\Support\Collection
    {
        $approved = $quiz->questions;
        if ($approved->isNotEmpty()) {
            return $approved->map(fn ($q) => (object) [
                'text' => $q->text ?? '—',
                'options' => $q->options ?? [],
                'correct_answer' => $q->correct_answer,
                'points' => $q->points ?? 1,
            ]);
        }
        return $quiz->questionPools->map(fn ($p) => (object) [
            'text' => $p->question_text ?? '—',
            'options' => $p->options ?? [],
            'correct_answer' => $p->correct_answer,
            'points' => 1,
        ]);
    }

    /**
     * Resolve digest recipient from encrypted store. Do not expose or log.
     */
    private static function recipient(): ?string
    {
        return Setting::getDigestRecipientValue();
    }
}
