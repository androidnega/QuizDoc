<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\Setting;
use App\Services\CloudinaryService;
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
    public function show(Request $request): View|\Illuminate\Http\RedirectResponse|JsonResponse
    {
        $quizId = session('quiz_id');
        $indexNumber = session('index_number');
        if (!$quizId || !$indexNumber) {
            return redirect()->route('student.landing')->with('error', 'Error');
        }
        $quiz = Quiz::find($quizId);
        if (!$quiz || !$quiz->isActive()) {
            return redirect()->route('student.landing')->with('error', 'Error');
        }
        $ip = $request->ip();
        $studentIndex = strtoupper(trim((string) $indexNumber));
        
        if ($this->isIpDeviceRestrictionEnabled()) {
            // Check if IP was used by a different student for this quiz
            $ipUsedByOther = QuizSession::where('quiz_id', $quiz->id)
                ->where('ip_address', $ip)
                ->whereRaw('UPPER(TRIM(student_index)) != ?', [$studentIndex])
                ->exists();

            if ($ipUsedByOther) {
                return redirect()->route('student.landing')->with('error', 'Error');
            }
        }
        return view('student.proctoring-capture', [
            'quiz' => $quiz,
            'indexNumber' => $indexNumber,
        ]);
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
        if (!$quiz->isActive()) {
            return response()->json(['success' => false, 'message' => 'Quiz is not active. Please try again from the quiz link.'], 403);
        }
        $ip = $request->ip();
        $studentIndex = strtoupper(trim((string) $request->index_number));

        if ($this->isIpDeviceRestrictionEnabled()) {
            $ipUsedByOther = QuizSession::where('quiz_id', $quiz->id)
                ->where('ip_address', $ip)
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
                if (CloudinaryService::isConfigured()) {
                    $imagePath = CloudinaryService::uploadFromDataUrl($data, 'pre_q' . $quiz->id . '_' . $studentIndex);
                }
                if ($imagePath === null && $imageBytes !== false) {
                    $imagePath = 'proctoring/pre_' . $quiz->id . '_' . $studentIndex . '_' . time() . '.jpg';
                    Storage::disk('public')->put($imagePath, $imageBytes);
                }
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

        try {
            $session = QuizSession::create([
                'quiz_id' => $quiz->id,
                'student_index' => $studentIndex,
                'ip_address' => $ip,
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
}
