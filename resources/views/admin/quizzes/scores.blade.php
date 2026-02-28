@extends('layouts.dashboard')

@section('title', 'Scores - ' . $quiz->title)
@section('dashboard_heading', 'Quiz Scores')

@section('dashboard_content')
<div class="w-full space-y-6">
    <div class="flex items-center gap-2 text-sm text-gray-600">
        <a href="{{ route('dashboard.quizzes.show', $quiz) }}" class="hover:text-primary-600 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to quiz
        </a>
        <span class="text-gray-300">•</span>
        <span class="font-medium text-gray-900">{{ $quiz->title }}</span>
    </div>

    {{-- Statistics cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Students</p>
                    <p class="text-2xl font-bold text-gray-900 tabular-nums">{{ $stats['total_students'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-success-100 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-success-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Average Score</p>
                    <p class="text-2xl font-bold text-gray-900 tabular-nums">{{ $stats['average_score'] }}%</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Score Range</p>
                    <p class="text-2xl font-bold text-gray-900 tabular-nums">{{ $stats['lowest_score'] }}-{{ $stats['highest_score'] }}%</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-danger-100 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-danger-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Violations</p>
                    <p class="text-2xl font-bold text-gray-900 tabular-nums">{{ $stats['total_violations'] }}</p>
                    @if($stats['students_with_violations'] > 0)
                        <p class="text-xs text-danger-600 mt-0.5">{{ $stats['students_with_violations'] }} {{ $stats['students_with_violations'] === 1 ? 'student' : 'students' }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Scores table --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Student Scores</h2>
                <p class="text-sm text-gray-600 mt-0.5">All completed quiz attempts</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('dashboard.quizzes.scores.export.pdf.preview', $quiz) }}" target="_blank" rel="noopener" class="btn btn-secondary text-sm inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    Preview PDF
                </a>
                <a href="{{ route('dashboard.quizzes.scores.export.pdf', $quiz) }}" class="btn btn-secondary text-sm inline-flex items-center gap-1.5" download>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Download PDF
                </a>
                <a href="{{ route('dashboard.quizzes.scores.export.excel', $quiz) }}" class="btn btn-secondary text-sm inline-flex items-center gap-1.5" download>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Excel
                </a>
                <a href="{{ route('dashboard.quizzes.scores.export', $quiz) }}" class="btn btn-secondary text-sm inline-flex items-center gap-1.5" download>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    CSV
                </a>
                <a href="{{ route('dashboard.quizzes.violations.export', $quiz) }}" class="btn btn-secondary text-sm inline-flex items-center gap-1.5" download>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Violations (CSV)
                </a>
            </div>
        </div>

        @if($sessions->isEmpty())
            <div class="p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-gray-500 font-medium">No completed attempts yet</p>
                <p class="text-sm text-gray-400 mt-1">Results will appear here once students complete the quiz</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-3 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Index</th>
                            <th scope="col" class="px-3 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mark</th>
                            <th scope="col" class="px-3 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Violation</th>
                            <th scope="col" class="px-3 py-1.5 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($sessions as $session)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-1.5 whitespace-nowrap">
                                    <span class="text-xs font-medium text-gray-900">{{ $session->student_index }}</span>
                                </td>
                                <td class="px-3 py-1.5 whitespace-nowrap">
                                    @if($session->result)
                                        @php
                                            $score = $session->result->score;
                                            $colorClass = $score >= 70 ? 'text-success-600 bg-success-100' : ($score >= 50 ? 'text-warning-600 bg-warning-100' : 'text-danger-600 bg-danger-100');
                                        @endphp
                                        <div class="flex items-center gap-1.5">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold tabular-nums {{ $colorClass }}">
                                                {{ number_format((float) $session->result->score, 1) }}%
                                            </span>
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold tabular-nums bg-slate-100 text-slate-700">
                                                {{ $session->result->correct_count }}/{{ $session->result->total_questions }}
                                            </span>
                                            @if($session->isResultWithheld())
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-700">Result on hold</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-1.5 whitespace-nowrap">
                                    @php $violationLabel = $session->getFirstCriticalViolationLabel(); @endphp
                                    @if($violationLabel)
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-xs font-medium bg-danger-100 text-danger-700">
                                            <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                            {{ $violationLabel }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-xs font-medium bg-success-100 text-success-700">
                                            <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            —
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-1.5 whitespace-nowrap text-right">
                                    <a href="{{ route('dashboard.quizzes.sessions.show', [$quiz, $session]) }}" class="inline-flex items-center gap-0.5 text-xs font-medium text-primary-600 hover:text-primary-700">
                                        View
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Violation summary --}}
    @if($stats['total_violations'] > 0)
        <div class="bg-danger-50 border border-danger-200 rounded-lg p-5">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-danger-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="flex-1">
                    <h3 class="font-semibold text-danger-900 mb-1">Violations Detected</h3>
                    <p class="text-sm text-danger-700">
                        {{ $stats['total_violations'] }} {{ $stats['total_violations'] === 1 ? 'violation was' : 'violations were' }} recorded during this quiz.
                        {{ $stats['students_with_violations'] }} {{ $stats['students_with_violations'] === 1 ? 'student has' : 'students have' }} violations.
                        Click "View details" to see specific violation types and timestamps.
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
