<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentLevel extends Model
{
    protected $table = 'student_levels';

    protected $fillable = ['value', 'label', 'sort_order', 'allows_docu_mentor'];

    protected $casts = ['allows_docu_mentor' => 'boolean'];

    public static function ordered(): \Illuminate\Database\Eloquent\Collection
    {
        return static::orderBy('sort_order')->orderBy('value')->get();
    }

    /** Whether this level grants Docu Mentor (project) access. */
    public function allowsDocuMentor(): bool
    {
        return (bool) $this->allows_docu_mentor;
    }
}
