<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Semesters: 1, 2
 */
class Semester extends Model
{
    protected $table = 'semesters';

    protected $fillable = ['value', 'name', 'sort_order'];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'semester_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'semester_id');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'semester_id');
    }

    public static function ordered(): \Illuminate\Database\Eloquent\Collection
    {
        return static::orderBy('sort_order')->get();
    }
}
