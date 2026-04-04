<?php

namespace App\Services;

use App\Models\ClassGroupStudent;
use App\Models\Otp;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only report for debugging student OTP / examiner fallback verification on a server.
 */
class OtpDiagnostics
{
    public static function buildReport(string $rawIndex, ?string $rawCode): string
    {
        $lines = [];
        $push = static function (string $s) use (&$lines): void {
            $lines[] = $s;
        };

        $push('=== QuizSnap OTP diagnose ===');
        $push('Time (app): '.now()->toIso8601String());
        $push('PHP: '.PHP_VERSION);
        $push('DB default: '.config('database.default'));
        $push('Universal student OTP codes configured: '.count(StudentUniversalOtp::normalizedCodes()).' (Settings / .env; never expire)');
        $push('');

        $inputIndex = trim($rawIndex);
        if ($inputIndex === '') {
            $push('ERROR: empty index');

            return implode("\n", $lines);
        }

        $normalized = Student::normalizeIndex($inputIndex);
        $hash = Student::hashIndexNumber($inputIndex);
        $push('Input index (raw): '.json_encode($rawIndex));
        $push('Trimmed: '.json_encode($inputIndex));
        $push('Normalized (hash input): '.json_encode($normalized));
        $push('SHA256 hash (full): '.$hash);
        $push('');

        $student = Student::where('index_number_hash', $hash)->first();
        if (! $student) {
            $push('STUDENT ROW: MISSING for this hash.');
            $push('verifyOtp would return: "Invalid session. Start again." (before checking any OTP)');
            $push('');
            $push('Hint: complete step 1 (index) on the login page so firstOrCreate runs, or check index spelling vs class list.');
        } else {
            $push('STUDENT ROW: found id='.$student->id);
            $push('  students.index_number: '.json_encode($student->index_number));
            $push('  students.index_number_hash: '.$student->index_number_hash);
            $hashFromStored = Student::hashIndexNumber($student->index_number);
            $push('  Re-hash from stored index_number: '.$hashFromStored);
            if ($hashFromStored !== $student->index_number_hash) {
                $push('  *** WARNING: stored index_number_hash does NOT match hash(students.index_number) — data corruption or old bug.');
            }
            if ($hashFromStored !== $hash) {
                $push('  *** WARNING: hash from LOGIN input differs from hash(students.index_number).');
                $push('      Login uses hash(normalize(trim(typed index))). Student row may have been created with a different spelling.');
            }
            $push('');
        }

        $cg = ClassGroupStudent::whereRaw('LOWER(TRIM(index_number)) = ?', [strtolower(trim($inputIndex))])->first();
        if ($cg) {
            $cgHash = Student::hashIndexNumber($cg->index_number);
            $push('CLASS_GROUP_STUDENTS: at least one row matches LOWER(TRIM(index))');
            $push('  class_group_students.index_number (stored): '.json_encode($cg->index_number));
            $push('  hash(class_group_students.index_number): '.$cgHash);
            if ($student && $cgHash !== $hash) {
                $push('  *** MISMATCH: hash from typed index != hash from class_group_students.index_number');
                $push('      OTP rows use hash from whichever path created them (examiner fallback uses class group student index).');
            }
            $push('');
        } else {
            $push('CLASS_GROUP_STUDENTS: no row where LOWER(TRIM(index_number)) = normalized input');
            $push('');
        }

        $code = $rawCode !== null && $rawCode !== '' ? preg_replace('/\D/', '', (string) $rawCode) : '';
        $codeDigits = strlen($code) === 6 ? $code : null;
        if (strlen($code) !== 6 && $code !== '') {
            $push('Provided code after digit strip: length '.strlen($code).' (need 6) — '.json_encode($code));
            $push('');
        }

        if (Schema::hasTable('otps')) {
            try {
                $driver = Schema::getConnection()->getDriverName();
                if ($driver === 'mysql') {
                    $col = DB::selectOne('SHOW COLUMNS FROM `otps` WHERE Field = ?', ['code']);
                    if ($col) {
                        $type = $col->Type ?? '?';
                        $push('MYSQL otps.code column type: '.$type);
                        if (stripos((string) $type, 'int') !== false) {
                            $push('  *** PROBLEM: code is numeric — leading zeros are lost (e.g. 012345 becomes 12345). ALTER to VARCHAR(10).');
                        }
                        $push('');
                    }
                }
            } catch (\Throwable $e) {
                $push('Could not inspect otps.code column: '.$e->getMessage());
                $push('');
            }
        }

        $otps = Otp::where('index_number_hash', $hash)->orderByDesc('id')->limit(25)->get();
        $push('OTP rows for hash from TYPED index (max 25, newest first): count='.$otps->count());
        if ($otps->isEmpty()) {
            $push('  (none — examiner/SMS codes may be stored under a DIFFERENT index_number_hash)');
            if ($cg) {
                $altHash = Student::hashIndexNumber($cg->index_number);
                if ($altHash !== $hash) {
                    $push('  Retrying lookup with hash(class_group_students.index_number)…');
                    $altOtps = Otp::where('index_number_hash', $altHash)->orderByDesc('id')->limit(25)->get();
                    $push('  OTP count for THAT hash: '.$altOtps->count());
                    foreach ($altOtps as $o) {
                        $push(self::formatOtpLine($o, $codeDigits));
                    }
                }
            }
        }
        foreach ($otps as $o) {
            $push(self::formatOtpLine($o, $codeDigits));
        }
        $push('');

        if ($student && strlen($code) === 6) {
            $fb = Otp::findValidExaminerFallbackForIndexAndCode($hash, $code);
            $sl = Otp::findValidStudentLoginForIndexAndCode($hash, $code);
            $push('SIMULATION (same as verifyOtp for this index + code):');
            $push('  findValidExaminerFallbackForIndexAndCode: '.($fb ? 'MATCH id='.$fb->id : 'no match'));
            $push('  findValidStudentLoginForIndexAndCode: '.($sl ? 'MATCH id='.$sl->id : 'no match'));
            if (! $fb && ! $sl && $cg) {
                $altHash = Student::hashIndexNumber($cg->index_number);
                if ($altHash !== $hash) {
                    $fb2 = Otp::findValidExaminerFallbackForIndexAndCode($altHash, $code);
                    $sl2 = Otp::findValidStudentLoginForIndexAndCode($altHash, $code);
                    $push('  Same code with hash(class_group_students.index_number):');
                    $push('    examiner fallback: '.($fb2 ? 'MATCH id='.$fb2->id : 'no match'));
                    $push('    student_login: '.($sl2 ? 'MATCH id='.$sl2->id : 'no match'));
                }
            }
        } elseif ($student) {
            $push('SIMULATION: pass --code=###### to test matcher.');
        }

        $push('');
        $push('=== end ===');

        return implode("\n", $lines);
    }

    private static function formatOtpLine(Otp $o, ?string $sixDigitInput): string
    {
        $codeStr = (string) $o->code;
        $len = strlen($codeStr);
        $masked = $len <= 2 ? str_repeat('*', $len) : substr($codeStr, 0, 1).str_repeat('*', max(0, $len - 2)).substr($codeStr, -1);
        $exp = $o->expires_at;
        $expired = $o->isExpired();
        $expOk = $exp === null || $exp->isFuture();
        $parts = [
            'id='.$o->id,
            'type='.$o->type,
            'code_len='.$len,
            'code_masked='.$masked,
            'expires_at='.($exp ? $exp->toIso8601String() : 'NULL'),
            'expired='.($expired ? 'yes' : 'no'),
            'expires_ok_for_query='.($expOk ? 'yes' : 'no'),
            'used_at='.($o->used_at ? $o->used_at->toIso8601String() : 'NULL'),
        ];
        if ($sixDigitInput !== null && strlen($sixDigitInput) === 6) {
            $parts[] = 'exact_match_input='.($codeStr === $sixDigitInput ? 'yes' : 'no');
            if ($codeStr !== $sixDigitInput && (int) $codeStr === (int) $sixDigitInput) {
                $parts[] = 'WARNING_int_equal_but_string_differs_CHECK_leading_zeros_and_column_type';
            }
        }

        return '  - '.implode(' | ', $parts);
    }
}
