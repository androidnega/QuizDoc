@extends('layouts.student-dashboard')

@section('title', 'Exam Calendar')
@php $dashboardTitle = 'Exam Calendar'; @endphp

@section('dashboard_content')
<header class="mb-5 sm:mb-6">
    <h1 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">Exam calendar</h1>
    <p class="text-sm text-slate-600 mt-1">Midsem and end-of-semester exams for your class. Times shown in your local time.</p>
</header>

@if($examCalendarEntries->isEmpty())
    <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center">
        <span class="flex justify-center w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 mx-auto mb-4"><i class="fas fa-calendar-alt text-xl self-center"></i></span>
        <p class="text-slate-600 font-medium">No exams scheduled</p>
        <p class="text-sm text-slate-500 mt-1">When your coordinator adds midsem or end-of-semester exams for your class, they will appear here.</p>
    </div>
@else
    {{-- Countdown threshold: show "time left" when exam is within this many hours --}}
    @php
        $countdownHours = 24;
        $now = now();
    @endphp
    <div class="space-y-4 sm:space-y-5">
        @foreach($examCalendarEntries->groupBy(fn ($e) => $e->scheduled_at->format('Y-m-d')) as $date => $entries)
            <section class="rounded-2xl border border-slate-200 bg-white overflow-hidden" aria-label="Exams on {{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}">
                <div class="px-4 py-3 sm:px-5 sm:py-3.5 bg-slate-50 border-b border-slate-200">
                    <h2 class="text-sm font-semibold text-slate-800">
                        <i class="fas fa-calendar-day text-slate-500 mr-2"></i>{{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}
                    </h2>
                </div>
                <ul class="divide-y divide-slate-100">
                    @foreach($entries as $e)
                    @php
                        $secondsUntil = $e->scheduled_at->diffInSeconds($now, false);
                        $hoursUntil = $secondsUntil > 0 ? (int) floor($secondsUntil / 3600) : -1;
                        $showCountdown = $hoursUntil >= 0 && $hoursUntil <= $countdownHours;
                    @endphp
                    <li class="px-4 py-4 sm:px-5 sm:py-5 {{ $showCountdown ? 'bg-amber-50/60' : '' }}">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                                    <span class="text-base font-semibold text-slate-900">{{ $e->course_display }}</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $e->exam_type === \App\Models\ExamCalendar::EXAM_TYPE_MIDSEM ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-800' }}">{{ $e->exam_type_label }}</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $e->mode === \App\Models\ExamCalendar::MODE_ONLINE ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' }}">{{ $e->mode_label }}</span>
                                </div>
                                <p class="text-sm text-slate-600">
                                    <i class="fas fa-clock text-slate-400 mr-1.5 w-4 text-center"></i>Start {{ $e->scheduled_at->format('g:i A') }}{{ $e->ends_at ? ' · End ' . $e->ends_at->format('g:i A') : '' }}
                                </p>
                                @if($e->lecturer)
                                    <p class="text-sm text-slate-600 mt-0.5"><i class="fas fa-user-tie text-slate-400 mr-1.5 w-4 text-center"></i>{{ $e->lecturer }}</p>
                                @endif
                                @if($e->venue)
                                    <p class="text-sm text-slate-600 mt-0.5"><i class="fas fa-map-marker-alt text-slate-400 mr-1.5 w-4 text-center"></i>{{ $e->venue }}</p>
                                @endif
                            </div>
                            @if($showCountdown)
                            <div class="shrink-0 flex items-center gap-2 px-3 py-2 rounded-xl bg-amber-100 border border-amber-200">
                                <span class="inline-flex w-8 h-8 rounded-lg bg-amber-200/80 items-center justify-center text-amber-800" aria-hidden="true"><i class="fas fa-hourglass-half text-sm"></i></span>
                                <div>
                                    <span class="block text-xs font-medium text-amber-800 uppercase tracking-wide">Starts in</span>
                                    <span class="exam-countdown text-sm font-bold text-amber-900 tabular-nums" data-scheduled-at="{{ $e->scheduled_at->toIso8601String() }}" data-exam-id="{{ $e->id }}" aria-live="polite">—</span>
                                </div>
                            </div>
                            @endif
                        </div>
                    </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>

    @push('scripts')
    <script>
    (function() {
        var countdownEls = document.querySelectorAll('.exam-countdown');
        if (!countdownEls.length) return;

        function formatCountdown(secondsLeft) {
            if (secondsLeft <= 0) return 'Started';
            var h = Math.floor(secondsLeft / 3600);
            var m = Math.floor((secondsLeft % 3600) / 60);
            var s = secondsLeft % 60;
            if (h > 0) return h + 'h ' + (m < 10 ? '0' : '') + m + 'm';
            if (m > 0) return m + 'm ' + (s < 10 ? '0' : '') + s + 's';
            return s + 's';
        }

        function updateAll() {
            var now = Date.now();
            countdownEls.forEach(function(el) {
                var scheduledAt = el.getAttribute('data-scheduled-at');
                if (!scheduledAt) return;
                var startMs = new Date(scheduledAt).getTime();
                var left = Math.max(0, Math.floor((startMs - now) / 1000));
                el.textContent = formatCountdown(left);
            });
        }

        updateAll();
        var interval = setInterval(updateAll, 1000);
    })();
    </script>
    @endpush
@endif
@endsection
