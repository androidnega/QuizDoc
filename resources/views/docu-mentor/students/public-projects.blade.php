@extends('layouts.student-dashboard')

@section('title', 'Public Projects')
@php $dashboardTitle = 'Public Projects'; @endphp

@section('dashboard_content')
<header class="mb-6">
    <h1 class="text-xl font-semibold text-slate-800 tracking-tight">Public projects</h1>
    <p class="text-sm text-slate-500 mt-1">Browse approved projects from other groups.</p>
</header>

@include('docu-mentor.students.partials.projects-tabs')

<section class="mb-8">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 sm:px-5 sm:py-4 border-b border-slate-100">
            <form method="get" class="flex flex-wrap gap-3 items-end">
                <div>
                    <label for="academic_year" class="text-xs font-medium text-slate-500 uppercase tracking-wide block mb-1">Academic year</label>
                    <select name="academic_year" id="academic_year" class="w-full min-w-0 sm:w-[130px] rounded-lg border-slate-300 text-sm py-1.5">
                        <option value="">All</option>
                        @foreach($academicYears ?? [] as $ay)
                            <option value="{{ $ay->id }}" {{ request('academic_year') == $ay->id ? 'selected' : '' }}>{{ $ay->year }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="category" class="text-xs font-medium text-slate-500 uppercase tracking-wide block mb-1">Category</label>
                    <select name="category" id="category" class="w-full min-w-0 sm:w-[130px] rounded-lg border-slate-300 text-sm py-1.5">
                        <option value="">All</option>
                        @foreach($categories ?? [] as $c)
                            <option value="{{ $c->id }}" {{ request('category') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="supervisor" class="text-xs font-medium text-slate-500 uppercase tracking-wide block mb-1">Supervisor</label>
                    <select name="supervisor" id="supervisor" class="w-full min-w-0 sm:w-[130px] rounded-lg border-slate-300 text-sm py-1.5">
                        <option value="">All</option>
                        @foreach($supervisors ?? [] as $s)
                            <option value="{{ $s->id }}" {{ request('supervisor') == $s->id ? 'selected' : '' }}>{{ $s->name ?? $s->username }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="inline-flex items-center justify-center px-3 py-2 rounded-lg text-sm font-medium bg-slate-600 text-white hover:bg-slate-700">Filter</button>
                    @if(request()->hasAny(['academic_year','category','supervisor']))
                    <a href="{{ route('dashboard.public-projects') }}" class="inline-flex items-center justify-center px-3 py-2 rounded-lg text-sm font-medium bg-white border border-slate-300 text-slate-700 hover:bg-slate-50">Clear</a>
                    @endif
                </div>
            </form>
        </div>

        @if($projects->isEmpty())
        <div class="p-8 text-center">
            <span class="w-12 h-12 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400 mx-auto"><i class="fas fa-search"></i></span>
            <h2 class="text-sm font-medium text-slate-800 mt-3">No public projects found</h2>
            <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">Try adjusting your filters or check back later.</p>
        </div>
        @else
        <div class="px-4 py-2 border-b border-slate-100">
            <p class="text-xs text-slate-500">{{ $projects->total() }} project{{ $projects->total() !== 1 ? 's' : '' }}</p>
        </div>
        <ul class="divide-y divide-slate-100">
            @foreach($projects as $project)
            <li class="px-4 py-4 hover:bg-slate-50/80 transition-colors">
                <div class="flex flex-col gap-1 min-w-0">
                    <h3 class="text-sm font-medium text-slate-800 truncate">{{ $project->title }}</h3>
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-slate-500">
                        <span>{{ $project->group?->name ?? '—' }}</span>
                        <span>·</span>
                        <span>{{ $project->academicYear?->year ?? '—' }}</span>
                        @if($project->budget)
                            <span>·</span>
                            <span>{{ number_format($project->budget, 2) }}</span>
                        @endif
                    </div>
                    <p class="text-sm text-slate-600 mt-1.5 break-words">{{ Str::limit($project->description, 200) }}</p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        @if($project->category)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-indigo-50 text-indigo-700">
                                {{ $project->category->name }}
                            </span>
                        @endif
                        @if($project->features->isNotEmpty())
                            <span class="text-xs text-slate-500 truncate">{{ $project->features->pluck('name')->take(3)->implode(', ') }}{{ $project->features->count() > 3 ? '…' : '' }}</span>
                        @endif
                    </div>
                    @if($project->supervisors->isNotEmpty())
                        <p class="text-xs text-slate-500 mt-0.5 truncate">Supervisors: {{ $project->supervisors->map(fn($u) => $u->name ?? $u->username)->implode(', ') }}</p>
                    @endif
                </div>
            </li>
            @endforeach
        </ul>
        @if($projects->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $projects->links() }}
            </div>
        @endif
        @endif
    </div>
</section>
@endsection
