<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Events\DataUpdated;
use App\Jobs\SendQuizResultReadyNotification;
use App\Models\Answer;
use App\Models\Question;
use App\Models\QuizSession;
use App\Models\QuizViolation;
use App\Models\Result;
use App\Models\Setting;
use App\Models\Student;
use App\Services\AiQuestionService;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class StudentQuizController extends Controller
{
    private const MAX_QUIZ_VIOLATION_CAPTURES = 5;
    /**
     * System readiness screen after pre-quiz face capture.
     */
    public function ready(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return redirect()->route('student.landing')->with('error', 'Error');
        }
        $session = QuizSession::with('quiz.course')->where('session_token', $token)->firstOrFail();
        if ($session->ended_at) {
            return redirect()->to($this->quizCompleteUrl());
        }
        if ($session->start_time !== null) {
            return redirect()->route('student.quiz.show');
        }
        $questionCount = is_array($session->assigned_question_ids)
            ? count($session->assigned_question_ids)
            : 0;
        return view('student.quiz-ready', [
            'session' => $session,
            'courseName' => $session->quiz->course->name ?? $session->quiz->title,
            'durationMinutes' => $session->quiz->duration_minutes,
            'questionCount' => $questionCount,
        ]);
    }

    /**
     * Start quiz session with camera verification.
     * Marks camera_verified = true and camera_started_at = now().
     */
    public function startSession(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'No active quiz session.'], 401);
            }
            return redirect()->route('student.landing')->with('error', 'Error');
        }
        $session = QuizSession::where('session_token', $token)->first();
        if (!$session) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Session not found.'], 404);
            }
            return redirect()->route('student.landing')->with('error', 'Error');
        }
        if ($session->ended_at) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Session already ended.'], 403);
            }
            return redirect()->to($this->quizCompleteUrl());
        }
        if ($session->start_time !== null) {
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'redirect' => route('student.quiz.show')]);
            }
            return redirect()->route('student.quiz.show');
        }
        $session->update([
            'camera_verified' => true,
            'camera_started_at' => now(),
        ]);
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => route('student.quiz.show'),
            ]);
        }
        return redirect()->route('student.quiz.show');
    }

    /**
     * Show quiz interface (StudentQuiz): timer, questions, auto-save.
     * Session token resolved from session (not URL). Timer starts on first load (start_time set here if null).
     * Requires camera_verified = true before allowing quiz to start.
     */
    public function show(Request $request): View|JsonResponse|\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return redirect()->route('student.landing')->with('error', 'Error');
        }
        $session = QuizSession::with(['quiz', 'quiz.questions'])->where('session_token', $token)->firstOrFail();
        if ($session->ended_at) {
            return redirect()->to($this->quizCompleteUrl());
        }
        // Enforce pre-capture gate only; do not use legacy /quiz/ready screen.
        if (!$session->camera_verified) {
            return redirect()->route('student.proctoring.capture')->with('error', 'Error');
        }
        if ($session->start_time === null) {
            $session->update(['start_time' => now()]);
        }
        if ($this->isIpDeviceRestrictionEnabled() && $session->ip_address !== $request->ip()) {
            QuizViolation::create([
                'quiz_session_id' => $session->id,
                'type' => 'multiple_ip',
                'severity' => QuizViolation::severityForType('multiple_ip'),
                'metadata' => json_encode(['expected' => $session->ip_address, 'got' => $request->ip()]),
                'occurred_at' => now(),
            ]);
            return redirect()->route('student.landing')->with('error', 'Error');
        }
        $questionIds = $session->assigned_question_ids ?? [];
        $questions = collect();
        $shuffledOptionsByQuestion = $session->shuffled_question_options ?? [];
        if (!empty($questionIds)) {
            $ids = array_map('intval', $questionIds);
            $questions = Question::whereIn('id', $ids)->get();
            $questions = $questions->sortBy(fn ($q) => array_search($q->id, $ids))->values();
        }
        $durationSeconds = $session->quiz->duration_minutes * 60;
        $elapsed = now()->diffInSeconds($session->start_time);
        $remaining = max(0, $durationSeconds - $elapsed);
        if ($session->quiz->ends_at && $session->quiz->ends_at->isPast()) {
            return redirect()->to($this->resultUrlWithToken($token));
        }
        if ($remaining <= 0) {
            return redirect()->to($this->resultUrlWithToken($token));
        }
        $savedAnswers = $session->answers()->pluck('student_answer', 'question_id')->toArray();
        $totalQuestions = $questions->count();
        $answeredCount = $questions->filter(function ($q) use ($savedAnswers) {
            $a = $savedAnswers[$q->id] ?? '';
            return trim((string) $a) !== '';
        })->count();
        $perPage = $totalQuestions <= 20 ? 10 : 20;
        $totalPages = $totalQuestions > 0 ? (int) ceil($totalQuestions / $perPage) : 1;
        $proctoringCameraRequired = Setting::getValue(Setting::KEY_PROCTORING_CAMERA_REQUIRED, '1') === '1';
        $proctoringFaceMonitor = Setting::getValue(Setting::KEY_PROCTORING_FACE_MONITOR, '1') === '1';
        $proctoringTabSwitch = Setting::getValue(Setting::KEY_PROCTORING_TAB_SWITCH, '1') === '1';
        $proctoringObjectDetect = Setting::getValue(Setting::KEY_PROCTORING_OBJECT_DETECT, '1') === '1';
        $proctoringBlockRightClick = Setting::getValue(Setting::KEY_PROCTORING_BLOCK_RIGHT_CLICK, '1') === '1';
        $proctoringBlockCopyPaste = Setting::getValue(Setting::KEY_PROCTORING_BLOCK_COPY_PASTE, '1') === '1';

        return response()
            ->view('student.quiz', [
                'session' => $session,
                'questions' => $questions,
                'shuffledOptionsByQuestion' => $shuffledOptionsByQuestion,
                'savedAnswers' => $savedAnswers,
                'answeredCount' => $answeredCount,
                'durationSeconds' => $durationSeconds,
                'remainingSeconds' => $remaining,
                'perPage' => $perPage,
                'totalPages' => $totalPages,
                'proctoringCameraRequired' => $proctoringCameraRequired,
                'proctoringFaceMonitor' => $proctoringFaceMonitor,
                'proctoringTabSwitch' => $proctoringTabSwitch,
                'proctoringObjectDetect' => $proctoringObjectDetect,
                'proctoringBlockRightClick' => $proctoringBlockRightClick,
                'proctoringBlockCopyPaste' => $proctoringBlockCopyPaste,
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Server time sync for quiz timer: returns server time, session start, and duration so client can correct drift.
     */
    public function timeSync(Request $request): JsonResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return response()->json(['error' => 'No session'], 401);
        }
        $session = QuizSession::with('quiz')->where('session_token', $token)->first();
        if (!$session || $session->ended_at) {
            return response()->json(['error' => 'Session ended or invalid'], 404);
        }
        $start = $session->start_time ? $session->start_time->timestamp : now()->timestamp;
        $durationSeconds = $session->quiz->duration_minutes * 60;
        $serverTime = now()->timestamp;
        $remaining = max(0, $durationSeconds - ($serverTime - $start));
        if ($session->quiz->ends_at && $session->quiz->ends_at->timestamp < $serverTime) {
            $remaining = 0;
        }
        return response()->json([
            'server_time' => $serverTime,
            'start_time' => $start,
            'duration_seconds' => $durationSeconds,
            'remaining_seconds' => (int) $remaining,
        ]);
    }

    /**
     * Auto-save single answer. Session resolved from HttpOnly session only.
     * Rejects if session is auto-submitted.
     */
    public function saveAnswer(Request $request): JsonResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Session expired.'], 401);
        }
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'answer' => 'nullable|string',
        ]);
        $session = QuizSession::where('session_token', $token)->firstOrFail();
        if ($session->ended_at) {
            return response()->json(['success' => false, 'message' => 'Quiz ended.'], 403);
        }
        if ($session->auto_submitted) {
            return response()->json(['success' => false, 'message' => 'Quiz was auto-submitted due to violations.'], 403);
        }
        if ($this->isIpDeviceRestrictionEnabled() && $session->ip_address !== $request->ip()) {
            return response()->json(['success' => false], 403);
        }
        Answer::updateOrCreate(
            [
                'quiz_session_id' => $session->id,
                'question_id' => $request->question_id,
            ],
            [
                'student_answer' => $request->answer,
                'answered_at' => now(),
            ]
        );
        return response()->json(['success' => true]);
    }

    /**
     * Save multiple answers in one request. Session resolved from HttpOnly session only.
     * Rejects if session is auto-submitted.
     */
    public function saveAnswersBatch(Request $request): JsonResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Session expired.'], 401);
        }
        $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.answer' => 'nullable|string',
        ]);
        $session = QuizSession::where('session_token', $token)->firstOrFail();
        if ($session->ended_at) {
            return response()->json(['success' => false, 'message' => 'Quiz ended.'], 403);
        }
        if ($session->auto_submitted) {
            return response()->json(['success' => false, 'message' => 'Quiz was auto-submitted due to violations.'], 403);
        }
        if ($this->isIpDeviceRestrictionEnabled() && $session->ip_address !== $request->ip()) {
            return response()->json(['success' => false], 403);
        }
        foreach ($request->answers as $item) {
            Answer::updateOrCreate(
                ['quiz_session_id' => $session->id, 'question_id' => $item['question_id']],
                ['student_answer' => $item['answer'] ?? '', 'answered_at' => now()]
            );
        }
        return response()->json(['success' => true]);
    }

    /**
     * Capture violation image and upload to Cloudinary.
     * Creates or updates violation record with image URL.
     */
    public function captureViolation(Request $request): JsonResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'No active session.'], 401);
        }

        $request->validate([
            'session_id' => 'required|exists:quiz_sessions,id',
            'violation_type' => 'required|string',
            'image_base64' => 'required|string',
        ]);

        $session = QuizSession::where('session_token', $token)->where('id', $request->session_id)->first();
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found or invalid.'], 404);
        }

        if ($session->ended_at) {
            return response()->json(['success' => false, 'message' => 'Session already ended.'], 403);
        }

        $violationType = (string) $request->violation_type;
        $faceLossTypes = ['no_face_during_quiz', 'face_out_of_frame'];
        $isFaceLossCapture = in_array($violationType, $faceLossTypes, true);
        $capturedCount = $isFaceLossCapture
            ? $session->violations()->whereNotNull('image_url')->whereIn('type', $faceLossTypes)->count()
            : $session->violations()->whereNotNull('image_url')->count();
        if ($isFaceLossCapture && $capturedCount >= self::MAX_QUIZ_VIOLATION_CAPTURES) {
            return response()->json([
                'success' => true,
                'image_url' => null,
                'captured' => false,
                'limit_reached' => true,
                'max_captures' => self::MAX_QUIZ_VIOLATION_CAPTURES,
            ]);
        }

        $imageUrl = null;
        $data = $request->image_base64;

        if (!Str::startsWith($data, 'data:image')) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid capture payload.',
            ], 422);
        }

        try {
            if (CloudinaryService::isConfigured()) {
                $imageUrl = CloudinaryService::uploadFromDataUrl(
                    $data,
                    'violation_s' . $session->id . '_' . time() . '_' . Str::random(8)
                );
            } else {
                $imageUrl = $this->storeViolationCaptureLocally($session->id, $data);
            }
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image.',
            ], 500);
        }

        // Create or update violation record
        $severity = QuizViolation::severityForType($violationType);

        QuizViolation::create([
            'quiz_session_id' => $session->id,
            'type' => $violationType,
            'severity' => $severity,
            'metadata' => json_encode(['captured_at' => now()->toIso8601String()]),
            'image_url' => $imageUrl,
            'occurred_at' => now(),
        ]);

        // Check if session should be marked as risky
        $this->checkAndMarkRiskySession($session);

        return response()->json([
            'success' => true,
            'image_url' => $imageUrl,
            'captured' => true,
            'remaining_captures' => max(
                0,
                self::MAX_QUIZ_VIOLATION_CAPTURES - ($capturedCount + 1)
            ),
        ]);
    }

    /**
     * Record violation. Right-click = warn only (no auto-submit).
     * Auto-submit: (1) critical (copy_paste, multiple_ip) on first, or (2) tab switch/blur: first time = 20s delay; second time = immediate.
     */
    public function recordViolation(Request $request): JsonResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return response()->json(['success' => false], 401);
        }
        $request->validate([
            'type' => 'required|string|in:blur,tab_switch,copy_paste,right_click,window_resize,screenshot_attempt,camera_disconnected,no_face,multiple_faces,multiple_faces_pre_quiz,multiple_faces_during_quiz,random_snapshot,phone_detected,external_audio,no_blink,head_turn,brief_face_loss,challenge_failed,static_face_detected,no_face_during_quiz,face_out_of_frame,face_lost_repeatedly,other',
        ]);
        $session = QuizSession::where('session_token', $token)->firstOrFail();
        if ($session->ended_at) {
            return response()->json(['success' => true]);
        }
        $type = $request->type;
        $severity = QuizViolation::severityForType($type);
        QuizViolation::create([
            'quiz_session_id' => $session->id,
            'type' => $type,
            'severity' => $severity,
            'metadata' => $request->input('metadata'),
            'occurred_at' => now(),
        ]);
        $autoSubmitted = false;
        // Zero-tolerance (copy_paste, screenshot_attempt, multiple_ip): auto-submit on first, no warning
        if ($severity === QuizViolation::SEVERITY_CRITICAL) {
            $session->update([
                'post_face_skipped_at' => now(),
                'post_face_skipped_reason' => 'auto_submit',
                'auto_submit_after' => null,
            ]);
            $this->finalizeQuiz($session);
            $autoSubmitted = true;
        }
        // Major violations (blur, tab_switch, window_resize, camera_disconnected, no_face, multiple_faces, static_face, challenge_failed): max 1 warning per session, then auto-submit
        $majorTypes = ['blur', 'tab_switch', 'window_resize', 'camera_disconnected', 'no_face', 'multiple_faces', 'multiple_faces_during_quiz', 'static_face_detected', 'challenge_failed'];
        if (!$autoSubmitted && in_array($type, $majorTypes, true)) {
            $majorCount = $session->violations()->whereIn('type', $majorTypes)->count();
            if ($majorCount >= 2) {
                $session->update([
                    'post_face_skipped_at' => now(),
                    'post_face_skipped_reason' => 'auto_submit',
                    'auto_submit_after' => null,
                ]);
                $this->finalizeQuiz($session);
                $autoSubmitted = true;
            }
        }

        // Check if session should be marked as risky
        $this->checkAndMarkRiskySession($session);

        $response = ['success' => true, 'auto_submitted' => $autoSubmitted];
        if (!$autoSubmitted && in_array($type, $majorTypes, true)) {
            $majorCount = $session->violations()->whereIn('type', $majorTypes)->count();
            $response['show_major_warning'] = $majorCount === 1;
        }
        if ($autoSubmitted) {
            $response['redirect'] = $this->quizCompleteUrl();
        }
        return response()->json($response);
    }

    /**
     * Auto-submit quiz due to violations.
     */
    public function autoSubmit(Request $request): JsonResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'No active session.'], 401);
        }

        $request->validate([
            'session_id' => 'required|exists:quiz_sessions,id',
            'reason' => 'required|string',
            'violation_summary' => 'nullable|array',
            'final_snapshot' => 'nullable|string',
        ]);

        $session = QuizSession::where('session_token', $token)->where('id', $request->session_id)->first();
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found or invalid.'], 404);
        }

        if ($session->ended_at) {
            return response()->json(['success' => true, 'redirect' => $this->quizCompleteUrl()]);
        }

        // Upload final snapshot if provided
        $finalSnapshotUrl = null;
        if ($request->final_snapshot && Str::startsWith($request->final_snapshot, 'data:image')) {
            try {
                if (CloudinaryService::isConfigured()) {
                    $finalSnapshotUrl = CloudinaryService::uploadFromDataUrl(
                        $request->final_snapshot,
                        'auto_submit_s' . $session->id . '_' . time()
                    );
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // Update session with violation counts and auto-submit status
        $violationSummary = $request->violation_summary ?? [];
        $minorCount = $violationSummary['minor_count'] ?? 0;
        $majorCount = $violationSummary['major_count'] ?? 0;

        $session->update([
            'minor_violations' => $minorCount,
            'major_violations' => $majorCount,
            'auto_submitted' => true,
            'submission_reason' => $request->reason,
            'post_face_skipped_at' => now(),
            'post_face_skipped_reason' => 'auto_submit',
        ]);

        // Finalize quiz
        $this->finalizeQuiz($session);

        return response()->json([
            'success' => true,
            'redirect' => $this->quizCompleteUrl(),
        ]);
    }

    /**
     * Check violation count and update counters.
     */
    protected function checkAndMarkRiskySession(QuizSession $session): void
    {
        $violations = $session->violations;
        $minorCount = $violations->where('severity', 'warning')->count();
        $majorCount = $violations->where('severity', 'critical')->count();

        $session->update([
            'minor_violations' => $minorCount,
            'major_violations' => $majorCount,
        ]);
    }

    /**
     * Heartbeat when user returns to the quiz tab. Clears the 20-second auto-submit countdown; returns flag to show "next time immediate" popup.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return response()->json(['success' => false], 401);
        }
        $session = QuizSession::where('session_token', $token)->first();
        if (!$session || $session->ended_at) {
            return response()->json(['success' => true]);
        }
        $hadScheduledSubmit = $session->auto_submit_after !== null;
        $session->update([
            'auto_submit_after' => null,
            'last_heartbeat_at' => now(),
        ]);
        return response()->json([
            'success' => true,
            'show_tab_switch_warning' => $hadScheduledSubmit,
        ]);
    }

    /**
     * Proctor feed: student sends a camera frame for the examiner's live view. Updates last_heartbeat_at.
     */
    public function proctorFeed(Request $request): JsonResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'No active session.'], 401);
        }

        $session = QuizSession::where('session_token', $token)->first();
        if (!$session || $session->ended_at) {
            return response()->json(['success' => false, 'message' => 'Session not found or ended.'], 403);
        }

        $request->validate([
            'image_base64' => 'required|string',
        ]);

        $data = $request->image_base64;
        if (!Str::startsWith($data, 'data:image')) {
            return response()->json(['success' => false, 'message' => 'Invalid image.'], 422);
        }

        $parts = explode(',', $data, 2);
        if (count($parts) !== 2) {
            return response()->json(['success' => false, 'message' => 'Invalid image.'], 422);
        }
        $binary = base64_decode($parts[1], true);
        if ($binary === false || strlen($binary) > 2 * 1024 * 1024) {
            return response()->json(['success' => false, 'message' => 'Invalid or oversized image.'], 422);
        }

        // Resize to max 320px width for lighter streaming (optional; requires GD)
        $maxWidth = 320;
        if (function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
            $img = @imagecreatefromstring($binary);
            if ($img !== false) {
                $w = imagesx($img);
                $h = imagesy($img);
                if ($w > $maxWidth && $w > 0) {
                    $newW = $maxWidth;
                    $newH = (int) round($h * ($maxWidth / $w));
                    $scaled = imagescale($img, $newW, $newH === 0 ? -1 : $newH);
                    if ($scaled !== false) {
                        ob_start();
                        imagejpeg($scaled, null, 78);
                        $binary = ob_get_clean();
                        imagedestroy($scaled);
                    }
                }
                imagedestroy($img);
            }
        }

        try {
            $path = 'proctor_feed/' . $session->id . '.jpg';
            Storage::disk('local')->put($path, $binary);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Failed to save frame.'], 500);
        }

        $session->update(['last_heartbeat_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Finalize quiz: compute score, create result. Session resolved from HttpOnly session only.
     */
    public function finalize(Request $request): JsonResponse
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return response()->json(['success' => false], 401);
        }
        $session = QuizSession::with('quiz')->where('session_token', $token)->firstOrFail();
        if ($session->ended_at) {
            return response()->json(['success' => true, 'redirect' => $this->quizCompleteUrl()]);
        }
        if ($this->isIpDeviceRestrictionEnabled() && $session->ip_address !== $request->ip()) {
            return response()->json(['success' => false], 403);
        }
        if (!$session->post_face_image && !$session->post_face_skipped_reason) {
            return response()->json([
                'success' => false,
                'message' => 'Post-quiz photo is required. Please capture your photo before submitting.',
            ], 403);
        }
        $this->finalizeQuiz($session);
        return response()->json([
            'success' => true,
            'redirect' => $this->quizCompleteUrl(),
        ]);
    }

    /**
     * Quiz complete page. If logged in as owner, redirect to result. Otherwise show friendly message.
     */
    public function quizComplete(): View|\Illuminate\Http\RedirectResponse
    {
        $token = session('quiz_session_token');
        $studentId = session('student_id');
        $student = $studentId ? Student::find($studentId) : null;
        $session = $token && is_string($token)
            ? QuizSession::where('session_token', $token)->first()
            : null;
        $isOwner = $student && $session && strtoupper(trim((string) $student->index_number)) === strtoupper(trim((string) $session->student_index));

        if ($isOwner) {
            return redirect()->route('student.result', ['token' => $token]);
        }

        return view('student.quiz-complete', [
            'isLoggedIn' => (bool) $student,
            'resultUrl' => $session ? route('student.result', ['token' => $token]) : null,
        ]);
    }

    /**
     * URL to show after quiz submit/auto-submit (no marks or review; student must log in).
     */
    private function quizCompleteUrl(): string
    {
        return route('student.quiz.complete');
    }

    /**
     * Result page URL with session token (for logged-in owner only; otherwise redirect to login prompt).
     */
    private function resultUrlWithToken(string $sessionToken): string
    {
        return route('student.result') . '?token=' . urlencode($sessionToken);
    }

    /**
     * Public entry point for finalizing a session (e.g. from scheduler/command).
     */
    public function finalizeQuizSession(QuizSession $session): void
    {
        $this->finalizeQuiz($session);
    }

    /**
     * Finalize quiz: score using assigned snapshot only. Do not re-query live questions table.
     */
    protected function finalizeQuiz(QuizSession $session): void
    {
        $session->update(['ended_at' => now()]);
        $lockedIds = $session->assigned_question_ids ?? [];
        $correctAnswersSnapshot = $session->assigned_correct_answers ?? [];
        $total = count($lockedIds);
        $correct = 0;
        
        if ($total > 0) {
            $answersByQuestion = $session->answers()->whereIn('question_id', $lockedIds)->pluck('student_answer', 'question_id')->toArray();
            
            foreach ($lockedIds as $qid) {
                // Try both integer and string keys
                $correctAnswer = $correctAnswersSnapshot[$qid] ?? $correctAnswersSnapshot[(string) $qid] ?? null;
                
                if ($correctAnswer === null) {
                    // Skip if no correct answer found for this question
                    continue;
                }
                
                $studentAnswer = $answersByQuestion[$qid] ?? $answersByQuestion[(string) $qid] ?? '';
                
                // Normalize both answers: trim whitespace, convert to string, uppercase for comparison
                $normalizedStudent = strtoupper(trim((string) $studentAnswer));
                $normalizedCorrect = strtoupper(trim((string) $correctAnswer));
                
                // Only count as correct if they match exactly after normalization
                if ($normalizedStudent === $normalizedCorrect && $normalizedStudent !== '') {
                    $correct++;
                }
            }
        }
        
        $violationsCount = $session->violations()->count();
        
        // Ensure score doesn't exceed 100% and correct count doesn't exceed total
        $correct = min($correct, $total);
        $score = $total > 0 ? round(100 * $correct / $total, 2) : 0;
        $score = min($score, 100.00); // Cap at 100%
        
        Result::create([
            'quiz_session_id' => $session->id,
            'score' => $score,
            'total_questions' => $total,
            'correct_count' => $correct,
            'violations_count' => $violationsCount,
            'submitted_at' => now(),
        ]);
        broadcast(new DataUpdated('dashboard'))->toOthers();
        SendQuizResultReadyNotification::dispatch($session->id);
    }

    private function isIpDeviceRestrictionEnabled(): bool
    {
        return Setting::getValue(Setting::KEY_DISABLE_IP_DEVICE_RESTRICTIONS, '0') !== '1';
    }

    private function storeViolationCaptureLocally(int $sessionId, string $dataUrl): string
    {
        $parts = explode(',', $dataUrl, 2);
        if (count($parts) !== 2) {
            throw new \RuntimeException('Invalid data URL');
        }
        $binary = base64_decode($parts[1], true);
        if ($binary === false) {
            throw new \RuntimeException('Failed to decode image');
        }

        $fileName = now()->format('Ymd_His_u') . '_' . Str::random(8) . '.jpg';
        $path = 'violations/session_' . $sessionId . '/' . $fileName;
        Storage::disk('public')->put($path, $binary);

        return asset('storage/' . $path);
    }

    /**
     * Result page. Marks and review are shown only when the visitor is logged in as the student who took the quiz.
     * Otherwise show "log in to see results". Session token from session or query (?token=).
     */
    public function result(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $token = session('quiz_session_token') ?? $request->query('token');
        if (!$token || !is_string($token)) {
            return redirect()->route('student.landing')->with('error', 'Not found');
        }
        $session = QuizSession::with(['quiz', 'result', 'answers.question'])->where('session_token', $token)->first();
        if (!$session) {
            return redirect()->route('student.landing')->with('error', 'Not found');
        }

        $studentId = session('student_id');
        $student = $studentId ? Student::find($studentId) : null;
        $isOwner = $student && strtoupper(trim((string) $student->index_number)) === strtoupper(trim((string) $session->student_index));
        $sessionToken = session('quiz_session_token');
        $isSameBrowserSession = is_string($sessionToken) && hash_equals($sessionToken, (string) $token);

        if (!$isOwner && !$isSameBrowserSession) {
            return view('student.quiz-complete', [
                'isLoggedIn' => (bool) $student,
            ]);
        }

        if (!session('quiz_session_token')) {
            session(['quiz_session_token' => $token]);
        }

        $assignedCorrect = $session->assigned_correct_answers ?? [];
        $aiService = app(AiQuestionService::class);
        foreach ($session->answers as $answer) {
            $q = $answer->question;
            if (!$q) {
                continue;
            }
            $sessionCorrect = $assignedCorrect[$q->id] ?? $assignedCorrect[(string) $q->id] ?? $q->correct_answer;
            $correct = trim((string) $answer->student_answer) === trim((string) $sessionCorrect);
            if ($correct) {
                continue;
            }
            $hasExplanation = !empty($q->explanation_wrong) || !empty($answer->explanation_wrong);
            if ($hasExplanation) {
                continue;
            }
            $generated = $aiService->generateWrongAnswerExplanation($q, (string) $answer->student_answer);
            if ($generated !== null && $generated !== '') {
                $answer->update(['explanation_wrong' => $generated]);
            }
        }

        $session->load(['quiz', 'result', 'answers.question']);
        return view('student.result', [
            'session' => $session,
            'resultUrl' => $this->resultUrlWithToken($token),
        ]);
    }
}
