@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('dashboard_heading', 'Dashboard')

@section('dashboard_content')
<div class="w-full space-y-6">
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <a href="{{ route('dashboard.coordinators.projects.index') }}" class="rounded-lg bg-blue-700 p-4 shadow-sm hover:bg-blue-800 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-blue-400">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600/20 text-white mb-2"><i class="fas fa-folder-open"></i></span>
            <p class="text-sm font-medium text-blue-100">Projects</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-white">{{ $overview['projects'] ?? 0 }}</p>
        </a>
        <a href="{{ route('dashboard.coordinators.projects.index') }}" class="rounded-lg bg-emerald-700 p-4 shadow-sm hover:bg-emerald-800 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-emerald-400">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-600/20 text-white mb-2"><i class="fas fa-check-circle"></i></span>
            <p class="text-sm font-medium text-emerald-100">Approved</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-white">{{ $overview['projects_approved'] ?? 0 }}</p>
        </a>
        <a href="{{ route('dashboard.coordinators.categories.index') }}" class="rounded-lg bg-amber-700 p-4 shadow-sm hover:bg-amber-800 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-amber-400">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-600/20 text-white mb-2"><i class="fas fa-tags"></i></span>
            <p class="text-sm font-medium text-amber-100">Categories</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-white">{{ $overview['categories'] ?? 0 }}</p>
        </a>
        <a href="{{ route('dashboard.coordinators.groups.index') }}" class="rounded-lg bg-violet-700 p-4 shadow-sm hover:bg-violet-800 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-violet-400">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-600/20 text-white mb-2"><i class="fas fa-users"></i></span>
            <p class="text-sm font-medium text-violet-100">Project groups</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-white">{{ $overview['groups'] ?? 0 }}</p>
        </a>
        <a href="{{ route('dashboard.coordinators.assign-group-leaders.index') }}" class="rounded-lg bg-rose-700 p-4 shadow-sm hover:bg-rose-800 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-rose-400">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-rose-600/20 text-white mb-2"><i class="fas fa-user-tie"></i></span>
            <p class="text-sm font-medium text-rose-100">Group leaders</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-white">{{ $overview['group_leaders'] ?? 0 }}</p>
        </a>
        <a href="{{ route('dashboard.coordinators.students.index') }}" class="rounded-lg bg-slate-700 p-4 shadow-sm hover:bg-slate-800 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-slate-400">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-600/20 text-white mb-2"><i class="fas fa-user-graduate"></i></span>
            <p class="text-sm font-medium text-slate-100">Students</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-white">{{ $overview['students'] ?? 0 }}</p>
        </a>
    </div>
    
    <details class="rounded-lg border border-gray-200 bg-white shadow-sm group">
        <summary class="flex cursor-pointer items-center justify-between px-4 py-3 text-sm font-medium text-gray-800 hover:bg-gray-50 rounded-t-lg list-none [&::-webkit-details-marker]:hidden">
            <span>Setup &amp; reports</span>
            <svg class="h-5 w-5 text-gray-400 shrink-0 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </summary>
        <div class="border-t border-gray-100 px-4 py-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="rounded-lg border border-sky-100 bg-sky-50/80 px-3 py-2.5">
                <p class="text-xs font-medium text-sky-700 uppercase tracking-wide mb-2">Academic</p>
                <div class="flex flex-wrap gap-x-1.5 gap-y-1 text-sm">
                    <a href="{{ route('dashboard.coordinators.academic-years.index') }}" class="text-sky-800 hover:text-sky-900 hover:underline">Years</a>
                    <span class="text-sky-300" aria-hidden="true">·</span>
                    <a href="{{ route('dashboard.coordinators.quiz-categories.index') }}" class="text-sky-800 hover:text-sky-900 hover:underline">Qualification</a>
                    <span class="text-sky-300" aria-hidden="true">·</span>
                    <a href="{{ route('dashboard.coordinators.semesters.index') }}" class="text-sky-800 hover:text-sky-900 hover:underline">Semesters</a>
                    <span class="text-sky-300" aria-hidden="true">·</span>
                    <a href="{{ route('dashboard.coordinators.academic-classes.index') }}" class="text-sky-800 hover:text-sky-900 hover:underline">Classes</a>
                    <span class="text-sky-300" aria-hidden="true">·</span>
                    <a href="{{ route('dashboard.class-groups.index') }}" class="text-sky-800 hover:text-sky-900 hover:underline">Class Groups</a>
                    <span class="text-sky-300" aria-hidden="true">·</span>
                    <a href="{{ route('dashboard.courses.index') }}" class="text-sky-800 hover:text-sky-900 hover:underline">Courses</a>
                </div>
            </div>
            <div class="rounded-lg border border-violet-100 bg-violet-50/80 px-3 py-2.5">
                <p class="text-xs font-medium text-violet-700 uppercase tracking-wide mb-2">Docu Mentor</p>
                <div class="flex flex-wrap gap-x-1.5 gap-y-1 text-sm">
                    <a href="{{ route('dashboard.coordinators.categories.index') }}" class="text-violet-800 hover:text-violet-900 hover:underline">Categories</a>
                    <span class="text-violet-300" aria-hidden="true">·</span>
                    <a href="{{ route('dashboard.coordinators.groups.index') }}" class="text-violet-800 hover:text-violet-900 hover:underline">Groups</a>
                    <span class="text-violet-300" aria-hidden="true">·</span>
                    <a href="{{ route('dashboard.coordinators.assign-group-leaders.index') }}" class="text-violet-800 hover:text-violet-900 hover:underline">Group Leaders</a>
                    <span class="text-violet-300" aria-hidden="true">·</span>
                    <a href="{{ route('dashboard.coordinators.workload') }}" class="text-violet-800 hover:text-violet-900 hover:underline">Workload</a>
                </div>
            </div>
            <div class="rounded-lg border border-emerald-100 bg-emerald-50/80 px-3 py-2.5">
                <p class="text-xs font-medium text-emerald-700 uppercase tracking-wide mb-2">Reports</p>
                <a href="{{ route('dashboard.coordinators.export-report') }}" class="text-sm text-emerald-800 hover:text-emerald-900 hover:underline font-medium">Export CSV</a>
            </div>
        </div>
    </details>
</div>
@endsection
