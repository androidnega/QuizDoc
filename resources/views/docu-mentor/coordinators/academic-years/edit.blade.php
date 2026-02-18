@extends('layouts.dashboard')

@section('title', 'Edit Academic Year')
@section('dashboard_heading', 'Edit Academic Year')
@section('breadcrumb_trail')
<a href="{{ route('dashboard.coordinators.academic-years.index') }}" class="hover:text-primary-600">Academic Years</a>
<svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
<span class="text-gray-900 font-medium">Edit</span>
@endsection

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6">
    <div class="rounded-lg border border-gray-200 bg-white p-6 max-w-lg">
        <form action="{{ route('dashboard.coordinators.academic-years.update', $academicYear) }}" method="post">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label for="year" class="block text-sm font-medium text-gray-700 mb-1">Year (e.g. 2024/2025)</label>
                    <input type="text" name="year" id="year" value="{{ old('year', $academicYear->year) }}" required class="w-full rounded border-gray-300 text-sm">
                    @error('year')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="submission_deadline" class="block text-sm font-medium text-gray-700 mb-1">Submission deadline</label>
                    <input type="date" name="submission_deadline" id="submission_deadline" value="{{ old('submission_deadline', $academicYear->submission_deadline?->format('Y-m-d')) }}" class="w-full rounded border-gray-300 text-sm">
                    <p class="mt-1 text-xs text-gray-500">If left empty: September 30 of that academic year.</p>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $academicYear->is_active) ? 'checked' : '' }} class="rounded border-gray-300">
                    <label for="is_active" class="ml-2 text-sm text-gray-700">Set as active year</label>
                </div>
            </div>
            <div class="mt-6 flex gap-3">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('dashboard.coordinators.academic-years.index') }}" class="btn border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
