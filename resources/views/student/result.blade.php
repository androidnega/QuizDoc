@extends('layouts.student')

@section('title', 'Quiz Result')
@section('body_class', 'bg-offwhite')

@section('content')
<div class="min-h-[100dvh] px-4 py-8 sm:py-10 pl-[max(1rem,env(safe-area-inset-left))] pr-[max(1rem,env(safe-area-inset-right))] pb-[max(1rem,env(safe-area-inset-bottom))] min-w-0 overflow-x-hidden">
    <div class="max-w-4xl mx-auto w-full min-w-0 space-y-8">
        @php $isWithheld = $session->isResultWithheld(); @endphp
        {{-- Header --}}
        <div class="text-center mb-8">
            <h1 class="text-xl font-semibold text-gray-900">{{ $session->quiz->title }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">Index: {{ $session->student_index }}</p>
        </div>

        @if($session->result && $isWithheld)
            <div class="mb-8 rounded-xl border-2 border-red-300 bg-red-50 p-6 sm:p-8 text-center">
                <h2 class="text-lg font-bold text-red-800 mb-2">Result on hold</h2>
                <p class="text-base font-semibold text-red-900">Withheld, contact lecturer.</p>
                <p class="text-sm text-red-700 mt-2">Your exam was auto-submitted after repeated proctoring violations.</p>
            </div>
        @endif

        {{-- Student feedback: clear performance verdict based on score --}}
        @if($session->result && !$isWithheld)
            @if($session->quiz->canShowScore())
            @php
                $score = (float) $session->result->score;
                $performance = $score >= 85 ? 'excellent' : ($score >= 70 ? 'good' : ($score >= 50 ? 'fair' : 'needs_improvement'));
            @endphp
            <div class="mb-8 rounded-xl border-2 p-6 sm:p-8
                @if($performance === 'excellent') border-success-300 bg-success-50
                @elseif($performance === 'good') border-success-200 bg-success-50/70
                @elseif($performance === 'fair') border-warning-300 bg-warning-50
                @else border-danger-200 bg-danger-50 @endif">
                <h2 class="text-lg font-bold text-gray-900 mb-2">Your exam result</h2>
                {{-- Clear performance verdict --}}
                <p class="font-semibold text-lg
                    @if($performance === 'excellent') text-success-800
                    @elseif($performance === 'good') text-success-700
                    @elseif($performance === 'fair') text-warning-800
                    @else text-danger-800 @endif">
                    @if($performance === 'excellent')
                        You performed very well.
                    @elseif($performance === 'good')
                        You performed well.
                    @elseif($performance === 'fair')
                        You did okay — there’s room to improve.
                    @else
                        You did not perform well this time.
                    @endif
                </p>
                <p class="text-sm text-gray-700 mt-2">You got {{ $session->result->correct_count }} out of {{ $session->result->total_questions }} questions correct ({{ round($score, 0) }}%).</p>
                @if($performance === 'excellent' || $performance === 'good')
                    <p class="text-sm text-gray-600 mt-1">Keep up the good work.</p>
                @elseif($performance === 'fair')
                    <p class="text-sm text-gray-600 mt-1">Review the feedback below and the topics you missed to do better next time.</p>
                @else
                    <p class="text-sm text-gray-600 mt-1">We recommend reviewing the material and the questions you got wrong so you’re ready next time.</p>
                @endif
            </div>
            @endif
        @endif

        @if($session->result && !$isWithheld)
            @if($session->quiz->canShowScore())
            @php $wasAutoSubmitted = ($session->post_face_skipped_reason ?? '') === 'auto_submit'; @endphp
            @if($wasAutoSubmitted)
            {{-- Auto-submitted: well-wrapped score with clear notice --}}
            <div class="mb-6 rounded-2xl border-2 border-warning-300 bg-warning-50 p-6 sm:p-8">
                <p class="text-center text-sm font-semibold text-warning-800 mb-4">Your quiz was auto-submitted due to tab switching. Your score is below😜.</p>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-sm mx-auto">
                    <div class="flex flex-col items-center gap-4 text-center">
                        <div class="w-20 h-20 rounded-full flex items-center justify-center
                            @if($session->result->score >= 70) bg-success-100
                            @elseif($session->result->score >= 50) bg-warning-100
                            @else bg-danger-100 @endif">
                            <span class="text-3xl font-bold tabular-nums
                                @if($session->result->score >= 70) text-success-700
                                @elseif($session->result->score >= 50) text-warning-700
                                @else text-danger-700 @endif">{{ round($session->result->score, 0) }}%</span>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">Your Score</p>
                            <p class="text-sm text-gray-500">{{ $session->result->correct_count }} / {{ $session->result->total_questions }} correct</p>
                        </div>
                        <div class="flex flex-wrap justify-center gap-2 text-sm">
                            <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-700">{{ $session->result->total_questions }} questions</span>
                            @if($session->result->violations_count > 0)
                                <span class="px-2.5 py-1 rounded-full bg-danger-100 text-danger-700">{{ $session->result->violations_count }} violation(s)</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @else
            {{-- Normal completion: score & stats card --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 sm:p-8 mb-6">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div class="flex items-center gap-3">
                        <div class="w-14 h-14 rounded-full flex items-center justify-center
                            @if($session->result->score >= 70) bg-success-100
                            @elseif($session->result->score >= 50) bg-warning-100
                            @else bg-danger-100 @endif">
                            <span class="text-2xl font-bold tabular-nums
                                @if($session->result->score >= 70) text-success-700
                                @elseif($session->result->score >= 50) text-warning-700
                                @else text-danger-700 @endif">{{ round($session->result->score, 0) }}%</span>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">Your Score</p>
                            <p class="text-sm text-gray-500">{{ $session->result->correct_count }} / {{ $session->result->total_questions }} correct</p>
                        </div>
                    </div>
                    <div class="flex gap-3 text-sm">
                        <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-700">{{ $session->result->total_questions }} questions</span>
                        @if($session->result->violations_count > 0)
                            <span class="px-2.5 py-1 rounded-full bg-danger-100 text-danger-700">{{ $session->result->violations_count }} violation(s)</span>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Performance reflection (uses $performance from above) --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 sm:p-8 mb-6
                @if(isset($performance) && $performance === 'excellent') border-l-4 border-l-success-500
                @elseif(isset($performance) && $performance === 'good') border-l-4 border-l-success-400
                @elseif(isset($performance) && $performance === 'fair') border-l-4 border-l-warning-500
                @else border-l-4 border-l-danger-500 @endif">
                <h2 class="text-sm font-semibold text-gray-900 mb-2">How you did</h2>
                @if(isset($performance))
                @if($performance === 'excellent')
                    <p class="text-sm text-gray-700">Excellent performance. You showed strong understanding of the material.</p>
                @elseif($performance === 'good')
                    <p class="text-sm text-gray-700">Good performance. You showed solid understanding. Keep it up.</p>
                @elseif($performance === 'fair')
                    <p class="text-sm text-gray-700">Fair performance. Review the questions you missed and the topics below to improve next time.</p>
                @else
                    <p class="text-sm text-gray-700">Review the material and the questions you got wrong. Focus on the correct answers and topics so you’re ready next time.</p>
                @endif
                @else
                    <p class="text-sm text-gray-700">Review your answers below to see where you did well and where to improve.</p>
                @endif
                @php
                    $wrongCount = $session->result->total_questions - $session->result->correct_count;
                @endphp
                @if($session->quiz->canShowFullReview() && $wrongCount > 0 && $session->result->total_questions > 0)
                    <p class="text-sm text-gray-600 mt-2">{{ $wrongCount }} question(s) were incorrect. Check the review below to see what you missed and what the correct answers are.</p>
                @endif
            </div>

            @if($session->result->violations_count > 0)
                <div class="bg-danger-50 border border-danger-200 rounded-xl p-4 mb-4">
                    <p class="text-sm text-danger-800">{{ $session->result->violations_count }} proctoring violation(s) were recorded. Your instructor may review them.</p>
                </div>
            @endif
            @else
            {{-- result_visibility = disabled: no score or review --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 mb-4 text-center">
                <p class="text-gray-700 font-medium">You have completed this quiz.</p>
                <p class="text-sm text-gray-500 mt-1">Results are not shown for this assessment.</p>
            </div>
            @endif

            {{-- View results button: links to /quiz/result#answer-review; only when full review is available --}}
            @if($session->quiz->canShowFullReview() && $session->answers->isNotEmpty())
                <div class="text-center mb-4">
                    <a href="{{ ($resultUrl ?? route('student.result')) }}#answer-review" class="btn btn-action text-sm py-2.5 px-5 inline-flex items-center gap-2">
                        View results
                        <span class="text-xs opacity-80">(questions &amp; answers)</span>
                    </a>
                </div>
            @endif

            {{-- Review: only when quiz allows full review after end (and quiz window has ended) --}}
            @if($session->quiz->canShowFullReview() && $session->answers->isNotEmpty())
                <div id="answer-review" class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 sm:p-8 mb-8 scroll-mt-4 min-w-0 max-w-full overflow-hidden">
                    <h2 class="text-sm font-semibold text-gray-900 mb-3">Review your answers</h2>
                    <p class="text-xs text-gray-500 mb-4">Full review is available now that the quiz window has ended. See your answers, the correct answers, and why any were wrong.</p>
                    <div class="space-y-4 min-w-0 max-w-full">
                        @foreach($session->answers as $idx => $answer)
                            @php
                                $question = $answer->question;
                                $rawQText = $question ? trim((string)($question->text ?? '')) : '';
                                if ($rawQText !== '') {
                                    $questionText = $question->text;
                                } else {
                                    $correctKey = $question ? ($session->assigned_correct_answers[$answer->question_id] ?? $session->assigned_correct_answers[(string)$answer->question_id] ?? $question->correct_answer ?? '') : '';
                                    $correctOpt = $question && is_array($question->options ?? null) ? collect($question->options)->firstWhere('key', $correctKey) : null;
                                    $correctOptText = $correctOpt['text'] ?? '';
                                    $questionText = $question && !empty($question->topic) ? 'Question about ' . $question->topic : 'Question (text not available)';
                                    if ($correctOptText !== '') {
                                        $questionText .= ' — Correct: ' . $correctOptText;
                                    }
                                }
                                $sessionCorrect = $session->assigned_correct_answers[$answer->question_id] ?? $session->assigned_correct_answers[(string)$answer->question_id] ?? ($question->correct_answer ?? '');
                                $correct = trim((string)$answer->student_answer) === trim((string)$sessionCorrect);
                                $shuffledOpts = $session->shuffled_question_options[$answer->question_id] ?? $session->shuffled_question_options[(string)$answer->question_id] ?? null;
                                $yourText = null;
                                $correctText = null;
                                if (is_array($shuffledOpts)) {
                                    foreach ($shuffledOpts as $o) {
                                        $k = $o['key'] ?? $o;
                                        $t = $o['text'] ?? $o;
                                        if ((string)$k === trim((string)$answer->student_answer)) $yourText = $t;
                                        if ((string)$k === trim((string)$sessionCorrect)) $correctText = $t;
                                    }
                                }
                                if ($yourText === null && $question && is_array($question->options ?? null)) {
                                    foreach ($question->options as $opt) {
                                        if (is_array($opt) && (string)($opt['key'] ?? '') === trim((string)$answer->student_answer)) { $yourText = $opt['text'] ?? $opt['key'] ?? ''; break; }
                                    }
                                }
                                if ($correctText === null && $question && is_array($question->options ?? null)) {
                                    foreach ($question->options as $opt) {
                                        if (is_array($opt) && (string)($opt['key'] ?? '') === trim((string)$sessionCorrect)) { $correctText = $opt['text'] ?? $opt['key'] ?? ''; break; }
                                    }
                                }
                            @endphp
                            <div class="border border-gray-200 rounded-lg p-3 min-w-0 max-w-full {{ $correct ? 'bg-success-50/50 border-success-200' : 'bg-danger-50/50 border-danger-200' }}">
                                    <p class="text-sm font-medium text-gray-900 mb-1 break-words">{{ $idx + 1 }}. {{ $questionText }}</p>
                                    <div class="flex flex-wrap gap-x-3 gap-y-1 text-xs mt-2 min-w-0">
                                        <span class="text-gray-600 break-words min-w-0">Your answer: <strong>{{ $yourText !== null ? $answer->student_answer . '. ' . $yourText : ($answer->student_answer ?: '—') }}</strong></span>
                                        <span class="text-success-700 break-words min-w-0">Correct: <strong>{{ $correctText !== null ? $sessionCorrect . '. ' . $correctText : $sessionCorrect }}</strong></span>
                                    </div>
                                    @if(!$correct)
                                        @php
                                            $whyWrong = ($question && trim((string)($question->explanation_wrong ?? '')) !== '') ? $question->explanation_wrong : ($answer->explanation_wrong ?? null);
                                        @endphp
                                        @if(!empty($whyWrong))
                                            <div class="mt-3 pt-3 border-t border-gray-200 text-xs break-words min-w-0">
                                                <p class="text-danger-700"><strong>Reason:</strong> {{ $whyWrong }}</p>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                        @endforeach
                    </div>
                </div>
            @elseif($session->quiz->canShowScore() && !$session->quiz->canShowFullReview() && $session->quiz->result_visibility === \App\Models\Quiz::RESULT_VISIBILITY_FULL_REVIEW_AFTER_END)
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-4">
                    <p class="text-sm text-gray-600">Answer review (questions, correct answers, and explanations) will be available after the quiz window has ended.</p>
                    <p class="text-sm text-gray-600 mt-2">Return to this page to see your score and, after the quiz ends, the full review.</p>
                    <a href="{{ ($resultUrl ?? route('student.result')) }}" class="btn btn-action text-sm py-2 px-4 mt-3 inline-block">View results</a>
                </div>
            @elseif($session->quiz->canShowScore() && $session->quiz->result_visibility === \App\Models\Quiz::RESULT_VISIBILITY_SCORE_ONLY)
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-4">
                    <p class="text-sm text-gray-600">Answer review is not shown for this quiz.</p>
                </div>
            @endif
        @elseif(!$isWithheld)
            <div class="bg-white border border-gray-200 rounded-xl p-8 text-center">
                <p class="text-gray-600">Processing your result…</p>
            </div>
        @endif

        <div class="text-center mt-10 flex flex-wrap justify-center gap-4">
            @if($session->result && !$isWithheld && $session->quiz->canShowFullReview() && $session->answers->isNotEmpty())
                <a href="{{ ($resultUrl ?? route('student.result')) }}#answer-review" class="btn btn-action text-sm py-2.5 px-5">View results</a>
            @endif
            <a href="{{ route('student.landing') }}" class="btn {{ ($session->result && !$isWithheld && $session->quiz->canShowFullReview() && $session->answers->isNotEmpty()) ? 'btn-secondary' : 'btn-action' }} text-sm py-2.5 px-5">Back to Home</a>
        </div>
    </div>
</div>
@endsection
