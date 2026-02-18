<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Coordinator-managed academic classes: e.g. "BTECH IT Level 100".
 * Belongs to Category, Level, Academic Year.
 */
class AcademicClass extends Model
{
    protected $table = 'academic_classes';

    protected $fillable = ['name', 'quiz_category_id', 'level_id', 'academic_year_id'];

    public function quizCategory(): BelongsTo
    {
        return $this->belongsTo(QuizCategory::class, 'quiz_category_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(StudentLevel::class, 'level_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(\App\Models\DocuMentor\AcademicYear::class, 'academic_year_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'academic_class_id');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'academic_class_id');
    }

    public function classGroups(): HasMany
    {
        return $this->hasMany(ClassGroup::class, 'academic_class_id');
    }

    /** Display label: "BTECH IT Level 100 (2025/2026)" */
    public function getDisplayLabelAttribute(): string
    {
        $parts = [$this->name];
        if ($this->academicYear) {
            $parts[] = '(' . $this->academicYear->year . ')';
        }
        return implode(' ', $parts);
    }
}
