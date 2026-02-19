<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    protected $table = 'otps';

    protected $fillable = [
        'index_number_hash',
        'type',
        'code',
        'phone',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public const TYPE_STUDENT_LOGIN = 'student_login';
    public const TYPE_EXAMINER_FALLBACK = 'examiner_fallback';

    /** Validity window for student login OTP (days). */
    public const STUDENT_LOGIN_VALID_DAYS = 14;

    /** Examiner fallback OTP validity (minutes). */
    public const EXAMINER_FALLBACK_VALID_MINUTES = 15;

    /**
     * Get the latest student_login OTP for the given index hash, if any.
     */
    public static function latestStudentLoginForIndex(string $indexNumberHash): ?self
    {
        return self::where('index_number_hash', $indexNumberHash)
            ->where('type', self::TYPE_STUDENT_LOGIN)
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Check if this OTP is still within the 14-day validity window (for student_login).
     */
    public function isWithinValidityWindow(): bool
    {
        $cutoff = now()->subDays(self::STUDENT_LOGIN_VALID_DAYS);
        return $this->created_at && $this->created_at->gte($cutoff);
    }

    /**
     * Days remaining until this OTP expires (created_at + 14 days).
     */
    public function daysRemaining(): int
    {
        if (!$this->created_at) {
            return 0;
        }
        $expiresAt = $this->created_at->copy()->addDays(self::STUDENT_LOGIN_VALID_DAYS);
        $remaining = (int) $expiresAt->diffInDays(now(), false);
        return max(0, $remaining);
    }

    /**
     * Get the latest valid (unused, not expired) examiner_fallback OTP for the given index hash.
     */
    public static function latestValidExaminerFallbackForIndex(string $indexNumberHash): ?self
    {
        return self::where('index_number_hash', $indexNumberHash)
            ->where('type', self::TYPE_EXAMINER_FALLBACK)
            ->whereNull('used_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('created_at')
            ->first();
    }
}
