<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassGroupStudent extends Model
{
    protected $fillable = ['class_group_id', 'index_number', 'student_name'];

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    /** Linked student account (by index_number) if they have logged in and set phone. */
    public function studentAccount(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'index_number', 'index_number');
    }
}
