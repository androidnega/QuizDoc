<?php

namespace App\Models\DocuMentor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    public $timestamps = false;

    protected $table = 'academic_years';

    protected $fillable = ['year', 'is_active', 'submission_deadline'];

    protected $casts = [
        'is_active' => 'boolean',
        'submission_deadline' => 'date',
    ];

    public function getEffectiveDeadlineAttribute(): \Carbon\Carbon
    {
        return $this->submission_deadline ?? \Carbon\Carbon::parse($this->year)->addYear()->setMonth(9)->setDay(30);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(ProjectGroup::class, 'academic_year_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'academic_year_id');
    }

    /** QuizSnap: academic classes in this year */
    public function academicClasses(): HasMany
    {
        return $this->hasMany(\App\Models\AcademicClass::class, 'academic_year_id');
    }

    public static function active(): ?self
    {
        return static::where('is_active', true)->first();
    }
}
