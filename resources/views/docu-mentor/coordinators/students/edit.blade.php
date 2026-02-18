@extends('layouts.dashboard')

@section('title', 'Edit student')
@section('dashboard_heading', 'Edit student')

@section('dashboard_content')
<div class="w-full">
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 mb-4">{{ session('error') }}</div>
    @endif

    <a href="{{ route('dashboard.coordinators.students.show', ['encodedIndex' => $encodedIndex]) }}" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-primary-600 mb-6">
        <i class="fas fa-arrow-left"></i> Back to student details
    </a>

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <form action="{{ route('dashboard.coordinators.students.update', ['encodedIndex' => $encodedIndex]) }}" method="post" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Index number</label>
                    <p class="text-base font-mono text-gray-900 py-1">{{ $indexNumber }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Cannot be changed</p>
                </div>
                <div>
                    <label for="student_name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" name="student_name" id="student_name" maxlength="255" class="input w-full" value="{{ old('student_name', $displayName) }}" placeholder="Optional">
                    @error('student_name')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="phone_contact" class="block text-sm font-medium text-gray-700 mb-1">Phone number</label>
                    <input type="text" name="phone_contact" id="phone_contact" maxlength="20" class="input w-full" value="{{ old('phone_contact', $phone) }}" placeholder="For OTP login">
                    @error('phone_contact')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="level_id" class="block text-sm font-medium text-gray-700 mb-1">Level</label>
                    <select name="level_id" id="level_id" class="input w-full">
                        <option value="">— None —</option>
                        @foreach($levels as $l)
                            <option value="{{ $l->id }}" {{ old('level_id', $studentAccount?->level_id) == $l->id ? 'selected' : '' }}>{{ $l->label }}{{ $l->allows_docu_mentor ? ' ✓ Docu Mentor eligible' : '' }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-0.5">One level for the entire system. Level 400+ grants Docu Mentor (project) access.</p>
                </div>
            </div>
            <div class="border-t border-gray-200 pt-4">
                <p class="text-sm font-medium text-gray-700 mb-3">QuizSnap context (optional)</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label for="quiz_category_id" class="block text-sm font-medium text-gray-600 mb-1">Category</label>
                        <select name="quiz_category_id" id="quiz_category_id" class="input w-full">
                            <option value="">— None —</option>
                            @foreach($quizCategories as $c)
                                <option value="{{ $c->id }}" {{ old('quiz_category_id', $studentAccount?->quiz_category_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="semester_id" class="block text-sm font-medium text-gray-600 mb-1">Semester</label>
                        <select name="semester_id" id="semester_id" class="input w-full">
                            <option value="">— None —</option>
                            @foreach($semesters as $s)
                                <option value="{{ $s->id }}" {{ old('semester_id', $studentAccount?->semester_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="academic_year_id" class="block text-sm font-medium text-gray-600 mb-1">Academic year</label>
                        <select name="academic_year_id" id="academic_year_id" class="input w-full">
                            <option value="">— None —</option>
                            @foreach($academicYears as $ay)
                                <option value="{{ $ay->id }}" {{ old('academic_year_id', $studentAccount?->academic_year_id) == $ay->id ? 'selected' : '' }}>{{ $ay->year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="academic_class_id" class="block text-sm font-medium text-gray-600 mb-1">Academic class</label>
                        <select name="academic_class_id" id="academic_class_id" class="input w-full">
                            <option value="">— None —</option>
                            @foreach($academicClasses as $ac)
                                <option value="{{ $ac->id }}" {{ old('academic_class_id', $studentAccount?->academic_class_id) == $ac->id ? 'selected' : '' }}>{{ $ac->display_label ?? $ac->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn btn-primary">Save changes</button>
                <a href="{{ route('dashboard.coordinators.students.show', ['encodedIndex' => $encodedIndex]) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
