@extends('layouts.student-dashboard')

@section('title', 'Dashboard')
@php $dashboardTitle = 'Dashboard'; @endphp

@section('dashboard_content')
{{-- Page header --}}
<header class="mb-5 sm:mb-6">
    <h1 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">{{ $greeting ?? 'Hello' }}, {{ $displayName ?? $student?->first_name ?? 'User' }}</h1>
    <p class="text-sm text-slate-600 mt-1">{{ ($hasQuizAccess ?? true) ? 'Your quiz history and quick actions.' : 'Manage your projects and proposals.' }}</p>
</header>

{{-- High-level navigation chips for key dashboard sections --}}
<nav class="mb-4 sm:mb-5 -mx-1 overflow-x-auto student-chip-scroll" aria-label="Dashboard sections">
    <div class="flex items-center gap-2 px-1 pb-0.5">
        <a href="{{ route('dashboard') }}"
           class="inline-flex items-center px-3 py-1.5 rounded-full text-xs sm:text-sm font-medium border transition-colors whitespace-nowrap
                  {{ request()->routeIs('dashboard') ? 'bg-amber-100 border-amber-300 text-slate-900' : 'bg-white border-slate-200 text-slate-900 hover:bg-slate-50' }}">
            <i class="fas fa-home mr-1.5 text-[10px] sm:text-xs text-slate-900"></i>
            Overview
        </a>

        @if($hasQuizAccess ?? true)
        <a href="{{ route('dashboard.my-quizzes') }}"
           class="inline-flex items-center px-3 py-1.5 rounded-full text-xs sm:text-sm font-medium border transition-colors whitespace-nowrap
                  {{ request()->routeIs('dashboard.my-quizzes*') ? 'bg-amber-100 border-amber-300 text-slate-900' : 'bg-white border-slate-200 text-slate-900 hover:bg-slate-50' }}">
            <i class="fas fa-clipboard-list mr-1.5 text-[10px] sm:text-xs text-slate-900"></i>
            Quizzes
        </a>
        <a href="{{ route('dashboard.calendar') }}"
           class="inline-flex items-center px-3 py-1.5 rounded-full text-xs sm:text-sm font-medium border transition-colors whitespace-nowrap
                  {{ request()->routeIs('dashboard.calendar') ? 'bg-amber-100 border-amber-300 text-slate-900' : 'bg-white border-slate-200 text-slate-900 hover:bg-slate-50' }}">
            <i class="fas fa-calendar-alt mr-1.5 text-[10px] sm:text-xs text-slate-900"></i>
            Calendar
        </a>
        <a href="{{ route('dashboard.course-materials') }}"
           class="inline-flex items-center px-3 py-1.5 rounded-full text-xs sm:text-sm font-medium border transition-colors whitespace-nowrap
                  {{ request()->routeIs('dashboard.course-materials') ? 'bg-amber-100 border-amber-300 text-slate-900' : 'bg-white border-slate-200 text-slate-900 hover:bg-slate-50' }}">
            <i class="fas fa-book mr-1.5 text-[10px] sm:text-xs text-slate-900"></i>
            Materials
        </a>
        @endif

        @if($hasProjectAccess ?? false)
            @if(isset($student) && $student)
            <a href="{{ route('student.enter-documentor', ['redirect' => 'dashboard.projects.index']) }}"
               class="inline-flex items-center px-3 py-1.5 rounded-full text-xs sm:text-sm font-medium border transition-colors whitespace-nowrap
                      {{ request()->routeIs('dashboard.projects*') || request()->routeIs('dashboard.public-projects') || request()->routeIs('dashboard.group*') ? 'bg-amber-100 border-amber-300 text-slate-900' : 'bg-white border-slate-200 text-slate-900 hover:bg-slate-50' }}">
                <i class="fas fa-folder-open mr-1.5 text-[10px] sm:text-xs text-slate-900"></i>
                Projects
            </a>
            @else
            <a href="{{ route('dashboard.projects.index') }}"
               class="inline-flex items-center px-3 py-1.5 rounded-full text-xs sm:text-sm font-medium border transition-colors whitespace-nowrap
                      {{ request()->routeIs('dashboard.projects*') || request()->routeIs('dashboard.public-projects') || request()->routeIs('dashboard.group*') ? 'bg-amber-100 border-amber-300 text-slate-900' : 'bg-white border-slate-200 text-slate-900 hover:bg-slate-50' }}">
                <i class="fas fa-folder-open mr-1.5 text-[10px] sm:text-xs text-slate-900"></i>
                Projects
            </a>
            @endif
        @endif

        <a href="{{ route('dashboard.my-profile') }}"
           class="inline-flex items-center px-3 py-1.5 rounded-full text-xs sm:text-sm font-medium border transition-colors whitespace-nowrap
                  {{ request()->routeIs('dashboard.my-profile') ? 'bg-amber-100 border-amber-300 text-slate-900' : 'bg-white border-slate-200 text-slate-900 hover:bg-slate-50' }}">
            <i class="fas fa-user mr-1.5 text-[10px] sm:text-xs text-slate-900"></i>
            Profile
        </a>

        @if($isClassRep ?? false)
        <a href="{{ route('dashboard.class-results.index') }}"
           class="inline-flex items-center px-3 py-1.5 rounded-full text-xs sm:text-sm font-medium border transition-colors whitespace-nowrap
                  {{ request()->routeIs('dashboard.class-results*') ? 'bg-amber-100 border-amber-300 text-slate-900' : 'bg-white border-slate-200 text-slate-900 hover:bg-slate-50' }}">
            <i class="fas fa-file-download mr-1.5 text-[10px] sm:text-xs text-slate-900"></i>
            Class results
        </a>
        @endif
    </div>
</nav>

{{-- At a glance: first 3 cards with solid colors; stacked on mobile for app-like feel --}}
<section class="mb-6 sm:mb-9" aria-label="At a glance">
    <h2 class="text-xs sm:text-sm font-semibold text-slate-900 mb-3 sm:mb-4 uppercase tracking-wide">At a glance</h2>
    {{-- On mobile show 2 cards per row; on larger screens expand to 4 --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
        @if($student && ($hasQuizAccess ?? true))
        <a href="{{ route('dashboard.my-quizzes') }}" class="rounded-2xl shadow-sm p-4 sm:p-5 flex flex-col no-underline hover:bg-blue-50/80 transition-colors min-h-[84px] sm:min-h-[100px]" style="background-color: #dbeafe; border: none;">
            <span class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center text-sm sm:text-base shrink-0" style="background-color: #bfdbfe; color: #1d4ed8;"><i class="fas fa-clipboard-list"></i></span>
            <span class="text-lg sm:text-xl font-bold tabular-nums mt-1.5 sm:mt-2 truncate text-slate-900">{{ $sessionsCount ?? 0 }}</span>
            <span class="text-[10px] sm:text-xs font-semibold uppercase tracking-wide mt-0.5 truncate text-slate-700">Quizzes taken</span>
        </a>
        @endif
        @if($hasQuizAccess ?? true)
        @php
            $hasScheduled = isset($scheduledQuiz) && $scheduledQuiz;
            $hasScheduledResult = isset($scheduledQuizSession) && $scheduledQuizSession?->result;
            $scheduledUpcoming = $hasScheduled && $scheduledQuiz->starts_at && $scheduledQuiz->starts_at->isFuture();
            $scheduledActive = $hasScheduled && !$hasScheduledResult && !$scheduledUpcoming;
        @endphp
        <div class="rounded-2xl shadow-sm p-4 sm:p-5 flex flex-col min-h-[84px] sm:min-h-[100px] relative overflow-hidden" style="background-color: #d1fae5; border: none;">
            <a href="@if($hasScheduled && $hasScheduledResult)
                      {{ route('dashboard.my-quizzes.show', ['sessionId' => $scheduledQuizSession->id]) }}
                  @elseif($scheduledUpcoming)
                      {{ route('student.quiz-will-start', ['token' => $scheduledQuiz->link_token]) }}
                  @elseif($hasScheduled)
                      {{ route('student.rules.show.quiz', ['token' => $scheduledQuiz->link_token]) }}
                  @else
                      {{ route('dashboard.course-materials') }}
                  @endif"
               @if($scheduledUpcoming) data-rules-url="{{ route('student.rules.show.quiz', ['token' => $scheduledQuiz->link_token]) }}" @endif
               class="flex flex-col flex-1 no-underline text-inherit hover:opacity-90 transition-opacity min-w-0">
                <span class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center text-sm sm:text-base shrink-0" style="background-color: #a7f3d0; color: #047857;"><i class="fas fa-book"></i></span>
                <span class="text-sm font-bold mt-1.5 sm:mt-2 truncate text-slate-900">
                    @if(isset($scheduledQuiz) && $scheduledQuiz)
                        {{ $scheduledQuiz->title }}
                    @else
                        View
                    @endif
                </span>
                <span class="text-[10px] sm:text-xs font-semibold uppercase tracking-wide mt-0.5 truncate text-slate-700">
                    @if(isset($scheduledQuizSession) && $scheduledQuizSession?->result)
                        Score: {{ number_format($scheduledQuizSession->result->score, 1) }}%
                    @elseif($scheduledUpcoming)
                        <span id="quiz-countdown-{{ $scheduledQuiz->id }}" aria-live="polite">—</span>
                    @elseif($scheduledActive)
                        Ready to take
                    @else
                        Course materials
                    @endif
                </span>
            </a>
            @if($scheduledActive && $scheduledQuiz)
            <a href="{{ route('student.rules.show.quiz', ['token' => $scheduledQuiz->link_token]) }}" class="mt-2 self-start inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wide text-amber-900 bg-amber-400 hover:bg-amber-500 border border-amber-500/50 shadow-sm transition-colors">Start</a>
            @endif
        </div>
        @endif
        <a href="{{ route('dashboard.my-profile') }}" class="rounded-2xl shadow-sm p-4 sm:p-5 flex flex-col no-underline hover:bg-amber-50/80 transition-colors min-h-[84px] sm:min-h-[100px]" style="background-color: #fef3c7; border: none;">
            <span class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center text-sm sm:text-base shrink-0" style="background-color: #fde68a; color: #b45309;"><i class="fas fa-user"></i></span>
            <span class="text-sm font-bold mt-1.5 sm:mt-2 truncate text-slate-900">View</span>
            <span class="text-[10px] sm:text-xs font-semibold uppercase tracking-wide mt-0.5 truncate text-slate-700">Profile</span>
        </a>
        @if($hasProjectAccess ?? false)
            @if($isClassRep ?? false)
            <a href="{{ route('dashboard.class-results.index') }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5 flex flex-col no-underline text-inherit hover:bg-slate-50 hover:border-slate-300 active:bg-slate-100 transition-colors min-h-[84px] sm:min-h-[100px]">
                <span class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600 text-sm sm:text-base shrink-0"><i class="fas fa-file-download"></i></span>
                <span class="text-sm font-semibold text-slate-800 mt-1.5 sm:mt-2 truncate">View</span>
                <span class="text-[10px] sm:text-xs font-medium text-slate-600 uppercase tracking-wide mt-0.5 truncate">Class results</span>
            </a>
            @else
            <a href="{{ route('student.enter-documentor', ['redirect' => 'dashboard.projects.index']) }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5 flex flex-col no-underline text-inherit hover:bg-slate-50 hover:border-slate-300 active:bg-slate-100 transition-colors min-h-[84px] sm:min-h-[100px]">
                <span class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600 text-sm sm:text-base shrink-0"><i class="fas fa-folder-open"></i></span>
                <span class="text-sm font-semibold text-slate-800 mt-1.5 sm:mt-2 truncate">View</span>
                <span class="text-[10px] sm:text-xs font-medium text-slate-600 uppercase tracking-wide mt-0.5 truncate">Projects</span>
            </a>
            @endif
        @endif
    </div>
</section>

{{-- Quick access: full-width cards on mobile (app-like), 3 per row on desktop --}}
<section class="mb-5 sm:mb-8" aria-label="Quick access">
    <h2 class="text-xs sm:text-sm font-semibold text-slate-900 mb-2.5 sm:mb-3 uppercase tracking-wide">Quick access</h2>
    {{-- Two cards per row on mobile, three on larger screens --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 sm:gap-3">
        {{-- Calendar is available to all students with dashboard access --}}
        <a href="{{ route('dashboard.calendar') }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-3.5 sm:p-4 flex items-center justify-between no-underline hover:bg-slate-50 hover:border-slate-300 active:bg-slate-100 transition-colors min-h-[56px] sm:min-h-[72px] overflow-hidden">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700 shrink-0">
                    <i class="fas fa-calendar-alt text-sm"></i>
                </span>
                <div class="min-w-0">
                    <span class="text-sm font-semibold text-slate-900 block truncate">Calendar</span>
                    <span class="text-xs text-slate-600 block truncate">Exam & quiz dates</span>
                </div>
            </div>
            <i class="fas fa-chevron-right text-slate-400 text-xs shrink-0 hidden sm:inline-block"></i>
        </a>
        {{-- Class results quick access for class reps (any level) --}}
        @if($isClassRep ?? false)
        <a href="{{ route('dashboard.class-results.index') }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-3.5 sm:p-4 flex items-center justify-between no-underline hover:bg-slate-50 hover:border-slate-300 active:bg-slate-100 transition-colors min-h-[56px] sm:min-h-[72px] overflow-hidden">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700 shrink-0"><i class="fas fa-file-download text-sm"></i></span>
                <div class="min-w-0">
                    <span class="text-sm font-semibold text-slate-900 block truncate">Class results</span>
                    <span class="text-xs text-slate-600 block truncate">See class results</span>
                </div>
            </div>
            <i class="fas fa-chevron-right text-slate-400 text-xs shrink-0"></i>
        </a>
        @endif
        @if($hasProjectAccess ?? false)
        <a href="{{ route('student.enter-documentor', ['redirect' => 'dashboard.projects.index']) }}" class="hidden sm:flex bg-white rounded-2xl border border-slate-200 shadow-sm p-3.5 sm:p-4 items-center justify-between no-underline hover:bg-slate-50 hover:border-slate-300 active:bg-slate-100 transition-colors min-h-[56px] sm:min-h-[72px] overflow-hidden">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700 shrink-0"><i class="fas fa-folder-open text-sm"></i></span>
                <div class="min-w-0">
                    <span class="text-sm font-semibold text-slate-900 block truncate">My projects</span>
                    <span class="text-xs text-slate-600 block truncate">View projects</span>
                </div>
            </div>
            <i class="fas fa-chevron-right text-slate-400 text-xs shrink-0 hidden sm:inline-block"></i>
        </a>
        @if(!($docuMentorOnlyDashboard ?? false))
        <a href="{{ route('student.enter-documentor', ['redirect' => 'dashboard.public-projects']) }}" class="hidden sm:flex bg-white rounded-2xl border border-slate-200 shadow-sm p-3.5 sm:p-4 items-center justify-between no-underline hover:bg-slate-50 hover:border-slate-300 active:bg-slate-100 transition-colors min-h-[56px] sm:min-h-[72px] overflow-hidden">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700 shrink-0"><i class="fas fa-search text-sm"></i></span>
                <div class="min-w-0">
                    <span class="text-sm font-semibold text-slate-900 block truncate">Public projects</span>
                    <span class="text-xs text-slate-600 block truncate">Browse</span>
                </div>
            </div>
            <i class="fas fa-chevron-right text-slate-400 text-xs shrink-0 hidden sm:inline-block"></i>
        </a>
        @endif
        @if($docuMentorGroup ?? null)
        <a href="{{ isset($student) && $student ? route('student.enter-documentor', ['redirect' => 'dashboard.group.show', 'group' => $docuMentorGroup->id]) : route('dashboard.group.show', ['group' => $docuMentorGroup->id]) }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-3.5 sm:p-4 flex items-center justify-between no-underline hover:bg-slate-50 hover:border-slate-300 active:bg-slate-100 transition-colors min-h-[56px] sm:min-h-[72px] overflow-hidden">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700 shrink-0"><i class="fas fa-users text-sm"></i></span>
                <div class="min-w-0">
                    <span class="text-sm font-semibold text-slate-900 block truncate">My group</span>
                    <span class="text-xs text-slate-600 block truncate">{{ $docuMentorGroup->name }}</span>
                </div>
            </div>
            <i class="fas fa-chevron-right text-slate-400 text-xs shrink-0 hidden sm:inline-block"></i>
        </a>
        @endif
        @if($leaderWithoutGroup ?? false)
        <a href="{{ route('student.enter-documentor', ['redirect' => 'dashboard.group.create']) }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-3.5 sm:p-4 flex items-center justify-between no-underline hover:bg-slate-50 hover:border-slate-300 active:bg-slate-100 transition-colors min-h-[56px] sm:min-h-[72px] overflow-hidden">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700 shrink-0"><i class="fas fa-users text-sm"></i></span>
                <div class="min-w-0">
                    <span class="text-sm font-semibold text-slate-900 block truncate">Create group</span>
                    <span class="text-xs text-slate-600 block truncate">Start a group</span>
                </div>
            </div>
            <i class="fas fa-chevron-right text-slate-400 text-xs shrink-0 hidden sm:inline-block"></i>
        </a>
        @endif
        @if(($isGroupLeader ?? false) && !($leaderWithoutGroup ?? false))
        @php $hasCreatedProject = $leaderHasProject ?? false; @endphp
        @if($hasCreatedProject)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-3.5 sm:p-4 flex items-center justify-between no-underline min-h-[56px] sm:min-h-[72px] overflow-hidden opacity-60 cursor-not-allowed" aria-disabled="true">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 shrink-0"><i class="fas fa-lock text-sm"></i></span>
                <div class="min-w-0">
                    <span class="text-sm font-semibold text-slate-900 block truncate">Project created</span>
                    <span class="text-xs text-slate-600 block truncate">You already registered a project</span>
                </div>
            </div>
            <i class="fas fa-chevron-right text-slate-300 text-xs shrink-0"></i>
        </div>
        @else
        <a href="{{ route('student.enter-documentor', ['redirect' => 'dashboard.projects.create']) }}"
           class="bg-white rounded-2xl border border-slate-200 shadow-sm p-3.5 sm:p-4 flex items-center justify-between no-underline hover:bg-slate-50 hover:border-slate-300 active:bg-slate-100 transition-colors min-h-[56px] sm:min-h-[72px] overflow-hidden">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700 shrink-0"><i class="fas fa-plus-circle text-sm"></i></span>
                <div class="min-w-0">
                    <span class="text-sm font-semibold text-slate-900 block truncate">Create project</span>
                    <span class="text-xs text-slate-600 block truncate">Register project</span>
                </div>
            </div>
            <i class="fas fa-chevron-right text-slate-400 text-xs shrink-0 hidden sm:inline-block"></i>
        </a>
        @endif
        @endif
        @endif
    </div>
</section>

{{-- Scheduled quiz is surfaced via the second At a glance card (replacing generic course materials text) --}}

@if($hasQuizAccess ?? true)
{{-- Enter quiz token modal --}}
@push('scripts')
@if(isset($scheduledQuiz) && $scheduledQuiz && $scheduledQuiz->starts_at && $scheduledQuiz->starts_at->isFuture())
<script>
(function() {
    var startsAt = @json($scheduledQuiz->starts_at->toIso8601String());
    var startMs = new Date(startsAt).getTime();
    var el = document.getElementById('quiz-countdown-{{ $scheduledQuiz->id }}');
    if (!el) return;
    var cardLink = el.closest('a');
    var rulesUrl = cardLink && cardLink.getAttribute('data-rules-url');
    function update() {
        var now = Date.now();
        var left = Math.max(0, Math.floor((startMs - now) / 1000));
        if (left <= 0) {
            el.textContent = 'Start';
            if (cardLink && rulesUrl) cardLink.href = rulesUrl;
            return;
        }
        var h = Math.floor(left / 3600), m = Math.floor((left % 3600) / 60), s = left % 60;
        el.textContent = (h > 0 ? h + ':' : '') + (m < 10 && h > 0 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
    }
    update();
    setInterval(update, 1000);
})();
</script>
@endif
@endpush
@endif
@endsection
