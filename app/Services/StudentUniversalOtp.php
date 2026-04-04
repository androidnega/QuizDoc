<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Optional global student login codes (6 digits). Not tied to SMS rows; never expire.
 * Configured via Settings → OTP (comma-separated) or QUIZSNAP_UNIVERSAL_OTP_CODES in .env.
 * DB value overrides .env when non-empty.
 */
final class StudentUniversalOtp
{
    public static function normalizedCodes(): array
    {
        $raw = self::rawCommaSeparated();

        return self::parseRawToSixDigitCodes($raw);
    }

    public static function matches(string $sixDigitCode): bool
    {
        if (strlen($sixDigitCode) !== 6 || ! ctype_digit($sixDigitCode)) {
            return false;
        }

        return in_array($sixDigitCode, self::normalizedCodes(), true);
    }

    private static function rawCommaSeparated(): string
    {
        $row = Setting::where('key', Setting::KEY_STUDENT_UNIVERSAL_OTP_CODES)->first();
        if ($row !== null && trim((string) $row->value) !== '') {
            return trim((string) $row->value);
        }

        return trim((string) config('quizsnap.universal_student_otp_codes', ''));
    }

    /**
     * @return list<string> unique 6-digit codes
     */
    public static function parseRawToSixDigitCodes(string $raw): array
    {
        if ($raw === '') {
            return [];
        }
        $out = [];
        foreach (explode(',', $raw) as $part) {
            $d = preg_replace('/\D/', '', trim($part));
            if (strlen($d) === 6) {
                $out[] = $d;
            }
        }

        return array_values(array_unique($out));
    }
}
