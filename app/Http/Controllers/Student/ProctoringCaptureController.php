<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\Setting;
use App\Services\QuestionAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ProctoringCaptureController extends Controller
{
    public function __construct(
        private QuestionAssignmentService $assignmentService
    ) {}

    /**
     * Show face capture screen (ProctoringCapture). Quiz and index from session.
     */
    public function show(Request $request): View|\Illuminate\Http\RedirectResponse|JsonResponse|\Illuminate\Http\Response
    {
        $quizId = session('quiz_id');
        $indexNumber = session('index_number');
        if (!$quizId || !$indexNumber) {
            $quizToken = session('quiz_link_token');
            if ($quizToken) {
                return redirect()->route('student.rules.show.quiz', ['token' => $quizToken])
                    ->with('error', 'Your quiz session expired. Please accept the rules again.');
            }
            return redirect()->route('student.landing')->with('error', 'Your quiz session expired. Please open your quiz link again.');
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
        $ip = $request->ip();
        $studentIndex = strtoupper(trim((string) $indexNumber));
        
        if ($this->isIpDeviceRestrictionEnabled()) {
            // Check if IP was used by a different student for this quiz (ignore reset sessions)
            $ipUsedByOther = QuizSession::where('quiz_id', $quiz->id)
                ->where('ip_address', $ip)
                ->whereRaw("ip_address NOT LIKE 'reset-%'")
                ->whereRaw('UPPER(TRIM(student_index)) != ?', [$studentIndex])
                ->exists();

            if ($ipUsedByOther) {
                return redirect()->route('student.rules.show.quiz', ['token' => $quiz->link_token])
                    ->with('error', 'This network has already been used for this quiz by another student.');
            }
        }

        // Enforce one attempt flow per student+quiz across tabs:
        // - if ended, do not allow a new attempt
        // - if active/in-progress, resume existing session instead of creating another
        $existingSession = QuizSession::where('quiz_id', $quiz->id)
            ->whereRaw('UPPER(TRIM(student_index)) = ?', [$studentIndex])
            ->latest('id')
            ->first();
        if ($existingSession) {
            session(['quiz_session_token' => $existingSession->session_token]);
            if ($existingSession->ended_at !== null) {
                return redirect()
                    ->route('student.result', ['token' => $existingSession->session_token])
                    ->with('info', 'You already completed this quiz attempt.');
            }
            if ($existingSession->start_time !== null) {
                return redirect()->route('student.quiz.show');
            }
            return redirect()->route('student.quiz.ready');
        }

        // Camera optional mode: skip capture page and bootstrap/resume session directly.
        if (!$this->isProctoringCameraRequired()) {
            try {
                $assignment = $this->assignmentService->assignQuestions($quiz);
            } catch (\Throwable $e) {
                report($e);
                return redirect()->route('student.rules.show.quiz', ['token' => $quiz->link_token])
                    ->with('error', 'Quiz is not ready at the moment. Please try again shortly.');
            }

            $assignedIds = $assignment['question_ids'] ?? [];
            if (count($assignedIds) < $quiz->getQuestionsPerStudent()) {
                return redirect()->route('student.rules.show.quiz', ['token' => $quiz->link_token])
                    ->with('error', 'Not enough questions are ready for this quiz yet.');
            }

            $ua = $request->userAgent();
            $device = QuizSession::parseUserAgent($ua);
            $session = QuizSession::create([
                'quiz_id' => $quiz->id,
                'student_index' => $studentIndex,
                'ip_address' => $ip,
                'user_agent' => $ua ? substr($ua, 0, 1024) : null,
                'device_type' => $device['device_type'],
                'device_name' => $device['device_name'],
                'start_time' => null,
                'camera_verified' => true,
                'camera_started_at' => now(),
                'pre_face_image' => null,
                'pre_face_image_hash' => null,
                'assigned_question_ids' => $assignment['question_ids'] ?? [],
                'assigned_correct_answers' => $assignment['correct_answers'] ?? [],
                'shuffled_question_options' => $assignment['shuffled_options'] ?? [],
                'session_token' => QuizSession::generateToken(),
            ]);

            session(['quiz_session_token' => $session->session_token]);

            return redirect()->route('student.quiz.ready');
        }
        return response()
            ->view('student.proctoring-capture', [
                'quiz' => $quiz,
                'indexNumber' => $indexNumber,
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Store face image, bind IP, create session, assign questions.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'quiz_id' => 'required|exists:quizzes,id',
            'index_number' => 'required|string',
            'face_image' => 'required|string', // base64 data URL
        ]);
        $quiz = Quiz::with('questions')->findOrFail($request->quiz_id);
        if (!$quiz->isAvailableForStudent()) {
            return response()->json(['success' => false, 'message' => 'Quiz is no longer available. Please try again from the quiz link.'], 403);
        }
        $ip = $request->ip();
        $studentIndex = strtoupper(trim((string) $request->index_number));

        // Guard against second-tab/session duplication.
        $existingSession = QuizSession::where('quiz_id', $quiz->id)
            ->whereRaw('UPPER(TRIM(student_index)) = ?', [$studentIndex])
            ->latest('id')
            ->first();
        if ($existingSession) {
            session(['quiz_session_token' => $existingSession->session_token]);
            if ($existingSession->ended_at !== null) {
                return response()->json([
                    'success' => true,
                    'redirect' => route('student.result', ['token' => $existingSession->session_token]),
                    'message' => 'You already completed this quiz attempt.',
                ]);
            }
            return response()->json([
                'success' => true,
                'redirect' => $existingSession->start_time !== null
                    ? route('student.quiz.show')
                    : route('student.quiz.ready'),
                'message' => 'Resuming your existing quiz session.',
            ]);
        }

        if ($this->isIpDeviceRestrictionEnabled()) {
            $ipUsedByOther = QuizSession::where('quiz_id', $quiz->id)
                ->where('ip_address', $ip)
                ->whereRaw("ip_address NOT LIKE 'reset-%'")
                ->whereRaw('UPPER(TRIM(student_index)) != ?', [$studentIndex])
                ->exists();

            if ($ipUsedByOther) {
                return response()->json(['success' => false, 'message' => 'IP already used for this quiz by another student.'], 403);
            }
        }

        $imagePath = null;
        $preFaceImageHash = null;
        $data = $request->face_image;
        if (Str::startsWith($data, 'data:image')) {
            $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $data);
            $imageBytes = base64_decode($base64, true);
            if ($imageBytes !== false) {
                $preFaceImageHash = hash('sha256', $imageBytes);
            }
            try {
                // Verification images always stored on server: verification/{index}/{date}_{time}_pre_quiz_{quiz_id}.jpg
                $safeIndex = preg_replace('/[^a-zA-Z0-9_-]/', '_', trim((string) $studentIndex)) ?: 'unknown';
                $now = now();
                $fileName = $now->format('Y-m-d') . '_' . $now->format('His') . '_pre_quiz_' . $quiz->id . '.jpg';
                $imagePath = 'verification/' . $safeIndex . '/' . $fileName;
                $disk = Storage::disk('public');
                $disk->makeDirectory('verification/' . $safeIndex);
                $disk->put($imagePath, $imageBytes);
            } catch (\Throwable $e) {
                report($e);
                return response()->json([
                    'success' => false,
                    'message' => 'Could not save your photo. Please try again.',
                ], 500);
            }
        }

        try {
            $assignment = $this->assignmentService->assignQuestions($quiz);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Quiz is not ready. Please try again in a moment.',
            ], 503);
        }

        $assignedIds = $assignment['question_ids'] ?? [];
        if (count($assignedIds) < $quiz->getQuestionsPerStudent()) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough questions for this quiz. Please contact your instructor.',
            ], 403);
        }
        $correctAnswersSnapshot = $assignment['correct_answers'] ?? [];
        $shuffledOptions = $assignment['shuffled_options'] ?? [];

        $ua = $request->userAgent();
        $device = QuizSession::parseUserAgent($ua);
        try {
            $session = QuizSession::create([
                'quiz_id' => $quiz->id,
                'student_index' => $studentIndex,
                'ip_address' => $ip,
                'user_agent' => $ua ? substr($ua, 0, 1024) : null,
                'device_type' => $device['device_type'],
                'device_name' => $device['device_name'],
                'start_time' => null,
                'camera_verified' => true,
                'camera_started_at' => now(),
                'pre_face_image' => $imagePath,
                'pre_face_image_hash' => $preFaceImageHash,
                'assigned_question_ids' => $assignedIds,
                'assigned_correct_answers' => $correctAnswersSnapshot,
                'shuffled_question_options' => $shuffledOptions,
                'session_token' => QuizSession::generateToken(),
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Could not start your session. Please try again.',
            ], 500);
        }

        session(['quiz_session_token' => $session->session_token]);

        return response()->json([
            'success' => true,
            'redirect' => route('student.quiz.ready'),
        ]);
    }

    private function isIpDeviceRestrictionEnabled(): bool
    {
        return Setting::getValue(Setting::KEY_DISABLE_IP_DEVICE_RESTRICTIONS, '0') !== '1';
    }

    private function isProctoringCameraRequired(): bool
    {
        return Setting::getValue(Setting::KEY_PROCTORING_CAMERA_REQUIRED, '1') === '1';
    }
}
