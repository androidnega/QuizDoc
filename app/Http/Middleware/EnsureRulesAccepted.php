<?php

namespace App\Http\Middleware;

use App\Models\Quiz;
use App\Models\QuizAcceptance;
use App\Models\QuizSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRulesAccepted
{
    /**
     * Ensure the student has accepted quiz rules before accessing login, course, proctoring, or quiz.
     * Session check is used for UX speed; database (quiz_acceptance) is the source of truth for enforcement
     * when both index_number and quiz_id are known (quiz entry).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $quizId = $this->resolveQuizId($request);
        $indexNumber = session('student_index');

        // Enforcement: when we have both index and quiz, verify against database
        if ($quizId !== null && $indexNumber !== null && $indexNumber !== '') {
            $hasAcceptance = QuizAcceptance::where('quiz_id', $quizId)
                ->where('index_number', $indexNumber)
                ->exists();

            if (!$hasAcceptance) {
                $quiz = Quiz::where('id', $quizId)->first();
                if ($quiz && $quiz->link_token) {
                    return redirect()->route('student.rules.show.quiz', ['token' => $quiz->link_token])
                        ->with('error', 'Error');
                }
                return redirect()->route('student.rules.show')
                    ->with('error', 'Error');
            }

            // Sync session for fast path on subsequent requests
            session(['rules_accepted' => true]);
            return $next($request);
        }

        // Fast path: session only when we cannot yet check DB (no index or no quiz)
        if (session('rules_accepted')) {
            return $next($request);
        }

        if ($quizId) {
            $quiz = Quiz::where('id', $quizId)->first();
            if ($quiz && $quiz->link_token) {
                return redirect()->route('student.rules.show.quiz', ['token' => $quiz->link_token])
                    ->with('error', 'Error');
            }
        }

        return redirect()->route('student.rules.show')
            ->with('error', 'Error');
    }

    /**
     * Get quiz ID from request (query string or from session token).
     */
    private function resolveQuizId(Request $request): ?int
    {
        $quizFromQuery = $request->query('quiz');
        if ($quizFromQuery !== null && $quizFromQuery !== '') {
            return (int) $quizFromQuery;
        }

        $quizFromSession = session('quiz_id') ?? session('quiz_id_for_login');
        if ($quizFromSession !== null && $quizFromSession !== '') {
            return (int) $quizFromSession;
        }

        $token = $request->route('token') ?? session('quiz_session_token');
        if ($token) {
            $session = QuizSession::where('session_token', $token)->first();
            if ($session) {
                return (int) $session->quiz_id;
            }
        }

        $quizToken = session('quiz_link_token');
        if ($quizToken) {
            $quiz = Quiz::where('link_token', $quizToken)->first();
            if ($quiz) {
                return (int) $quiz->id;
            }
        }

        return null;
    }
}
