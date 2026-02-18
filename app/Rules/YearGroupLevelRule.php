<?php

namespace App\Rules;

use App\Models\DocuMentor\AcademicYear;
use App\Models\StudentLevel;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that the level is allowed for the given academic year (year group).
 * 2025/2026 → Level 100 only
 * 2024/2025 → Level 100 or 200
 * 2023/2024 → Level 100, 200, or 300
 * 2022/2023 and older → up to Level 400
 */
class YearGroupLevelRule implements ValidationRule
{
    public function __construct(
        private readonly ?int $academicYearId = null,
        private readonly ?int $levelId = null
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->academicYearId || !$this->levelId) {
            return;
        }

        $academicYear = AcademicYear::find($this->academicYearId);
        if (!$academicYear || !$academicYear->year) {
            return;
        }

        $level = StudentLevel::find($this->levelId);
        if (!$level) {
            return;
        }

        $levelValue = (int) ($level->value ?? 0);
        $maxAllowed = self::maxLevelForYearGroup($academicYear->year);

        if ($levelValue > $maxAllowed) {
            $fail("Level {$level->label} is not allowed for year group {$academicYear->year}. Students in {$academicYear->year} can only be in Level " . ($maxAllowed) . " or below.");
        }
    }

    /**
     * Get max allowed level value for an academic year string (e.g. "2025/2026").
     * 2025/2026 and 2026/2027 → 100 only (freshers; level 200 not allowed).
     * 2024/2025 → 200; 2023/2024 → 300; 2022/2023 → 400.
     */
    public static function maxLevelForYearGroup(string $yearString): int
    {
        if (!preg_match('/^(\d{4})\//', trim($yearString), $m)) {
            return 400;
        }
        $startYear = (int) $m[1];
        // 2025/2026 and 2026/2027 are fresher cohorts: Level 100 only
        if ($startYear >= 2025) {
            return 100;
        }
        $referenceYear = (int) date('Y');
        $yearsSinceEnrollment = max(0, $referenceYear - $startYear);
        return (int) min(400, 100 * (1 + $yearsSinceEnrollment));
    }
}
