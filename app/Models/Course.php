<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = ['name', 'code', 'is_archived', 'quiz_category_id', 'level_id', 'semester_id'];

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

    protected static function booted(): void
    {
        static::saving(function (Course $course) {
            // Force course name to uppercase
            if (isset($course->name)) {
                $course->name = strtoupper(trim($course->name));
            }
        });
    }

    protected function casts(): array
    {
        return ['is_archived' => 'boolean'];
    }

    public function validIndices(): HasMany
    {
        return $this->hasMany(ValidIndex::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    /** Examiners assigned to this course (Coordinator assigns via course_user table). */
    public function examiners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_user')->withTimestamps();
    }

    /** Class groups that have this course attached. */
    public function classGroups(): BelongsToMany
    {
        return $this->belongsToMany(ClassGroup::class, 'class_group_course')->withTimestamps();
    }

    /**
     * Resolve route model binding. Access control is handled by course middleware/policies.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return parent::resolveRouteBinding($value, $field);
    }
}
