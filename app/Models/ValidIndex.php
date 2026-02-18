<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidIndex extends Model
{
    protected $table = 'valid_indices';

    protected $fillable = ['index_number', 'course_id', 'student_name'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
