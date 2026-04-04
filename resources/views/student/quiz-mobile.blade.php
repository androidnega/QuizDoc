@extends('layouts.student')

@section('title', 'Quiz - ' . $session->quiz->title)
@section('body_class', 'bg-offwhite')

@push('styles')
<style>
.quiz-mobile-timer-green { color: #059669; }
.quiz-mobile-timer-blue { color: #2563eb; }
.quiz-mobile-timer-red { color: #dc2626; }
/* Top region: timer + camera side by side so both are visible without scrolling */
.quiz-mobile-top-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.5rem;
}
.quiz-mobile-timer-card {
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    background-color: #ffffff;
    padding: 0.55rem 0.75rem;
    min-height: 72px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.quiz-mobile-timer-card-title {
    font-size: 0.70rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #6b7280;
}
.quiz-mobile-timer-card-time {
    font-size: 1.05rem;
    font-weight: 700;
}
/* Answer layout on mobile: single column list for clarity */
.quiz-mobile-options-grid {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
/* Answer cards: compact, column layout inside each option */
.quiz-mobile-option-label {
    display: flex;
    flex-direction: row;
    align-items: flex-start;
    gap: 0.625rem;
}
.quiz-mobile-option-input {
    width: 1.1rem;
    height: 1.1rem;
    margin-top: 0.1rem;
}
.quiz-mobile-option-text {
    font-size: 0.8rem;
    line-height: 1.35;
}
/* Live feed: keep green guide circle fully inside frame */
#live-camera-guide-circle {
    width: 70%;
    max-width: 70%;
    max-height: 70%;
    aspect-ratio: 1 / 1;
    box-sizing: border-box;
    border-radius: 9999px;
}
/* Live feed: green border and pulse when active */
#live-camera-frame.quiz-mobile-live-frame .live-pulse-dot { width: 6px; height: 6px; background: #22c55e; border-radius: 50%; animation: quiz-mobile-pulse 1.5s ease-in-out infinite; }
@keyframes quiz-mobile-pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.6; transform: scale(1.2); } }
/* Safe area for notched devices */
.quiz-mobile-container { padding-bottom: env(safe-area-inset-bottom, 0); height: 100dvh; overflow: hidden; }
.safe-area-pt { padding-top: max(0.5rem, env(safe-area-inset-top, 0)); }
.safe-area-pb { padding-bottom: max(0.5rem, env(safe-area-inset-bottom, 0)); }
/* Floating AI camera: fixed overlay so it stays visible; main content has top offset so nav is smooth and no accidental auto-submit from overlay */
.quiz-mobile-camera-overlay { position: fixed; top: 0; left: 0; right: 0; z-index: 30; }
.quiz-mobile-content-below-camera { padding-top: var(--quiz-mobile-camera-height, 0); }
</style>
@endpush

@section('content')
<div class="quiz-mobile-container min-h-[100dvh] min-w-0 w-full flex flex-col max-w-full overflow-x-hidden">
    {{-- Warnings (same IDs as desktop so proctoring JS can show them) --}}
    <div id="blur-warning" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/90 px-4">
        <div class="bg-white border border-gray-200 rounded-xl p-4 max-w-md w-full text-center">
            <p class="text-sm text-gray-700 mb-4">Please stay on the quiz page. Further violations may submit your quiz automatically.</p>
            <button type="button" onclick="this.closest('#blur-warning').classList.add('hidden')" class="btn btn-action py-2.5 px-5 text-sm font-semibold">OK</button>
        </div>
    </div>
    <div id="tab-switch-once-warning" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/90 px-4">
        <div class="bg-amber-50 border border-amber-400 rounded-xl p-4 max-w-md w-full text-center">
            <h4 class="font-semibold text-amber-800 mb-1">You left this tab</h4>
            <p class="text-sm text-amber-800 mb-3">If you switch tabs again, your quiz may be auto-submitted. Stay on this tab to continue.</p>
            <button type="button" onclick="this.closest('#tab-switch-once-warning').classList.add('hidden')" class="btn btn-action py-2.5 px-5 text-sm font-semibold">OK</button>
        </div>
    </div>
    <div id="face-loss-warning-first" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/90 px-4">
        <div class="bg-yellow-50 border-2 border-yellow-400 rounded-xl p-4 max-w-md w-full text-center">
            <h4 class="font-semibold text-yellow-800 mb-2">You are out of the camera frame</h4>
            <p class="text-sm text-yellow-900 mb-4">Please return your face to the center of the camera. More warnings may result in auto-submission.</p>
            <button type="button" onclick="this.closest('#face-loss-warning-first').classList.add('hidden')" class="btn btn-action py-2.5 px-5 text-sm font-semibold">I understand</button>
        </div>
    </div>
    <div id="camera-off-overlay" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-gray-900 px-4">
        <div class="bg-white border border-gray-200 rounded-xl p-4 max-w-md w-full text-center">
            <h4 class="font-semibold text-gray-800 mb-2">Camera is required</h4>
            <p class="text-sm text-gray-600 mb-4">Your camera must stay on throughout the quiz. Click below to allow camera access.</p>
            <button type="button" id="camera-off-allow-btn" class="py-2.5 px-5 text-sm font-semibold rounded-lg border-2 border-sky-400 bg-sky-50 text-sky-800">Allow camera &amp; continue</button>
        </div>
    </div>
    <div id="phone-detected-modal" class="hidden fixed inset-0 z-[70] flex items-center justify-center bg-gray-900/95 px-4">
        <div class="bg-red-50 border-2 border-red-600 rounded-xl p-5 max-w-md w-full text-center">
            <h4 class="font-bold text-red-900 mb-3 text-lg">Critical violation</h4>
            <p class="text-sm text-red-900 mb-4">A phone or device was detected in your camera. Your quiz has been automatically submitted.</p>
        </div>
    </div>

    {{-- Floating AI camera: fixed overlay <div> inside the quiz page (no separate window/iframe); blur detection ignores this overlay --}}
    <div id="quiz-mobile-camera-overlay" class="quiz-mobile-camera-overlay quiz-proctoring-camera-overlay" aria-label="Live camera feed">
        <div class="bg-white border-b border-gray-200 px-3 pt-2 pb-3 safe-area-pt">
            <div class="quiz-mobile-top-grid">
                {{-- Left: compact timer card --}}
                <section class="quiz-mobile-timer-card" aria-label="Quiz timer">
                    <p class="quiz-mobile-timer-card-title truncate">{{ $session->quiz->title }}</p>
                    <div class="flex items-center justify-between mt-1">
                        <div class="inline-flex items-center gap-1.5">
                            <span class="live-pulse-dot" aria-hidden="true" style="width:8px;height:8px;border-radius:9999px;background:#22c55e;"></span>
                            <span class="text-[0.70rem] font-medium text-gray-500 uppercase tracking-wide">Time left</span>
                        </div>
                        <span id="quiz-mobile-timer" class="quiz-mobile-timer-card-time tabular-nums quiz-mobile-timer-green" aria-live="polite">--:--</span>
                    </div>
                </section>

                {{-- Right: live camera card (same IDs as desktop for proctoring JS) --}}
                <section class="rounded-xl border border-emerald-500/80 bg-gray-900 overflow-hidden relative" aria-label="Monitoring camera feed">
                    <div id="live-camera-frame" class="quiz-mobile-live-frame flex-shrink-0 w-full aspect-video max-h-[22vh] bg-gray-900 border-2 border-emerald-500 rounded-xl overflow-hidden relative mx-auto">
                        <div id="live-camera-video-slot" class="absolute inset-0 flex items-center justify-center">
                            <span class="text-gray-500 text-[10px]">Camera feed</span>
                        <div id="live-camera-guide-overlay" class="absolute inset-0 pointer-events-none flex items-center justify-center z-10" aria-hidden="true" style="padding-bottom: 24px; box-sizing: border-box;">
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div id="live-camera-guide-circle" class="min-w-[72px] max-w-[160px] rounded-full border-2 border-dashed border-emerald-500 bg-transparent" title="Keep your head inside"></div>
                                </div>
                                <div id="live-camera-face-dot" class="absolute w-2.5 h-2.5 rounded-full bg-emerald-500 border-2 border-white shadow z-10 hidden" style="left:50%;top:50%;transform:translate(-50%,-50%);"></div>
                            </div>
                        </div>
                        <div class="absolute top-1.5 left-1.5 z-20 px-1.5 py-0.5 rounded-full bg-emerald-600 text-white text-[9px] font-bold uppercase tracking-wider shadow flex items-center">
                            <span class="live-pulse-dot inline-block mr-1"></span>
                            <span>Live</span>
                        </div>
                        <div id="live-camera-pill" class="absolute top-1.5 right-1.5 z-20 px-1.5 py-0.5 rounded-full text-[9px] font-semibold text-white bg-emerald-500 hidden">Face detected</div>
                        {{-- Visually hide status banner on mobile (keep element/IDs for JS) --}}
                        <div id="live-camera-banner" class="sr-only" aria-hidden="true">
                            <span id="live-camera-banner-icon">✓</span>
                            <p id="live-camera-status-text">Monitoring camera feed.</p>
                        </div>
                        <div id="live-camera-position-label" class="sr-only" aria-hidden="true">Position: Good</div>
                        <div class="sr-only absolute opacity-0 pointer-events-none"><div id="live-bar-x" class="h-full bg-emerald-500 rounded-full transition-all" style="width:50%;"></div></div>
                        <div class="sr-only absolute opacity-0 pointer-events-none"><div id="live-bar-y" class="h-full bg-emerald-500 rounded-full transition-all" style="width:50%;"></div></div>
                        <div class="sr-only absolute opacity-0 pointer-events-none"><div id="live-bar-size" class="h-full bg-emerald-500 rounded-full transition-all" style="width:50%;"></div></div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    {{-- Spacer so content is not hidden under fixed camera overlay --}}
    <div id="quiz-mobile-camera-spacer" class="flex-shrink-0 w-full" style="height: var(--quiz-mobile-camera-height, 0);" aria-hidden="true"></div>

    {{-- Middle: One question per page (smooth back/forth navigation) --}}
    <main class="quiz-mobile-content-below-camera flex-1 min-h-0 overflow-y-auto overflow-x-hidden px-4 py-4">
        <form id="quiz-mobile-form" class="space-y-4">
            @foreach($questions as $idx => $question)
            @php $pageNum = $idx + 1; @endphp
            <div class="quiz-mobile-page bg-white border border-gray-200 rounded-xl p-4 min-w-0 shadow-sm" data-page="{{ $pageNum }}" data-question-id="{{ $question->id }}">
                <div class="flex gap-3 mb-3">
                    <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-primary-100 text-primary-800 flex items-center justify-center text-sm font-bold">{{ $pageNum }}</span>
                    <p class="flex-1 min-w-0 text-gray-800 font-medium text-base leading-relaxed break-words">{{ $question->text }}</p>
                </div>
                @if($question->type === 'mcq')
                    @php $optionsForQuestion = $shuffledOptionsByQuestion[$question->id] ?? $question->options; @endphp
                    @if(is_array($optionsForQuestion) && !empty($optionsForQuestion))
                    <div class="quiz-mobile-options-grid min-w-0">
                        @foreach($optionsForQuestion as $opt)
                            @php $key = $opt['key'] ?? $opt; $saved = $savedAnswers[$question->id] ?? null; @endphp
                            <label class="quiz-mobile-option-label p-3 rounded-xl border border-gray-200 cursor-pointer hover:border-primary-300 active:bg-primary-50 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50 min-h-[48px] min-w-0 touch-manipulation">
                                <input type="radio" name="q_{{ $question->id }}" value="{{ $key }}" data-question-id="{{ $question->id }}" {{ $saved === $key ? 'checked' : '' }} class="quiz-mobile-option-input text-primary-600 border-gray-300 focus:ring-2 focus:ring-primary-500">
                                <span class="quiz-mobile-option-text text-gray-700 break-words">{{ $opt['text'] ?? $opt }}</span>
                            </label>
                        @endforeach
                    </div>
                    @endif
                @else
                    <div class="min-w-0">
                        <textarea name="q_{{ $question->id }}" data-question-id="{{ $question->id }}" rows="4" placeholder="Type your answer here..." class="input min-h-[100px] w-full min-w-0 rounded-xl border border-gray-300 px-3 py-2.5 text-sm">{{ $savedAnswers[$question->id] ?? '' }}</textarea>
                    </div>
                @endif
            </div>
            @endforeach

            {{-- Submit block (shown on last page) --}}
            <div id="quiz-mobile-submit-block" class="quiz-mobile-page bg-white border border-gray-200 rounded-xl p-4 min-w-0" data-page="submit">
                <p id="quiz-mobile-answered-summary" class="text-sm text-gray-700 mb-2">{{ $answeredCount ?? 0 }} of {{ $questions->count() }} questions answered.</p>
                <p class="text-sm text-gray-600 mb-4">When you finish, you will go to a final photo screen to complete your submission.</p>
            </div>
        </form>
    </main>

    {{-- Bottom: Prev / Next + Complete and Confirm --}}
    <footer class="flex-shrink-0 bg-white border-t border-gray-200 px-4 py-3 safe-area-pb">
        <div class="flex items-center justify-between gap-3 mb-3">
            <button type="button" id="quiz-mobile-prev" class="flex items-center justify-center w-12 h-12 rounded-xl border-2 border-gray-300 text-gray-700 hover:bg-gray-50 active:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none touch-manipulation" disabled aria-label="Previous question">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <span id="quiz-mobile-page-info" class="text-sm font-medium text-gray-600">1 of {{ $totalPages }}</span>
            <button type="button" id="quiz-mobile-next" class="flex items-center justify-center w-12 h-12 rounded-xl border-2 border-gray-300 text-gray-700 hover:bg-gray-50 active:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none touch-manipulation" aria-label="Next question">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
        <button type="button" id="quiz-mobile-complete-btn" class="w-full py-3.5 px-4 rounded-xl font-semibold text-white bg-primary-600 hover:bg-primary-700 active:bg-primary-800 border-0 text-base touch-manipulation" data-final-url="{{ route('student.final-photo.capture') }}">
            Complete and confirm
        </button>
    </footer>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.10.0/dist/tf.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface@0.1.0/dist/blazeface.min.umd.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/coco-ssd@2.2.2/dist/coco-ssd.min.js" crossorigin="anonymous"></script>
<script src="{{ asset('js/quiz-window-state.js') }}"></script>
<script src="{{ asset('js/quiz-proctoring.js') }}" defer></script>
<script src="{{ asset('js/proctoring/intelligentFaceMonitor.js') }}" defer></script>
<script src="{{ asset('js/proctoring/objectMonitor.js') }}" defer></script>
<script src="{{ asset('js/proctoring/audioMonitor.js') }}" defer></script>
<script>
window.QuizSnapQuiz = {
    saveAnswerUrl: "{{ route('student.quiz.save') }}",
    saveAnswersBatchUrl: "{{ route('student.quiz.save.batch') }}",
    violationUrl: "{{ route('student.quiz.violation') }}",
    violationCaptureUrl: "{{ route('student.quiz.violation.capture') }}",
    heartbeatUrl: "{{ route('student.quiz.heartbeat') }}",
    proctorFeedUrl: "{{ route('student.quiz.proctor-feed') }}",
    finalPhotoUrl: "{{ route('student.final-photo.capture') }}",
    finalizeUrl: "{{ route('student.quiz.finalize') }}",
    timeSyncUrl: "{{ route('student.quiz.time-sync') }}",
    csrfToken: "{{ csrf_token() }}",
    sessionId: {{ $session->id ?? 0 }},
    storagePrefix: "quizsnap_answers_{{ $session->id ?? 0 }}",
    durationSeconds: {{ $durationSeconds }},
    remainingSeconds: {{ $remainingSeconds }},
    totalPages: {{ $totalPages }},
    perPage: 1,
    windowResizeLimit: 3,
    cameraRequired: {{ ($proctoringCameraRequired ?? true) ? 'true' : 'false' }},
    proctoringFaceMonitor: {{ ($proctoringFaceMonitor ?? true) ? 'true' : 'false' }},
    proctoringTabSwitch: {{ ($proctoringTabSwitch ?? true) ? 'true' : 'false' }},
    proctoringObjectDetect: {{ ($proctoringObjectDetect ?? true) ? 'true' : 'false' }},
    proctoringBlockRightClick: {{ ($proctoringBlockRightClick ?? true) ? 'true' : 'false' }},
    proctoringBlockCopyPaste: {{ ($proctoringBlockCopyPaste ?? true) ? 'true' : 'false' }},
    liveProctorEnabled: {{ ($liveProctorEnabled ?? false) ? 'true' : 'false' }},
    studentIndex: @json($session->student_index ?? null),
    studentName: @json($matchedStudentName ?? null),
    studentNameLinked: {{ ($studentNameLinked ?? false) ? 'true' : 'false' }}
};
window.QuizSnapIntelligentFaceMonitor = window.QuizSnapIntelligentFaceMonitor || {};
window.QuizSnapIntelligentFaceMonitor.config = {
    violationUrl: "{{ route('student.quiz.violation') }}",
    violationCaptureUrl: "{{ route('student.quiz.violation.capture') }}",
    autoSubmitUrl: "{{ route('student.quiz.auto-submit') }}",
    csrfToken: "{{ csrf_token() }}",
    sessionId: {{ $session->id ?? 0 }},
    initialOutOfFrameCount: {{ (int) ($outOfFrameCount ?? 0) }},
    initialNormalViolationCount: {{ (int) ($normalViolationCount ?? 0) }},
    initialHeadTurnCount: {{ (int) ($headTurnCount ?? 0) }},
    studentIndex: @json($session->student_index ?? null),
    studentName: @json($matchedStudentName ?? null),
    studentNameLinked: {{ ($studentNameLinked ?? false) ? 'true' : 'false' }}
};
window.QuizSnapObjectMonitor = { config: { violationCaptureUrl: "{{ route('student.quiz.violation.capture') }}", csrfToken: "{{ csrf_token() }}", sessionId: {{ $session->id ?? 0 }} } };
window.QuizSnapAudioMonitor = { config: { violationCaptureUrl: "{{ route('student.quiz.violation.capture') }}", csrfToken: "{{ csrf_token() }}", sessionId: {{ $session->id ?? 0 }} } };

document.addEventListener('DOMContentLoaded', function() {
    var c = window.QuizSnapQuiz || {};
    // Set content offset so fixed camera overlay doesn't cover questions (smooth navigation)
    var overlay = document.getElementById('quiz-mobile-camera-overlay');
    if (overlay) {
        var h = overlay.offsetHeight;
        document.documentElement.style.setProperty('--quiz-mobile-camera-height', h + 'px');
    }
    var sessionId = c.sessionId || {{ $session->id ?? 0 }};
    var storagePrefix = c.storagePrefix || ('quizsnap_answers_' + sessionId);
    var questionsKey = storagePrefix + '_questions';
    var answersKey = storagePrefix + '_answers';
    var totalPages = c.totalPages || 1;
    var questionIds = @json($questions->pluck('id')->values()->all());
    var currentPage = 1;
    var storageKey = 'quizsnap_quiz_mobile_page_' + sessionId;
    try { var s = sessionStorage.getItem(storageKey); if (s) currentPage = Math.max(1, Math.min(totalPages, parseInt(s, 10))); } catch (e) {}

    var form = document.getElementById('quiz-mobile-form');
    var pages = form ? form.querySelectorAll('.quiz-mobile-page[data-page]') : [];
    var submitBlock = document.getElementById('quiz-mobile-submit-block');
    var pageInfo = document.getElementById('quiz-mobile-page-info');
    var prevBtn = document.getElementById('quiz-mobile-prev');
    var nextBtn = document.getElementById('quiz-mobile-next');
    var completeBtn = document.getElementById('quiz-mobile-complete-btn');
    var timerEl = document.getElementById('quiz-mobile-timer');

    // --- On quiz start: store questions in sessionStorage (local copy) ---
    try {
        sessionStorage.setItem(questionsKey, JSON.stringify(questionIds));
    } catch (e) {}

    // Restore answers from sessionStorage (e.g. after reload) so local state is preserved
    try {
        var stored = sessionStorage.getItem(answersKey);
        if (stored && form) {
            var parsed = JSON.parse(stored);
            for (var qId in parsed) {
                var name = 'q_' + qId;
                var val = String(parsed[qId] || '');
                var ta = form.querySelector('textarea[name="' + name + '"]');
                if (ta) { ta.value = val; continue; }
                form.querySelectorAll('input[name="' + name + '"]').forEach(function(r) {
                    r.checked = (r.value === val);
                });
            }
        }
    } catch (e) {}

    // --- During quiz: save answers locally only (sessionStorage) ---
    function saveAnswersToStorage() {
        if (!form) return;
        var answers = {};
        form.querySelectorAll('input[type="radio"][name^="q_"]').forEach(function(r) {
            var name = r.name;
            if (name && name.indexOf('q_') === 0) {
                var qId = name.replace('q_', '');
                var checked = form.querySelector('input[name="' + name + '"]:checked');
                answers[qId] = checked ? (checked.value || '') : '';
            }
        });
        form.querySelectorAll('textarea[data-question-id]').forEach(function(ta) {
            if (ta.name && ta.dataset && ta.dataset.questionId) {
                answers[ta.dataset.questionId] = ta.value ? String(ta.value).trim() : '';
            }
        });
        try {
            sessionStorage.setItem(answersKey, JSON.stringify(answers));
        } catch (e) {}
    }

    function getAllAnswersFromForm() {
        var answers = {};
        if (!form) return answers;
        form.querySelectorAll('input[type="radio"][name^="q_"]').forEach(function(r) {
            var name = r.name;
            if (name && name.indexOf('q_') === 0) {
                var qId = name.replace('q_', '');
                var checked = form.querySelector('input[name="' + name + '"]:checked');
                answers[qId] = checked ? (checked.value || '') : '';
            }
        });
        form.querySelectorAll('textarea[data-question-id]').forEach(function(ta) {
            if (ta.name && ta.dataset && ta.dataset.questionId) {
                answers[ta.dataset.questionId] = ta.value ? String(ta.value).trim() : '';
            }
        });
        try {
            var stored = sessionStorage.getItem(answersKey);
            if (stored) {
                var parsed = JSON.parse(stored);
                for (var k in parsed) answers[k] = parsed[k];
            }
        } catch (e) {}
        return answers;
    }

    function buildAnswersPayload() {
        var answers = getAllAnswersFromForm();
        return questionIds.map(function(qId) {
            return { question_id: parseInt(qId, 10), answer: answers[String(qId)] || '' };
        });
    }

    var timerEndKey = storagePrefix + '_quizEndTime';

    function clearLocalQuizData() {
        try {
            sessionStorage.removeItem(questionsKey);
            sessionStorage.removeItem(answersKey);
            sessionStorage.removeItem(storageKey);
            sessionStorage.removeItem(timerEndKey);
        } catch (e) {}
    }

    function persistTimerEnd(endTimeMs) {
        try {
            if (endTimeMs != null) sessionStorage.setItem(timerEndKey, String(endTimeMs));
        } catch (e) {}
    }

    function getPersistedTimerEnd() {
        try {
            var s = sessionStorage.getItem(timerEndKey);
            return s ? parseInt(s, 10) : null;
        } catch (e) { return null; }
    }

    var submitting = false;
    var MOBILE_SAVE_CHUNK = 25;
    function submitAllAnswersThenRedirect() {
        if (submitting) return;
        submitting = true;
        if (completeBtn) completeBtn.disabled = true;
        var payload = buildAnswersPayload();
        var batchUrl = c.saveAnswersBatchUrl;
        var finalPhotoUrl = completeBtn ? (completeBtn.getAttribute('data-final-url') || c.finalPhotoUrl) : c.finalPhotoUrl;
        if (!batchUrl || !finalPhotoUrl) {
            submitting = false;
            if (completeBtn) completeBtn.disabled = false;
            if (finalPhotoUrl) window.location.href = finalPhotoUrl;
            return;
        }
        function postChunk(startIdx) {
            var chunk = payload.slice(startIdx, startIdx + MOBILE_SAVE_CHUNK);
            if (chunk.length === 0) {
                clearLocalQuizData();
                if (window.QuizSnapQuiz) window.QuizSnapQuiz.navigatingToFinalPhoto = true;
                window.location.href = finalPhotoUrl;
                return;
            }
            fetch(batchUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': c.csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ answers: chunk })
            })
                .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
                .then(function(result) {
                    if (result.ok && result.data && result.data.success) {
                        postChunk(startIdx + MOBILE_SAVE_CHUNK);
                    } else {
                        submitting = false;
                        if (completeBtn) completeBtn.disabled = false;
                        alert((result.data && result.data.message) ? result.data.message : 'Could not save all answers. Check your connection and try Finish again.');
                    }
                })
                .catch(function() {
                    submitting = false;
                    if (completeBtn) completeBtn.disabled = false;
                    alert('Network error while saving answers. Check your connection and try Finish again.');
                });
        }
        postChunk(0);
    }

    function isAnswered(questionId) {
        if (!form) return false;
        var name = 'q_' + questionId;
        var radio = form.querySelector('input[name="' + name + '"]:checked');
        if (radio) return true;
        var ta = form.querySelector('textarea[name="' + name + '"]');
        return ta && ta.value && String(ta.value).trim() !== '';
    }

    function updateAnsweredSummary() {
        var total = questionIds.length;
        var seen = {};
        var answered = 0;
        form.querySelectorAll('input[type="radio"][name^="q_"]').forEach(function(r) {
            if (!seen[r.name]) { seen[r.name] = true; if (form.querySelector('input[name="' + r.name + '"]:checked')) answered++; }
        });
        form.querySelectorAll('textarea[data-question-id]').forEach(function(ta) {
            if (ta.name && !seen[ta.name]) { seen[ta.name] = true; if (ta.value && String(ta.value).trim() !== '') answered++; }
        });
        var el = document.getElementById('quiz-mobile-answered-summary');
        if (el) el.textContent = answered + ' of ' + total + ' questions answered.';
    }

    function showPage(page) {
        currentPage = Math.max(1, Math.min(totalPages + 1, page));
        try { sessionStorage.setItem(storageKey, String(currentPage)); } catch (e) {}
        saveAnswersToStorage();
        var isSubmit = currentPage > totalPages;
        pages.forEach(function(el) {
            var p = el.getAttribute('data-page');
            if (p === 'submit') {
                el.style.display = isSubmit ? 'block' : 'none';
            } else {
                el.style.display = (parseInt(p, 10) === currentPage) ? 'block' : 'none';
            }
        });
        if (submitBlock) submitBlock.style.display = isSubmit ? 'block' : 'none';
        if (pageInfo) pageInfo.textContent = isSubmit ? (totalPages + ' of ' + totalPages) : (currentPage + ' of ' + totalPages);
        if (prevBtn) prevBtn.disabled = currentPage <= 1;
        if (nextBtn) nextBtn.disabled = isSubmit;
        if (completeBtn) completeBtn.style.display = isSubmit ? 'block' : 'none';
        if (isSubmit) updateAnsweredSummary();
    }

    if (prevBtn) prevBtn.addEventListener('click', function() { if (currentPage > 1) showPage(currentPage - 1); });
    if (nextBtn) nextBtn.addEventListener('click', function() {
        if (currentPage <= totalPages) showPage(currentPage + 1);
    });
    if (completeBtn) completeBtn.addEventListener('click', function() {
        submitAllAnswersThenRedirect();
    });

    if (form) {
        form.addEventListener('change', function() {
            saveAnswersToStorage();
            updateAnsweredSummary();
        });
        form.addEventListener('input', function() {
            saveAnswersToStorage();
            updateAnsweredSummary();
        });
    }

    showPage(currentPage);
    updateAnsweredSummary();

    // --- Mobile timer: persist end time so timer continues after reload; server validates ---
    var remainingSeconds = c.remainingSeconds || 0;
    var endTimeMs = null;
    var timerInterval = null;
    var timeSyncInterval = null;
    var TIME_SYNC_MS = 40000;

    // Restore timer from sessionStorage so global timer continues after page reload
    var persistedEnd = getPersistedTimerEnd();
    if (persistedEnd != null && persistedEnd > Date.now()) {
        endTimeMs = persistedEnd;
        remainingSeconds = Math.max(0, Math.ceil((endTimeMs - Date.now()) / 1000));
    }

    function formatTime(sec) {
        sec = Math.max(0, Math.floor(sec));
        var m = Math.floor(sec / 60);
        var s = sec % 60;
        return m + ':' + (s < 10 ? '0' : '') + s;
    }

    function applyTimerColor(sec) {
        if (!timerEl) return;
        timerEl.classList.remove('quiz-mobile-timer-green', 'quiz-mobile-timer-blue', 'quiz-mobile-timer-red');
        if (sec <= 30) timerEl.classList.add('quiz-mobile-timer-red');
        else if (sec <= 120) timerEl.classList.add('quiz-mobile-timer-blue');
        else timerEl.classList.add('quiz-mobile-timer-green');
    }

    function syncTimeFromServer() {
        if (!c.timeSyncUrl || remainingSeconds <= 0) return;
        fetch(c.timeSyncUrl, { method: 'GET', headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function(r) { return r.ok ? r.json() : null; })
            .then(function(data) {
                if (data && typeof data.remaining_seconds === 'number') {
                    remainingSeconds = Math.max(0, data.remaining_seconds);
                    endTimeMs = Date.now() + remainingSeconds * 1000;
                    persistTimerEnd(endTimeMs);
                    if (timerEl) timerEl.textContent = formatTime(remainingSeconds);
                    applyTimerColor(remainingSeconds);
                    if (remainingSeconds <= 0) {
                        if (timerInterval) clearInterval(timerInterval);
                        if (timeSyncInterval) clearInterval(timeSyncInterval);
                        submitAllAnswersThenRedirect();
                    }
                }
            })
            .catch(function() {});
    }

    function updateTimer() {
        if (endTimeMs !== null) {
            remainingSeconds = Math.max(0, Math.ceil((endTimeMs - Date.now()) / 1000));
        } else {
            remainingSeconds = Math.max(0, remainingSeconds - 1);
        }
        if (remainingSeconds <= 0) {
            if (timerInterval) clearInterval(timerInterval);
            if (timeSyncInterval) clearInterval(timeSyncInterval);
            if (timerEl) timerEl.textContent = '0:00';
            applyTimerColor(0);
            submitAllAnswersThenRedirect();
            return;
        }
        if (timerEl) timerEl.textContent = formatTime(remainingSeconds);
        applyTimerColor(remainingSeconds);
    }

    if (timerEl && remainingSeconds > 0) {
        if (endTimeMs == null) {
            endTimeMs = Date.now() + remainingSeconds * 1000;
            persistTimerEnd(endTimeMs);
        }
        timerEl.textContent = formatTime(remainingSeconds);
        applyTimerColor(remainingSeconds);
        timerInterval = setInterval(updateTimer, 1000);
        if (c.timeSyncUrl) {
            syncTimeFromServer();
            timeSyncInterval = setInterval(syncTimeFromServer, TIME_SYNC_MS);
            document.addEventListener('visibilitychange', function() {
                if (document.visibilityState === 'visible') syncTimeFromServer();
            });
        }
    }
});
</script>
@endpush
@endsection
