@extends('layouts.examiner')

@section('title', 'Attendance')
@section('examiner_heading', 'Attendance')

@section('examiner_content')
<div class="w-full space-y-8">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error">
            <strong>Please fix the following:</strong>
            <ul class="list-disc list-inside mt-2">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Add single index --}}
    <section class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Add single index</h2>
                    <p class="text-sm text-gray-600">Add one student index number (and optional name) for a course</p>
                </div>
            </div>
            <form action="{{ route('admin.attendance.add') }}" method="post" class="space-y-4">
                @csrf
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label for="course_id_single" class="block text-sm font-medium text-gray-700 mb-1">Course</label>
                        <select name="course_id" id="course_id_single" required class="input">
                            <option value="">Select course</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}">{{ $course->name }} ({{ $course->valid_indices_count ?? 0 }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="index_number" class="block text-sm font-medium text-gray-700 mb-1">Index number</label>
                        <input type="text" name="index_number" id="index_number" required maxlength="64" placeholder="e.g. BC/ITS/24/047" class="input">
                        @error('index_number')<p class="text-sm text-danger-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="student_name" class="block text-sm font-medium text-gray-700 mb-1">Student name (optional)</label>
                        <input type="text" name="student_name" id="student_name" maxlength="255" placeholder="Optional" class="input">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Add index</button>
            </form>
        </div>
    </section>

    {{-- Upload Excel --}}
    <section class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Upload attendance (Excel)</h2>
                    <p class="text-sm text-gray-600">Choose <strong>Replace</strong> to clear the course and set indices from the file, or <strong>Merge</strong> to add/update rows without removing existing ones. Uploads are logged (uploader, course, timestamp).</p>
                </div>
            </div>
            <form action="{{ route('admin.attendance.upload') }}" method="post" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label for="course_id" class="block text-sm font-medium text-gray-700 mb-1">Course</label>
                        <select name="course_id" id="course_id" required class="input">
                            <option value="">Select course</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}">{{ $course->name }} ({{ $course->valid_indices_count ?? 0 }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="upload_mode" class="block text-sm font-medium text-gray-700 mb-1">Upload mode</label>
                        <select name="upload_mode" id="upload_mode" required class="input">
                            <option value="replace">Replace — clear course, then add from file</option>
                            <option value="merge">Merge — add new and update existing</option>
                        </select>
                    </div>
                    <div>
                        <label for="file" class="block text-sm font-medium text-gray-700 mb-1">Excel file (.xlsx, .xls, .csv)</label>
                        <input type="file" name="file" id="file" accept=".xlsx,.xls,.csv" required class="input file:mr-4 file:py-2 file:px-4 file:rounded file:border file:border-primary-300 file:bg-primary-50 file:text-primary-700">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Upload Excel</button>
            </form>
        </div>
    </section>

    {{-- Course summary: white card --}}
    <section class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Indices by course</h2>
            <ul class="divide-y divide-gray-200">
                @forelse($courses as $course)
                    <li class="py-3 flex items-center justify-between">
                        <span class="font-medium text-gray-900">{{ $course->name }}</span>
                        <span class="text-sm text-gray-600">{{ $course->valid_indices_count ?? 0 }} indices</span>
                    </li>
                @empty
                    <li class="py-4 text-gray-500 text-sm">No courses yet.</li>
                @endforelse
            </ul>
        </div>
    </section>
</div>
@endsection
