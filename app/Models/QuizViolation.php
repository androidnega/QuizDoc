<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizViolation extends Model
{
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_CRITICAL = 'critical';

    protected $fillable = ['quiz_session_id', 'type', 'severity', 'metadata', 'image_url', 'occurred_at'];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    public function quizSession(): BelongsTo
    {
        return $this->belongsTo(QuizSession::class);
    }

    public static function types(): array
    {
        return [
            'blur', 'multiple_ip', 'copy_paste', 'right_click', 'face_mismatch', 'tab_switch', 
            'window_resize', 'screenshot_attempt', 'camera_disconnected', 'no_face', 
            'multiple_faces', 'multiple_faces_pre_quiz', 'multiple_faces_during_quiz',
            'random_snapshot', 'phone_detected', 'external_audio', 'no_blink', 'head_turn', 
            'brief_face_loss', 'challenge_failed', 'static_face_detected', 'no_face_during_quiz',
            'face_out_of_frame',
            'face_lost_repeatedly', 'other'
        ];
    }

    /**
     * Classify violation type into severity.
     * Critical (auto-submit on first): copy_paste, multiple_ip.
     * Warning (no auto-submit; right-click only warns): right_click, blur, tab_switch, other.
     * Auto-submit on tab switch is handled separately when blur/tab_switch count reaches threshold.
     */
    public static function severityForType(string $type): string
    {
        // Critical violations
        if (in_array($type, ['copy_paste', 'multiple_ip', 'screenshot_attempt', 'camera_disconnected', 'face_lost_repeatedly'], true)) {
            return self::SEVERITY_CRITICAL;
        }
        // Major violations
        if (in_array($type, ['multiple_faces', 'phone_detected', 'external_audio', 'tab_switch', 'blur', 'window_resize'], true)) {
            return self::SEVERITY_CRITICAL; // Using CRITICAL for major to match backend expectations
        }
        // Minor violations (default to warning)
        return self::SEVERITY_WARNING;
    }
}
