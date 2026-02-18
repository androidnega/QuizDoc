@extends('layouts.dashboard')

@section('title', 'Add course')
@section('dashboard_heading', 'Add course')

@section('dashboard_content')
<div class="w-full space-y-6">
        <div class="flex items-center gap-2 text-sm text-gray-600 mb-6">
            <a href="{{ route('dashboard') }}" class="hover:text-primary-600">Dashboard</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('dashboard.courses.index') }}" class="hover:text-primary-600">Courses</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-900 font-medium">Add course</span>
        </div>

        <div class="card p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Add course</h1>
            <p class="text-sm text-gray-600 mb-4">
                @if(isset($canAssignLecturers) && $canAssignLecturers)
                    Course code and title are institutional data. Assign examiners who can create quizzes for this course.
                @else
                    Create a new course. You will be automatically assigned as an examiner for this course.
                @endif
            </p>

            <form action="{{ route('dashboard.courses.store') }}" method="post" class="space-y-4">
                @csrf
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Course code *</label>
                    <input type="text" name="code" id="code" value="{{ old('code') }}" required maxlength="64" placeholder="e.g. CSC 201" class="input w-full">
                    @error('code')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Course name *</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required maxlength="255" placeholder="e.g. Introduction to Programming" class="input w-full">
                    @error('name')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                @if(isset($quizCategories) && isset($levels) && isset($semesters))
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label for="quiz_category_id" class="block text-sm font-medium text-gray-700 mb-1">Category (HND, BTECH, etc.)</label>
                        <select name="quiz_category_id" id="quiz_category_id" class="input w-full">
                            <option value="">— Select —</option>
                            @foreach($quizCategories as $c)
                                <option value="{{ $c->id }}" {{ old('quiz_category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="level_id" class="block text-sm font-medium text-gray-700 mb-1">Level</label>
                        <select name="level_id" id="level_id" class="input w-full">
                            <option value="">— Select —</option>
                            @foreach($levels as $l)
                                <option value="{{ $l->id }}" {{ old('level_id') == $l->id ? 'selected' : '' }}>{{ $l->label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="semester_id" class="block text-sm font-medium text-gray-700 mb-1">Semester</label>
                        <select name="semester_id" id="semester_id" class="input w-full">
                            <option value="">— Select —</option>
                            @foreach($semesters as $s)
                                <option value="{{ $s->id }}" {{ old('semester_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @endif
                @if(isset($canAssignLecturers) && $canAssignLecturers)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Assign examiners</label>
                    <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3">
                        @forelse($examiners as $e)
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="examiner_ids[]" value="{{ $e->id }}" {{ in_array($e->id, old('examiner_ids', [])) ? 'checked' : '' }}>
                                <span class="text-sm">{{ $e->username }}{{ $e->name ? ' (' . $e->name . ')' : '' }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-gray-500">No examiners yet. Create users with role Examiner first.</p>
                        @endforelse
                    </div>
                    @error('examiner_ids')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                @else
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <p class="text-sm text-gray-600">You will be automatically assigned as an examiner for this course.</p>
                </div>
                @endif
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn btn-primary">Create course</button>
                    <a href="{{ route('dashboard.courses.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
</div>
@endsection
