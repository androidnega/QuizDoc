<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class QuizSession extends Model
{
    protected $fillable = [
        'quiz_id', 'student_index', 'ip_address', 'start_time', 'ended_at', 'last_heartbeat_at',
        'pre_face_image', 'pre_face_image_hash', 'post_face_image', 'post_face_image_hash', 'post_face_captured_at',
        'post_face_skipped_at', 'post_face_skipped_reason', 'auto_submit_after',
        'assigned_question_ids', 'assigned_correct_answers', 'shuffled_question_options', 'session_token',
        'camera_verified', 'camera_started_at', 'minor_violations', 'major_violations', 'auto_submitted', 'submission_reason',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'ended_at' => 'datetime',
            'last_heartbeat_at' => 'datetime',
            'post_face_captured_at' => 'datetime',
            'post_face_skipped_at' => 'datetime',
            'auto_submit_after' => 'datetime',
            'camera_started_at' => 'datetime',
            'camera_verified' => 'boolean',
            'assigned_question_ids' => 'array',
            'assigned_correct_answers' => 'array',
            'shuffled_question_options' => 'array',
        ];
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class, 'quiz_session_id');
    }

    public function violations(): HasMany
    {
        return $this->hasMany(QuizViolation::class, 'quiz_session_id');
    }

    public function result(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Result::class, 'quiz_session_id');
    }
}
