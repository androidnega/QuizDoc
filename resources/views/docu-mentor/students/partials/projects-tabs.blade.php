{{-- Shared tab navigation for Projects section. Tailwind only. --}}
<nav class="flex flex-wrap items-center gap-2 border-b border-slate-200 pb-3 mb-6" aria-label="Projects">
    <a href="{{ route('dashboard.projects.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('dashboard.projects.*') && !request()->routeIs('dashboard.projects.create') ? 'bg-blue-100 text-blue-800' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50' }}">
        <i class="fas fa-folder-open text-xs"></i>
        <span class="truncate">My Projects</span>
    </a>
    <a href="{{ route('dashboard.public-projects') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('dashboard.public-projects') ? 'bg-blue-100 text-blue-800' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50' }}">
        <i class="fas fa-search text-xs"></i>
        <span class="truncate">Public Projects</span>
    </a>
    @if($isClassRep ?? false)
    <a href="{{ route('dashboard.class-results.index') }}" class="ml-auto inline-flex items-center justify-center px-3 py-2 rounded-lg text-sm font-medium bg-slate-600 text-white hover:bg-slate-700 shrink-0">
        <i class="fas fa-file-download mr-1"></i>Class Results
    </a>
    @elseif($isGroupLeader ?? false)
    @if(isset($projects) && $projects->isNotEmpty() && $projects->first()->group)
    <a href="{{ route('dashboard.group.show', $projects->first()->group) }}" class="ml-auto inline-flex items-center justify-center px-3 py-2 rounded-lg text-sm font-medium bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 shrink-0">
        <i class="fas fa-users mr-1"></i>Manage group
    </a>
    @endif
    @php $leaderHasProject = $leaderHasProject ?? false; @endphp
    @if($leaderHasProject)
    <div class="ml-auto inline-flex items-center justify-center px-3 py-2 rounded-lg text-sm font-medium bg-slate-100 border border-slate-200 text-slate-400 cursor-not-allowed opacity-70" aria-disabled="true">
        <i class="fas fa-lock mr-1"></i>Project created
    </div>
    @else
    <a href="{{ route('dashboard.projects.create') }}" class="ml-auto inline-flex items-center justify-center px-3 py-2 rounded-lg text-sm font-medium bg-slate-600 text-white hover:bg-slate-700 shrink-0">
        <i class="fas fa-plus mr-1"></i>Create Project
    </a>
    @endif
    @endif
</nav>
