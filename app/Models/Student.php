<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model implements Authenticatable
{
    protected $table = 'students';

    protected $fillable = ['index_number', 'index_number_hash', 'phone_contact', 'student_name', 'level'];

    /**
     * Completely remove a student (by index) from QuizSnap + Docu Mentor.
     *
     * This is the single source of truth for cascading deletions when a student
     * is removed from all class groups. It:
     * - Deletes Quiz sessions + acceptances for the index.
     * - Deletes OTPs and the Student account row.
     * - Cleans up Docu Mentor users tied to this index:
     *   - Detaches them from all groups (group_members).
     *   - Clears leader flags and leader assignments on groups.
     *   - Deletes the Docu Mentor user when it is safe to do so.
     */
    public static function deleteEverywhereByIndex(string $indexNumber): void
    {
        $indexUpper = strtoupper(trim($indexNumber));
        if ($indexUpper === '') {
            return;
        }

        $hash = self::hashIndexNumber($indexUpper);

        // QuizSnap data
        \App\Models\QuizSession::whereRaw('UPPER(TRIM(student_index)) = ?', [$indexUpper])->delete();
        \App\Models\QuizAcceptance::whereRaw('UPPER(TRIM(index_number)) = ?', [$indexUpper])->delete();

        // OTPs tied to this student login index
        \App\Models\Otp::where('index_number_hash', $hash)->delete();

        // Student account (global student row)
        self::where('index_number_hash', $hash)->delete();

        // Docu Mentor: users mapped to this index
        $dmUsers = \App\Models\User::whereIn('role', [
                \App\Models\User::DM_ROLE_STUDENT,
                \App\Models\User::DM_ROLE_LEADER,
            ])
            ->whereRaw('UPPER(TRIM(index_number)) = ?', [$indexUpper])
            ->get();

        if ($dmUsers->isEmpty()) {
            return;
        }

        foreach ($dmUsers as $user) {
            // Detach from all Docu Mentor project groups
            $user->docuMentorGroups()->detach();

            // If this user was a leader of any group, clear the leader_id
            \App\Models\DocuMentor\ProjectGroup::where('leader_id', $user->id)->update(['leader_id' => null]);

            // Clear leader flag
            $user->group_leader = false;

            // Only delete the user when it is safe:
            // - Not a coordinator or staff (super admin/examiner)
            // - Not supervising any projects
            // - Not a member/leader of any groups after detach
            $hasGroups = $user->docuMentorGroups()->exists();
            $hasSupervisedProjects = $user->supervisedProjects()->exists();
            $isCoordinator = $user->isDocuMentorCoordinator();
            $isStaff = $user->isStaff();

            if (!$hasGroups && !$hasSupervisedProjects && !$isCoordinator && !$isStaff) {
                $user->delete();
            } else {
                $user->save();
            }
        }
    }

    /** @deprecated Use StudentLevel::allowsDocuMentor for dynamic check */
    public const LEVEL_DOCU_MENTOR = 300;

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
     * Normalize phone for storage/comparison: digits only; Ghana local (0...) becomes 233...
     * Accepts +233, 233, or 0... formats.
     */
    public static function normalizePhoneForStorage(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        $digits = preg_replace('/\D/', '', $raw);
        if ($digits === '') {
            return null;
        }
        if (strlen($digits) >= 10 && $digits[0] === '0') {
            return '233' . substr($digits, 1);
        }
        return $digits;
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
        if (strlen($digitsOnly) >= 12 && str_starts_with($digitsOnly, '233')) {
            $candidates[] = '0' . substr($digitsOnly, 3);
        }
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

    /** WebAuthn passkeys (fingerprint / Face ID) for this student. */
    public function passkeys(): HasMany
    {
        return $this->hasMany(StudentPasskey::class);
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
