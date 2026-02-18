<?php

namespace App\Models;

use App\Casts\EncryptedNullable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Answer extends Model
{
    protected $fillable = ['quiz_session_id', 'question_id', 'student_answer', 'explanation_wrong', 'answered_at'];

    protected function casts(): array
    {
        return [
            'answered_at' => 'datetime',
            'student_answer' => EncryptedNullable::class,
        ];
    }

    public function quizSession(): BelongsTo
    {
        return $this->belongsTo(QuizSession::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
