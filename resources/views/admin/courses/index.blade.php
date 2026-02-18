@extends('layouts.dashboard')

@section('title', 'Courses')
@section('dashboard_heading', 'Courses')

@section('dashboard_content')
<div class="w-full space-y-4">
    <div class="flex items-center justify-between flex-wrap gap-4">
        @if($canManageAll)
        <a href="{{ route('dashboard.courses.create') }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-900 bg-yellow-400 hover:bg-yellow-500 border border-yellow-600/30 shadow-sm">Add course</a>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success text-sm">{{ session('success') }}</div>
    @endif

    @if($courses->isNotEmpty())
        <section class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                <h2 class="text-sm font-semibold text-gray-700">Courses</h2>
            </div>
            <div class="overflow-x-auto">
                @include('admin.courses.partials.courses-table', ['courses' => $courses, 'canManageAll' => $canManageAll])
            </div>
        </section>
        @if($courses->hasPages())
            <div class="mt-4">{{ $courses->links() }}</div>
        @endif
    @else
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-8 text-center">
            <p class="text-sm text-gray-500">No courses yet. Create one to assign lecturers and create quizzes.</p>
            @if($canManageAll)
            <a href="{{ route('dashboard.courses.create') }}" class="mt-3 inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-900 bg-yellow-400 hover:bg-yellow-500 border border-yellow-600/30 shadow-sm">Add course</a>
            @endif
        </div>
    @endif
</div>
@endsection
