<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenValidationController extends Controller
{
    /**
     * Validate quiz token (AJAX). Returns valid, starts_at (if future), and message.
     * Token format: alphanumeric with optional hyphen, e.g. KTdie54-3Sx9.
     */
    public function validateToken(Request $request): JsonResponse
    {
        $request->validate(['token' => 'required|string|max:128']);
        $input = trim($request->input('token', ''));
        $token = $this->extractToken($input);
        if (!$token) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid token format. Use your quiz token (e.g. KTdie54-3Sx9).',
            ]);
        }
        $quiz = Quiz::where('link_token', $token)->first();
        if (!$quiz) {
            return response()->json([
                'valid' => false,
                'message' => 'This token is not valid. Please check and try again.',
            ]);
        }
        if ((!$quiz->is_published && !$quiz->is_active) || !$quiz->hasEnoughApprovedQuestions()) {
            return response()->json([
                'valid' => false,
                'message' => 'This quiz is not available yet.',
            ]);
        }
        if ($quiz->ends_at && $quiz->ends_at->isPast()) {
            return response()->json([
                'valid' => false,
                'message' => 'This quiz has ended.',
            ]);
        }
        $startsAt = null;
        if ($quiz->starts_at && $quiz->starts_at->isFuture()) {
            $startsAt = $quiz->starts_at->toIso8601String();
        }
        return response()->json([
            'valid' => true,
            'message' => 'Valid token, proceed.',
            'starts_at' => $startsAt,
            'token' => $token,
        ]);
    }

    private function extractToken(string $input): ?string
    {
        if (preg_match('#/t/([a-zA-Z0-9_-]+)#', $input, $m)) {
            return $m[1];
        }
        if (preg_match('#^([a-zA-Z0-9_-]{8,64})$#', $input, $m)) {
            return $m[1];
        }
        return null;
    }
}
