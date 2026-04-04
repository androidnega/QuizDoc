<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassGroupStudent;
use App\Models\Otp;
use App\Models\Student;
use App\Services\ArkeselService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentAccountController extends Controller
{

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

        // Coordinator (who has the student's class groups) or examiner with SMS balance (for deducting); if none, we still send OTP so students can log in
        $smsOwner = $this->smsOwnerForIndex($cgStudent->index_number);

        // STEP 1 — If a code was just sent, avoid duplicate SMS (code itself does not auto-expire when expires_at is null)
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

        // CASE B — Issue a new login code (replace any previous SMS codes for this index)
        $code = (string) random_int(100000, 999999);
        Otp::deleteStudentLoginOtpsForIndex($indexHash);
        Otp::create([
            'index_number_hash' => $indexHash,
            'type' => Otp::TYPE_STUDENT_LOGIN,
            'code' => $code,
            'expires_at' => null,
        ]);
        $message = 'Your QuizSnap login code is: ' . $code . '. Do not share. This code stays valid until you receive a new one.';
        $result = ArkeselService::sendSms($student->phone_contact, $message);
        if (!$result['success']) {
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
            'message' => 'A code has been sent to your registered number. It stays valid until you request a new code.',
            'has_name' => ! empty($student->student_name),
            'can_resend' => true,
            'days_remaining' => null,
            'otp_never_expires' => true,
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
        $phone = Student::normalizePhoneForStorage($inputPhone);

        if (!$phone) {
            $storedNormalized = $student->phone_contact ? Student::normalizePhoneForStorage($student->phone_contact) : '';
            if ($storedNormalized) {
                // Registered students can request a new OTP without re-entering phone.
                $phone = $storedNormalized;
            }
        }
        if (!$phone || strlen($phone) < 10) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid phone number (e.g. 0244123456, +233244123456).',
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

        // Examiner with SMS balance (for deducting); if none, we still send OTP so students can log in
        $smsOwner = $this->smsOwnerForIndex($student->index_number);

        $indexHash = $student->index_number_hash;

        $resendKey = 'otp_resend:'.$indexHash;
        if (Cache::has($resendKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Please wait about a minute before requesting another code.',
                'can_resend' => false,
            ], 422);
        }

        // Generate new OTP, save to DB, send SMS (replaces previous SMS codes for this index)
        $code = (string) random_int(100000, 999999);
        Otp::deleteStudentLoginOtpsForIndex($indexHash);
        Otp::create([
            'index_number_hash' => $indexHash,
            'type' => Otp::TYPE_STUDENT_LOGIN,
            'code' => $code,
            'phone' => $phone,
            'expires_at' => null,
        ]);

        $message = 'Your QuizSnap login code is: ' . $code . '. Do not share. This code stays valid until you receive a new one.';
        $result = ArkeselService::sendSms($phone, $message);
        if (!$result['success']) {
            $msg = $result['message'] ?? 'We couldn\'t send the code.';
            if (strpos($msg, 'try again') === false && strpos($msg, 'Try again') === false) {
                $msg .= ' Please try again.';
            }
            return response()->json(['success' => false, 'message' => $msg], 422);
        }
        if ($smsOwner) {
            $smsOwner->increment('sms_used');
        }
        Cache::put($resendKey, 1, now()->addSeconds(Otp::RESEND_COOLDOWN_SECONDS));

        return response()->json([
            'success' => true,
            'step' => 'otp',
            'index_number' => $student->index_number,
            'message' => 'A code has been sent to your number. It stays valid until you request a new code.',
            'has_name' => ! empty($student->student_name),
            'can_resend' => true,
            'days_remaining' => null,
            'otp_never_expires' => true,
        ]);
    }

    /** User whose SMS balance is deducted for this index: coordinator (who has the student's class groups) first, then examiner (class group owner or lecturers). */
    private function smsOwnerForIndex(string $indexNumber): ?\App\Models\User
    {
        $cgStudents = ClassGroupStudent::whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($indexNumber))])
            ->with('classGroup.examiner')
            ->get();

        // 1) Coordinator who has any of the student's class groups (and has SMS balance)
        foreach ($cgStudents as $cg) {
            $classGroup = $cg->classGroup;
            if ($classGroup) {
                $coordinator = \App\Models\User::coordinatorWithSmsBalanceForClassGroup($classGroup);
                if ($coordinator) {
                    return $coordinator;
                }
            }
        }

        // 2) Class group owner (examiner_id on class_groups)
        foreach ($cgStudents as $cg) {
            $examiner = $cg->classGroup?->examiner;
            if ($examiner && $examiner->isExaminer() && $examiner->sms_remaining > 0) {
                return $examiner;
            }
        }

        // 3) Lecturers assigned to this class group via class_group_course (per-course examiner_id)
        $classGroupIds = $cgStudents->pluck('class_group_id')->unique()->filter()->values()->all();
        if (empty($classGroupIds)) {
            return null;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('class_group_course', 'examiner_id')) {
            $examinerIds = \Illuminate\Support\Facades\DB::table('class_group_course')
                ->whereIn('class_group_id', $classGroupIds)
                ->whereNotNull('examiner_id')
                ->distinct()
                ->pluck('examiner_id');
            foreach ($examinerIds as $eid) {
                $examiner = \App\Models\User::find($eid);
                if ($examiner && $examiner->isExaminer() && $examiner->sms_remaining > 0) {
                    return $examiner;
                }
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
            'code' => 'required|string',
            'student_name' => 'nullable|string|max:255',
        ]);
        $inputIndex = trim((string) $request->index_number);
        $code = preg_replace('/\D/', '', (string) $request->code);
        if (strlen($code) !== 6) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter the 6-digit code.',
            ], 422);
        }
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

        // Examiner fallback: one-time use; mark used_at
        $fallbackOtp = Otp::findValidExaminerFallbackForIndexAndCode($indexHash, $code);
        if ($fallbackOtp) {
            $fallbackOtp->used_at = now();
            $fallbackOtp->save();
            $this->completeStudentLogin($student, null, $name);

            return response()->json([
                'success' => true,
                'redirect' => $this->studentLoginRedirect($student),
            ]);
        }

        // Student login SMS code: any matching non-expired row; do NOT set used_at
        $lastOtp = Otp::findValidStudentLoginForIndexAndCode($indexHash, $code);
        if (! $lastOtp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid code. Check the digits or ask your examiner for a new login code.',
            ], 422);
        }

        $phone = $lastOtp->phone ? (Student::normalizePhoneForStorage($lastOtp->phone) ?? $lastOtp->phone) : null;
        if ($phone) {
            $otherStudent = Student::where('phone_contact', $phone)->where('id', '!=', $student->id)->first();
            if ($otherStudent) {
                return response()->json([
                    'success' => false,
                    'message' => 'This phone number is already registered to another student. Use a different number.',
                ], 422);
            }
        }
        $this->completeStudentLogin($student, $phone ?? null, $name);
        return response()->json([
            'success' => true,
            'redirect' => $this->studentLoginRedirect($student),
        ]);
    }

    private function completeStudentLogin(Student $student, ?string $phone, ?string $name): void
    {
        if ($phone) {
            $student->phone_contact = $phone;
        }
        if ($name !== null && $name !== '') {
            $student->student_name = ucwords(strtolower(trim($name)));
        }
        $student->save();

        session([
            'student_id' => $student->id,
            'student_index' => $student->index_number,
        ]);
    }

    private function studentLoginRedirect(Student $student): string
    {
        if (session()->has('quiz_id')) {
            session()->forget('quiz_id');
            return route('student.proctoring.capture');
        }
        if ($student->level === null || $student->level === '') {
            return route('student.select-level');
        }
        return route('dashboard');
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
