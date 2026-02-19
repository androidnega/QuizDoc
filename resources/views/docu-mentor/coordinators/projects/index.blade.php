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
                                    {{-- Manage / supervisor assignment --}}
                                    <a href="{{ route('dashboard.coordinators.projects.show', $project) }}#assign-supervisors"
                                       class="inline-flex items-center justify-center rounded-full p-1.5 text-primary-600 hover:text-primary-800 hover:bg-primary-50"
                                       title="Assign supervisors & manage project">
                                        <i class="fas fa-user-tie text-xs"></i>
                                    </a>

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
                            <td colspan="6" class="px-3 py-8 text-center text-gray-500">No projects yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $projects->links() }}</div>
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
        if (e.key === 'Escape') closeModal();
    });
})();
</script>
@endpush
@endsection
