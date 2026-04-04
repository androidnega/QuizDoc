<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\IssuesStudentLoginSmsOtp;
use App\Models\ClassGroupStudent;
use App\Models\Otp;
use App\Models\Student;
use App\Services\StudentUniversalOtp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentAccountController extends Controller
{
    use IssuesStudentLoginSmsOtp;


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
        
        return view('student.account-login', [
            'password_login_enabled' => Student::isPasswordLoginEnabled(),
        ]);
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

        if (Student::isPasswordLoginEnabled()) {
            if ($student->hasPassword()) {
                return response()->json([
                    'success' => true,
                    'step' => 'password',
                    'index_number' => $student->index_number,
                    'message' => 'Enter the password you saved for your account.',
                    'password_login_enabled' => true,
                ]);
            }

            $payload = [
                'success' => true,
                'step' => 'phone',
                'index_number' => $student->index_number,
                'require_password_setup' => true,
                'password_login_enabled' => true,
                'message' => 'Enter your phone number and choose a password you will remember. We will send one SMS code to confirm your number; after that you can sign in with your password without SMS.',
            ];
            if ($student->hasPhone()) {
                $payload['prefill_phone'] = $student->phone_contact;
            }

            return response()->json($payload);
        }

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

        return $this->jsonAfterIssuingOrReusingSmsOtp($student, $indexHash, $smsOwner, null);
    }

    /**
     * Step 2: Send OTP to the given phone (first-time or new phone). Ties phone to account after OTP verify.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'index_number' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'new_password' => 'nullable|string|max:128',
            'new_password_confirmation' => 'nullable|string|max:128',
        ]);
        $inputIndex = trim((string) $request->index_number);

        $student = Student::where('index_number_hash', Student::hashIndexNumber($inputIndex))->first();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Invalid session. Start again.'], 422);
        }
        $inputPhone = trim((string) ($request->phone ?? ''));
        $phone = Student::normalizePhoneForStorage($inputPhone);

        if (!$phone) {
            $storedNormalized = $student->phone_contact ? Student::normalizePhoneForStorage($student->phone_contact) : '';
            if ($storedNormalized) {
                $phone = $storedNormalized;
            }
        }
        if (!$phone || strlen($phone) < 10) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid phone number (e.g. 0244123456, +233244123456).',
            ], 422);
        }

        $otherStudent = Student::where('phone_contact', $phone)->where('id', '!=', $student->id)->first();
        if ($otherStudent) {
            return response()->json([
                'success' => false,
                'message' => 'This phone number is already registered to another student. Use a different number or ask your examiner for help.',
            ], 422);
        }

        if (Student::isPasswordLoginEnabled() && ! $student->hasPassword()) {
            $pwKey = $this->pendingPasswordCacheKey($student->index_number_hash);
            if (! Cache::has($pwKey)) {
                $request->validate([
                    'new_password' => 'required|string|min:8|max:128',
                    'new_password_confirmation' => 'required|same:new_password',
                ]);
                Cache::put($pwKey, Hash::make($request->new_password), now()->addMinutes(20));
            }
        }

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

        return $this->jsonAfterIssuingOrReusingSmsOtp($student, $indexHash, $smsOwner, $phone);
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

        // Configured universal codes (Settings / .env): any valid index, no expiry, no SMS row
        if (StudentUniversalOtp::matches($code)) {
            Cache::forget($this->pendingPasswordCacheKey($indexHash));
            $this->completeStudentLogin($student, null, $name, false);

            return response()->json([
                'success' => true,
                'redirect' => $this->studentLoginRedirect($student),
            ]);
        }

        // Examiner fallback: one-time use; mark used_at
        $fallbackOtp = Otp::findValidExaminerFallbackForIndexAndCode($indexHash, $code);
        if ($fallbackOtp) {
            $fallbackOtp->used_at = now();
            $fallbackOtp->save();
            Cache::forget($this->pendingPasswordCacheKey($indexHash));
            $this->completeStudentLogin($student, null, $name, false);

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
        $this->completeStudentLogin($student, $phone ?? null, $name, true);
        return response()->json([
            'success' => true,
            'redirect' => $this->studentLoginRedirect($student),
        ]);
    }

    /**
     * Sign in with index + password when the feature is enabled and the student has set a password.
     */
    public function verifyPassword(Request $request): JsonResponse
    {
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
        if (! Student::isPasswordLoginEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Password login is not enabled.',
            ], 422);
        }

        $request->validate([
            'index_number' => 'required|string|max:100',
            'password' => 'required|string|max:128',
        ]);
        $inputIndex = trim((string) $request->index_number);
        $indexHash = Student::hashIndexNumber($inputIndex);
        $student = Student::where('index_number_hash', $indexHash)->first();
        if (! $student || ! $student->hasPassword()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid index or password.',
            ], 422);
        }
        if (! Hash::check($request->password, $student->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid index or password.',
            ], 422);
        }

        $this->completeStudentLogin($student, null, null, false);

        return response()->json([
            'success' => true,
            'redirect' => $this->studentLoginRedirect($student),
        ]);
    }

    /**
     * Send SMS OTP when the student chose "Use SMS code instead" from the password step.
     */
    public function requestOtpLogin(Request $request): JsonResponse
    {
        if (session('student_id')) {
            return response()->json([
                'success' => false,
                'message' => 'You are already logged in.',
            ], 422);
        }
        if (! Student::isPasswordLoginEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Password login is not enabled.',
            ], 422);
        }

        $request->validate(['index_number' => 'required|string|max:100']);
        $inputIndex = trim((string) $request->index_number);
        $inputNormalized = strtolower($inputIndex);
        $indexHash = Student::hashIndexNumber($inputIndex);

        $quizId = session('quiz_id');
        if ($quizId) {
            $sessionIndex = session('index_number');
            if (! $sessionIndex || strtoupper(trim((string) $sessionIndex)) !== strtoupper(trim($inputIndex))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session expired. Start from your quiz link again.',
                ], 422);
            }
        } else {
            $cgStudent = ClassGroupStudent::whereRaw('LOWER(TRIM(index_number)) = ?', [$inputNormalized])->first();
            if (! $cgStudent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Index number not found.',
                ], 422);
            }
        }

        $student = Student::where('index_number_hash', $indexHash)->first();
        if (! $student || ! $student->hasPhone()) {
            return response()->json([
                'success' => false,
                'message' => 'Add a phone number first using the setup steps.',
            ], 422);
        }

        $smsOwner = $quizId
            ? $this->smsOwnerForQuiz(\App\Models\Quiz::with('classGroup')->find((int) $quizId))
            : $this->smsOwnerForIndex($student->index_number);

        return $this->jsonAfterIssuingOrReusingSmsOtp($student, $indexHash, $smsOwner, null);
    }

    private function smsOwnerForQuiz(?\App\Models\Quiz $quiz): ?\App\Models\User
    {
        if (! $quiz) {
            return null;
        }
        $quiz->load(['classGroup.examiner', 'examiner']);
        $classGroup = $quiz->classGroup;
        if ($classGroup) {
            $coordinator = \App\Models\User::coordinatorWithSmsBalanceForClassGroup($classGroup);
            if ($coordinator) {
                return $coordinator;
            }
        }
        $candidates = [];
        if ($quiz->classGroup?->examiner) {
            $candidates[] = $quiz->classGroup->examiner;
        }
        if ($quiz->examiner && ! $quiz->classGroup?->examiner?->is($quiz->examiner)) {
            $candidates[] = $quiz->examiner;
        }
        foreach ($candidates as $examiner) {
            if ($examiner && $examiner->isExaminer() && $examiner->sms_remaining > 0) {
                return $examiner;
            }
        }
        $classGroupId = $quiz->class_group_id;
        if ($classGroupId && \Illuminate\Support\Facades\Schema::hasColumn('class_group_course', 'examiner_id')) {
            $examinerIds = \Illuminate\Support\Facades\DB::table('class_group_course')
                ->where('class_group_id', $classGroupId)
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

    private function completeStudentLogin(Student $student, ?string $phone, ?string $name, bool $applyPendingPassword = true): void
    {
        if ($applyPendingPassword) {
            $pending = Cache::pull($this->pendingPasswordCacheKey($student->index_number_hash));
            if ($pending) {
                $student->password = $pending;
            }
        }
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
