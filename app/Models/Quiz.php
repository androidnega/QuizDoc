<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Quiz extends Model
{
    /** Result visibility: score_only, full_review_after_end, disabled */
    public const RESULT_VISIBILITY_SCORE_ONLY = 'score_only';
    public const RESULT_VISIBILITY_FULL_REVIEW_AFTER_END = 'full_review_after_end';
    public const RESULT_VISIBILITY_DISABLED = 'disabled';

    /** Exam type for PDF/reports: quiz, midsem, end_of_semester */
    public const EXAM_TYPE_QUIZ = 'quiz';
    public const EXAM_TYPE_MIDSEM = 'midsem';
    public const EXAM_TYPE_END_OF_SEMESTER = 'end_of_semester';

    protected $fillable = [
        'link_token', 'class_group_id', 'title', 'exam_type', 'topics', 'script_url', 'script_public_id', 'script_text',
        'number_of_questions', 'questions_per_student', 'duration_minutes', 'course_id', 'is_active', 'is_published', 'starts_at', 'ends_at', 'result_visibility',
        'academic_year_id', 'quiz_category_id', 'level_id', 'semester_id', 'academic_class_id', 'examiner_id', 'status',
    ];

    public const STATUS_DRAFT = 'Draft';
    public const STATUS_PUBLISHED = 'Published';
    /** Display status: published but window not yet open (starts_at in future). */
    public const STATUS_SCHEDULED = 'Scheduled';
    /** Display status: published and window open (between starts_at and ends_at). */
    public const STATUS_ACTIVE = 'Active';

    protected static function booted(): void
    {
        static::creating(function (Quiz $quiz) {
            if (empty($quiz->link_token)) {
                $quiz->link_token = self::generateUniqueLinkToken();
            }
        });
    }

    /**
     * Resolve route model binding. Access control is handled by QuizPolicy.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return parent::resolveRouteBinding($value, $field);
    }

    /** Generate a unique token: alphanumeric with hyphen (e.g. KTdie54-3Sx9). */
    public static function generateUniqueLinkToken(): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        do {
            $part1 = '';
            $part2 = '';
            for ($i = 0; $i < 8; $i++) {
                $part1 .= $chars[random_int(0, strlen($chars) - 1)];
            }
            for ($i = 0; $i < 6; $i++) {
                $part2 .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $token = $part1 . '-' . $part2;
        } while (static::where('link_token', $token)->exists());
        return $token;
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_published' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /** Quizzes whose window is still open (no ends_at or ends_at in future), excluding unpublished ones with completed sessions. */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where(function (Builder $b) {
            $b->whereNull('ends_at')->orWhere('ends_at', '>', now());
        })->where(function (Builder $b) {
            $b->where('is_published', true)
                ->orWhereDoesntHave('sessions', fn (Builder $sq) => $sq->whereNotNull('ended_at'));
        });
    }

    /** Quizzes whose window has ended: ends_at in the past, or unpublished with at least one completed session. */
    public function scopeEnded(Builder $q): Builder
    {
        $now = now();
        return $q->where(function (Builder $b) use ($now) {
            $b->where(function (Builder $b2) use ($now) {
                $b2->whereNotNull('ends_at')->where('ends_at', '<=', $now);
            })->orWhere(function (Builder $b2) {
                $b2->where('is_published', false)
                    ->whereHas('sessions', fn (Builder $sq) => $sq->whereNotNull('ended_at'));
            });
        });
    }

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(\App\Models\DocuMentor\AcademicYear::class, 'academic_year_id');
    }

    public function quizCategory(): BelongsTo
    {
        return $this->belongsTo(QuizCategory::class, 'quiz_category_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(StudentLevel::class, 'level_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'academic_class_id');
    }

    public function examiner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'examiner_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function questionPools(): HasMany
    {
        return $this->hasMany(QuestionPool::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(QuizSession::class, 'quiz_id');
    }

    /**
     * Number of questions each student receives (from pool). Uses questions_per_student when set, else number_of_questions.
     */
    public function getQuestionsPerStudent(): int
    {
        $v = $this->questions_per_student ?? $this->number_of_questions;
        return (int) max(1, $v);
    }

    /**
     * Whether the quiz has enough approved questions for students to take it.
     * Approved count must be >= questions_per_student.
     * Uses eager-loaded questions_count when present to avoid N+1.
     */
    public function hasEnoughApprovedQuestions(): bool
    {
        $count = $this->getAttribute('questions_count');
        if ($count !== null) {
            return (int) $count >= $this->getQuestionsPerStudent();
        }
        return $this->questions()->count() >= $this->getQuestionsPerStudent();
    }

    /**
     * Whether the quiz is active and ready (enough approved questions).
     * Students cannot take the quiz until approval is complete.
     */
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        if (!$this->hasEnoughApprovedQuestions()) {
            return false;
        }
        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }
        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }
        return true;
    }

    /**
     * Whether the quiz has ended (ends_at is set and in the past).
     * When true, the student link is expired; examiner can still view questions and scores.
     */
    public function hasEnded(): bool
    {
        return $this->ends_at !== null && $this->ends_at->isPast();
    }

    /**
     * Whether at least one student has started this quiz (session has start_time set).
     * Once true, examiner cannot edit the quiz.
     * Uses eager-loaded sessions_started_count when present to avoid N+1.
     */
    public function hasStarted(): bool
    {
        $count = $this->getAttribute('sessions_started_count');
        if ($count !== null) {
            return (int) $count > 0;
        }
        return $this->sessions()->whereNotNull('start_time')->exists();
    }

    /**
     * Whether the quiz window is still open for review (no ends_at or ends_at in the future).
     */
    public function isReviewAvailable(): bool
    {
        return $this->ends_at === null || $this->ends_at->isFuture();
    }

    /**
     * Whether the student can see full answer review (questions, their answers, correct answers, explanations).
     * When result_visibility is "full_review_after_end", review is shown on the result page once the student has submitted.
     */
    public function canShowFullReview(): bool
    {
        $visibility = $this->getAttribute('result_visibility') ?? self::RESULT_VISIBILITY_FULL_REVIEW_AFTER_END;
        if ($visibility === self::RESULT_VISIBILITY_DISABLED) {
            return false;
        }
        if ($visibility === self::RESULT_VISIBILITY_SCORE_ONLY) {
            return false;
        }
        if ($visibility === self::RESULT_VISIBILITY_FULL_REVIEW_AFTER_END) {
            return true;
        }
        return false;
    }

    /**
     * Whether the student can see their score and stats (correct count, etc.).
     */
    public function canShowScore(): bool
    {
        $visibility = $this->getAttribute('result_visibility') ?? self::RESULT_VISIBILITY_FULL_REVIEW_AFTER_END;
        return $visibility !== self::RESULT_VISIBILITY_DISABLED;
    }

    public static function resultVisibilityOptions(): array
    {
        return [
            self::RESULT_VISIBILITY_SCORE_ONLY => 'Immediately (score only)',
            self::RESULT_VISIBILITY_FULL_REVIEW_AFTER_END => 'After Deadline (full review)',
            self::RESULT_VISIBILITY_DISABLED => 'Manual Release (no score or review until released)',
        ];
    }

    /**
     * Display status for examiner UI: Draft | Scheduled | Active | Ended.
     * Draft = not published. Scheduled = published, starts_at in future. Active = published and window open. Ended = published and ends_at past.
     */
    public function getDisplayStatus(): string
    {
        if (! $this->is_published) {
            return self::STATUS_DRAFT;
        }
        $now = now();
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return self::STATUS_SCHEDULED;
        }
        if ($this->ends_at && $this->ends_at->isPast()) {
            return 'Ended';
        }
        return self::STATUS_ACTIVE;
    }

    public static function examTypeOptions(): array
    {
        return [
            self::EXAM_TYPE_QUIZ => 'Quiz',
            self::EXAM_TYPE_MIDSEM => 'Midsem',
            self::EXAM_TYPE_END_OF_SEMESTER => 'End of Semester',
        ];
    }

    /** Human-readable exam type for PDF/reports. */
    public function getExamTypeLabel(): string
    {
        $options = self::examTypeOptions();
        return $options[$this->exam_type] ?? $this->title;
    }
}
