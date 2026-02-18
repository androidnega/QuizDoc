@extends('layouts.student-dashboard')

@section('title', 'Class Results')
@php $dashboardTitle = 'Class Results'; @endphp

@section('dashboard_content')
<header class="mb-6">
    <h1 class="text-xl font-semibold text-slate-800 tracking-tight">Class results</h1>
    <p class="text-sm text-slate-500 mt-1">As a class rep, you can download quiz result PDFs for your class.</p>
</header>

<section class="mb-8">
    <h2 class="text-sm font-medium text-slate-700 mb-3">Quiz results</h2>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        @if($quizzes->isEmpty())
        <div class="p-8 text-center">
            <span class="w-12 h-12 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 mx-auto"><i class="fas fa-file-pdf"></i></span>
            <h3 class="text-sm font-medium text-slate-800 mt-3">No quizzes yet</h3>
            <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">No published quizzes have been completed in your class groups. Results will appear here once available.</p>
        </div>
        @else
        <ul class="divide-y divide-slate-100">
            @foreach($quizzes as $quiz)
            <li>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-4 sm:px-5 sm:py-4">
                    <div class="min-w-0 flex-1">
                        <h3 class="text-sm font-medium text-slate-800 truncate">{{ $quiz->title }}</h3>
                        <p class="text-xs text-slate-500 mt-1">
                            {{ $quiz->classGroup?->name ?? '—' }}
                            @if($quiz->course)
                            · {{ $quiz->course->code ?? $quiz->course->name ?? '' }}
                            @endif
                            @if($quiz->ends_at)
                            · {{ $quiz->ends_at->format('M j, Y') }}
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('dashboard.class-results.download-pdf', $quiz) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium bg-slate-600 text-white hover:bg-slate-700 flex-shrink-0 min-h-[44px] sm:min-h-0">
                        <i class="fas fa-file-pdf"></i> Download PDF
                    </a>
                </div>
            </li>
            @endforeach
        </ul>
        @endif
    </div>
</section>
@endsection
