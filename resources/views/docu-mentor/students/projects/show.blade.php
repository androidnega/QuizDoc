@extends('layouts.student-dashboard')

@section('title', $project->title)
@php $dashboardTitle = $project->title; @endphp

@section('dashboard_content')
<a href="{{ route('dashboard.projects.index') }}" class="text-sm text-slate-500 mb-4 inline-block hover:text-slate-800">← My projects</a>

<header class="flex flex-wrap items-start justify-between gap-2 mb-6">
    <div class="min-w-0">
        <h1 class="text-xl font-semibold text-slate-800 tracking-tight">{{ $project->title }}</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Group: {{ $project->group?->name }} · {{ $project->academicYear?->year ?? '—' }}
                @if($project->budget)
                    · Budget: {{ number_format($project->budget, 2) }}
                @endif
                @if($project->submission_deadline || $project->academicYear?->effective_deadline)
                    · Deadline: {{ ($project->submission_deadline ?? $project->academicYear?->effective_deadline)?->format('M j, Y') }}
                @endif
            </p>
            @if($project->supervisors->isNotEmpty())
                <p class="text-xs text-slate-500 mt-0.5">Supervisors: {{ $project->supervisors->map(fn($u) => $u->name ?? $u->username)->implode(', ') }}</p>
            @endif
            @if($project->category)
                <p class="mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-indigo-50 text-indigo-700">
                        {{ $project->category->name }}
                    </span>
                </p>
            @endif
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            @if($user->canLeadDocuMentorProjects())
                <a href="{{ route('dashboard.projects.create') }}" class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-sm font-medium bg-slate-600 text-white hover:bg-slate-700">
                    <i class="fas fa-plus mr-1 text-xs"></i>
                    Create project
                </a>
            @endif
            @if($project->group && $project->group->leader_id === $user->id)
                <a href="{{ route('dashboard.group.show', $project->group) }}" class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-sm font-medium bg-white border border-slate-300 text-slate-700 hover:bg-slate-50">Manage group</a>
            @endif
            @if($project->isFullyCompleted() && in_array($project->status, ['completed','graded']))
                <span class="px-3 py-1 rounded-lg text-xs font-medium bg-green-100 text-green-700">Completed</span>
            @endif
            <span class="px-3 py-1 rounded-lg text-xs font-medium {{ $project->approved ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                {{ $project->approved ? 'Approved' : 'Pending' }}
            </span>
            @if(($canEditBasics ?? false) && $project->group && $project->group->leader_id === $user->id)
                <span class="px-3 py-1 rounded-lg text-xs font-medium bg-blue-50 text-blue-700">
                    Editable draft
                </span>
            @endif
    </div>
</header>

<section class="mb-8">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
        <h2 class="text-sm font-medium text-slate-700 mb-3">Project dashboard</h2>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2 text-sm">
            <div><dt class="text-slate-500">Project</dt><dd class="font-medium text-slate-800">{{ $project->title }}</dd></div>
            <div><dt class="text-slate-500">Approval status</dt><dd><span class="font-medium {{ $project->approved ? 'text-emerald-700' : 'text-amber-700' }}">{{ $project->approved ? 'Approved' : 'Pending' }}</span></dd></div>
            <div><dt class="text-slate-500">Supervisor</dt><dd class="text-slate-800">{{ $project->supervisors->isNotEmpty() ? $project->supervisors->map(fn($u) => $u->name ?? $u->username)->implode(', ') : '—' }}</dd></div>
            <div><dt class="text-slate-500">Chapter progress</dt><dd class="text-slate-800">{{ $project->completedChaptersCount() }}/6 completed</dd></div>
            <div><dt class="text-slate-500">Open chapter</dt><dd class="text-slate-800">@php $openCh = $project->chapters->where('is_open', true)->first(); @endphp {{ $openCh ? $openCh->title : 'None' }}</dd></div>
            <div><dt class="text-slate-500">Proposal comments</dt><dd class="text-slate-800">{{ $project->proposals->contains(fn($p) => !empty($p->coordinator_comment)) ? 'See Proposals below' : 'None' }}</dd></div>
        </dl>
    </div>
</section>

@if(($canEditBasics ?? false) && $project->group && $project->group->leader_id === $user->id)
<section class="mb-8">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5 space-y-3">
        <div class="flex items-center justify-between gap-2">
            <h2 class="text-sm font-medium text-slate-700">Edit project details</h2>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-blue-50 text-blue-700">
                Draft — editable
            </span>
        </div>
        <p class="text-xs text-slate-500">You can update these details until a coordinator assigns a supervisor or adds a comment on your proposal.</p>
        <form action="{{ route('dashboard.projects.update', $project) }}" method="post" class="space-y-3">
            @csrf
            @method('PUT')
            <div>
                <label for="edit_title" class="block text-xs font-medium text-slate-600 mb-1 uppercase tracking-wide">Title</label>
                <input type="text" id="edit_title" name="title" required
                       value="{{ old('title', $project->title) }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
            </div>
            <div>
                <label for="edit_description" class="block text-xs font-medium text-slate-600 mb-1 uppercase tracking-wide">Description</label>
                <textarea id="edit_description" name="description" rows="4"
                          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
                          placeholder="Detailed explanation">{{ old('description', $project->description) }}</textarea>
            </div>
            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label for="edit_category_id" class="block text-xs font-medium text-slate-600 mb-1 uppercase tracking-wide">Category</label>
                    <select id="edit_category_id" name="category_id"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
                        <option value="">— None —</option>
                        @foreach($categories ?? [] as $c)
                            <option value="{{ $c->id }}" {{ old('category_id', $project->category_id) == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="edit_budget" class="block text-xs font-medium text-slate-600 mb-1 uppercase tracking-wide">Budget</label>
                    <input type="number" id="edit_budget" name="budget" min="0" step="0.01"
                           value="{{ old('budget', $project->budget) }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
                           placeholder="0.00">
                </div>
            </div>
            <div class="pt-1 flex items-center justify-end gap-2">
                <button type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium bg-slate-800 text-white hover:bg-slate-900">
                    Save changes
                </button>
            </div>
        </form>
    </div>
</section>
@elseif($project->description)
<section class="mb-8">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
        <h2 class="text-sm font-medium text-slate-700 mb-2">Description</h2>
        <p class="text-sm text-slate-500 whitespace-pre-wrap">{{ $project->description }}</p>
    </div>
</section>
@endif

<section class="mb-8">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
        <h2 class="text-sm font-medium text-slate-700 mb-3">Features</h2>
        @if($project->features->isEmpty())
            <p class="text-sm text-slate-500">No features yet.@if($project->group->leader_id === $user->id && !$project->approved) Add one below.@endif</p>
        @else
            <ul class="space-y-3 mb-4 divide-y divide-slate-100">
                @foreach($project->features as $f)
                    <li class="flex items-start justify-between gap-4 py-3 first:pt-0">
                        <div>
                            <span class="text-sm font-medium text-slate-800">{{ $f->name }}</span>
                            @if($f->description)
                                <p class="text-xs text-slate-500 mt-0.5">{{ $f->description }}</p>
                            @endif
                        </div>
                        @if($project->group->leader_id === $user->id && !$project->approved)
                        <form action="{{ route('dashboard.projects.features.destroy', [$project, $f]) }}" method="post" onsubmit="return confirm('Delete this feature?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 text-xs">Delete</button>
                        </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
        @if($project->group->leader_id === $user->id && !$project->approved)
        <form action="{{ route('dashboard.projects.features.store', $project) }}" method="post" class="flex flex-wrap gap-2 mt-3">
            @csrf
            <input type="text" name="name" placeholder="Feature name" required class="flex-1 min-w-0 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
            <input type="text" name="description" placeholder="Description (optional)" class="flex-1 min-w-0 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
            <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-semibold bg-amber-500 text-slate-900 hover:bg-amber-600 shrink-0">
                Add
            </button>
        </form>
        @endif
    </div>
</section>

<section class="mb-8">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
        <h2 class="text-sm font-medium text-slate-700 mb-3">Proposals</h2>
        @if($project->proposals->isEmpty())
            <p class="text-sm text-slate-500 mb-4">No proposals yet. Upload one below.</p>
        @else
            <ul class="space-y-2 mb-4 divide-y divide-slate-100">
                @foreach($project->proposals as $p)
                    <li class="py-3 first:pt-0">
                        <div class="flex items-center gap-2 text-sm flex-wrap">
                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-sm text-slate-800">Version {{ $p->version_number }}</span>
                            <a href="{{ route('dashboard.projects.proposals.download', [$project, $p]) }}" class="text-sm font-medium text-slate-700 hover:underline">Download</a>
                            <span class="text-xs text-slate-500">{{ $p->uploaded_at?->format('M j, Y') }}</span>
                            @if($p->comment)
                                <span class="text-xs text-slate-500">— {{ Str::limit($p->comment, 40) }}</span>
                            @endif
                        </div>
                        @if($p->coordinator_comment)
                            <p class="text-xs font-medium text-amber-800 mt-1 mb-0.5">Coordinator comment (on your dashboard):</p>
                            <p class="text-xs text-amber-700 bg-amber-50 p-2 rounded">{{ $p->coordinator_comment }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
        @if($project->group->leader_id === $user->id && !$project->approved && $project->proposals->count() < 3)
        <form action="{{ route('dashboard.projects.proposals.store', $project) }}" method="post" enctype="multipart/form-data" class="space-y-3 mt-2">
            @csrf
            <div class="flex flex-wrap gap-2 items-end">
                <div>
                    <label for="file" class="block text-xs text-slate-600 mb-1">PDF only, &lt; 1MB. Maximum 3 proposals per project.</label>
                    <input type="file" name="file" id="file" accept=".pdf" required
                        class="text-sm">
                </div>
                <div>
                    <label for="comment" class="block text-xs text-slate-600 mb-1">Comment (optional)</label>
                    <input type="text" name="comment" id="comment" placeholder="e.g. Initial draft"
                        class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
                </div>
                <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-semibold bg-amber-500 text-slate-900 hover:bg-amber-600">
                    Upload
                </button>
            </div>
            @error('file')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </form>
        @endif
    </div>
</section>

@if(($canAccessParent ?? false) && $project->parentProject)
<section class="mb-8">
        @php $parent = $project->parentProject; @endphp
        <div class="bg-white rounded-xl border border-slate-200 border-l-4 border-slate-400 shadow-sm p-4 sm:p-5">
            <h2 class="text-sm font-medium text-slate-700 mb-3">Previous project (tagged)</h2>
            <p class="text-xs text-slate-500 mb-3">{{ $parent->title }} · {{ $parent->academicYear?->year ?? '—' }}</p>
            @if($parent->proposals->isNotEmpty())
                <p class="text-xs font-medium text-slate-700 mb-1">Proposal</p>
                <ul class="space-y-1 mb-4">
                    @foreach($parent->proposals as $p)
                        <li>
                            <a href="{{ route('dashboard.projects.proposals.download', [$parent, $p]) }}" class="text-slate-700 hover:text-slate-900 text-sm font-medium">Version {{ $p->version_number }} — {{ $p->uploaded_at?->format('M j, Y') }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
            @if($parent->chapters->isNotEmpty())
                @php $ch6 = $parent->chapters->first(); @endphp
                @if($ch6 && $ch6->submissions->isNotEmpty())
                    <p class="text-xs font-medium text-slate-700 mb-1">Chapter 6 submissions</p>
                    <ul class="space-y-1 text-sm text-slate-600">
                        @foreach($ch6->submissions as $s)
                            <li>{{ $s->uploaded_at?->format('M j, Y') }} — {{ basename($s->file ?? '') }}</li>
                        @endforeach
                    </ul>
                @endif
            @endif
        </div>
</section>
@endif

{{-- Grading results --}}
    @if(($project->isFullyCompleted() || in_array($project->status, ['completed','graded'])) && $project->group)
        @php
            $myScores = $project->studentScores->where('student_id', $user->id);
            $finalAvg = $project->getFinalScoreForStudent($user->id);
        @endphp
        @if($myScores->isNotEmpty())
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
            <h2 class="text-sm font-medium text-slate-700 mb-1">Grading results</h2>
            <p class="text-sm text-slate-500 mb-4">Each supervisor’s score, final average score, and remarks.</p>
            <ul class="space-y-3 divide-y divide-slate-100">
                @foreach($myScores as $s)
                    <li class="border border-slate-200 rounded-lg p-3 first:pt-0">
                        <p class="text-sm font-medium text-slate-800">{{ $s->supervisor?->name ?? $s->supervisor?->username ?? 'Supervisor' }}</p>
                        <div class="mt-2 flex flex-wrap gap-4 text-xs text-slate-600">
                            <span>Doc score: {{ $s->document_score ?? '—' }}</span>
                            <span>System score: {{ $s->system_score ?? '—' }}</span>
                            <span>Total: {{ ($s->document_score ?? 0) + ($s->system_score ?? 0) }}/100</span>
                        </div>
                        @if($s->remarks)
                            <p class="text-xs text-slate-600 mt-2"><span class="font-medium">Remarks:</span> {{ $s->remarks }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
            @if($finalAvg !== null)
                <p class="mt-4 text-sm font-medium text-slate-800">Final average score: {{ $finalAvg }}/100</p>
            @endif
        </div>
        @endif
    @endif

@if($project->chapters->isNotEmpty())
<section class="mb-8">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
            <h2 class="text-sm font-medium text-slate-700 mb-4">Chapters ({{ $project->completedChaptersCount() }}/6 completed)</h2>
            @php $openChapter = $project->chapters->where('is_open', true)->first(); @endphp
            @if($openChapter)
                <p class="text-xs text-emerald-700 font-medium mb-3">Currently open for submission: {{ $openChapter->title }}</p>
            @endif
            <ul class="space-y-3 divide-y divide-slate-100">
                @foreach($project->chapters as $ch)
                    <li class="border border-slate-200 rounded-lg p-3 first:pt-0">
                        <div class="flex items-center gap-2 text-sm mb-2">
                            <span class="text-sm font-medium text-slate-800">{{ $ch->title }}</span>
                            <span class="text-xs text-slate-500">(max {{ $ch->max_score }} pts)</span>
                            @if($ch->is_open)
                                <span class="text-xs text-emerald-600">Open for submission</span>
                            @else
                                <span class="text-xs text-slate-400">Closed</span>
                            @endif
                            @if($ch->completed)
                                <span class="text-xs font-medium text-slate-600">Completed</span>
                            @endif
                        </div>
                        {{-- Submission history (members can view) --}}
                        @if($ch->submissions->isNotEmpty())
                            <div class="mb-3">
                                <p class="text-xs font-medium text-slate-600 mb-1">Submission history</p>
                                <ul class="text-xs text-slate-600 space-y-0.5">
                                    @foreach($ch->submissions as $sub)
                                        <li>{{ $sub->uploadedBy?->name ?? $sub->uploadedBy?->username ?? 'Unknown' }} · {{ $sub->submitted_at?->format('M j, Y H:i') }}{!! $sub->comment ? ' · ' . e(Str::limit($sub->comment, 40)) : '' !!}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if($ch->is_open)
                            @php $canUploadCh6 = $ch->order === 6 && ($project->group->leader_id === $user->id || $project->supervisors->contains('id', $user->id)); @endphp
                            @if($ch->order !== 6 || $canUploadCh6)
                            <form action="{{ route('dashboard.projects.submissions.store', [$project, $ch]) }}" method="post" enctype="multipart/form-data" class="flex gap-2 items-end">
                                @csrf
                                <input type="file" name="file" accept="{{ $ch->order === 6 ? '.zip' : '.pdf,.docx,.txt' }}" required class="text-sm">
                                <input type="text" name="comment" placeholder="Comment" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
                                <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium bg-slate-600 text-white hover:bg-slate-700">Upload</button>
                            </form>
                            <p class="text-xs text-slate-500 mt-1">{{ $ch->order === 6 ? 'ZIP (Ch6: Leader & Supervisor only)' : 'PDF, DOCX, TXT max 1MB' }}</p>
                            @endif
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
</section>
@endif
@endsection
