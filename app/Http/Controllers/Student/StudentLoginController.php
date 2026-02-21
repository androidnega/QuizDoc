<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassGroupStudent;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\Setting;
use App\Models\Otp;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Services\ArkeselService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class StudentLoginController extends Controller
{
    /**
     * Show index number entry. Quiz is fixed from the link (stored in session after rules acceptance).
     */
    public function showLoginForm(Request $request): View|RedirectResponse
    {
        // Prevent login if already authenticated
        if (session('student_id')) {
            return redirect()->route('dashboard')
                ->with('info', 'You are already logged in. Please logout first to login with a different account.');
        }
        
        if (session('admin_authenticated', false)) {
            return redirect()->route('dashboard')
                ->with('info', 'You are already logged in as staff. Please logout first to take a quiz.');
        }
        
        $quizId = session('quiz_id_for_login');
        if (!$quizId) {
            $quizToken = session('quiz_link_token');
            if ($quizToken) {
                return redirect()->route('student.rules.show.quiz', ['token' => $quizToken])
                    ->with('error', 'Your quiz session expired. Please accept the rules again.');
            }
            return redirect()->route('student.landing')
                ->with('error', 'Your quiz session expired. Please open your quiz link again.');
        }
        $quiz = Quiz::find($quizId);
        if (!$quiz || !$quiz->isAvailableForStudent()) {
            if ($quiz && $quiz->starts_at && $quiz->starts_at->isFuture()) {
                return redirect()->route('student.quiz-will-start', ['token' => $quiz->link_token]);
            }
            if ($quiz && $quiz->link_token) {
                return redirect()->route('student.rules.show.quiz', ['token' => $quiz->link_token])
                    ->with('error', 'This quiz is not currently available.');
            }
            return redirect()->route('student.landing')->with('error', 'This quiz is not currently available.');
        }
        return view('student.login', ['quiz' => $quiz]);
    }

    /**
     * Verify index against the quiz's class group student list. On success store quiz_id + index_number, redirect to proctoring.
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
                'message' => 'You are already logged in as staff. Please logout first to take a quiz.',
            ], 422);
        }
        
        $request->validate(['index_number' => 'required|string']);
        $indexNumber = strtoupper(trim($request->index_number));
        $quizId = session('quiz_id_for_login');
        if (!$quizId) {
            return response()->json([
                'success' => false,
                'message' => 'Session expired. Please start from the quiz link again.',
            ], 422);
        }

        $quiz = Quiz::with('classGroup')->find($quizId);
        if (!$quiz || !$quiz->isAvailableForStudent()) {
            return response()->json([
                'success' => false,
                'message' => 'This quiz is no longer available.',
            ], 422);
        }
        if (!$quiz->class_group_id && !$quiz->academic_class_id) {
            return response()->json([
                'success' => false,
                'message' => 'This quiz is no longer available.',
            ], 422);
        }

        $exists = false;
        if ($quiz->class_group_id) {
            $exists = ClassGroupStudent::where('class_group_id', $quiz->class_group_id)
                ->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper($indexNumber)])
                ->exists();
        }
        if (!$exists && $quiz->academic_class_id && $quiz->academic_year_id) {
            $studentRecord = Student::where('index_number_hash', Student::hashIndexNumber($indexNumber))->first();
            if ($studentRecord
                && (int) $studentRecord->academic_class_id === (int) $quiz->academic_class_id
                && (int) $studentRecord->academic_year_id === (int) $quiz->academic_year_id
                && (!$quiz->level_id || (int) $studentRecord->level_id === (int) $quiz->level_id)
                && (!$quiz->semester_id || (int) $studentRecord->semester_id === (int) $quiz->semester_id)) {
                $exists = true;
            }
        }
        if (!$exists) {
            return response()->json([
                'success' => false,
                'message' => 'Index number not found. You must belong to a class first.',
            ], 422);
        }

        if ($this->isIpDeviceRestrictionEnabled()) {
            $ip = $request->ip();
            if (QuizSession::where('quiz_id', $quiz->id)->where('ip_address', $ip)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This IP address has already been used for this quiz. Access denied.',
                ], 422);
            }
        }

        session([
            'quiz_id' => $quiz->id,
            'index_number' => $indexNumber,
        ]);
        session()->forget('quiz_id_for_login');

        $indexHash = Student::hashIndexNumber($indexNumber);
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
                'message' => 'Enter an active phone number to receive an SMS. We\'ll save it to your index for future logins. The code we send will also be your login for the next 14 days—keep it.',
            ]);
        }

        // Examiner (from class group, quiz, or class_group_course lecturers) must have SMS balance
        $quiz->load(['classGroup.examiner', 'examiner']);
        $examiner = $this->examinerWithSmsBalanceForQuiz($quiz);
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
        $message = 'Your QuizSnap code is: ' . $code . '. Use it to continue the quiz and as login for 14 days. Do not share.';
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
     * Get an examiner with SMS balance for this quiz: class group owner, then quiz examiner, then lecturers from class_group_course.
     */
    private function examinerWithSmsBalanceForQuiz(Quiz $quiz): ?User
    {
        $candidates = [];
        if ($quiz->classGroup?->examiner) {
            $candidates[] = $quiz->classGroup->examiner;
        }
        if ($quiz->examiner && !$quiz->classGroup?->examiner?->is($quiz->examiner)) {
            $candidates[] = $quiz->examiner;
        }
        foreach ($candidates as $examiner) {
            if ($examiner && $examiner->isExaminer() && $examiner->sms_remaining > 0) {
                return $examiner;
            }
        }
        $classGroupId = $quiz->class_group_id;
        if ($classGroupId && Schema::hasColumn('class_group_course', 'examiner_id')) {
            $examinerIds = DB::table('class_group_course')
                ->where('class_group_id', $classGroupId)
                ->whereNotNull('examiner_id')
                ->distinct()
                ->pluck('examiner_id');
            foreach ($examinerIds as $eid) {
                $examiner = User::find($eid);
                if ($examiner && $examiner->isExaminer() && $examiner->sms_remaining > 0) {
                    return $examiner;
                }
            }
        }
        return null;
    }

    private function isIpDeviceRestrictionEnabled(): bool
    {
        return Setting::getValue(Setting::KEY_DISABLE_IP_DEVICE_RESTRICTIONS, '0') !== '1';
    }
}
