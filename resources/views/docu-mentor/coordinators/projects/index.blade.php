@extends('layouts.dashboard')

@section('title', 'Projects')
@section('dashboard_heading', 'Projects')

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6">
    <div class="flex flex-wrap items-center gap-4">
        <form method="get" action="{{ route('dashboard.coordinators.projects.index') }}" class="flex flex-wrap items-center gap-2">
            <label for="academic_year_id" class="text-sm text-gray-600">Academic year</label>
            <select name="academic_year_id" id="academic_year_id" class="rounded border-gray-300 text-sm py-1.5 px-2" onchange="this.form.submit()">
                <option value="">All years</option>
                @foreach($academicYears ?? [] as $ay)
                    <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>{{ $ay->year }}{{ $ay->is_active ? ' (active)' : '' }}</option>
                @endforeach
            </select>
        </form>
    </div>
    <div class="card overflow-hidden min-w-0 rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[500px] divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Group</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Year</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Budget</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Progress</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($projects as $project)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 text-sm font-medium text-gray-900 max-w-xs truncate">{{ $project->title }}</td>
                            <td class="px-3 py-2 text-sm text-gray-600 max-w-[10rem] truncate">{{ $project->group?->name }}</td>
                            <td class="px-3 py-2 text-sm text-gray-600 whitespace-nowrap">{{ $project->academicYear?->year ?? '—' }}</td>
                            <td class="px-3 py-2 text-sm text-gray-600">{{ $project->budget !== null ? number_format($project->budget, 2) : '—' }}</td>
                            <td class="px-3 py-2">
                                @php
                                    $chaptersTotal = max(1, $project->chapters?->count() ?? 0);
                                    $chaptersCompleted = $project->chapters?->where('completed', true)->count() ?? 0;
                                    $progressPercent = (int) round(($chaptersCompleted / $chaptersTotal) * 100);
                                @endphp
                                <div class="flex flex-col gap-1 w-28">
                                    <div class="h-1.5 w-full rounded-full bg-gray-100 overflow-hidden">
                                        <div
                                            class="h-1.5 rounded-full transition-all duration-300"
                                            style="width: {{ $progressPercent }}%; background: linear-gradient(to right, #9ca3af, #16a34a);"
                                        ></div>
                                    </div>
                                    <div class="flex items-center justify-between text-[11px] text-gray-500">
                                        <span>{{ $chaptersCompleted }}/{{ $chaptersTotal }} chapters</span>
                                        <span class="font-medium text-gray-700">{{ $progressPercent }}%</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full {{ $project->approved ? 'bg-success-100 text-success-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $project->approved ? 'Approved' : 'Pending' }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <div class="inline-flex items-center gap-2 justify-end">
                                    {{-- Quick look modal: group, year, budget, supervisors, proposals --}}
                                    <button type="button" class="quicklook-btn inline-flex items-center justify-center rounded-full p-1.5 text-slate-600 hover:text-slate-800 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-400"
                                            title="Quick look"
                                            data-quicklook-id="quicklook-{{ $project->id }}">
                                        <i class="fas fa-eye text-xs"></i>
                                    </button>
                                    {{-- Assign supervisor dropdown --}}
                                    <div class="relative inline-block group/dd">
                                        <button type="button" class="inline-flex items-center justify-center rounded-full p-1.5 text-primary-600 hover:text-primary-800 hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-primary-500"
                                                title="Assign supervisor"
                                                aria-haspopup="true" aria-expanded="false"
                                                data-dropdown-toggle="assign-dd-{{ $project->id }}">
                                            <i class="fas fa-user-tie text-xs"></i>
                                            <i class="fas fa-caret-down text-[10px] ml-0.5 opacity-70"></i>
                                        </button>
                                        <div id="assign-dd-{{ $project->id }}" class="hidden absolute right-0 top-full mt-1 z-20 min-w-[180px] rounded-lg border border-gray-200 bg-white shadow-lg py-1 text-left">
                                            <a href="{{ route('dashboard.coordinators.projects.show', $project) }}#assign-supervisors" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 whitespace-nowrap">
                                                Manage supervisors →
                                            </a>
                                            <div class="border-t border-gray-100 my-1"></div>
                                            <p class="px-3 py-1 text-xs font-medium text-gray-500 uppercase">Add supervisor</p>
                                            @php $availableSupervisors = ($supervisors ?? collect())->filter(fn($s) => !$project->supervisors->contains('id', $s->id)); @endphp
                                            @forelse($availableSupervisors as $sup)
                                                <form action="{{ route('dashboard.coordinators.projects.supervisors.store', $project) }}" method="post" class="block">
                                                    @csrf
                                                    <input type="hidden" name="supervisor_id" value="{{ $sup->id }}">
                                                    <button type="submit" class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                                        {{ $sup->name ?? $sup->username }}
                                                    </button>
                                                </form>
                                            @empty
                                                <p class="px-3 py-2 text-xs text-gray-500">No more supervisors to add</p>
                                            @endforelse
                                        </div>
                                    </div>

                                    {{-- Open full project page --}}
                                    <a href="{{ route('dashboard.coordinators.projects.show', $project) }}"
                                       class="inline-flex items-center justify-center rounded-full p-1.5 text-gray-600 hover:text-gray-800 hover:bg-gray-100"
                                       title="Open project details">
                                        <i class="fas fa-external-link-alt text-xs"></i>
                                    </a>

                                    @can('delete', $project)
                                    <form action="{{ route('dashboard.coordinators.projects.destroy', $project) }}" method="post" class="inline" onsubmit="return confirm('Delete this project and all its data? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center justify-center rounded-full p-1.5 text-red-600 hover:text-red-800 hover:bg-red-50" title="Delete project">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                    @endcan

                                    {{-- Quick comment on latest proposal (opens modal) --}}
                                    @php
                                        $latestProposal = $project->proposals->sortByDesc('uploaded_at')->first();
                                    @endphp
                                    @if($latestProposal)
                                    <button type="button"
                                            class="inline-flex items-center justify-center rounded-full p-1.5 text-amber-600 hover:text-amber-800 hover:bg-amber-50 comment-btn"
                                            title="Comment on latest proposal"
                                            data-project-id="{{ $project->id }}"
                                            data-proposal-id="{{ $latestProposal->id }}"
                                            data-current-comment="{{ e($latestProposal->coordinator_comment ?? '') }}">
                                        <i class="fas fa-comment-dots text-xs"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-8 text-center text-gray-500">No projects yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Hidden quick look content per project (used by modal) --}}
    @foreach($projects as $project)
    <div id="quicklook-{{ $project->id }}" class="hidden quicklook-content">
        <div class="space-y-4 text-left">
            <h3 class="text-sm font-semibold text-gray-900 border-b border-gray-100 pb-2">{{ Str::limit($project->title, 50) }}</h3>
            <div class="grid grid-cols-2 gap-3">
                <div><span class="text-xs font-medium text-gray-500 uppercase">Group</span><p class="text-sm font-medium text-gray-900 mt-0.5">{{ $project->group?->name ?? '—' }}</p></div>
                <div><span class="text-xs font-medium text-gray-500 uppercase">Year</span><p class="text-sm font-medium text-gray-900 mt-0.5">{{ $project->academicYear?->year ?? '—' }}</p></div>
                <div><span class="text-xs font-medium text-gray-500 uppercase">Budget</span><p class="text-sm font-medium text-gray-900 mt-0.5">{{ $project->budget !== null ? number_format($project->budget, 2) : '—' }}</p></div>
                <div><span class="text-xs font-medium text-gray-500 uppercase">Status</span><p class="text-sm mt-0.5"><span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full {{ $project->approved ? 'bg-success-100 text-success-800' : 'bg-amber-100 text-amber-800' }}">{{ $project->approved ? 'Approved' : 'Pending' }}</span></p></div>
            </div>
            <div>
                <span class="text-xs font-medium text-gray-500 uppercase">Supervisors</span>
                @if($project->supervisors && $project->supervisors->isNotEmpty())
                    <ul class="text-sm text-gray-900 mt-1 space-y-0.5">
                        @foreach($project->supervisors as $sup)
                            <li>{{ $sup->name ?? $sup->username }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-gray-500 mt-1">None assigned</p>
                @endif
            </div>
            <div>
                <span class="text-xs font-medium text-gray-500 uppercase">Proposals</span>
                @if($project->proposals && $project->proposals->isNotEmpty())
                    <ul class="text-sm text-gray-900 mt-1 space-y-1">
                        @foreach($project->proposals->sortByDesc('uploaded_at') as $proposal)
                            <li class="flex items-center justify-between gap-2">
                                <span>Version {{ $proposal->version_number }} — {{ $proposal->uploaded_at?->format('M j, Y') }}</span>
                                <a href="{{ route('dashboard.coordinators.projects.proposals.download', [$project, $proposal]) }}" class="text-primary-600 hover:text-primary-800 text-xs font-medium shrink-0">Download</a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-gray-500 mt-1">No proposals yet</p>
                @endif
            </div>
            <div class="pt-2 border-t border-gray-100 flex justify-end">
                <a href="{{ route('dashboard.coordinators.projects.show', $project) }}" class="text-sm font-medium text-primary-600 hover:text-primary-800">Open full details →</a>
            </div>
        </div>
    </div>
    @endforeach

    <div class="mt-4">{{ $projects->links() }}</div>
</div>

{{-- Quick look modal --}}
<div id="quicklook-modal-overlay" class="fixed inset-0 z-50 bg-black/40 hidden flex items-center justify-center px-4">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full max-h-[90vh] overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-900">Quick look</h2>
            <button type="button" id="quicklook-modal-close" class="rounded-full p-1.5 text-gray-500 hover:text-gray-700 hover:bg-gray-100">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <div id="quicklook-modal-body" class="p-5 overflow-y-auto flex-1">
            {{-- Filled by JS --}}
        </div>
    </div>
</div>

{{-- Modal for coordinator comments on latest proposal --}}
<div id="comment-modal-overlay" class="fixed inset-0 z-40 bg-black/40 hidden flex items-center justify-center px-4">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-gray-900">Comment on proposal</h2>
            <button type="button" id="comment-modal-close" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <form id="comment-modal-form" method="post" action="#">
            @csrf
            <div class="mb-3">
                <label for="comment-modal-textarea" class="block text-xs font-medium text-gray-600 mb-1">
                    Coordinator comment (students will see this on their project dashboard)
                </label>
                <textarea id="comment-modal-textarea" name="coordinator_comment" rows="3"
                          class="w-full rounded border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500"></textarea>
            </div>
            <div class="flex items-center justify-end gap-2">
                <button type="button" id="comment-modal-cancel"
                        class="px-3 py-1.5 rounded border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="px-3 py-1.5 rounded bg-primary-600 text-sm text-white hover:bg-primary-700">
                    Save comment
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var overlay = document.getElementById('comment-modal-overlay');
    var form = document.getElementById('comment-modal-form');
    var textarea = document.getElementById('comment-modal-textarea');
    var closeBtn = document.getElementById('comment-modal-close');
    var cancelBtn = document.getElementById('comment-modal-cancel');
    if (!overlay || !form || !textarea) return;

    function openModal(actionUrl, currentComment) {
        form.action = actionUrl;
        textarea.value = currentComment || '';
        overlay.classList.remove('hidden');
        textarea.focus();
    }

    function closeModal() {
        overlay.classList.add('hidden');
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.comment-btn');
        if (!btn) return;
        e.preventDefault();
        var projectId = btn.getAttribute('data-project-id');
        var proposalId = btn.getAttribute('data-proposal-id');
        var currentComment = btn.getAttribute('data-current-comment') || '';
        if (!projectId || !proposalId) return;
        var base = "{{ url('/dashboard/coordinators/projects') }}";
        var actionUrl = base + '/' + projectId + '/proposals/' + proposalId + '/comment';
        openModal(actionUrl, currentComment);
    });

    if (closeBtn) closeBtn.addEventListener('click', function (e) { e.preventDefault(); closeModal(); });
    if (cancelBtn) cancelBtn.addEventListener('click', function (e) { e.preventDefault(); closeModal(); });
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) {
            closeModal();
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay && !overlay.classList.contains('hidden')) closeModal();
    });
})();

(function () {
    var quicklookOverlay = document.getElementById('quicklook-modal-overlay');
    var quicklookBody = document.getElementById('quicklook-modal-body');
    var quicklookClose = document.getElementById('quicklook-modal-close');
    if (!quicklookOverlay || !quicklookBody) return;

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.quicklook-btn');
        if (!btn) return;
        e.preventDefault();
        var id = btn.getAttribute('data-quicklook-id');
        if (!id) return;
        var source = document.getElementById(id);
        if (!source) return;
        quicklookBody.innerHTML = source.innerHTML;
        quicklookOverlay.classList.remove('hidden');
    });

    function closeQuicklook() {
        quicklookOverlay.classList.add('hidden');
    }
    if (quicklookClose) quicklookClose.addEventListener('click', closeQuicklook);
    quicklookOverlay.addEventListener('click', function (e) {
        if (e.target === quicklookOverlay) closeQuicklook();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !quicklookOverlay.classList.contains('hidden')) closeQuicklook();
    });
})();

(function () {
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-dropdown-toggle]');
        var targetId = btn ? btn.getAttribute('data-dropdown-toggle') : null;
        if (targetId) {
            e.preventDefault();
            e.stopPropagation();
            var dd = document.getElementById(targetId);
            var open = dd && !dd.classList.contains('hidden');
            document.querySelectorAll('[id^="assign-dd-"]').forEach(function (el) { el.classList.add('hidden'); });
            if (dd && !open) dd.classList.remove('hidden');
            return;
        }
        if (!e.target.closest('[id^="assign-dd-"]')) {
            document.querySelectorAll('[id^="assign-dd-"]').forEach(function (el) { el.classList.add('hidden'); });
        }
    });
})();
</script>
@endpush
@endsection
