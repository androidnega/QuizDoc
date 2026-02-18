@extends('layouts.dashboard')

@section('title', 'Export Report')
@section('dashboard_heading', 'Export Report')

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6">
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif
    <div class="rounded-lg border border-gray-200 bg-white p-6 max-w-md">
        <p class="text-sm text-gray-600 mb-4">Download all projects + students + scores + supervisors for the selected academic year. Columns: Project Title, Student Name, Phone, Supervisor(s), Doc Score, System Score, Final Score, Academic Year.</p>
        <form action="{{ route('dashboard.coordinators.export-report.download') }}" method="get">
            <div class="mb-4">
                <label for="academic_year" class="block text-sm font-medium text-gray-700 mb-2">Select Academic Year</label>
                <select name="academic_year" id="academic_year" required class="w-full rounded border-gray-300 text-sm">
                    <option value="">— Select —</option>
                    @foreach($academicYears as $ay)
                        <option value="{{ $ay->id }}" {{ (\App\Models\DocuMentor\AcademicYear::active()?->id ?? null) == $ay->id ? 'selected' : '' }}>{{ $ay->year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" name="format" value="csv" class="btn btn-primary text-sm">Download CSV</button>
                <button type="submit" name="format" value="xlsx" class="inline-flex items-center px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Download Excel</button>
            </div>
        </form>
    </div>
</div>
@endsection
