<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassGroupStudent;
use App\Models\Quiz;
use App\Models\QuizAcceptance;
use App\Models\QuizSession;
use App\Models\Setting;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class QuizRulesController extends Controller
{
    /**
     * Show quiz rules & warning screen. Optional token (link_token) for context; generic rules when none.
     * When quiz link is invalid or expired, show link-expired view.
     * When quiz has a future starts_at, redirect to countdown page.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $token = $request->route('token');
        $quiz = null;
        if ($token) {
            $quiz = Quiz::with('course')->where('link_token', $token)->first();
            if (!$quiz || (!$quiz->is_published && !$quiz->is_active) || !$quiz->hasEnoughApprovedQuestions()) {
                return view('student.link-expired');
            }
            if ($quiz->ends_at && $quiz->ends_at->isPast()) {
                return view('student.link-expired');
            }
            if ($quiz->starts_at && $quiz->starts_at->isFuture()) {
                return redirect()->route('student.quiz-will-start', ['token' => $token]);
            }
        }
        return view('student.quiz-rules', compact('quiz'));
    }

    /**
     * Show "Quiz will start at X" countdown page when quiz has a future starts_at.
     * When countdown reaches zero, student can proceed to rules.
     */
    public function quizWillStart(Request $request): View|RedirectResponse
    {
        $token = $request->route('token');
        $quiz = Quiz::with('course')->where('link_token', $token)->first();
        if (!$quiz || (!$quiz->is_published && !$quiz->is_active) || !$quiz->hasEnoughApprovedQuestions()) {
            return view('student.link-expired');
        }
        if ($quiz->ends_at && $quiz->ends_at->isPast()) {
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
        $quizId = $request->input('quiz_id');
        $sessionData = ['rules_accepted' => true];
        
        if ($quizId) {
            $request->validate(['quiz_id' => 'exists:quizzes,id']);
            $quiz = Quiz::with('classGroup')->find($quizId);
            
            if ($quiz && $quiz->isActive()) {
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
                        // Check if this student_index has already completed this quiz
                        // Allow retake if student was removed and re-added (no active session)
                        $existingSession = QuizSession::where('quiz_id', $quiz->id)
                            ->whereRaw('UPPER(TRIM(student_index)) = ?', [strtoupper($student->index_number)])
                            ->whereNotNull('ended_at')
                            ->exists();
                        
                        if ($existingSession && $this->isIpDeviceRestrictionEnabled()) {
                            // Check IP hasn't been used for this quiz by a different student
                            $ip = $request->ip();
                            $ipUsedByOther = QuizSession::where('quiz_id', $quiz->id)
                                ->where('ip_address', $ip)
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
                        session()->forget('eligible_courses');
                        
                        return response()->json([
                            'success' => true,
                            'redirect' => route('student.proctoring.capture'),
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
            }
        }

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
