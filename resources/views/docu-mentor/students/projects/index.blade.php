@extends('layouts.student-dashboard')

@section('title', 'My Projects')
@php $dashboardTitle = 'My Projects'; @endphp

@section('dashboard_content')
<header class="mb-6">
    <h1 class="text-xl font-semibold text-slate-800 tracking-tight">My projects</h1>
    <p class="text-sm text-slate-500 mt-1">View and manage your project proposals.</p>
</header>

@include('docu-mentor.students.partials.projects-tabs')

@if(session('success') || session('info') || session('error'))
<section class="mb-6" aria-label="Notice">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
        @if(session('success'))<p class="text-sm font-medium text-slate-800">{{ session('success') }}</p>@endif
        @if(session('info'))<p class="text-sm text-slate-500 mt-1">{{ session('info') }}</p>@endif
        @if(session('error'))<p class="text-sm font-medium text-red-600 mt-1">{{ session('error') }}</p>@endif
    </div>
</section>
@endif

<section class="mb-8" aria-label="Projects">
    @if($projects->isEmpty())
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-8 text-center">
        <span class="w-12 h-12 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 mx-auto"><i class="fas fa-folder-open"></i></span>
        <h2 class="text-sm font-medium text-slate-800 mt-3">No projects yet</h2>
        @if($leaderWithoutGroup ?? false)
        <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">Create your group and add members, then create a project.</p>
        <a href="{{ route('dashboard.group.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium bg-slate-600 text-white hover:bg-slate-700 mt-4 min-h-[44px] sm:min-h-0">Create your group</a>
        @elseif($isGroupLeader ?? false)
        <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">You are a group leader. Start your project to submit proposals and chapters.</p>
        <a href="{{ route('dashboard.projects.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium bg-slate-600 text-white hover:bg-slate-700 mt-4 min-h-[44px] sm:min-h-0">
            <i class="fas fa-plus text-xs"></i>
            Create project
        </a>
        @else
        <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">Your group leader will add you when a group is created.</p>
        @endif
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach($projects as $project)
        <a href="{{ route('dashboard.projects.show', $project) }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex flex-col no-underline min-w-0 hover:bg-slate-50 hover:border-slate-300 transition-colors min-h-[100px]">
            <span class="text-sm font-medium text-slate-800 truncate">{{ $project->title }}</span>
            <p class="text-xs text-slate-500 mt-0.5">{{ $project->group?->name ?? '—' }} · {{ $project->academicYear?->year ?? '—' }}</p>
            @if($project->description)
            <p class="text-xs text-slate-500 mt-1 truncate">{{ Str::limit($project->description, 80) }}</p>
            @endif
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $project->approved ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                    {{ $project->approved ? 'Approved' : 'Pending' }}
                </span>
                @if($project->category)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-indigo-50 text-indigo-700">
                        {{ $project->category->name }}
                    </span>
                @endif
            </div>
        </a>
        @endforeach
    </div>
    @endif
</section>
@endsection
