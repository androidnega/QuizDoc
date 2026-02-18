<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Section 5: Log into SMSLog. Records each SMS send (phone, message, status, response).
 */
class SmsLog extends Model
{
    protected $table = 'sms_logs';

    /**
     * Legacy table does not have created_at/updated_at timestamps.
     */
    public $timestamps = false;

    protected $fillable = ['phone', 'message', 'status', 'response', 'user_id'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function logSend(string $phone, string $message, bool $success, ?string $responseMessage = null, ?int $userId = null): self
    {
        return self::create([
            'phone' => $phone,
            'message' => $message,
            'status' => $success ? 'success' : 'failed',
            'response' => $responseMessage,
            'user_id' => $userId,
            // Ensure non-null created_at for legacy schema that requires it
            'created_at' => now(),
        ]);
    }
}
