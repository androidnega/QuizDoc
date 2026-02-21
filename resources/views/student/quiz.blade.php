@extends('layouts.student')

@section('title', 'Quiz - ' . $session->quiz->title)
@section('body_class', 'bg-offwhite')

@push('styles')
<style>
.quiz-timer-green{color:#059669}.quiz-timer-blue{color:#2563eb}.quiz-timer-red{color:#dc2626}.quiz-side-num.quiz-side-answered{border-color:#22c55e;background-color:#f0fdf4;color:#15803d}
/* Pulsing AI invigilator badge when camera is active */
#ai-invigilator-badge{display:none;align-items:center;justify-content:center;gap:0.5rem;padding:0.5rem 1rem;background:linear-gradient(135deg,#1e3a5f 0%,#0f172a 100%);color:#e2e8f0;font-size:0.75rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;border:1px solid rgba(148,163,184,0.3);border-radius:9999px;box-shadow:0 0 0 2px rgba(59,130,246,0.2)}
#ai-invigilator-badge.visible{display:flex}
#ai-invigilator-badge .pulse-dot{width:6px;height:6px;background:#22c55e;border-radius:50%;animation:ai-invigilator-pulse 1.5s ease-in-out infinite}
#ai-invigilator-badge .text{animation:ai-invigilator-glow 2s ease-in-out infinite}
@keyframes ai-invigilator-pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:0.5;transform:scale(1.2)}}
@keyframes ai-invigilator-glow{0%,100%{opacity:1;text-shadow:0 0 8px rgba(34,197,94,0.4)}50%{opacity:0.9;text-shadow:0 0 14px rgba(34,197,94,0.6)}}
</style>
@endpush

@section('content')
<div class="min-h-screen min-w-0 w-full">
    {{-- Phase 7: Clean desktop-only notice on mobile (quiz writing page only). Other student pages remain fully accessible on mobile. --}}
    <div class="quiz-desktop-only-notice block md:hidden min-h-screen flex flex-col items-center justify-center px-4 py-8 bg-gray-50">
        <div class="w-full max-w-md bg-white border border-[#e5e5e5] rounded-xl p-6 text-center" style="box-shadow: none;">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-lg bg-gray-100 text-gray-500 mb-4">
                <i class="fas fa-desktop text-xl" aria-hidden="true"></i>
            </div>
            <h2 class="text-lg font-medium text-gray-800 mb-2">Desktop only</h2>
            <p class="text-sm text-gray-600">This quiz is designed for a desktop or laptop. Please use a computer to take it.</p>
        </div>
    </div>

    <div class="quiz-writing-content hidden md:block min-h-screen min-w-0 w-full">
    {{-- Sticky header --}}
    <header class="sticky top-0 z-40 bg-white border-b border-gray-200 shrink-0">
        <div class="w-full px-4 sm:px-6 py-3 flex items-center min-w-0">
            <h1 class="text-base font-semibold text-gray-800 truncate min-w-0">{{ $session->quiz->title }}</h1>
        </div>
    </header>

    {{-- Pulsing "AI INVIGILATOR WATCHING" when camera is active --}}
    <div id="ai-invigilator-badge" class="fixed left-4 bottom-4 z-40 pointer-events-none" aria-hidden="true">
        <span class="pulse-dot" aria-hidden="true"></span>
        <span class="text">AI Invigilator Watching</span>
    </div>

    {{-- Single major violation warning (max once per session): calm, non-accusatory --}}
    <div id="blur-warning" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-90 px-4">
        <div class="bg-white border border-gray-200 rounded-lg p-4 max-w-md w-full text-center">
            <p class="text-sm text-gray-700 mb-4">Please stay on the quiz page. Further violations will submit your quiz automatically.</p>
            <button type="button" onclick="this.closest('#blur-warning').classList.add('hidden')" class="btn btn-action py-2.5 px-5 text-sm font-semibold">OK</button>
        </div>
    </div>
    <div id="right-click-warning" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-90 px-4">
        <div class="bg-warning-50 border border-warning-300 rounded-lg p-4 max-w-md w-full">
            <h4 class="font-semibold text-warning-800 mb-1">Do not right-click</h4>
            <p class="text-sm text-warning-800 mb-3">Stay on this tab. Right-click is not allowed; your attempt has been noted.</p>
            <button type="button" onclick="this.closest('#right-click-warning').classList.add('hidden')" class="btn btn-secondary py-2.5 px-5 text-sm font-semibold">OK</button>
        </div>
    </div>
    <div id="new-tab-zone-warning" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-90 px-4">
        <div class="bg-amber-50 border border-amber-300 rounded-lg p-4 max-w-md w-full">
            <h4 class="font-semibold text-amber-800 mb-1">Stay in the quiz</h4>
            <p class="text-sm text-amber-800 mb-3">If you open a new tab or switch to another tab, that will be detected as leaving the page and may result in your quiz being auto-submitted. Stay on this tab and keep your cursor in the quiz area.</p>
            <button type="button" onclick="this.closest('#new-tab-zone-warning').classList.add('hidden')" class="btn btn-action py-2.5 px-5 text-sm font-semibold">OK</button>
        </div>
    </div>
    <div id="tab-switch-once-warning" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-90 px-4">
        <div class="bg-amber-50 border border-amber-400 rounded-lg p-4 max-w-md w-full">
            <h4 class="font-semibold text-amber-800 mb-1">You left this tab</h4>
            <p class="text-sm text-amber-800 mb-3">If you switch tabs again, your quiz will be auto-submitted immediately with no further warning. Stay on this tab to continue.</p>
            <button type="button" onclick="this.closest('#tab-switch-once-warning').classList.add('hidden')" class="btn btn-action py-2.5 px-5 text-sm font-semibold">OK</button>
        </div>
    </div>

    {{-- Face loss warning system (max 4 warnings, 5th = auto-submit) --}}
    <div id="face-loss-warning-first" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-90 px-4">
        <div class="bg-yellow-50 border-2 border-yellow-400 rounded-lg p-4 max-w-md w-full text-center">
            <h4 class="font-semibold text-yellow-800 mb-2">⚠️ You are out of the camera frame</h4>
            <p class="text-sm text-yellow-900 mb-4">Please return your face to the center of the camera and keep it visible at all times. <strong>4 more warnings remaining</strong> before auto-submission.</p>
            <button type="button" onclick="this.closest('#face-loss-warning-first').classList.add('hidden')" class="btn btn-action py-2.5 px-5 text-sm font-semibold">I understand</button>
        </div>
    </div>
    <div id="face-loss-warning-second" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-90 px-4">
        <div class="bg-orange-50 border-2 border-orange-500 rounded-lg p-4 max-w-md w-full text-center">
            <h4 class="font-semibold text-orange-800 mb-2">⚠️ Second Warning!</h4>
            <p class="text-sm text-orange-900 mb-4">You are out of the camera frame again. Please return your face to the center. <strong>3 more warnings remaining</strong> before your quiz is auto-submitted.</p>
            <button type="button" onclick="this.closest('#face-loss-warning-second').classList.add('hidden')" class="btn btn-action py-2.5 px-5 text-sm font-semibold">I understand</button>
        </div>
    </div>
    <div id="face-loss-warning-third" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-90 px-4">
        <div class="bg-orange-100 border-2 border-orange-600 rounded-lg p-4 max-w-md w-full text-center">
            <h4 class="font-semibold text-orange-900 mb-2">⚠️ Third Warning!</h4>
            <p class="text-sm text-orange-900 mb-4">You are out of the camera frame again. Please return your face to the center. <strong>2 more warnings remaining</strong> before auto-submission.</p>
            <button type="button" onclick="this.closest('#face-loss-warning-third').classList.add('hidden')" class="btn btn-action py-2.5 px-5 text-sm font-semibold">I understand</button>
        </div>
    </div>
    <div id="face-loss-warning-fourth" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-90 px-4">
        <div class="bg-red-50 border-2 border-red-500 rounded-lg p-4 max-w-md w-full text-center">
            <h4 class="font-semibold text-red-800 mb-2">⚠️ Fourth Warning!</h4>
            <p class="text-sm text-red-900 mb-4">You are out of the camera frame again. <strong>1 more warning will auto-submit your quiz.</strong> Please return your face to the center of the camera.</p>
            <button type="button" onclick="this.closest('#face-loss-warning-fourth').classList.add('hidden')" class="btn btn-action py-2.5 px-5 text-sm font-semibold">I understand</button>
        </div>
    </div>
    <div id="face-loss-warning-final" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-90 px-4">
        <div class="bg-red-50 border-2 border-red-600 rounded-lg p-4 max-w-md w-full text-center">
            <h4 class="font-semibold text-red-800 mb-2">🚨 Auto-submitting</h4>
            <p class="text-sm text-red-900 mb-4">This is your <strong>fifth face-out-of-frame violation</strong>. Your quiz will be <strong>automatically submitted now</strong> to protect exam integrity.</p>
            <button type="button" onclick="this.closest('#face-loss-warning-final').classList.add('hidden')" class="btn btn-danger py-2.5 px-5 text-sm font-semibold">I understand</button>
        </div>
    </div>
    <div id="object-detection-warning" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-gray-900 bg-opacity-90 px-4">
        <div class="bg-red-50 border-2 border-red-500 rounded-lg p-4 max-w-md w-full text-center">
            <h4 class="font-semibold text-red-800 mb-2">🚨 Prohibited object in camera</h4>
            <p id="object-detection-warning-text" class="text-sm text-red-900 mb-4">Please remove the object from the camera view. Repeated detection may result in auto-submission.</p>
            <button type="button" onclick="this.closest('#object-detection-warning').classList.add('hidden')" class="btn btn-danger py-2.5 px-5 text-sm font-semibold">I understand</button>
        </div>
    </div>
    {{-- Phone/device detected = critical violation, quiz auto-submitted --}}
    <div id="phone-detected-modal" class="hidden fixed inset-0 z-[70] flex items-center justify-center bg-gray-900 bg-opacity-95 px-4" aria-modal="true">
        <div class="bg-red-50 border-2 border-red-600 rounded-lg p-5 max-w-md w-full text-center">
            <h4 class="font-bold text-red-900 mb-3 text-lg">🚨 Critical violation</h4>
            <p class="text-sm text-red-900 mb-4">A phone or device was detected in your camera. Your quiz has been <strong>automatically submitted</strong>. This is a critical violation of exam rules.</p>
            <p class="text-xs text-red-700">You will be redirected shortly.</p>
        </div>
    </div>

    {{-- Reusable proctoring warning (out of frame, two faces, etc.) --}}
    <div id="proctoring-message-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-90 px-4">
        <div class="bg-amber-50 border-2 border-amber-500 rounded-lg p-4 max-w-md w-full text-center">
            <h4 id="proctoring-message-title" class="font-semibold text-amber-900 mb-2">⚠️ Warning</h4>
            <p id="proctoring-message-body" class="text-sm text-amber-900 mb-4"></p>
            <button type="button" onclick="this.closest('#proctoring-message-modal').classList.add('hidden')" class="btn btn-action py-2.5 px-5 text-sm font-semibold">OK</button>
        </div>
    </div>

    {{-- Camera off overlay: block quiz until user allows camera --}}
    <div id="camera-off-overlay" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-gray-900 px-4 pointer-events-auto" aria-hidden="true">
        <div class="bg-white border border-gray-200 rounded-lg p-4 max-w-md w-full border border-gray-200 text-center">
            <h4 class="font-semibold text-gray-800 mb-2">Camera is required</h4>
            <p class="text-sm text-gray-600 mb-4">Your camera must stay on throughout the quiz. Please allow camera access to continue.</p>
            <button type="button" id="camera-off-allow-btn" class="btn btn-action py-2.5 px-5 text-sm font-semibold">Allow camera &amp; continue</button>
        </div>
    </div>

    {{-- Resize / exit fullscreen overlay: opaque, blocks interaction until full screen or maximized --}}
    <div id="resize-blur-overlay" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-gray-900 px-4 pointer-events-auto" aria-hidden="true">
        <div class="bg-white border border-gray-200 rounded-lg p-4 max-w-md w-full border border-gray-200 text-center">
            <h4 id="resize-blur-title" class="font-semibold text-gray-800 mb-2">Window resized or left full screen</h4>
            <p id="resize-blur-message" class="text-sm text-gray-600 mb-2">Return to full screen or maximize this window to continue. The timer is still running.</p>
            <p id="resize-blur-warning" class="text-sm font-medium text-amber-700 mb-3 hidden">Repeated violations will result in auto-submission of your quiz.</p>
            <p id="resize-blur-final-warning" class="text-sm font-bold text-red-600 mb-3 hidden">One more resize or exit from full screen will auto-submit your quiz.</p>
        </div>
    </div>

    {{-- Main: full width on desktop so questions show on full screen --}}
    <main class="w-full max-w-full px-4 sm:px-6 py-6 min-w-0 overflow-x-hidden">
        <div class="flex flex-col lg:flex-row gap-6 lg:gap-8 items-start w-full max-w-full mx-auto">
            {{-- Questions + pagination --}}
            <div id="quiz-container" class="min-w-0 flex-1 w-full lg:max-w-[calc(100%-220px)] max-w-full">

                <form id="quiz-form" class="space-y-6">
                    @foreach($questions as $idx => $question)
                    @php
                        $pageNum = (int) floor($idx / $perPage) + 1;
                    @endphp
                    <div class="quiz-page-question bg-white border border-gray-200 rounded-xl p-5 min-w-0 overflow-hidden" data-page="{{ $pageNum }}" data-question-id="{{ $question->id }}">
                        <div class="flex gap-3 mb-4">
                            <span class="flex-shrink-0 w-8 h-8 bg-action rounded-lg flex items-center justify-center text-gray-900 font-bold text-sm">{{ $idx + 1 }}</span>
                            <p class="flex-1 min-w-0 text-gray-800 font-medium text-base leading-relaxed break-words">{{ $question->text }}</p>
                        </div>
                        @if($question->type === 'mcq')
                            @php
                                $optionsForQuestion = $shuffledOptionsByQuestion[$question->id] ?? $question->options;
                            @endphp
                            @if(is_array($optionsForQuestion) && !empty($optionsForQuestion))
                            <div class="ml-11 space-y-2 min-w-0">
                                @foreach($optionsForQuestion as $opt)
                                    @php $key = $opt['key'] ?? $opt; $saved = $savedAnswers[$question->id] ?? null; @endphp
                                    <label class="flex gap-3 p-3 rounded-lg border border-gray-200 cursor-pointer hover:border-primary-300 has-[:checked]:border-action has-[:checked]:bg-action-50 block min-h-[44px] min-w-0">
                                        <input type="radio" name="q_{{ $question->id }}" value="{{ $key }}" data-question-id="{{ $question->id }}" {{ $saved === $key ? 'checked' : '' }} class="mt-0.5 w-5 h-5 flex-shrink-0 text-primary-600 border-gray-300 focus:ring-2 focus:ring-primary-500">
                                        <span class="flex-1 min-w-0 text-gray-700 text-sm break-words">{{ $opt['text'] ?? $opt }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @endif
                        @else
                            <div class="ml-11 min-w-0">
                                <textarea name="q_{{ $question->id }}" data-question-id="{{ $question->id }}" rows="4" placeholder="Type your answer here..." class="input min-h-[100px] w-full min-w-0">{{ $savedAnswers[$question->id] ?? '' }}</textarea>
                            </div>
                        @endif
                    </div>
                    @endforeach

                    {{-- Submit (last page only): summary + finish — do not show which questions or skipped --}}
                    <div id="quiz-submit-block" class="bg-white border border-gray-200 rounded-xl p-5 min-w-0">
                        <p id="quiz-answered-summary" class="text-sm text-gray-700 mb-2" data-total="{{ $questions->count() }}">{{ $answeredCount ?? 0 }} of {{ $questions->count() }} questions answered.</p>
                        <p class="text-sm text-gray-600 mb-4">When you finish, you will go to a final photo screen to complete your submission.</p>
                        <button
                            type="button"
                            class="btn btn-action w-full sm:w-auto py-2.5 px-5 text-sm font-semibold"
                            id="post-face-btn"
                            data-final-url="{{ route('student.final-photo.capture') }}"
                            onclick="window.location.href=this.dataset.finalUrl"
                        >
                            Finish quiz
                        </button>
                    </div>
                </form>

                @if($totalPages > 1)
                <div id="quiz-pagination-bottom" class="flex items-center justify-between gap-4 mt-8 mb-2 py-5 flex-wrap">
                    <button type="button" id="quiz-prev-bottom" class="btn btn-secondary py-2.5 px-5 text-sm" disabled>Previous</button>
                    <span id="quiz-page-info-bottom" class="text-sm font-medium text-gray-700">Page 1 of {{ $totalPages }}</span>
                    <button type="button" id="quiz-next-bottom" class="btn btn-secondary py-2.5 px-5 text-sm">Next</button>
                </div>
                @endif
            </div>

            {{-- Side: one card with timer on top, then question nav (desktop); same card width as questions --}}
            <aside class="hidden lg:block w-[200px] flex-shrink-0 lg:self-start" aria-label="Quiz sidebar">
                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden sticky top-20 max-h-[calc(100vh-6rem)] overflow-y-auto min-w-0 shadow-sm">
                    @if($remainingSeconds > 0)
                    <div id="quiz-timer-card" class="p-4 border-b border-gray-200 flex items-center justify-center min-w-0">
                        <p id="quiz-timer" class="text-lg font-bold tabular-nums quiz-timer quiz-timer-green" aria-live="polite">--:--</p>
                    </div>
                    @endif
                    <div class="p-4 min-w-0">
                        <p class="text-xs text-gray-500 mb-2">Questions</p>
                        <div id="quiz-side-nav" class="flex flex-wrap gap-1">
                            @foreach($questions as $idx => $q)
                                @php $qPage = (int) floor($idx / $perPage) + 1; @endphp
                                <a href="#quiz-container" class="quiz-side-num w-8 h-8 rounded border border-gray-200 flex items-center justify-center text-sm font-medium text-gray-600 hover:border-primary-300 hover:bg-primary-50 shrink-0" data-question-id="{{ $q->id }}" data-page="{{ $qPage }}">{{ $idx + 1 }}</a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </main>
    </div>
</div>

@push('scripts')
<!-- TensorFlow.js + BlazeFace for Face Detection -->
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.10.0/dist/tf.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface@0.1.0/dist/blazeface.min.umd.js" crossorigin="anonymous"></script>

<!-- TensorFlow.js for Object Detection -->
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
    perPage: {{ $perPage }},
    windowResizeLimit: 3,
    cameraRequired: {{ ($proctoringCameraRequired ?? true) ? 'true' : 'false' }},
    proctoringFaceMonitor: {{ ($proctoringFaceMonitor ?? true) ? 'true' : 'false' }},
    proctoringTabSwitch: {{ ($proctoringTabSwitch ?? true) ? 'true' : 'false' }},
    proctoringObjectDetect: {{ ($proctoringObjectDetect ?? true) ? 'true' : 'false' }},
    proctoringBlockRightClick: {{ ($proctoringBlockRightClick ?? true) ? 'true' : 'false' }},
    proctoringBlockCopyPaste: {{ ($proctoringBlockCopyPaste ?? true) ? 'true' : 'false' }},
    liveProctorEnabled: {{ ($liveProctorEnabled ?? true) ? 'true' : 'false' }},
    studentIndex: @json($session->student_index ?? null),
    studentName: @json($matchedStudentName ?? null),
    studentNameLinked: {{ ($studentNameLinked ?? false) ? 'true' : 'false' }}
};

// Configure intelligent face monitor for quiz monitoring
window.QuizSnapIntelligentFaceMonitor = window.QuizSnapIntelligentFaceMonitor || {};
window.QuizSnapIntelligentFaceMonitor.config = window.QuizSnapIntelligentFaceMonitor.config || {};
window.QuizSnapIntelligentFaceMonitor.config.violationUrl = "{{ route('student.quiz.violation') }}";
window.QuizSnapIntelligentFaceMonitor.config.violationCaptureUrl = "{{ route('student.quiz.violation.capture') }}";
window.QuizSnapIntelligentFaceMonitor.config.autoSubmitUrl = "{{ route('student.quiz.auto-submit') }}";
window.QuizSnapIntelligentFaceMonitor.config.csrfToken = "{{ csrf_token() }}";
window.QuizSnapIntelligentFaceMonitor.config.sessionId = {{ $session->id ?? 0 }};
window.QuizSnapIntelligentFaceMonitor.config.studentIndex = @json($session->student_index ?? null);
window.QuizSnapIntelligentFaceMonitor.config.studentName = @json($matchedStudentName ?? null);
window.QuizSnapIntelligentFaceMonitor.config.studentNameLinked = {{ ($studentNameLinked ?? false) ? 'true' : 'false' }};

// Configure object monitor
window.QuizSnapObjectMonitor = window.QuizSnapObjectMonitor || {};
window.QuizSnapObjectMonitor.config = window.QuizSnapObjectMonitor.config || {};
window.QuizSnapObjectMonitor.config.violationCaptureUrl = "{{ route('student.quiz.violation.capture') }}";
window.QuizSnapObjectMonitor.config.csrfToken = "{{ csrf_token() }}";
window.QuizSnapObjectMonitor.config.sessionId = {{ $session->id ?? 0 }};

// Configure audio monitor
window.QuizSnapAudioMonitor = window.QuizSnapAudioMonitor || {};
window.QuizSnapAudioMonitor.config = window.QuizSnapAudioMonitor.config || {};
window.QuizSnapAudioMonitor.config.violationCaptureUrl = "{{ route('student.quiz.violation.capture') }}";
window.QuizSnapAudioMonitor.config.csrfToken = "{{ csrf_token() }}";
window.QuizSnapAudioMonitor.config.sessionId = {{ $session->id ?? 0 }};
document.addEventListener('DOMContentLoaded', function() {
    window.QuizSnapQuiz.showRightClickWarning = function() {
        var el = document.getElementById('right-click-warning');
        if (el) { el.classList.remove('hidden'); }
    };
    window.QuizSnapQuiz.showNewTabZoneWarning = function() {
        var el = document.getElementById('new-tab-zone-warning');
        if (el) { el.classList.remove('hidden'); }
    };
    window.QuizSnapQuiz.hideNewTabZoneWarning = function() {
        var el = document.getElementById('new-tab-zone-warning');
        if (el) { el.classList.add('hidden'); }
    };
    window.QuizSnapQuiz.showTabSwitchWarning = function() {
        var el = document.getElementById('tab-switch-once-warning');
        if (el) { el.classList.remove('hidden'); }
    };
    window.QuizSnapQuiz.showResizeFinalWarning = function() {
        var el = document.getElementById('resize-blur-final-warning');
        if (el) { el.classList.remove('hidden'); }
    };

    var totalPages = window.QuizSnapQuiz.totalPages || 1;
    var storageKey = 'quizsnap_quiz_page_{{ $session->id ?? 0 }}';
    var currentPage = 1;
    try {
        var saved = sessionStorage.getItem(storageKey);
        if (saved) { currentPage = Math.max(1, Math.min(totalPages, parseInt(saved, 10))); }
    } catch (e) {}

    function isQuestionAnswered(questionId) {
        var form = document.getElementById('quiz-form');
        if (!form) return false;
        var name = 'q_' + questionId;
        var radio = form.querySelector('input[name="' + name + '"]:checked');
        if (radio) return true;
        var ta = form.querySelector('textarea[name="' + name + '"]');
        if (ta && ta.value && ta.value.trim() !== '') return true;
        return false;
    }

    function updateAnsweredSummary() {
        var total = parseInt(document.getElementById('quiz-answered-summary')?.getAttribute('data-total') || '0', 10);
        var form = document.getElementById('quiz-form');
        if (!form) return;
        var answered = 0;
        var seenNames = {};
        form.querySelectorAll('input[type="radio"]').forEach(function(radio) {
            var name = radio.name;
            if (!seenNames[name]) {
                seenNames[name] = true;
                if (form.querySelector('input[name="' + name + '"]:checked')) answered++;
            }
        });
        form.querySelectorAll('textarea[data-question-id]').forEach(function(ta) {
            if (ta.name && !seenNames[ta.name]) {
                seenNames[ta.name] = true;
                if (ta.value && ta.value.trim() !== '') answered++;
            }
        });
        var el = document.getElementById('quiz-answered-summary');
        if (el) el.textContent = answered + ' of ' + total + ' questions answered.';
        document.querySelectorAll('.quiz-side-num').forEach(function(a) {
            var qid = parseInt(a.getAttribute('data-question-id'), 10);
            if (qid && isQuestionAnswered(qid)) {
                a.classList.add('quiz-side-answered');
                a.setAttribute('aria-label', 'Question ' + a.textContent.trim() + ' answered');
            } else {
                a.classList.remove('quiz-side-answered');
                a.removeAttribute('aria-label');
            }
        });
    }

    function showPage(page) {
        currentPage = Math.max(1, Math.min(totalPages, page));
        try { sessionStorage.setItem(storageKey, String(currentPage)); } catch (e) {}
        var questions = document.querySelectorAll('.quiz-page-question');
        questions.forEach(function(el) {
            var p = parseInt(el.getAttribute('data-page'), 10);
            el.style.display = p === currentPage ? '' : 'none';
        });
        var submitBlock = document.getElementById('quiz-submit-block');
        if (submitBlock) submitBlock.style.display = currentPage === totalPages ? '' : 'none';

        if (currentPage === totalPages) updateAnsweredSummary();

        var infoBottom = document.getElementById('quiz-page-info-bottom');
        if (infoBottom) infoBottom.textContent = 'Page ' + currentPage + ' of ' + totalPages;

        var prevBottom = document.getElementById('quiz-prev-bottom');
        var nextBottom = document.getElementById('quiz-next-bottom');
        if (prevBottom) prevBottom.disabled = currentPage <= 1;
        if (nextBottom) nextBottom.disabled = currentPage >= totalPages;

        document.querySelectorAll('.quiz-side-num').forEach(function(a) {
            var p = parseInt(a.getAttribute('data-page'), 10);
            a.classList.toggle('border-primary-500', p === currentPage);
            a.classList.toggle('bg-primary-50', p === currentPage);
            a.classList.toggle('text-primary-700', p === currentPage);
        });
    }

    var form = document.getElementById('quiz-form');
    if (form) {
        form.addEventListener('change', updateAnsweredSummary);
        form.addEventListener('input', updateAnsweredSummary);
    }
    if (totalPages > 1) {
        showPage(currentPage);
        var prevBtn = document.getElementById('quiz-prev-bottom');
        var nextBtn = document.getElementById('quiz-next-bottom');
        if (prevBtn) prevBtn.addEventListener('click', function() { showPage(currentPage - 1); });
        if (nextBtn) nextBtn.addEventListener('click', function() { showPage(currentPage + 1); });
        document.querySelectorAll('.quiz-side-num').forEach(function(a) {
            a.addEventListener('click', function(e) {
                var p = parseInt(a.getAttribute('data-page'), 10);
                if (p) { e.preventDefault(); showPage(p); }
            });
        });
    } else {
        var submitBlock = document.getElementById('quiz-submit-block');
        if (submitBlock) submitBlock.style.display = '';
    }
    updateAnsweredSummary();
});
</script>
@endpush
@endsection
