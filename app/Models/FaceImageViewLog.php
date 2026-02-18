<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaceImageViewLog extends Model
{
    public const IMAGE_TYPE_PRE = 'pre';
    public const IMAGE_TYPE_POST = 'post';

    protected $fillable = ['admin_id', 'quiz_session_id', 'image_type', 'viewed_at'];

    protected function casts(): array
    {
        return ['viewed_at' => 'datetime'];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function quizSession(): BelongsTo
    {
        return $this->belongsTo(QuizSession::class);
    }
}
