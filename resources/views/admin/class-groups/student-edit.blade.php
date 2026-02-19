@extends('layouts.dashboard')

@section('title', 'Edit index — ' . $classGroup->display_name)
@section('dashboard_heading')
    <span class="inline-flex items-center gap-2"><i class="fas fa-pen text-primary-600"></i> Edit student index</span>
@endsection

@push('styles')
<style>
    .student-edit-form .form-field-input {
        width: 100%;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
        background-color: #ffffff;
        padding: 0.625rem 0.75rem;
        font-size: 1rem;
        color: #111827;
        min-height: 44px;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .student-edit-form .form-field-input:focus {
        outline: none;
        border-color: var(--primary-500, #3b82f6);
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
    }
    .student-edit-form .form-field-input::placeholder { color: #9ca3af; }
</style>
@endpush

@section('dashboard_content')
<div class="w-full">
    @if(session('error'))
        <div class="alert alert-error mb-4">{{ session('error') }}</div>
    @endif

    <a href="{{ route('dashboard.class-groups.students.index', $classGroup) }}" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-primary-600 mb-6">
        <i class="fas fa-arrow-left"></i> Back to student list
    </a>

    <div class="w-full rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <form action="{{ route('dashboard.class-groups.students.update', [$classGroup, $student]) }}" method="post" class="student-edit-form space-y-6">
            @csrf
            @method('PUT')
            <div>
                <label for="index_number" class="block text-sm font-semibold text-gray-800 mb-2">Index number</label>
                <input type="text" name="index_number" id="index_number" required maxlength="64" class="form-field-input" value="{{ old('index_number', $student->index_number) }}" placeholder="e.g. PS/IT/20/0001">
                @error('index_number')
                    <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="student_name" class="block text-sm font-semibold text-gray-800 mb-2">Name <span class="font-normal text-gray-500">(optional)</span></label>
                <input type="text" name="student_name" id="student_name" maxlength="255" class="form-field-input" value="{{ old('student_name', $student->student_name ?? $studentAccount?->student_name) }}" placeholder="Display name in class list">
                @error('student_name')
                    <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                @enderror
                @if($studentAccount && $studentAccount->student_name)
                    <p class="text-xs text-gray-500 mt-1.5">Student's account name: {{ $studentAccount->student_name }}</p>
                @endif
            </div>
            <div>
                <label for="phone_contact" class="block text-sm font-semibold text-gray-800 mb-2">Phone number</label>
                <input type="text" name="phone_contact" id="phone_contact" maxlength="20" class="form-field-input" value="{{ old('phone_contact', $phone) }}" placeholder="For OTP login (optional)">
                @error('phone_contact')
                    <p class="text-sm text-red-600 mt-1.5">{{ $message }}</p>
                @enderror
                <p class="text-xs text-gray-500 mt-1.5">Leave empty to require the student to provide it when logging in.</p>
            </div>
            <p class="text-xs text-gray-500 border-t border-gray-100 pt-4">Level and program context come from the class group.</p>
            <div class="flex items-center gap-3 pt-2">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                >
                    Save changes
                </button>
                <a
                    href="{{ route('dashboard.class-groups.students.index', $classGroup) }}"
                    class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-gray-100 px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-gray-300"
                >
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
