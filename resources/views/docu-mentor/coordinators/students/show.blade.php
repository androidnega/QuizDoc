@extends('layouts.dashboard')

@section('title', 'Student details')
@section('dashboard_heading', 'Student details')

@section('dashboard_content')
<div class="w-full space-y-6">
    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-2.5 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <a href="{{ route('dashboard.coordinators.students.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700">
        <i class="fas fa-arrow-left"></i> Back to students
    </a>

    <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Student information</p>
            <div class="flex flex-wrap items-center gap-2">
                <form action="{{ route('dashboard.coordinators.students.toggle-leader', ['encodedIndex' => $encodedIndex]) }}" method="post" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-1 focus:ring-gray-300">
                        <i class="fas fa-user-tie text-xs"></i> {{ ($isGroupLeader ?? false) ? 'Remove as leader' : 'Set as group leader' }}
                    </button>
                </form>
                <a href="{{ route('dashboard.coordinators.students.edit', ['encodedIndex' => $encodedIndex]) }}" class="inline-flex items-center justify-center h-8 w-8 rounded-md border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 focus:outline-none focus:ring-1 focus:ring-gray-300" title="Edit" aria-label="Edit">
                    <i class="fas fa-edit text-sm"></i>
                </a>
                <form action="{{ route('dashboard.coordinators.students.destroy', ['encodedIndex' => $encodedIndex]) }}" method="post" class="inline" onsubmit="return confirm('Remove this student from all your class groups?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center justify-center h-8 w-8 rounded-md border border-gray-300 bg-white text-gray-600 hover:bg-red-50 hover:text-red-600 hover:border-red-200 focus:outline-none focus:ring-1 focus:ring-gray-300" title="Remove" aria-label="Remove">
                        <i class="fas fa-user-minus text-sm"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="px-4 py-5 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-0.5">Index number</p>
                    <p class="text-sm font-mono font-medium text-gray-900">{{ $indexNumber }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-0.5">Name</p>
                    <p class="text-sm text-gray-900">{{ $displayName ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-0.5">Phone number</p>
                    <p class="text-sm text-gray-900">{{ $phone ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-0.5">Level</p>
                    <p class="text-sm text-gray-900">{{ $levelLabel ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-0.5">Year group</p>
                    <p class="text-sm text-gray-900">{{ $yearGroup ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-0.5">Qualification type</p>
                    <p class="text-sm text-gray-900">{{ $qualificationType ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-0.5">Group leader</p>
                    <p class="text-sm text-gray-900">{{ ($isGroupLeader ?? false) ? 'Yes' : 'No' }}</p>
                </div>
                @if($institution || $faculty || $department)
                <div class="sm:col-span-2">
                    <p class="text-xs font-medium text-gray-500 mb-0.5">Institution / Faculty / Department</p>
                    <p class="text-sm text-gray-900">{{ implode(' · ', array_filter([$institution, $faculty, $department])) ?: '—' }}</p>
                </div>
                @endif
            </div>

            <div class="pt-4 border-t border-gray-100">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Class group</p>
                @if($cgStudents->isEmpty())
                    <p class="text-sm text-gray-500">No class group assigned.</p>
                @else
                    <ul class="space-y-1">
                        @foreach($cgStudents as $cgs)
                            <li class="text-sm text-gray-900">{{ $cgs->classGroup?->name }}{{ $cgs->classGroup?->level ? ' — ' . $cgs->classGroup->level->label : '' }}{{ $cgs->classGroup?->academicYear ? ' (' . $cgs->classGroup->academicYear->year . ')' : '' }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @if($studentAccount)
            <div class="pt-4 border-t border-gray-100">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-3">Quiz history</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <p class="text-xs font-medium text-gray-500 mb-0.5">Quizzes taken</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $quizzesCount }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 mb-0.5">Average score</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $averageScore ? number_format($averageScore, 1) . '%' : '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 mb-0.5">Last quiz</p>
                        <p class="text-sm text-gray-900">{{ $lastQuizDate ?: '—' }}</p>
                    </div>
                </div>
            </div>
            @else
            <div class="pt-4 border-t border-gray-100 rounded-md bg-gray-50/80 px-4 py-3">
                <p class="text-sm text-gray-500">This student has not logged in or taken any quizzes yet.</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
