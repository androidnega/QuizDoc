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

    /** Legacy messaging / bulk tools; login codes do not use a short expiry when expires_at is null. */
    public const STUDENT_LOGIN_VALID_DAYS = 14;

    /** Shown in UI for examiner-generated codes (no automatic expiry when expires_at is null). */
    public const EXAMINER_FALLBACK_VALID_DAYS = 12;

    /** Avoid sending duplicate SMS if a code was just issued (minutes). */
    public const STUDENT_LOGIN_SMS_COOLDOWN_MINUTES = 15;

    /** Minimum seconds between “resend code” requests for the same index. */
    public const RESEND_COOLDOWN_SECONDS = 60;

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
     * Check if this OTP has passed its expiry. Null expires_at means the code does not auto-expire.
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Days remaining until this OTP expires (uses expires_at when set, else created_at + 14 days).
     * Carbon's diffInDays(now(), false) returns negative when $this is in the future, so we take the absolute value.
     */
    public function daysRemaining(): ?int
    {
        if ($this->expires_at === null) {
            return null;
        }
        $expiresAt = $this->expires_at;
        if ($expiresAt->isPast()) {
            return 0;
        }
        $remaining = (int) $expiresAt->diffInDays(now(), false);

        return max(0, abs($remaining));
    }

    /**
     * Remove SMS login codes for this index so a new code is the only active one.
     */
    public static function deleteStudentLoginOtpsForIndex(string $indexNumberHash): void
    {
        self::where('index_number_hash', $indexNumberHash)
            ->where('type', self::TYPE_STUDENT_LOGIN)
            ->delete();
    }

    /**
     * Any valid (not expired) student_login row matching this index and 6-digit code.
     */
    public static function findValidStudentLoginForIndexAndCode(string $indexNumberHash, string $code): ?self
    {
        return self::where('index_number_hash', $indexNumberHash)
            ->where('type', self::TYPE_STUDENT_LOGIN)
            ->where('code', $code)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Unused examiner fallback matching code (handles multiple rows; picks newest match).
     */
    public static function findValidExaminerFallbackForIndexAndCode(string $indexNumberHash, string $code): ?self
    {
        return self::where('index_number_hash', $indexNumberHash)
            ->where('type', self::TYPE_EXAMINER_FALLBACK)
            ->whereNull('used_at')
            ->where('code', $code)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('id')
            ->first();
    }

}
