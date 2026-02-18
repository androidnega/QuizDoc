<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassGroupStudent;
use App\Models\Student;
use App\Services\ArkeselService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class StudentAccountController extends Controller
{
    private const OTP_CACHE_PREFIX = 'student_otp:';

    private static function otpTtlSeconds(): int
    {
        return (int) config('quizsnap.otp_ttl_seconds', 14 * 86400);
    }

    /**
     * Student account login form (index → phone → OTP flow).
     */
    public function showLoginForm(): View|RedirectResponse
    {
        // Prevent login if student is already logged in
        if (session('student_id')) {
            return redirect()->route('dashboard')
                ->with('info', 'You are already logged in.');
        }
        
        // Prevent login if admin/examiner is already logged in
        if (session('admin_authenticated', false)) {
            return redirect()->route('dashboard')
                ->with('info', 'You are already logged in as staff. Please logout first to login as a student.');
        }
        
        return view('student.account-login');
    }

    /**
     * Step 1: Verify index number. Index must exist in at least one class group.
     * Returns: need_phone (and student), or sends OTP and returns need_otp.
     */
    public function verifyIndex(Request $request): JsonResponse
    {
        // Prevent login if already authenticated
        if (session('student_id')) {
            return response()->json([
                'success' => false,
                'message' => 'You are already logged in. Please logout first to login with a different account.',
            ], 422);
        }
        
        if (session('admin_authenticated', false)) {
            return response()->json([
                'success' => false,
                'message' => 'You are already logged in as staff. Please logout first to login as a student.',
            ], 422);
        }
        
        $request->validate(['index_number' => 'required|string|max:100']);
        $inputIndex = trim((string) $request->index_number);
        $inputNormalized = strtolower($inputIndex);

        // Match class_group_students case- and trim-insensitively (admin may have added as "BC/ITN/23/285" or "bc/itn/23/285")
        $cgStudent = ClassGroupStudent::whereRaw('LOWER(TRIM(index_number)) = ?', [$inputNormalized])->first();
        if (!$cgStudent) {
            return response()->json([
                'success' => false,
                'message' => 'Index number not found. You must belong to a class first.',
            ], 422);
        }

        // Use a canonical (uppercase) form for display; store hash for lookups
        $indexNumber = strtoupper(trim($cgStudent->index_number));
        $indexHash = Student::hashIndexNumber($cgStudent->index_number);

        $student = Student::firstOrCreate(
            ['index_number_hash' => $indexHash],
            [
                'index_number' => $indexNumber,
                'index_number_hash' => $indexHash,
            ]
        );

        if (!$student->hasPhone()) {
            return response()->json([
                'success' => true,
                'step' => 'phone',
                'index_number' => $student->index_number,
                'message' => 'Enter your active phone number to receive a one-time code.',
            ]);
        }

        // Check examiner SMS balance: student must be linked to an examiner with remaining SMS
        $examiner = $this->examinerWithSmsBalanceForIndex($cgStudent->index_number);
        if (!$examiner) {
            return response()->json([
                'success' => false,
                'message' => 'We\'re unable to send your login code right now. Please contact your lecturer or course administrator for assistance.',
            ], 422);
        }

        // Check if there's an existing valid OTP (within validity period)
        $cached = Cache::get(self::OTP_CACHE_PREFIX . $indexNumber);
        if ($cached && isset($cached['code'])) {
            return response()->json([
                'success' => true,
                'step' => 'otp',
                'index_number' => $student->index_number,
                'message' => 'Use your existing code sent within the last 14 days, or request a new one below.',
                'has_name' => !empty($student->student_name),
                'can_resend' => true,
            ]);
        }

        // Has phone: generate OTP and send (deduct from examiner)
        $code = (string) random_int(100000, 999999);
        Cache::put(self::OTP_CACHE_PREFIX . $indexNumber, [
            'code' => $code,
            'phone' => $student->phone_contact,
        ], self::otpTtlSeconds());

        $message = 'Your QuizSnap login code is: ' . $code . '. Do not share. Valid for 14 days.';
        $result = ArkeselService::sendSms($student->phone_contact, $message);
        if (!$result['success']) {
            $msg = $result['message'] ?? 'We couldn\'t send the code.';
            if (strpos($msg, 'try again') === false && strpos($msg, 'Try again') === false) {
                $msg .= ' Please try again.';
            }
            return response()->json(['success' => false, 'message' => $msg], 422);
        }

        if ($result['success']) {
            $examiner->increment('sms_used');
        }
        return response()->json([
            'success' => true,
            'step' => 'otp',
            'index_number' => $student->index_number,
            'message' => 'A code has been sent to your registered number. This code is valid for 14 days.',
            'has_name' => !empty($student->student_name),
            'can_resend' => true,
        ]);
    }

    /**
     * Step 2: Send OTP to the given phone (first-time or new phone). Ties phone to account after OTP verify.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'index_number' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
        ]);
        $inputIndex = trim((string) $request->index_number);

        $student = Student::where('index_number_hash', Student::hashIndexNumber($inputIndex))->first();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Invalid session. Start again.'], 422);
        }
        $indexNumber = $student->index_number;
        $inputPhone = trim((string) ($request->phone ?? ''));
        $phone = preg_replace('/\D/', '', $inputPhone);

        // Student cannot change phone—only examiner can remove it
        $storedNormalized = $student->phone_contact ? preg_replace('/\D/', '', $student->phone_contact) : '';
        if ($storedNormalized !== '' && $phone !== '' && $storedNormalized !== $phone) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number cannot be changed. Ask your examiner to remove it first.',
            ], 422);
        }
        if ($storedNormalized !== '' && $phone === '') {
            // Registered students can request a new OTP without re-entering phone.
            $phone = $storedNormalized;
        }
        if ($phone === '' || strlen($phone) < 10) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid phone number (e.g. 233XXXXXXXXX).',
            ], 422);
        }

        // Phone must not be used by another student
        $otherStudent = Student::where('phone_contact', $phone)->where('id', '!=', $student->id)->first();
        if ($otherStudent) {
            return response()->json([
                'success' => false,
                'message' => 'This phone number is already registered to another student. Use a different number or ask your examiner for help.',
            ], 422);
        }

        // Examiner must have SMS balance
        $examiner = $this->examinerWithSmsBalanceForIndex($student->index_number);
        if (!$examiner) {
            return response()->json([
                'success' => false,
                'message' => 'We\'re unable to send your login code right now. Please contact your lecturer or course administrator for assistance.',
            ], 422);
        }

        // Check if there's an existing valid OTP for this index
        $existingCached = Cache::get(self::OTP_CACHE_PREFIX . $indexNumber);
        if ($existingCached && isset($existingCached['code']) && ($existingCached['phone'] ?? '') === $phone) {
            Cache::forget(self::OTP_CACHE_PREFIX . $indexNumber);
        }

        $code = (string) random_int(100000, 999999);
        Cache::put(self::OTP_CACHE_PREFIX . $indexNumber, [
            'code' => $code,
            'phone' => $phone,
        ], self::otpTtlSeconds());

        $message = 'Your QuizSnap login code is: ' . $code . '. Do not share. Valid for 14 days.';
        $result = ArkeselService::sendSms($phone, $message);
        if (!$result['success']) {
            $msg = $result['message'] ?? 'We couldn\'t send the code.';
            if (strpos($msg, 'try again') === false && strpos($msg, 'Try again') === false) {
                $msg .= ' Please try again.';
            }
            return response()->json(['success' => false, 'message' => $msg], 422);
        }
        if ($result['success']) {
            $examiner->increment('sms_used');
        }
        return response()->json([
            'success' => true,
            'step' => 'otp',
            'index_number' => $student->index_number,
            'message' => 'A code has been sent to your number. It is valid for 14 days.',
            'has_name' => !empty($student->student_name),
            'can_resend' => true,
        ]);
    }

    /** Get an examiner with SMS balance for the given index (via class group membership). */
    private function examinerWithSmsBalanceForIndex(string $indexNumber): ?\App\Models\User
    {
        $cgStudents = ClassGroupStudent::whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($indexNumber))])
            ->with('classGroup.examiner')
            ->get();
        foreach ($cgStudents as $cg) {
            $examiner = $cg->classGroup?->examiner;
            if ($examiner && $examiner->isExaminer() && $examiner->sms_remaining > 0) {
                return $examiner;
            }
        }
        return null;
    }

    /**
     * Step 3: Verify OTP and create session. Optionally accept student_name to tie to account.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        // Prevent login if already authenticated
        if (session('student_id')) {
            return response()->json([
                'success' => false,
                'message' => 'You are already logged in. Please logout first to login with a different account.',
            ], 422);
        }
        
        if (session('admin_authenticated', false)) {
            return response()->json([
                'success' => false,
                'message' => 'You are already logged in as staff. Please logout first to login as a student.',
            ], 422);
        }
        
        $request->validate([
            'index_number' => 'required|string|max:100',
            'code' => 'required|string|size:6',
            'student_name' => 'nullable|string|max:255',
        ]);
        $inputIndex = trim((string) $request->index_number);
        $code = trim($request->code);
        $name = $request->filled('student_name') ? trim($request->student_name) : null;

        $indexHash = Student::hashIndexNumber($inputIndex);
        $student = Student::where('index_number_hash', $indexHash)->first();
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session. Start again.',
            ], 422);
        }
        $indexNumber = $student->index_number;

        $cached = Cache::get(self::OTP_CACHE_PREFIX . $indexNumber);
        if (!$cached || ($cached['code'] ?? '') !== $code) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired code. Please request a new one.',
            ], 422);
        }

        // Tie phone to account if this was first-time (phone from cache)
        $phone = $cached['phone'] ?? null;
        if ($phone && !$student->phone_contact) {
            $otherStudent = Student::where('phone_contact', $phone)->where('id', '!=', $student->id)->first();
            if ($otherStudent) {
                return response()->json([
                    'success' => false,
                    'message' => 'This phone number is already registered to another student. Use a different number.',
                ], 422);
            }
            $student->phone_contact = $phone;
        }
        if ($name !== null && $name !== '') {
            $student->student_name = ucwords(strtolower(trim($name)));
        }
        $student->save();

        // Don't delete OTP - keep it valid for 24 hours for reuse
        // Cache::forget(self::OTP_CACHE_PREFIX . $indexNumber);

        session([
            'student_id' => $student->id,
            'student_index' => $student->index_number,
        ]);

        $redirect = route('dashboard');
        if (session()->has('quiz_id')) {
            $redirect = route('student.proctoring.capture');
            session()->forget('quiz_id');
        } elseif ($student->level === null || $student->level === '') {
            $redirect = route('student.select-level');
        }

        return response()->json([
            'success' => true,
            'redirect' => $redirect,
        ]);
    }

    /**
     * Log out student (clear session and redirect to login).
     */
    public function logout(Request $request): RedirectResponse
    {
        session()->forget(['student_id', 'student_index']);
        return redirect()->route('student.account.login.form')->with('success', 'Logged out');
    }
}
