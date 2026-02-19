<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassGroupStudent;
use App\Models\Otp;
use App\Models\Student;
use App\Services\ArkeselService;
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

        // Check examiner SMS balance: student must be linked to an examiner with remaining SMS
        $examiner = $this->examinerWithSmsBalanceForIndex($cgStudent->index_number);
        if (!$examiner) {
            return response()->json([
                'success' => false,
                'message' => 'We\'re unable to send your login code right now. Please contact your lecturer or course administrator for assistance.',
            ], 422);
        }

        // STEP 1 — Check last OTP for this index (type = student_login)
        $lastOtp = Otp::latestStudentLoginForIndex($indexHash);

        // CASE A — OTP exists AND is within 14 days: do not generate/send; allow use of existing OTP
        if ($lastOtp && $lastOtp->isWithinValidityWindow()) {
            $daysRemaining = $lastOtp->daysRemaining();
            $dayText = $daysRemaining === 1 ? '1 day' : $daysRemaining . ' days';
            return response()->json([
                'success' => true,
                'step' => 'otp',
                'index_number' => $student->index_number,
                'message' => 'Your existing OTP is still valid. Please use the OTP previously sent to you. It expires in ' . $dayText . '.',
                'has_name' => !empty($student->student_name),
                'can_resend' => false,
                'days_remaining' => $daysRemaining,
            ]);
        }

        // CASE B — No OTP or older than 14 days: generate new OTP, save, send, replace old one
        $code = (string) random_int(100000, 999999);
        Otp::create([
            'index_number_hash' => $indexHash,
            'type' => Otp::TYPE_STUDENT_LOGIN,
            'code' => $code,
            'expires_at' => now()->addDays(Otp::STUDENT_LOGIN_VALID_DAYS),
        ]);
        $message = 'Your QuizSnap login code is: ' . $code . '. Do not share. Valid for 14 days.';
        $result = ArkeselService::sendSms($student->phone_contact, $message);
        if (!$result['success']) {
            $msg = $result['message'] ?? 'We couldn\'t send the code.';
            if (strpos($msg, 'try again') === false && strpos($msg, 'Try again') === false) {
                $msg .= ' Please try again.';
            }
            return response()->json(['success' => false, 'message' => $msg], 422);
        }
        $examiner->increment('sms_used');
        return response()->json([
            'success' => true,
            'step' => 'otp',
            'index_number' => $student->index_number,
            'message' => 'A code has been sent to your registered number. This code is valid for 14 days.',
            'has_name' => !empty($student->student_name),
            'can_resend' => false,
            'days_remaining' => Otp::STUDENT_LOGIN_VALID_DAYS,
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

        $indexHash = $student->index_number_hash;

        // Resend rule: if student already has phone, only allow new OTP after 14 days
        if ($storedNormalized !== '') {
            $lastOtp = Otp::latestStudentLoginForIndex($indexHash);
            if ($lastOtp && $lastOtp->isWithinValidityWindow()) {
                $daysRemaining = $lastOtp->daysRemaining();
                $dayText = $daysRemaining === 1 ? '1 day' : $daysRemaining . ' days';
                return response()->json([
                    'success' => false,
                    'message' => 'Your existing OTP is still valid. You can request a new code in ' . $dayText . '.',
                    'can_resend' => false,
                    'days_remaining' => $daysRemaining,
                ], 422);
            }
        }

        // Generate new OTP, save to DB, send SMS (replace old one — latest wins)
        $code = (string) random_int(100000, 999999);
        Otp::create([
            'index_number_hash' => $indexHash,
            'type' => Otp::TYPE_STUDENT_LOGIN,
            'code' => $code,
            'phone' => $student->phone_contact ? null : $phone,
            'expires_at' => now()->addDays(Otp::STUDENT_LOGIN_VALID_DAYS),
        ]);

        $message = 'Your QuizSnap login code is: ' . $code . '. Do not share. Valid for 14 days.';
        $result = ArkeselService::sendSms($phone, $message);
        if (!$result['success']) {
            $msg = $result['message'] ?? 'We couldn\'t send the code.';
            if (strpos($msg, 'try again') === false && strpos($msg, 'Try again') === false) {
                $msg .= ' Please try again.';
            }
            return response()->json(['success' => false, 'message' => $msg], 422);
        }
        $examiner->increment('sms_used');
        return response()->json([
            'success' => true,
            'step' => 'otp',
            'index_number' => $student->index_number,
            'message' => 'A code has been sent to your number. It is valid for 14 days.',
            'has_name' => !empty($student->student_name),
            'can_resend' => false,
            'days_remaining' => Otp::STUDENT_LOGIN_VALID_DAYS,
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

        // Examiner fallback: one-time use; mark used_at and invalidate immediately
        $fallbackOtp = Otp::latestValidExaminerFallbackForIndex($indexHash);
        if ($fallbackOtp && $fallbackOtp->code === $code) {
            $fallbackOtp->used_at = now();
            $fallbackOtp->save();
            $this->completeStudentLogin($student, null, $name);
            return response()->json([
                'success' => true,
                'redirect' => $this->studentLoginRedirect($student),
            ]);
        }

        // Student login OTP: reusable for 14 days; do NOT set used_at
        $lastOtp = Otp::latestStudentLoginForIndex($indexHash);
        if (!$lastOtp || !$lastOtp->isWithinValidityWindow() || $lastOtp->code !== $code) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired code. Please request a new one.',
            ], 422);
        }

        $phone = $lastOtp->phone;
        if ($phone && !$student->phone_contact) {
            $otherStudent = Student::where('phone_contact', $phone)->where('id', '!=', $student->id)->first();
            if ($otherStudent) {
                return response()->json([
                    'success' => false,
                    'message' => 'This phone number is already registered to another student. Use a different number.',
                ], 422);
            }
        }
        $this->completeStudentLogin($student, $phone, $name);
        return response()->json([
            'success' => true,
            'redirect' => $this->studentLoginRedirect($student),
        ]);
    }

    private function completeStudentLogin(Student $student, ?string $phone, ?string $name): void
    {
        if ($phone && !$student->phone_contact) {
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
