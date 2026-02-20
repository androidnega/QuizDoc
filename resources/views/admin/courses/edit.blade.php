@extends('layouts.dashboard')

@section('title', 'Edit course')
@section('dashboard_heading', 'Edit course')

@push('styles')
<style>
#course-edit-form .input,
#course-edit-form input[type="text"],
#course-edit-form select {
    border: 1px solid #e5e7eb;
    background-color: #fff;
    color: #374151;
    font-size: 1rem;
    font-weight: 400;
    padding: 0.5rem 0.75rem;
    min-height: 44px;
    border-radius: 0.5rem;
    width: 100%;
}
#course-edit-form .input:focus,
#course-edit-form input:focus,
#course-edit-form select:focus {
    border-color: #93c5fd;
    outline: none;
    box-shadow: 0 0 0 2px rgba(147, 197, 253, 0.35);
}
#course-edit-form label {
    font-weight: 500;
    color: #4b5563;
    font-size: 0.875rem;
    display: block;
    margin-bottom: 0.5rem;
}
</style>
@endpush

@section('dashboard_content')
<div class="w-full space-y-6">
        <div class="flex items-center gap-2 text-sm text-gray-600 mb-6">
            <a href="{{ route('dashboard.courses.index') }}" class="hover:text-primary-600">Courses</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-900 font-medium">Edit {{ $course->name }}</span>
        </div>

        <div class="card p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Edit course</h1>

            <form id="course-edit-form" action="{{ route('dashboard.courses.update', $course) }}" method="post" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-2">Course code *</label>
                    <input type="text" name="code" id="code" value="{{ old('code', $course->code) }}" required maxlength="64" class="input w-full" placeholder="e.g. BTCS NT-3101">
                    @error('code')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Course name *</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $course->name) }}" required maxlength="255" class="input w-full" placeholder="Course name">
                    @error('name')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                </div>
                @if(isset($quizCategories) && isset($levels) && isset($semesters))
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label for="quiz_category_id" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="quiz_category_id" id="quiz_category_id" class="input w-full">
                            <option value="">— Select —</option>
                            @foreach($quizCategories as $c)
                                <option value="{{ $c->id }}" {{ old('quiz_category_id', $course->quiz_category_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="level_id" class="block text-sm font-medium text-gray-700 mb-2">Level</label>
                        <select name="level_id" id="level_id" class="input w-full">
                            <option value="">— Select —</option>
                            @foreach($levels as $l)
                                <option value="{{ $l->id }}" {{ old('level_id', $course->level_id) == $l->id ? 'selected' : '' }}>{{ $l->label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="semester_id" class="block text-sm font-medium text-gray-700 mb-2">Semester</label>
                        <select name="semester_id" id="semester_id" class="input w-full">
                            <option value="">— Select —</option>
                            @foreach($semesters as $s)
                                <option value="{{ $s->id }}" {{ old('semester_id', $course->semester_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
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
                                <input type="checkbox" name="examiner_ids[]" value="{{ $e->id }}" {{ in_array($e->id, old('examiner_ids', $course->examiners->pluck('id')->all())) ? 'checked' : '' }}>
                                <span class="text-sm">{{ $e->username }}{{ $e->name ? ' (' . $e->name . ')' : '' }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-gray-500">No examiners yet.</p>
                        @endforelse
                    </div>
                </div>
                @else
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <p class="text-sm text-gray-600">You are assigned as an examiner for this course.</p>
                </div>
                @endif
                <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-gray-200">
                    <button type="submit" class="inline-flex items-center justify-center px-6 py-3 rounded-lg font-semibold min-h-[48px] bg-yellow-400 hover:bg-yellow-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2">Save</button>
                    <a href="{{ route('dashboard.courses.index') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-lg font-semibold min-h-[48px] bg-red-600 hover:bg-red-700 text-white focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
