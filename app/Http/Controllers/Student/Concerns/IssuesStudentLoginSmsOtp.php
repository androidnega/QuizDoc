<?php

namespace App\Http\Controllers\Student\Concerns;

use App\Models\Otp;
use App\Models\Student;
use App\Services\ArkeselService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

trait IssuesStudentLoginSmsOtp
{
    /**
     * Issue or reuse student_login SMS OTP. $sendToPhone: use when number is not yet saved on the student row (send-otp step).
     */
    protected function jsonAfterIssuingOrReusingSmsOtp(Student $student, string $indexHash, ?\App\Models\User $smsOwner, ?string $sendToPhone): JsonResponse
    {
        $destination = $sendToPhone ?? $student->phone_contact;
        if (! $destination || strlen((string) $destination) < 10) {
            return response()->json([
                'success' => false,
                'message' => 'No valid phone number on file.',
            ], 422);
        }

        $lastOtp = Otp::latestStudentLoginForIndex($indexHash);

        if ($lastOtp && ! $lastOtp->isExpired()
            && $lastOtp->created_at
            && $lastOtp->created_at->gt(now()->subMinutes(Otp::STUDENT_LOGIN_SMS_COOLDOWN_MINUTES))) {
            $daysRemaining = $lastOtp->daysRemaining();

            return response()->json([
                'success' => true,
                'step' => 'otp',
                'index_number' => $student->index_number,
                'message' => 'A code was already sent recently. Use the 6-digit code from your last SMS, or wait a few minutes and use Resend code.',
                'has_name' => ! empty($student->student_name),
                'can_resend' => true,
                'days_remaining' => $daysRemaining,
                'otp_never_expires' => $daysRemaining === null,
            ]);
        }

        $code = (string) random_int(100000, 999999);
        Otp::deleteStudentLoginOtpsForIndex($indexHash);
        Otp::create([
            'index_number_hash' => $indexHash,
            'type' => Otp::TYPE_STUDENT_LOGIN,
            'code' => $code,
            'phone' => $destination,
            'expires_at' => null,
        ]);
        $message = 'Your QuizSnap login code is: ' . $code . '. Do not share. This code stays valid until you receive a new one.';
        $result = ArkeselService::sendSms($destination, $message);
        if (! $result['success']) {
            $msg = $result['message'] ?? 'We couldn\'t send the code.';
            if (strpos($msg, 'try again') === false && strpos($msg, 'Try again') === false) {
                $msg .= ' Please try again.';
            }

            return response()->json(['success' => false, 'message' => $msg], 422);
        }
        if ($smsOwner) {
            $smsOwner->increment('sms_used');
        }
        Cache::put('otp_resend:'.$indexHash, 1, now()->addSeconds(Otp::RESEND_COOLDOWN_SECONDS));

        return response()->json([
            'success' => true,
            'step' => 'otp',
            'index_number' => $student->index_number,
            'message' => $sendToPhone === null
                ? 'A code has been sent to your registered number. It stays valid until you request a new code.'
                : 'A code has been sent to your number. It stays valid until you request a new code.',
            'has_name' => ! empty($student->student_name),
            'can_resend' => true,
            'days_remaining' => null,
            'otp_never_expires' => true,
        ]);
    }

    protected function pendingPasswordCacheKey(string $indexHash): string
    {
        return 'student_pw_setup:'.$indexHash;
    }
}
