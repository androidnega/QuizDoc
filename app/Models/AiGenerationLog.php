<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiGenerationLog extends Model
{
    protected $table = 'ai_generation_logs';

    protected $fillable = [
        'quiz_id',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'provider',
        'questions_generated',
        'generated_at',
    ];

    protected function casts(): array
    {
        return ['generated_at' => 'datetime'];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}
