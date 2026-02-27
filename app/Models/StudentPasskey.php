<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPasskey extends Model
{
    protected $table = 'student_passkeys';

    protected $fillable = [
        'student_id',
        'credential_id',
        'credential_public_key',
        'counter',
        'device_name',
    ];

    protected $casts = [
        'counter' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
