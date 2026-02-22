@extends('layouts.dashboard')

@section('title', 'Edit student')
@section('dashboard_heading', 'Edit student')

@section('dashboard_content')
<div class="w-full space-y-6">
    <a href="{{ route('dashboard.coordinators.students.show', ['encodedIndex' => $encodedIndex]) }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700">
        <i class="fas fa-arrow-left"></i> Back to student details
    </a>

    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <form action="{{ route('dashboard.coordinators.students.update', ['encodedIndex' => $encodedIndex]) }}" method="post" class="p-0">
            @csrf
            @method('PUT')

            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-4">Student info</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Index number</label>
                        <p class="text-base font-mono text-gray-900 py-2">{{ $indexNumber }}</p>
                        <p class="text-xs text-gray-500">Cannot be changed</p>
                    </div>
                    <div>
                        <label for="student_name" class="block text-sm font-medium text-gray-700 mb-1.5">Name</label>
                        <input type="text" name="student_name" id="student_name" maxlength="255" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-400 text-gray-900" value="{{ old('student_name', $displayName) }}" placeholder="Optional">
                        @error('student_name')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label for="phone_contact" class="block text-sm font-medium text-gray-700 mb-1.5">Phone number</label>
                        <input type="text" name="phone_contact" id="phone_contact" maxlength="20" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-400 text-gray-900" value="{{ old('phone_contact', $phone) }}" placeholder="For OTP login">
                        <p class="text-xs text-gray-500 mt-1">Leave blank to remove phone number. Coordinator can clear this field.</p>
                        @error('phone_contact')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="level_id" class="block text-sm font-medium text-gray-700 mb-1.5">Level</label>
                        <select name="level_id" id="level_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-400 text-gray-900">
                            <option value="">— None —</option>
                            @foreach($levels as $l)
                                <option value="{{ $l->id }}" {{ old('level_id', $studentAccount?->level_id) == $l->id ? 'selected' : '' }}>{{ $l->label }}{{ $l->allows_docu_mentor ? ' ✓ Docu Mentor eligible' : '' }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">One level for the entire system. Level 400+ grants Docu Mentor (project) access.</p>
                    </div>
                </div>
            </div>

            <div class="px-6 py-5 border-t border-gray-100">
                <p class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-4">QuizSnap context (optional)</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div>
                        <label for="quiz_category_id" class="block text-sm font-medium text-gray-700 mb-1.5">Category</label>
                        <select name="quiz_category_id" id="quiz_category_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-400 text-gray-900">
                            <option value="">— None —</option>
                            @foreach($quizCategories as $c)
                                <option value="{{ $c->id }}" {{ old('quiz_category_id', $studentAccount?->quiz_category_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="semester_id" class="block text-sm font-medium text-gray-700 mb-1.5">Semester</label>
                        <select name="semester_id" id="semester_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-400 text-gray-900">
                            <option value="">— None —</option>
                            @foreach($semesters as $s)
                                <option value="{{ $s->id }}" {{ old('semester_id', $studentAccount?->semester_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="academic_year_id" class="block text-sm font-medium text-gray-700 mb-1.5">Academic year</label>
                        <select name="academic_year_id" id="academic_year_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-400 text-gray-900">
                            <option value="">— None —</option>
                            @foreach($academicYears as $ay)
                                <option value="{{ $ay->id }}" {{ old('academic_year_id', $studentAccount?->academic_year_id) == $ay->id ? 'selected' : '' }}>{{ $ay->year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="academic_class_id" class="block text-sm font-medium text-gray-700 mb-1.5">Academic class</label>
                        <select name="academic_class_id" id="academic_class_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-400 text-gray-900">
                            <option value="">— None —</option>
                            @foreach($academicClasses as $ac)
                                <option value="{{ $ac->id }}" {{ old('academic_class_id', $studentAccount?->academic_class_id) == $ac->id ? 'selected' : '' }}>{{ $ac->display_label ?? $ac->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex flex-wrap items-center gap-3">
                <button type="submit" class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-sm font-semibold text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">
                    Save changes
                </button>
                <a href="{{ route('dashboard.coordinators.students.show', ['encodedIndex' => $encodedIndex]) }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-sm font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-1">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
