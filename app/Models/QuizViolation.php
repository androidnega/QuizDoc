<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizViolation extends Model
{
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_CRITICAL = 'critical';

    protected $fillable = ['quiz_session_id', 'type', 'severity', 'metadata', 'image_url', 'occurred_at', 'out_of_frame_duration', 'evidence_timestamp'];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'evidence_timestamp' => 'datetime',
        ];
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

    /** Critical types (same as severityForType). Used for lecturer preview/PDF: show one critical violation only. */
    public static function criticalTypes(): array
    {
        return [
            'phone_detected',
            'screenshot_attempt',
            'tab_switch',
            'multiple_faces',
            'multiple_faces_pre_quiz',
            'multiple_faces_during_quiz',
            'window_resize',
            'blur',
            'copy_paste',
            'multiple_ip',
        ];
    }

    /**
     * Human-readable label for violation type (for lecturer preview and PDF).
     */
    public static function labelForType(string $type): string
    {
        $labels = [
            'phone_detected' => 'Phone detected',
            'screenshot_attempt' => 'Screenshot attempt',
            'tab_switch' => 'Tab switched',
            'multiple_faces' => 'Multiple faces',
            'multiple_faces_during_quiz' => 'Multiple faces',
            'multiple_faces_pre_quiz' => 'Multiple faces',
            'window_resize' => 'Window resize',
            'blur' => 'Tab/window blur',
            'copy_paste' => 'Copy/paste',
            'multiple_ip' => 'Multiple IP',
            'right_click' => 'Right click',
            'face_mismatch' => 'Face mismatch',
            'camera_disconnected' => 'Camera disconnected',
            'no_face' => 'No face',
            'no_face_during_quiz' => 'No face',
            'face_out_of_frame' => 'Face out of frame',
            'face_lost_repeatedly' => 'Face lost',
            'external_audio' => 'External audio',
            'no_blink' => 'No blink',
            'head_turn' => 'Head turn',
            'brief_face_loss' => 'Brief face loss',
            'challenge_failed' => 'Challenge failed',
            'static_face_detected' => 'Static face',
            'random_snapshot' => 'Snapshot',
            'other' => 'Other',
        ];
        return $labels[$type] ?? str_replace('_', ' ', ucfirst($type));
    }

    /**
     * Classify violation type into severity.
     * Critical (auto-submit on first):
     * phone_detected, screenshot_attempt, tab_switch, multiple_faces,
     * multiple_faces_during_quiz, window_resize, blur, copy_paste, multiple_ip.
     * Warning: head-turn/out-of-frame/static-face and other non-critical proctoring signals.
     */
    public static function severityForType(string $type): string
    {
        if (in_array($type, self::criticalTypes(), true)) {
            return self::SEVERITY_CRITICAL;
        }
        return self::SEVERITY_WARNING;
    }
}
