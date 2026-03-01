<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassGroupStudent;
use App\Models\Quiz;
use App\Models\QuizAcceptance;
use App\Models\QuizSession;
use App\Models\Setting;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuizRulesController extends Controller
{
    /**
     * Show quiz rules & warning screen. Optional token (link_token) for context; generic rules when none.
     * When quiz link is invalid or expired, show link-expired view.
     * When quiz has a future starts_at, redirect to countdown page.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $token = $request->route('token') ?? $request->query('t') ?? $request->query('token');
        $quiz = null;
        if ($token) {
            $token = trim((string) $token);
            $quiz = Quiz::with(['course', 'classGroup'])->where('link_token', $token)->first();
            if (!$quiz || !$quiz->isAvailableForStudent(false)) {
                return view('student.link-expired');
            }
            if ($quiz->starts_at && $quiz->starts_at->isFuture()) {
                return redirect()->route('student.quiz-will-start', ['token' => $token]);
            }
            // Keep quiz context in session so Accept can recover even if client payload misses quiz_id.
            session([
                'quiz_id_for_login' => $quiz->id,
                'quiz_link_token' => $quiz->link_token,
            ]);
            // Canonicalize old query-style links to /t/{token} while preserving flow.
            if (!$request->route('token')) {
                return redirect()->route('student.rules.show.quiz', ['token' => $quiz->link_token]);
            }
        }
        // Single source of truth: quiz effective allowed devices (class group → quiz → desktop).
        $allowedDevices = $quiz ? (function () use ($quiz) {
            $quiz->loadMissing('classGroup');
            return $quiz->getEffectiveAllowedDevices();
        })() : 'desktop';
        $mobileAllowed = in_array($allowedDevices, ['mobile', 'both'], true);
        return view('student.quiz-rules', compact('quiz', 'mobileAllowed'));
    }

    /**
     * Show "Quiz will start at X" countdown page when quiz has a future starts_at.
     * When countdown reaches zero, student can proceed to rules.
     */
    public function quizWillStart(Request $request): View|RedirectResponse
    {
        $token = $request->route('token');
        $quiz = Quiz::with('course')->where('link_token', $token)->first();
        if (!$quiz || !$quiz->isAvailableForStudent(false)) {
            return view('student.link-expired');
        }
        if (!$quiz->starts_at || $quiz->starts_at->isPast()) {
            return redirect()->route('student.rules.show.quiz', ['token' => $token]);
        }
        return view('student.quiz-will-start', compact('quiz'));
    }

    /**
     * Store acceptance (dos & don'ts accepted). 
     * If student is already logged in, skip login form and go directly to proctoring.
     * Otherwise, store quiz_id in session so login validates index against this quiz's class group.
     */
    public function accept(Request $request): JsonResponse
    {
        $quizId = $request->input('quiz_id') ?: session('quiz_id_for_login');
        if (!$quizId && session('quiz_link_token')) {
            $quizId = Quiz::where('link_token', session('quiz_link_token'))->value('id');
        }
        $sessionData = ['rules_accepted' => true];
        
        if (!$quizId) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz session was not found. Please reopen the quiz link and try again.',
            ], 422);
        }
        $quiz = Quiz::with('classGroup')->find($quizId);
        if (!$quiz || !$quiz->isAvailableForStudent(false)) {
            return response()->json([
                'success' => false,
                'message' => 'This quiz is not available right now. Please reopen the quiz link.',
            ], 422);
        }
        if ($quiz->starts_at && $quiz->starts_at->isFuture()) {
            return response()->json([
                'success' => true,
                'redirect' => route('student.quiz-will-start', ['token' => $quiz->link_token]),
            ]);
        }

        // Check if student is already logged in
        $studentId = session('student_id');
        $student = $studentId ? Student::find($studentId) : null;
        
        if ($student && $student->index_number) {
            $allowed = false;
            if ($quiz->class_group_id) {
                $allowed = ClassGroupStudent::where('class_group_id', $quiz->class_group_id)
                    ->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper($student->index_number)])
                    ->exists();
            }
            if (!$allowed && $quiz->academic_class_id && $quiz->academic_year_id) {
                $allowed = (int) $student->academic_class_id === (int) $quiz->academic_class_id
                    && (int) $student->academic_year_id === (int) $quiz->academic_year_id
                    && (!$quiz->level_id || (int) $student->level_id === (int) $quiz->level_id)
                    && (!$quiz->semester_id || (int) $student->semester_id === (int) $quiz->semester_id);
            }
            if ($allowed) {
                // Reuse/lock attempt per student+quiz across tabs.
                $existingSession = QuizSession::where('quiz_id', $quiz->id)
                    ->whereRaw('UPPER(TRIM(student_index)) = ?', [strtoupper($student->index_number)])
                    ->latest('id')
                    ->first();

                if ($existingSession && $existingSession->ended_at !== null) {
                    session(['quiz_session_token' => $existingSession->session_token]);
                    return response()->json([
                        'success' => true,
                        'redirect' => route('student.result', ['token' => $existingSession->session_token]),
                    ]);
                }

                if ($existingSession && $this->isIpDeviceRestrictionEnabled()) {
                    // Check IP hasn't been used for this quiz by a different student (ignore reset sessions)
                    $ip = $request->ip();
                    $ipUsedByOther = QuizSession::where('quiz_id', $quiz->id)
                        ->where('ip_address', $ip)
                        ->whereRaw("ip_address NOT LIKE 'reset-%'")
                        ->whereRaw('UPPER(TRIM(student_index)) != ?', [strtoupper($student->index_number)])
                        ->exists();
                    
                    if ($ipUsedByOther) {
                        return response()->json([
                            'success' => false,
                            'message' => 'This IP address has already been used for this quiz by another student. Access denied.',
                        ], 422);
                    }
                }
                
                // Record acceptance (will overwrite if exists)
                QuizAcceptance::updateOrCreate(
                    [
                        'quiz_id' => $quiz->id,
                        'index_number' => $student->index_number,
                    ],
                    [
                        'ip_address' => $request->ip(),
                        'accepted_at' => now(),
                    ]
                );
                
                // Set quiz session data and redirect to proctoring
                session([
                    'quiz_id' => $quiz->id,
                    'index_number' => $student->index_number,
                    'rules_accepted' => true,
                ]);
                if ($existingSession) {
                    session(['quiz_session_token' => $existingSession->session_token]);
                }
                session()->forget('eligible_courses');

                return response()->json([
                    'success' => true,
                    'redirect' => $existingSession
                        ? ($existingSession->start_time !== null ? route('student.quiz.show') : route('student.quiz.ready'))
                        : route('student.proctoring.capture'),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Your index number is not registered for this quiz class group.',
                ], 422);
            }
        }

        // Student not logged in, proceed with normal login flow
        $indexNumber = $request->input('index_number') ?? session('student_index') ?? 'pending';
        QuizAcceptance::create([
            'quiz_id' => $quiz->id,
            'index_number' => $indexNumber,
            'ip_address' => $request->ip(),
            'accepted_at' => now(),
        ]);
        $sessionData['quiz_id_for_login'] = $quiz->id;
        $sessionData['quiz_link_token'] = $quiz->link_token;

        session($sessionData);
        session()->forget('eligible_courses');

        return response()->json([
            'success' => true,
            'redirect' => route('student.login.form'),
        ]);
    }

    private function isIpDeviceRestrictionEnabled(): bool
    {
        return Setting::getValue(Setting::KEY_DISABLE_IP_DEVICE_RESTRICTIONS, '0') !== '1';
    }
}
