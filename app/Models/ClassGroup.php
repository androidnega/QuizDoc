<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassGroup extends Model
{
    /** Soft accent colors for cards (no gradient). Keys used in DB; values are Tailwind bg/border classes. */
    public const ACCENT_COLORS = [
        'sky'    => ['bg' => 'bg-sky-50', 'border' => 'border-sky-200', 'text' => 'text-sky-800'],
        'emerald'=> ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'text' => 'text-emerald-800'],
        'amber'  => ['bg' => 'bg-amber-50', 'border' => 'border-amber-200', 'text' => 'text-amber-800'],
        'violet' => ['bg' => 'bg-violet-50', 'border' => 'border-violet-200', 'text' => 'text-violet-800'],
        'rose'   => ['bg' => 'bg-rose-50', 'border' => 'border-rose-200', 'text' => 'text-rose-800'],
        'teal'   => ['bg' => 'bg-teal-50', 'border' => 'border-teal-200', 'text' => 'text-teal-800'],
        'indigo' => ['bg' => 'bg-indigo-50', 'border' => 'border-indigo-200', 'text' => 'text-indigo-800'],
        'slate'  => ['bg' => 'bg-slate-100', 'border' => 'border-slate-200', 'text' => 'text-slate-800'],
    ];

    protected $fillable = ['name', 'examiner_id', 'quiz_category_id', 'level_id', 'semester_id', 'academic_year_id', 'academic_class_id', 'accent_color'];

    /** Soft accent per group (no gradient). When accent_color is set use it; otherwise vary by id so groups get different colors. */
    public function getAccentClassesAttribute(): array
    {
        $keys = array_keys(self::ACCENT_COLORS);
        $key = $this->accent_color && isset(self::ACCENT_COLORS[$this->accent_color])
            ? $this->accent_color
            : $keys[((int) $this->id) % count($keys)];
        return self::ACCENT_COLORS[$key];
    }

    /** Pick next accent from palette (round-robin) for auto-assign. */
    public static function nextAccentColor(): string
    {
        $keys = array_keys(self::ACCENT_COLORS);
        $idx = self::query()->count() % count($keys);
        return $keys[$idx];
    }

    public function examiner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'examiner_id');
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

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(\App\Models\DocuMentor\AcademicYear::class, 'academic_year_id');
    }

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'academic_class_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(ClassGroupStudent::class, 'class_group_id');
    }

    /** Courses in this class with lecturer per course (pivot has examiner_id). */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'class_group_course')
            ->withPivot('examiner_id')
            ->withTimestamps();
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'class_group_id');
    }

    /** Whether this class group has at least one student (required for quiz creation). */
    public function hasStudents(): bool
    {
        return $this->students()->exists();
    }

    /** Display name with level appended (e.g. "BTECH IT GROUP A - Level 100"). Updates when level_id changes. */
    public function getDisplayNameAttribute(): string
    {
        $name = $this->attributes['name'] ?? '';
        $level = $this->relationLoaded('level') ? $this->level : $this->level;
        if ($level && $level->label) {
            return trim($name) . ' - ' . $level->label;
        }
        return trim($name);
    }

    /**
     * Resolve route model binding. Access control is handled by ClassGroupPolicy.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return parent::resolveRouteBinding($value, $field);
    }
}
