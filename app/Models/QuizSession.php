<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class QuizSession extends Model
{
    protected $fillable = [
        'quiz_id', 'student_index', 'ip_address', 'user_agent', 'device_type', 'device_name', 'start_time', 'ended_at', 'last_heartbeat_at',
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

    public function isResultWithheld(): bool
    {
        $reason = trim((string) $this->submission_reason);
        return in_array($reason, [
            'withheld_due_to_violations',
            'critical_violation_auto_submit',
            'critical_violation',
        ], true);
    }

    /**
     * First critical violation label for lecturer preview/PDF (one only).
     * Returns human-readable label or null if no critical violation.
     */
    public function getFirstCriticalViolationLabel(): ?string
    {
        $critical = QuizViolation::criticalTypes();
        $first = $this->violations
            ->filter(fn ($v) => in_array($v->type ?? '', $critical, true) || ($v->severity ?? '') === QuizViolation::SEVERITY_CRITICAL)
            ->sortBy('occurred_at')
            ->first();
        return $first ? QuizViolation::labelForType($first->type ?? 'other') : null;
    }

    /**
     * Parse User-Agent and return device_type (desktop|mobile|tablet) and optional device_name.
     * Used to show "Laptop" vs "Mobile phone (iPhone 14)" on session detail page.
     */
    public static function parseUserAgent(?string $userAgent): array
    {
        $ua = $userAgent ?? '';
        $deviceType = 'desktop';
        $deviceName = null;

        if (preg_match('/iPad|Tablet|PlayBook|Silk\/|KFAPWI|GT-P|SM-T|Tab\b/i', $ua)) {
            $deviceType = 'tablet';
            if (preg_match('/iPad.*OS (\d+)[_\d]*/i', $ua, $m)) {
                $deviceName = 'iPad' . (isset($m[1]) ? ' (iOS ' . $m[1] . ')' : '');
            } elseif (preg_match('/Android/i', $ua)) {
                $deviceName = 'Android tablet';
            } else {
                $deviceName = 'Tablet';
            }
        } elseif (preg_match('/Mobile|Android|iPhone|iPod|webOS|BlackBerry|IEMobile|Opera Mini|Opera Mobi|MiuiBrowser|SamsungBrowser|CriOS|FxiOS|EdgA|EdgiOS|\bwv\b|Mobile Safari/i', $ua)) {
            $deviceType = 'mobile';
            if (preg_match('/iPhone(?: OS (\d+)[_\d]*)?/i', $ua, $m)) {
                $deviceName = 'iPhone' . (isset($m[1]) ? ' (iOS ' . $m[1] . ')' : '');
            } elseif (preg_match('/iPod/i', $ua)) {
                $deviceName = 'iPod touch';
            } elseif (preg_match('/Android[^;]*;(?: [^;]*)?\s*([A-Za-z0-9\-]+(?:\s+[A-Za-z0-9\-]+)?)\s*Build/i', $ua, $m)) {
                $model = trim($m[1] ?? '');
                if (strlen($model) <= 40) {
                    $deviceName = $model;
                } else {
                    $deviceName = 'Android phone';
                }
            } elseif (preg_match('/Samsung[^\/]*\/([A-Za-z0-9\-]+)/i', $ua, $m) && strlen($m[1] ?? '') <= 30) {
                $deviceName = 'Samsung ' . ($m[1] ?? '');
            } elseif (preg_match('/Pixel\s*(\d+)/i', $ua, $m)) {
                $deviceName = 'Google Pixel ' . ($m[1] ?? '');
            } elseif (preg_match('/Android/i', $ua)) {
                $deviceName = 'Android phone';
            } else {
                $deviceName = 'Mobile phone';
            }
        }

        return [
            'device_type' => $deviceType,
            'device_name' => $deviceName,
        ];
    }

    /** Human-readable device label for session detail (e.g. "Laptop", "Mobile phone (iPhone)"). */
    public function getDeviceLabelAttribute(): string
    {
        $type = $this->device_type;
        $name = trim((string) ($this->device_name ?? ''));
        if ($type === null && ! empty(trim((string) ($this->user_agent ?? '')))) {
            $parsed = static::parseUserAgent($this->user_agent);
            $type = $parsed['device_type'];
            $name = trim((string) ($parsed['device_name'] ?? ''));
        }
        $type = $type ?? 'desktop';
        if ($type === 'mobile') {
            $base = 'Mobile phone';
            return $name !== '' ? $base . ' (' . $name . ')' : $base;
        }
        if ($type === 'tablet') {
            $base = 'Tablet';
            return $name !== '' ? $base . ' (' . $name . ')' : $base;
        }
        return 'Laptop';
    }
}
