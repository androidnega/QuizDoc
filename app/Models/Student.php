<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model implements Authenticatable
{
    protected $table = 'students';

    protected $fillable = ['index_number', 'index_number_hash', 'phone_contact', 'student_name', 'level'];

    /** @deprecated Use StudentLevel::allowsDocuMentor for dynamic check */
    public const LEVEL_DOCU_MENTOR = 400;

    public function canAccessDocuMentor(): bool
    {
        $levelValue = (int) ($this->attributes['level'] ?? 0);
        if ($levelValue <= 0 && !empty($this->attributes['level_id'] ?? null)) {
            $levelModel = \App\Models\StudentLevel::find($this->attributes['level_id']);
            $levelValue = $levelModel ? (int) $levelModel->value : 0;
        }
        if ($levelValue <= 0) {
            return false;
        }
        $levelModel = \App\Models\StudentLevel::where('value', $levelValue)->first();
        return $levelModel && $levelModel->allowsDocuMentor();
    }

    /**
     * Normalize index for hashing and comparison (trim + lowercase).
     */
    public static function normalizeIndex(?string $index): string
    {
        return $index !== null ? strtolower(trim($index)) : '';
    }

    /**
     * SHA-256 hash of normalized index number. Use for lookups; store in index_number_hash.
     */
    public static function hashIndexNumber(?string $index): string
    {
        return hash('sha256', self::normalizeIndex($index));
    }

    /**
     * Find a student by phone (digits only). Tries exact, 0-prefix, and 233 (Ghana) prefix.
     */
    public static function findByPhone(string $digitsOnly): ?self
    {
        if ($digitsOnly === '') {
            return null;
        }
        $normalized = ltrim($digitsOnly, '0') ?: $digitsOnly;
        $candidates = array_unique([
            $digitsOnly,
            $normalized,
            '0' . $normalized,
            '233' . $normalized,
        ]);
        return self::whereIn('phone_contact', $candidates)->first();
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): mixed
    {
        return $this->id;
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void
    {
    }

    public function getRememberTokenName(): ?string
    {
        return null;
    }

    /** Class group memberships (this index in various groups). */
    public function classGroupStudents(): HasMany
    {
        return $this->hasMany(ClassGroupStudent::class, 'index_number', 'index_number');
    }

    /** Quiz sessions where this student (by index) took a quiz. */
    public function quizSessions(): HasMany
    {
        return $this->hasMany(QuizSession::class, 'student_index', 'index_number');
    }

    public function hasPhone(): bool
    {
        return $this->phone_contact !== null && trim($this->phone_contact) !== '';
    }

    /**
     * Students are never examiners. Used by shared dashboard layout to gate SMS UI.
     */
    public function isExaminer(): bool
    {
        return false;
    }

    /** Display name: student_name or index_number. */
    public function getDisplayNameAttribute(): string
    {
        return trim($this->student_name ?? '') !== ''
            ? $this->student_name
            : $this->index_number;
    }

    /** First name only (first word of student_name, or index_number if no name). */
    public function getFirstNameAttribute(): string
    {
        $name = trim($this->student_name ?? '');
        if ($name === '') {
            return $this->index_number;
        }
        $first = explode(' ', $name, 2)[0] ?? '';
        return $first !== '' ? $first : $this->index_number;
    }

    /** Initials for avatar placeholder (e.g. "Emmanuel Kofi" → "EK"). */
    public function getInitialsAttribute(): string
    {
        $name = trim($this->student_name ?? '');
        if ($name === '') {
            return strtoupper(substr($this->index_number, 0, 2));
        }
        $words = preg_split('/\s+/', $name, 3);
        if (count($words) === 1) {
            return strtoupper(substr($words[0], 0, 2));
        }
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
}
