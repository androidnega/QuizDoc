{{-- 7 PROJECT PUBLIC PAGE – URL /projects. Display: Title, Description, Features, Budget, Supervisors. Filter by: Academic Year, Category, Supervisor. --}}
@extends('layouts.app')

@section('title', 'Projects – Docu Mentor')

@section('content')
<div class="min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <a href="{{ url('/') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-2 inline-block">← Home</a>
            <h1 class="text-2xl font-bold text-gray-900">Projects</h1>
            <p class="text-sm text-gray-500 mt-0.5">Browse approved projects. Filter by academic year, category, or supervisor.</p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
            {{-- Filters --}}
            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                <form method="get" action="{{ route('public.projects.index') }}" class="flex flex-wrap gap-3 items-end">
                    <div>
                        <label for="academic_year" class="block text-xs text-gray-600 mb-1">Academic Year</label>
                        <select name="academic_year" id="academic_year" class="w-full min-w-0 sm:w-[140px] rounded-lg border-gray-300 text-sm py-2">
                            <option value="">All</option>
                            @foreach($academicYears ?? [] as $ay)
                                <option value="{{ $ay->id }}" {{ request('academic_year') == $ay->id ? 'selected' : '' }}>{{ $ay->year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="category" class="block text-xs text-gray-600 mb-1">Category</label>
                        <select name="category" id="category" class="w-full min-w-0 sm:w-[140px] rounded-lg border-gray-300 text-sm py-2">
                            <option value="">All</option>
                            @foreach($categories ?? [] as $c)
                                <option value="{{ $c->id }}" {{ request('category') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="supervisor" class="block text-xs text-gray-600 mb-1">Supervisor</label>
                        <select name="supervisor" id="supervisor" class="w-full min-w-0 sm:w-[180px] rounded-lg border-gray-300 text-sm py-2">
                            <option value="">All</option>
                            @foreach($supervisors ?? [] as $s)
                                <option value="{{ $s->id }}" {{ request('supervisor') == $s->id ? 'selected' : '' }}>{{ $s->name ?? $s->username }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Filter</button>
                        @if(request()->hasAny(['academic_year','category','supervisor']))
                            <a href="{{ route('public.projects.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Clear</a>
                        @endif
                    </div>
                </form>
            </div>

            @if($projects->isEmpty())
                <div class="p-12 text-center">
                    <p class="text-gray-500">No projects found. Try adjusting the filters.</p>
                </div>
            @else
                <div class="px-4 py-2 border-b border-gray-100 bg-white">
                    <p class="text-sm text-gray-500">{{ $projects->total() }} project{{ $projects->total() !== 1 ? 's' : '' }}</p>
                </div>
                <ul class="divide-y divide-gray-100">
                    @foreach($projects as $project)
                    <li class="px-4 py-5 hover:bg-gray-50/50 transition-colors">
                        <h2 class="text-lg font-semibold text-gray-900">{{ $project->title }}</h2>
                        @if($project->description)
                            <p class="text-sm text-gray-600 mt-2 whitespace-pre-wrap">{{ Str::limit($project->description, 400) }}</p>
                        @endif
                        @if($project->features->isNotEmpty())
                            <div class="mt-2">
                                <span class="text-xs font-medium text-gray-500 uppercase">Features</span>
                                <ul class="mt-0.5 text-sm text-gray-700 list-disc list-inside space-y-0.5">
                                    @foreach($project->features as $f)
                                        <li>{{ $f->name }}{!! $f->description ? ' – ' . e(Str::limit($f->description, 80)) : '' !!}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-600">
                            @if($project->budget !== null && $project->budget !== '')
                                <span><span class="font-medium text-gray-500">Budget:</span> {{ number_format($project->budget, 2) }}</span>
                            @endif
                            @if($project->supervisors->isNotEmpty())
                                <span><span class="font-medium text-gray-500">Supervisors:</span> {{ $project->supervisors->map(fn($u) => $u->name ?? $u->username)->implode(', ') }}</span>
                            @endif
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                            <span>{{ $project->academicYear?->year ?? '—' }}</span>
                            @if($project->category)
                                <span>·</span>
                                <span>{{ $project->category->name }}</span>
                            @endif
                        </div>
                    </li>
                    @endforeach
                </ul>
                @if($projects->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">
                        {{ $projects->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
