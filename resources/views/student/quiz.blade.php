@extends('layouts.student')

@section('title', 'Quiz - ' . $session->quiz->title)
@section('body_class', 'bg-offwhite')

@push('styles')
<style>
.quiz-timer-green{color:#059669}.quiz-timer-blue{color:#2563eb}.quiz-timer-red{color:#dc2626}.quiz-side-num.quiz-side-answered{border-color:#22c55e;background-color:#f0fdf4;color:#15803d}
/* AI invigilator badge (top panel only) when camera is active */
#ai-invigilator-badge-panel.visible{display:flex!important}
#ai-invigilator-badge-panel .pulse-dot{width:6px;height:6px;background:#22c55e;border-radius:50%;animation:ai-invigilator-pulse 1.5s ease-in-out infinite}
@keyframes ai-invigilator-pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:0.5;transform:scale(1.2)}}

/* Fixed left panel - camera, timer, questions (hide vertical scrollbar); narrower for live feed */
.quiz-left-panel {
    position: fixed;
    top: 4rem;
    left: 1rem;
    width: 260px;
    max-height: calc(100vh - 5rem);
    overflow-y: auto;
    overflow-x: hidden;
    z-index: 40;
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.quiz-left-panel::-webkit-scrollbar { display: none; }

/* Main content area - full width when panel hidden; offset when panel visible (lg) */
.quiz-main-content {
    margin-left: 0;
    width: 100%;
    padding: 1rem 1rem 2rem 1rem;
}
@media (min-width: 1024px) {
    .quiz-main-content { margin-left: 280px; width: calc(100% - 300px); padding: 1rem 1.5rem 2rem 0; }
}

/* Live camera frame border states (synced with intelligentFaceMonitor) */
#live-camera-frame.border-emerald-500 { border-color: #22c55e; }
#live-camera-frame.border-amber-400 { border-color: #eab308; animation: quiz-frame-pulse-warn 1s infinite; }
#live-camera-frame.border-red-500 { border-color: #ef4444; animation: quiz-frame-pulse-critical 1s infinite; }
@keyframes quiz-frame-pulse-warn { 0%,100%{border-color:#eab308} 50%{border-color:#fbbf24} }
@keyframes quiz-frame-pulse-critical { 0%,100%{border-color:#ef4444} 50%{border-color:#dc2626} }

/* Violation number in live feed banner */
.quiz-violation-num { padding: 0.25rem 0.5rem; border-radius: 0.375rem; color: white; font-size: 0.875rem; }
.quiz-violation-num.warn-1 { background: #eab308; color: black; }
.quiz-violation-num.warn-2 { background: #f97316; color: white; }
.quiz-violation-num.warn-3 { background: #ef4444; color: white; }
.quiz-violation-num.warn-4 { background: #dc2626; color: white; }
</style>
@endpush

@section('content')
@php
    $quizDesktopOnly = ($allowedDevices ?? 'desktop') === 'desktop';
@endphp
<div class="min-h-screen min-w-0 w-full">
    {{-- Show "use desktop" only when this quiz/group is desktop-only. When quiz allows mobile/both, do not show so mobile users see content. --}}
    @if($quizDesktopOnly)
    <div class="quiz-desktop-only-notice block md:hidden min-h-screen flex flex-col items-center justify-center px-4 py-8 bg-gray-50">
        <div class="w-full max-w-md bg-white border border-[#e5e5e5] rounded-xl p-6 text-center" style="box-shadow: none;">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-lg bg-gray-100 text-gray-500 mb-4">
                <i class="fas fa-desktop text-xl" aria-hidden="true"></i>
            </div>
            <h2 class="text-lg font-medium text-gray-800 mb-2">Desktop only</h2>
            <p class="text-sm text-gray-600">This quiz is designed for a desktop or laptop. Please use a computer to take it.</p>
        </div>
    </div>
    @endif

    <div class="quiz-writing-content {{ $quizDesktopOnly ? 'hidden md:block' : 'block' }} min-h-screen min-w-0 w-full">
        {{-- Fixed header --}}
        <header class="fixed top-0 left-0 right-0 z-50 bg-white border-b border-gray-200 h-16 flex items-center">
            <div class="w-full px-4 sm:px-6">
                <h1 class="text-base font-semibold text-gray-800 truncate">{{ $session->quiz->title }}</h1>
            </div>
        </header>

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

    {{-- Camera off overlay: block quiz until user clicks and allows camera (prompt shown on click) --}}
    <div id="camera-off-overlay" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-gray-900 px-4 pointer-events-auto" aria-hidden="true">
        <div class="bg-white border border-gray-200 rounded-lg p-4 max-w-md w-full border border-gray-200 text-center">
            <h4 class="font-semibold text-gray-800 mb-2">Camera is required</h4>
            <p class="text-sm text-gray-600 mb-4">Your camera must stay on throughout the quiz. Click the button below — your browser will ask for camera permission.</p>
            <button type="button" id="camera-off-allow-btn" class="py-2.5 px-5 text-sm font-semibold rounded-lg border-2 border-sky-400 bg-sky-50 text-sky-800 hover:bg-sky-100 focus:ring-2 focus:ring-sky-500 focus:ring-offset-1 transition-colors">Allow camera &amp; continue</button>
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

        {{-- Fixed left panel: AI invigilator label first, then camera, then timer, then questions --}}
        <aside class="quiz-left-panel hidden lg:flex lg:flex-col lg:gap-4" aria-label="Quiz sidebar">
            {{-- AI invigilator watching (before time / live coverage on lg); visibility toggled with camera --}}
            <div id="ai-invigilator-badge-panel" class="hidden items-center justify-center gap-2 py-2 px-3 rounded-xl bg-gradient-to-br from-slate-800 to-slate-900 text-slate-200 text-xs font-bold uppercase tracking-wider shadow-sm" aria-hidden="true">
                <span class="pulse-dot w-2 h-2 rounded-full bg-emerald-500 animate-pulse" aria-hidden="true"></span>
                <span>AI Invigilator Watching</span>
            </div>
            {{-- Small mic/speaker hint: examiner can speak to students; allow audio to hear --}}
            <div id="examiner-voice-hint" class="hidden lg:flex items-center gap-1.5 py-1.5 px-2 rounded-lg bg-slate-100 text-slate-600 text-[10px] font-medium" aria-hidden="true">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
                <span>Allow audio to hear examiner</span>
            </div>
            <div class="rounded-xl overflow-hidden shadow-sm bg-amber-50 border border-amber-200 min-w-0">
                <div class="p-3">
                    <h2 class="text-xs font-semibold text-amber-900 mb-2">LIVE CAMERA FEED</h2>
                    <div id="live-camera-frame" class="bg-amber-100 border-2 border-emerald-500 rounded-xl overflow-hidden min-w-0 flex flex-col transition-all duration-200 relative">
                        <div id="live-camera-video-slot" class="aspect-video bg-gray-900 rounded-t-lg flex items-center justify-center min-h-[100px] relative overflow-hidden">
                            <span class="text-gray-500 text-xs">Camera feed</span>
                            {{-- Guide: oval/circle (reacts green/yellow/red) + center lines + face dot; preserved when video is injected --}}
                            <div id="live-camera-guide-overlay" class="absolute inset-0 pointer-events-none flex items-center justify-center z-10" aria-hidden="true">
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div id="live-camera-guide-circle" class="w-[55%] min-w-[80px] max-w-[180px] aspect-square rounded-full border-2 border-dashed border-emerald-500 transition-colors duration-300 bg-transparent" style="box-shadow: 0 0 0 1px rgba(0,0,0,0.15);" title="Keep your head inside this circle"></div>
                                </div>
                                <div class="absolute top-0 bottom-0 left-1/2 w-0.5 -translate-x-px bg-emerald-400/60 transition-colors duration-300 guide-line-v" style="width: 2px;"></div>
                                <div class="absolute left-0 right-0 top-1/2 h-0.5 -translate-y-px bg-emerald-400/60 transition-colors duration-300 guide-line-h" style="height: 2px;"></div>
                                <div id="live-camera-face-dot" class="absolute w-2.5 h-2.5 rounded-full bg-emerald-500 border-2 border-white shadow z-10 hidden" style="left:50%;top:50%;transform:translate(-50%,-50%);"></div>
                            </div>
                        </div>
                        {{-- Face detected pill: inside frame, responsive, never clipped --}}
                        <div id="live-camera-pill" class="absolute top-1.5 left-1/2 -translate-x-1/2 z-20 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase text-white bg-emerald-500 shadow-sm hidden">Face detected</div>
                        {{-- Bottom banner: status text (face detected / good position) --}}
                        <div id="live-camera-banner" class="rounded-b-xl px-2 py-1.5 z-10 flex items-center gap-2 min-h-[36px]" style="background: rgba(251,191,36,0.95);">
                            <span id="live-camera-banner-icon" class="flex-shrink-0 w-4 h-4 rounded bg-emerald-500 flex items-center justify-center text-white text-[10px]">✓</span>
                            <p id="live-camera-status-text" class="text-xs font-medium text-amber-900 truncate">Monitoring camera feed.</p>
                        </div>
                    </div>
                    {{-- X, Y, Size bars (dark gray card) --}}
                    <div id="live-camera-bars" class="mt-3 p-3 rounded-xl space-y-2" style="background: #374151;">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium text-white w-6">X</span>
                            <div class="flex-1 h-2 bg-gray-600 rounded-full overflow-hidden"><div id="live-bar-x" class="h-full bg-emerald-500 rounded-full transition-all duration-200" style="width: 50%;"></div></div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium text-white w-6">Y</span>
                            <div class="flex-1 h-2 bg-gray-600 rounded-full overflow-hidden"><div id="live-bar-y" class="h-full bg-emerald-500 rounded-full transition-all duration-200" style="width: 50%;"></div></div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium text-white w-6">Size</span>
                            <div class="flex-1 h-2 bg-gray-600 rounded-full overflow-hidden"><div id="live-bar-size" class="h-full bg-emerald-500 rounded-full transition-all duration-200" style="width: 50%;"></div></div>
                        </div>
                    </div>
                    {{-- Violations / errors list under live camera --}}
                    <div id="live-camera-violations-wrap" class="mt-3 rounded-xl overflow-hidden border border-gray-300 bg-gray-50 max-h-24 overflow-y-auto">
                        <p class="text-xs font-semibold text-gray-600 px-3 py-1.5 border-b border-gray-200 bg-gray-100">Violations &amp; alerts</p>
                        <ul id="live-camera-violations-list" class="text-xs text-gray-700 px-3 py-2 space-y-0.5 list-none"></ul>
                    </div>
                </div>
            </div>
            @if($remainingSeconds > 0)
            <div id="quiz-timer-card" class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                <p class="text-xs text-gray-500 mb-1 text-center font-semibold tracking-wider">TIME REMAINING</p>
                <p id="quiz-timer" class="text-2xl font-bold tabular-nums quiz-timer quiz-timer-green text-center" aria-live="polite">--:--</p>
            </div>
            @endif
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <p class="text-xs text-gray-500 mb-2 font-semibold tracking-wider">QUESTIONS</p>
                <div id="quiz-side-nav" class="flex flex-wrap gap-1">
                    @foreach($questions as $idx => $q)
                        @php $qPage = (int) floor($idx / $perPage) + 1; @endphp
                        <a href="#quiz-container" class="quiz-side-num w-8 h-8 rounded border border-gray-200 flex items-center justify-center text-sm font-medium text-gray-600 hover:border-primary-300 hover:bg-primary-50 shrink-0" data-question-id="{{ $q->id }}" data-page="{{ $qPage }}">{{ $idx + 1 }}</a>
                    @endforeach
                </div>
            </div>
        </aside>

        {{-- Main content area: questions + pagination + submit --}}
        <div class="quiz-main-content">
            <div id="quiz-container" class="bg-white rounded-xl shadow-lg border border-gray-200 min-w-0 p-6">

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
                            class="btn btn-action w-full sm:w-auto py-2.5 px-5 text-sm font-semibold bg-red-600 text-white hover:bg-red-700 border-0"
                            id="post-face-btn"
                            data-final-url="{{ route('student.final-photo.capture') }}"
                        >
                            Finish quiz
                        </button>
                    </div>
                </form>

                @if($totalPages > 1)
                <div id="quiz-pagination-bottom" class="flex items-center justify-between gap-4 mt-8 mb-2 py-5 flex-wrap">
                    <button type="button" id="quiz-prev-bottom" class="btn btn-secondary py-2.5 px-5 text-sm bg-slate-200 hover:bg-slate-300 text-slate-800 border border-slate-300 disabled:opacity-50 disabled:cursor-not-allowed" disabled>Previous</button>
                    <span id="quiz-page-info-bottom" class="text-sm font-medium text-gray-700">Page 1 of {{ $totalPages }}</span>
                    <button type="button" id="quiz-next-bottom" class="btn btn-secondary py-2.5 px-5 text-sm bg-slate-200 hover:bg-slate-300 text-slate-800 border border-slate-300 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
                </div>
                @endif
            </div>
        </div>
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
window.QuizSnapIntelligentFaceMonitor.config.initialOutOfFrameCount = {{ (int) ($outOfFrameCount ?? 0) }};
window.QuizSnapIntelligentFaceMonitor.config.initialNormalViolationCount = {{ (int) ($normalViolationCount ?? 0) }};
window.QuizSnapIntelligentFaceMonitor.config.initialHeadTurnCount = {{ (int) (($headTurnCount ?? 0)) }};
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
            el.style.display = p === currentPage ? 'block' : 'none';
        });
        var submitBlock = document.getElementById('quiz-submit-block');
        if (submitBlock) submitBlock.style.display = currentPage === totalPages ? 'block' : 'none';

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
    showPage(currentPage);
    if (totalPages > 1) {
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
    }
    updateAnsweredSummary();

    // Sync violation counter (out-of-frame events 0/5) from intelligent face monitor
    function updateViolationCounter() {
        var el = document.getElementById('quiz-violation-number');
        if (!el) return;
        var mon = window.QuizSnapIntelligentFaceMonitor;
        var count = (mon && typeof mon.getValidOutOfFrameEvents === 'function') ? mon.getValidOutOfFrameEvents() : 0;
        el.textContent = count + '/10';
        el.className = 'quiz-violation-num';
        if (count >= 9) el.classList.add('warn-4');
        else if (count >= 7) el.classList.add('warn-3');
        else if (count >= 5) el.classList.add('warn-2');
        else if (count >= 1) el.classList.add('warn-1');
    }
    setInterval(updateViolationCounter, 500);

    // Examiner voice: subscribe to live-proctor-voice.{sessionId} and play received audio
    (function() {
        var sessionId = window.QuizSnapQuiz && window.QuizSnapQuiz.sessionId;
        if (!sessionId) return;
        var c = window.REVERB_CONFIG;
        if (!c || !c.key) return;
        function init() {
            if (typeof Pusher === 'undefined') return;
            try {
                var pusher = new Pusher(c.key, {
                    wsHost: c.host,
                    wsPort: parseInt(c.port, 10) || 8080,
                    wssPort: 443,
                    forceTLS: (c.scheme || 'http') === 'https',
                    disableStats: true,
                    enabledTransports: ['ws', 'wss'],
                    cluster: 'mt1'
                });
                var channel = pusher.subscribe('live-proctor-voice.' + sessionId);
                var audioQueue = [];
                var playing = false;
                function playNext() {
                    if (playing || audioQueue.length === 0) return;
                    var chunk = audioQueue.shift();
                    if (!chunk) { playing = false; return; }
                    try {
                        var binary = atob(chunk);
                        var bytes = new Uint8Array(binary.length);
                        for (var i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
                        var blob = new Blob([bytes], { type: 'audio/webm' });
                        var url = URL.createObjectURL(blob);
                        var audio = new Audio();
                        playing = true;
                        audio.addEventListener('ended', function() {
                            URL.revokeObjectURL(url);
                            playing = false;
                            playNext();
                        });
                        audio.addEventListener('error', function() {
                            URL.revokeObjectURL(url);
                            playing = false;
                            playNext();
                        });
                        audio.src = url;
                        audio.play().catch(function() {
                            URL.revokeObjectURL(url);
                            playing = false;
                            playNext();
                        });
                    } catch (e) {
                        playing = false;
                        playNext();
                    }
                }
                channel.bind('ExaminerVoice', function(data) {
                    if (data && data.chunk) {
                        audioQueue.push(data.chunk);
                        playNext();
                    }
                });
            } catch (e) { console.warn('Examiner voice:', e); }
        }
        if (typeof Pusher !== 'undefined') init(); else window.addEventListener('load', init);
    })();
});
</script>
@endpush
@endsection
