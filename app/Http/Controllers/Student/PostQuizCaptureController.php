<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\QuizSession;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PostQuizCaptureController extends Controller
{
    /**
     * Show final photo capture screen (same UI/layout as first proctoring capture).
     * Student must have an active quiz session; after capturing, they submit and are redirected to result.
     */
    public function show(Request $request): View|RedirectResponse|Response
    {
        $token = session('quiz_session_token');
        if (!$token) {
            return redirect()->route('student.quiz.complete')->with('info', 'Your session expired. If you just finished the quiz, check your results below.');
        }
        $session = QuizSession::with('quiz')->where('session_token', $token)->first();
        if (!$session) {
            return redirect()->route('student.quiz.complete')->with('info', 'Session not found. If you completed the quiz, your results may already be saved.');
        }
        if ($session->ended_at) {
            return redirect()->route('student.quiz.complete');
        }
        if (!$this->isProctoringCameraRequired()) {
            return redirect()->route('student.quiz.show');
        }
        if ($this->isIpDeviceRestrictionEnabled() && $session->ip_address !== $request->ip()) {
            return redirect()->route('student.quiz.complete')->with('info', 'Session could not be verified. If you completed the quiz, check your results.');
        }
        return response()
            ->view('student.final-photo-capture', ['quiz' => $session->quiz])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Store post-quiz face image (PostQuizCapture). Session resolved from HttpOnly session only.
     */
    public function store(Request $request): JsonResponse
    {
        if (!$this->isProctoringCameraRequired()) {
            return response()->json(['success' => true]);
        }
        $request->validate(['face_image' => 'required|string']);
        $token = session('quiz_session_token');
        if (!$token) {
            return response()->json(['success' => false], 401);
        }
        $session = QuizSession::where('session_token', $token)->firstOrFail();
        if ($session->ended_at) {
            return response()->json(['success' => true]);
        }
        if ($this->isIpDeviceRestrictionEnabled() && $session->ip_address !== $request->ip()) {
            return response()->json(['success' => false], 403);
        }
        $imagePath = null;
        $postFaceImageHash = null;
        $data = $request->face_image;
        if (Str::startsWith($data, 'data:image')) {
            $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $data);
            $imageBytes = base64_decode($base64, true);
            if ($imageBytes !== false) {
                $postFaceImageHash = hash('sha256', $imageBytes);
            }
            try {
                // Verification images always on server: verification/{index}/{date}_{time}_post_s{session_id}.jpg
                $studentIndex = $session->student_index ?? 'unknown';
                $safeIndex = preg_replace('/[^a-zA-Z0-9_-]/', '_', trim((string) $studentIndex)) ?: 'unknown';
                $now = now();
                $fileName = $now->format('Y-m-d') . '_' . $now->format('His') . '_post_s' . $session->id . '.jpg';
                $localPath = 'verification/' . $safeIndex . '/' . $fileName;
                $disk = Storage::disk('public');
                $disk->makeDirectory('verification/' . $safeIndex);
                if ($disk->put($localPath, $imageBytes)) {
                    $imagePath = $localPath;
                }
                if ($imagePath !== null) {
                    $session->update([
                        'post_face_image' => $imagePath,
                        'post_face_image_hash' => $postFaceImageHash,
                        'post_face_captured_at' => now(),
                    ]);
                }
            } catch (\Throwable $e) {
                report($e);
                return response()->json([
                    'success' => false,
                    'message' => 'Could not save your photo. Please try again.',
                ], 500);
            }
        }
        return response()->json(['success' => true]);
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
