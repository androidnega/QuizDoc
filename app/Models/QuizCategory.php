<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * QuizSnap categories: HND, BTECH, Diploma, Top Up.
 * Distinct from Docu Mentor project categories.
 */
class QuizCategory extends Model
{
    protected $table = 'quiz_categories';

    protected $fillable = ['name', 'sort_order'];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'quiz_category_id');
    }

    public function academicClasses(): HasMany
    {
        return $this->hasMany(AcademicClass::class, 'quiz_category_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'quiz_category_id');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'quiz_category_id');
    }

    public static function ordered(): \Illuminate\Database\Eloquent\Collection
    {
        return static::orderBy('sort_order')->orderBy('name')->get();
    }
}
