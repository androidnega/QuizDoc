<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushSubscription extends Model
{
    protected $fillable = ['student_id', 'endpoint', 'public_key', 'auth_token'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
