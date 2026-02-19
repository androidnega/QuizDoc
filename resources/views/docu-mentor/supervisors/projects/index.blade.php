@extends('docu-mentor.layout')

@section('title', 'Supervisor Dashboard – Docu Mentor')

@section('content')
<div class="max-w-6xl mx-auto w-full pt-4 sm:pt-6">
<h1 class="text-2xl font-bold text-slate-900 mb-2">Supervisor Dashboard</h1>
<p class="text-slate-500 text-sm mb-6">All assigned projects · Progress (Completed Chapters / 6) · Tagged previous project access</p>

@if($projects->isEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center">
        <p class="text-slate-600">No projects assigned to you yet.</p>
        <p class="text-slate-500 text-sm mt-2">A coordinator can assign you as a supervisor to projects.</p>
    </div>
@else
    <div class="grid gap-4">
        @foreach($projects as $project)
            <a href="{{ route('dashboard.docu-mentor.projects.show', $project) }}" class="block bg-white rounded-xl shadow-sm border border-slate-200 p-6 hover:border-indigo-300 transition">
                <h2 class="text-lg font-semibold text-slate-900">{{ $project->title }}</h2>
                <p class="text-sm text-slate-500 mt-1">Group: {{ $project->group?->name }} · {{ $project->academicYear?->year ?? '—' }}</p>
                @if($project->description)
                    <p class="text-sm text-slate-600 mt-2">{{ Str::limit($project->description, 120) }}</p>
                @endif
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                        Progress: {{ $project->completedChaptersCount() }}/6 completed
                    </span>
                    <span class="px-2 py-0.5 rounded text-xs {{ $project->approved ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ $project->approved ? 'Approved' : 'Pending' }}
                    </span>
                    @if($project->parent_project_id)
                        <span class="px-2 py-0.5 rounded text-xs bg-slate-100 text-slate-700" title="Tagged to previous project">Tagged</span>
                    @endif
                </div>
            </a>
        @endforeach
    </div>
@endif

<p class="mt-6">
    <a href="{{ route('dashboard') }}" class="text-indigo-600 hover:text-indigo-800 text-sm">← Back to Dashboard</a>
</p>
</div>
@endsection
