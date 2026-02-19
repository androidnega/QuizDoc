@extends('layouts.dashboard')

@section('title', $project->title)
@section('dashboard_heading', 'Project')
@section('breadcrumb_trail')
<a href="{{ route('dashboard.coordinators.projects.index') }}" class="hover:text-primary-600">Projects</a>
<svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
<span class="text-gray-900 font-medium truncate">{{ Str::limit($project->title, 40) }}</span>
@endsection

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6">
    @if(session('success'))<div class="rounded-lg border border-success-200 bg-success-50 p-3 text-sm text-success-800">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}</div>@endif

    {{-- Project details --}}
    <div class="rounded-lg border border-gray-200 bg-white p-6">
        <div class="flex items-center justify-between gap-2 mb-4">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Project details</h2>
                <p class="text-xs text-gray-500 mt-0.5">Snapshot of the student’s project as submitted.</p>
            </div>
            @if($project->category)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700 shrink-0">
                    {{ $project->category->name }}
                </span>
            @endif
        </div>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
            <div class="rounded-lg border border-primary-100 bg-primary-50/60 px-3 py-2.5">
                <dt class="text-xs font-medium text-primary-700 uppercase tracking-wide">Title</dt>
                <dd class="mt-1 text-sm font-medium text-gray-900">{{ $project->title }}</dd>
            </div>
            <div class="rounded-lg border border-indigo-100 bg-indigo-50/60 px-3 py-2.5">
                <dt class="text-xs font-medium text-indigo-700 uppercase tracking-wide">Group</dt>
                <dd class="mt-1 text-sm text-gray-800">{{ $project->group?->name ?? '—' }}</dd>
            </div>
            <div class="rounded-lg border border-sky-100 bg-sky-50/60 px-3 py-2.5">
                <dt class="text-xs font-medium text-sky-700 uppercase tracking-wide">Academic year</dt>
                <dd class="mt-1 text-sm text-gray-800">{{ $project->academicYear?->year ?? '—' }}</dd>
            </div>
            <div class="rounded-lg border border-emerald-100 bg-emerald-50/60 px-3 py-2.5">
                <dt class="text-xs font-medium text-emerald-700 uppercase tracking-wide">Budget</dt>
                <dd class="mt-1 text-sm text-gray-800">{{ $project->budget !== null ? number_format($project->budget, 2) : '—' }}</dd>
            </div>
            <div class="rounded-lg border border-amber-100 bg-amber-50/60 px-3 py-2.5">
                <dt class="text-xs font-medium text-amber-700 uppercase tracking-wide">Status</dt>
                <dd class="mt-1 flex flex-wrap items-center gap-1.5">
                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-md {{ $project->approved ? 'bg-success-100 text-success-800' : 'bg-amber-200/80 text-amber-800' }}">
                        {{ $project->approved ? 'Approved' : 'Pending' }}
                    </span>
                    @if($project->status)
                        <span class="text-xs text-gray-600">{{ $project->status }}</span>
                    @endif
                </dd>
            </div>
            <div class="rounded-lg border border-violet-100 bg-violet-50/60 px-3 py-2.5">
                <dt class="text-xs font-medium text-violet-700 uppercase tracking-wide">Submission deadline</dt>
                <dd class="mt-1 text-sm text-gray-800">
                    {{ ($project->submission_deadline ?? $project->academicYear?->effective_deadline)?->format('M j, Y') ?? '—' }}
                </dd>
            </div>
        </dl>
        @if($project->description)
            <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2.5 sm:col-span-2">
                <dt class="text-xs font-medium text-slate-600 uppercase tracking-wide">Description</dt>
                <dd class="mt-1 text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $project->description }}</dd>
            </div>
        @endif
    </div>

    {{-- Features --}}
    <div class="rounded-lg border border-gray-200 bg-white p-6">
        <div class="mb-4">
            <h2 class="text-sm font-semibold text-gray-900">Features</h2>
            <p class="text-xs text-gray-500 mt-0.5">Project features and requirements.</p>
        </div>
        @if($project->features->isEmpty())
            <p class="text-gray-500 text-sm rounded-lg bg-gray-50 px-3 py-2.5 border border-gray-100">No features listed.</p>
        @else
            <ul class="space-y-0 rounded-lg border border-gray-100 divide-y divide-gray-100 overflow-hidden">
                @foreach($project->features as $f)
                    <li class="flex flex-wrap items-start gap-2 px-3 py-2.5 bg-white hover:bg-gray-50/80 transition-colors">
                        <span class="text-sm font-medium text-gray-900">{{ $f->name }}</span>
                        @if($f->description)
                            <span class="text-sm text-gray-500">— {{ $f->description }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Assign Supervisors --}}
    <div id="assign-supervisors" class="rounded-lg border border-gray-200 bg-white p-6">
        <div class="mb-4">
            <h2 class="text-sm font-semibold text-gray-900">Assign Supervisors</h2>
            <p class="text-xs text-gray-500 mt-0.5">Assigning at least one supervisor approves the project and auto-creates 6 chapters.</p>
        </div>
        <form action="{{ route('dashboard.coordinators.projects.update', $project) }}" method="post" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid md:grid-cols-2 gap-4 rounded-lg border border-gray-200 bg-gray-50/50 p-4">
                <div>
                    <label for="submission_deadline" class="block text-xs font-medium text-gray-600 mb-1">Submission deadline</label>
                    <input
                        type="datetime-local"
                        name="submission_deadline"
                        id="submission_deadline"
                        value="{{ old('submission_deadline', ($project->submission_deadline ?? $project->academicYear?->effective_deadline)?->format('Y-m-d\TH:i')) }}"
                        class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none"
                    >
                </div>
                <div>
                    <label for="supervisor_ids" class="block text-xs font-medium text-gray-600 mb-1">Supervisors</label>
                    <select
                        name="supervisor_ids[]"
                        id="supervisor_ids"
                        multiple
                        class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none min-h-[2.5rem]"
                    >
                        @foreach($supervisors as $s)
                            <option value="{{ $s->id }}" {{ $project->supervisors->contains($s) ? 'selected' : '' }}>
                                {{ $s->name ?? $s->username }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Hold Ctrl (Windows) or ⌘ (Mac) to select multiple.</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                >
                    Save changes
                </button>
                <form
                    action="{{ route('dashboard.coordinators.projects.alert', $project) }}"
                    method="post"
                    onsubmit="return confirm('Send SMS to group members and supervisors?');"
                    class="inline-flex"
                >
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                    >
                        Notify group &amp; supervisors via SMS
                    </button>
                </form>
            </div>
        </form>
    </div>

    {{-- Group Members --}}
    <div class="rounded-lg border border-gray-200 bg-white p-6">
        <div class="mb-4">
            <h2 class="text-sm font-semibold text-gray-900">Group Members</h2>
            @if($project->group)
                <p class="text-xs text-gray-500 mt-0.5">Leader: {{ $project->group->leader?->name ?? $project->group->leader?->username ?? '—' }}</p>
            @endif
        </div>
        @if($project->group)
            @if(($project->group->members ?? []) !== [])
                <ul class="space-y-3">
                    @foreach($project->group->members ?? [] as $m)
                        @php
                            $colors = [
                                ['bg' => 'bg-primary-50/60', 'border' => 'border-primary-100', 'label' => 'text-primary-700'],
                                ['bg' => 'bg-indigo-50/60', 'border' => 'border-indigo-100', 'label' => 'text-indigo-700'],
                                ['bg' => 'bg-sky-50/60', 'border' => 'border-sky-100', 'label' => 'text-sky-700'],
                                ['bg' => 'bg-emerald-50/60', 'border' => 'border-emerald-100', 'label' => 'text-emerald-700'],
                                ['bg' => 'bg-amber-50/60', 'border' => 'border-amber-100', 'label' => 'text-amber-700'],
                                ['bg' => 'bg-violet-50/60', 'border' => 'border-violet-100', 'label' => 'text-violet-700'],
                            ];
                            $style = $colors[$loop->index % 6];
                        @endphp
                        <li class="flex items-center justify-between gap-3 rounded-lg border px-3 py-2.5 {{ $style['border'] }} {{ $style['bg'] }} hover:opacity-95 transition-opacity">
                            <span class="text-sm font-medium text-gray-900">{{ $m->name ?? $m->username }}</span>
                            @if($m->id === $project->group->leader_id)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-primary-100 text-primary-800 shrink-0">Leader</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-gray-500 text-sm rounded-lg bg-slate-50/80 px-3 py-2.5 border border-slate-200">No members in this group.</p>
            @endif
        @else
            <p class="text-gray-500 text-sm rounded-lg bg-slate-50/80 px-3 py-2.5 border border-slate-200">No group.</p>
        @endif
    </div>

    {{-- Proposals — Max 3, PDF only, < 1MB. Coordinator comment; students see on dashboard. --}}
    <div id="proposals" class="rounded-lg border border-gray-200 bg-white p-6">
        <div class="mb-4">
            <h2 class="text-sm font-semibold text-gray-900">Proposals</h2>
            <p class="text-xs text-gray-500 mt-0.5">Max 3 per project · PDF only · &lt; 1MB. Comment on a proposal — students see your comment on their project dashboard.</p>
        </div>
        @if($project->proposals->isEmpty())
            <p class="text-gray-500 text-sm rounded-lg bg-gray-50 px-3 py-2.5 border border-gray-100">No proposals yet.</p>
        @else
            <ul class="space-y-3">
                @foreach($project->proposals as $p)
                    <li class="rounded-lg border border-gray-200 bg-gray-50/30 p-4 hover:bg-gray-50/50 transition-colors">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                            <span class="text-sm font-semibold text-gray-900">Version {{ $p->version_number }}</span>
                            <span class="text-xs text-gray-500">{{ $p->uploaded_at?->format('M j, Y') }}</span>
                        </div>
                        @if($p->comment)
                            <p class="text-xs text-gray-600 mb-2">Student note: {{ Str::limit($p->comment, 60) }}</p>
                        @endif
                        @if($p->coordinator_comment)
                            <div class="rounded-md bg-amber-50 border border-amber-100 px-3 py-2 mb-3">
                                <p class="text-xs font-medium text-amber-800 mb-0.5">Your comment</p>
                                <p class="text-sm text-amber-900">{{ Str::limit($p->coordinator_comment, 120) }}</p>
                            </div>
                        @endif
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('dashboard.docu-mentor.proposals.download', [$project, $p]) }}"
                               class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">
                                <i class="fas fa-file-pdf text-red-500"></i>
                                Open PDF
                            </a>
                            <button type="button"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1 comment-btn"
                                    data-proposal-id="{{ $p->id }}"
                                    data-current-comment="{{ e($p->coordinator_comment ?? '') }}">
                                <i class="fas fa-comment-dots"></i>
                                Comment
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Chapters --}}
    <div class="rounded-lg border border-gray-200 bg-white p-6">
        <div class="mb-4">
            <h2 class="text-sm font-semibold text-gray-900">Chapters</h2>
            <p class="text-xs text-gray-500 mt-0.5">{{ $project->chapters->where('completed', true)->count() }}/6 completed</p>
        </div>
        @if($project->chapters->isNotEmpty())
            <ul class="space-y-0 mb-4 rounded-lg border border-gray-100 divide-y divide-gray-100 overflow-hidden">
                @foreach($project->chapters as $ch)
                    <li class="flex items-center justify-between gap-3 px-3 py-2.5 bg-white hover:bg-gray-50/80 transition-colors">
                        <span class="text-sm text-gray-800">{{ $ch->order }}. {{ $ch->title }} <span class="text-gray-400">(max {{ $ch->max_score }} pts)</span>@if($ch->completed) <span class="text-success-600">✓</span>@endif</span>
                        <a href="{{ route('dashboard.docu-mentor.chapters.show', [$project, $ch]) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700 hover:underline shrink-0">View</a>
                    </li>
                @endforeach
            </ul>
        @endif
        @if($project->chapters->count() < 6 && !$project->approved)
            <p class="text-gray-500 text-sm rounded-lg bg-gray-50 px-3 py-2.5 border border-gray-100">Chapters are auto-created when a supervisor is assigned.</p>
        @elseif($project->chapters->count() < 6 && $project->approved)
            <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-4">
                <p class="text-xs font-medium text-gray-600 mb-3">Add Chapter</p>
                <form action="{{ route('dashboard.coordinators.projects.chapters.store', $project) }}" method="post" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="min-w-0 flex-1" style="min-width: 180px;">
                        <label for="chapter_title" class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                        <input type="text" name="title" id="chapter_title" placeholder="e.g. Environment Variables" required class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                    </div>
                    <div class="w-20">
                        <label for="chapter_order" class="block text-xs font-medium text-gray-600 mb-1">Order</label>
                        <input type="number" name="order" id="chapter_order" value="{{ ($project->chapters->max('order') ?? -1) + 1 }}" min="0" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                    </div>
                    <div class="w-24">
                        <label for="chapter_max_score" class="block text-xs font-medium text-gray-600 mb-1">Max pts</label>
                        <input type="number" name="max_score" id="chapter_max_score" value="100" min="0" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">Add Chapter</button>
                </form>
            </div>
        @endif
    </div>

    {{-- Delete project (coordinator when allowed by setting; Super Admin always) --}}
    @can('delete', $project)
    <div class="rounded-lg border border-red-200 bg-red-50/50 p-6">
        <h2 class="text-sm font-semibold text-red-800 mb-2">Delete project</h2>
        <p class="text-xs text-red-700 mb-3">This will permanently delete this project and all related data (chapters, submissions, proposals, scores). The group will remain.</p>
        <form action="{{ route('dashboard.coordinators.projects.destroy', $project) }}" method="post" onsubmit="return confirm('Delete this project and all its data? This cannot be undone.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn border border-red-400 text-red-700 bg-white hover:bg-red-50">Delete project</button>
        </form>
    </div>
    @else
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-6">
        <p class="text-sm text-gray-600">Only Super Admin can delete projects. Your administrator can allow coordinators to delete in Settings → General.</p>
    </div>
    @endcan
</div>

{{-- Modal for coordinator comments on a proposal (show page) --}}
<div id="coord-comment-modal-overlay" class="fixed inset-0 z-40 bg-black/40 hidden flex items-center justify-center px-4">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-gray-900">Comment on proposal</h2>
            <button type="button" id="coord-comment-modal-close" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <form id="coord-comment-modal-form" method="post" action="#">
            @csrf
            <div class="mb-3">
                <label for="coord-comment-modal-textarea" class="block text-xs font-medium text-gray-600 mb-1">
                    Coordinator comment (students see this on their project dashboard)
                </label>
                <textarea id="coord-comment-modal-textarea" name="coordinator_comment" rows="3"
                          class="w-full rounded border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500"></textarea>
            </div>
            <div class="flex items-center justify-end gap-2">
                <button type="button" id="coord-comment-modal-cancel"
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
    var overlay = document.getElementById('coord-comment-modal-overlay');
    var form = document.getElementById('coord-comment-modal-form');
    var textarea = document.getElementById('coord-comment-modal-textarea');
    var closeBtn = document.getElementById('coord-comment-modal-close');
    var cancelBtn = document.getElementById('coord-comment-modal-cancel');
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
        var proposalId = btn.getAttribute('data-proposal-id');
        var currentComment = btn.getAttribute('data-current-comment') || '';
        if (!proposalId) return;
        var base = "{{ url('/dashboard/coordinators/projects/' . $project->id . '/proposals') }}";
        var actionUrl = base + '/' + proposalId + '/comment';
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
