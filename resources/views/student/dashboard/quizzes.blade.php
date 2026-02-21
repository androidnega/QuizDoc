@extends('layouts.student-dashboard')

@section('title', 'My Quizzes')
@php $dashboardTitle = 'My Quizzes'; @endphp

@section('dashboard_content')
<header class="mb-6">
    <h1 class="text-xl font-semibold text-slate-800 tracking-tight">My quizzes</h1>
    <p class="text-sm text-slate-500 mt-1">Active quizzes you can take and your past results. Marks are kept forever; question review is available for 21 days.</p>
</header>

@if(isset($activeQuizzes) && $activeQuizzes->isNotEmpty())
<section class="mb-8" aria-label="Active quizzes">
    <h2 class="text-sm font-medium text-slate-700 mb-3">Active quizzes</h2>
    <div class="space-y-3">
        @foreach($activeQuizzes as $quiz)
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-slate-800 truncate">{{ $quiz->title }}</p>
                @if($quiz->course)
                <p class="text-xs text-slate-500 mt-0.5">{{ $quiz->course->name }}</p>
                @endif
                <p class="text-xs text-slate-500 mt-1">{{ $quiz->duration_minutes }} min · {{ $quiz->getQuestionsPerStudent() }} questions</p>
            </div>
            <a href="{{ route('student.rules.show.quiz', ['token' => $quiz->link_token]) }}" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium bg-slate-600 text-white hover:bg-slate-700 flex-shrink-0 min-h-[44px] sm:min-h-0">
                Start quiz
                <i class="fas fa-arrow-right text-xs"></i>
            </a>
        </div>
        @endforeach
    </div>
</section>
@endif

<section class="mb-8" aria-label="Completed quizzes">
    <h2 class="text-sm font-medium text-slate-700 mb-3">Completed quizzes</h2>
    @if($sessions->isNotEmpty())
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <ul class="divide-y divide-slate-100">
            @foreach($sessions as $s)
            <li class="px-4 py-3 sm:px-5 sm:py-4 first:pt-4 last:pb-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('dashboard.my-quizzes.show', ['sessionId' => $s->id]) }}" class="text-sm font-medium text-slate-800 hover:text-slate-600 truncate block">{{ $s->quiz->title ?? 'Quiz' }}</a>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $s->created_at ? $s->created_at->format('M j, Y g:i A') : '' }}</p>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0 flex-wrap">
                        @if(isset($s->result) && $s->result)
                        @if($s->isResultWithheld())
                        <span class="inline-flex items-center rounded-lg bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">Withheld, contact lecturer.</span>
                        @else
                        <span class="inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">{{ number_format($s->result->score ?? 0, 1) }}%</span>
                        <span class="text-xs text-slate-500">{{ $s->result->correct_count ?? 0 }}/{{ $s->result->total_questions ?? 0 }} correct</span>
                        @endif
                        <a href="{{ route('dashboard.my-quizzes.show', ['sessionId' => $s->id]) }}" class="text-xs font-medium text-slate-600 hover:underline">Review</a>
                        <a href="{{ route('dashboard.my-quizzes.download-pdf', ['sessionId' => $s->id]) }}" target="_blank" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-white border border-slate-300 text-slate-700 hover:bg-slate-50" title="Download PDF">
                            <i class="fas fa-file-pdf"></i> PDF
                        </a>
                        @else
                        <span class="text-xs text-slate-500">No result</span>
                        @endif
                    </div>
                </div>
            </li>
            @endforeach
        </ul>
        @if($sessions->hasPages())
        <div class="px-4 py-3 border-t border-slate-100">
            {{ $sessions->links() }}
        </div>
        @endif
    </div>
    @else
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-8 text-center">
        <span class="w-12 h-12 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 mx-auto"><i class="fas fa-clipboard-list"></i></span>
        <p class="text-sm text-slate-500 mt-3">You haven't taken any quizzes yet.</p>
        <a href="{{ route('student.landing') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium bg-slate-600 text-white hover:bg-slate-700 mt-4 min-h-[44px] sm:min-h-0">Start a quiz</a>
    </div>
    @endif
</section>
@endsection
