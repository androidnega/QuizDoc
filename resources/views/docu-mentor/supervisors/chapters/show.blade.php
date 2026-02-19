@extends('docu-mentor.layout')

@section('title', $chapter->title . ' – Docu Mentor')

@section('content')
<div class="max-w-6xl mx-auto w-full pt-4 sm:pt-6 pb-10">
    {{-- Back + header --}}
    <div class="mb-6">
        <a href="{{ route('dashboard.docu-mentor.projects.show', $project) }}" class="inline-flex items-center gap-1.5 text-sm text-indigo-600 hover:text-indigo-800 mb-3">
            <span aria-hidden="true">←</span> {{ $project->title }}
        </a>
        <h1 class="text-2xl font-bold text-slate-900">{{ $chapter->title }}</h1>
        <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
            <span class="text-slate-500">Max score: <strong class="text-slate-700">{{ $chapter->max_score }}</strong></span>
            <span class="text-slate-300">·</span>
            <span class="{{ $chapter->is_open ? 'text-emerald-600' : 'text-slate-500' }}">{{ $chapter->is_open ? 'Open' : 'Closed' }}</span>
            <span class="text-slate-300">·</span>
            <span class="{{ $chapter->completed ? 'text-indigo-600' : 'text-slate-500' }}">{{ $chapter->completed ? 'Completed' : 'In progress' }}</span>
        </div>
    </div>

    {{-- Chapter controls --}}
    <section class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
        <h2 class="text-sm font-semibold text-slate-800 uppercase tracking-wide mb-4">Chapter controls</h2>
        <div class="flex flex-wrap gap-3">
            <form action="{{ route('dashboard.docu-mentor.chapters.toggle-open', [$project, $chapter->order]) }}" method="post" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">
                    {{ $chapter->is_open ? 'Close for submission' : 'Open for submission' }}
                </button>
            </form>
            <form action="{{ route('dashboard.docu-mentor.chapters.mark-completed', [$project, $chapter->order]) }}" method="post" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">
                    {{ $chapter->completed ? 'Mark in progress' : 'Complete chapter' }}
                </button>
            </form>
            <form action="{{ route('dashboard.docu-mentor.chapters.toggle-submissions', [$project, $chapter->order]) }}" method="post" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">
                    Toggle all submissions
                </button>
            </form>
        </div>

        {{-- Edit chapter form --}}
        <form action="{{ route('dashboard.docu-mentor.chapters.update', [$project, $chapter->order]) }}" method="post" class="mt-6 pt-6 border-t border-slate-200 min-w-0">
            @csrf
            @method('PUT')
            <h3 class="text-sm font-semibold text-slate-800 uppercase tracking-wide mb-4">Edit chapter details</h3>
            <div class="flex flex-wrap items-end gap-4">
                <div class="min-w-0 flex-1 basis-40">
                    <label for="ch_title" class="block text-xs font-medium text-slate-600 uppercase tracking-wide mb-1.5">Title</label>
                    <input id="ch_title" type="text" name="title" value="{{ old('title', $chapter->title) }}" required
                        class="w-full min-w-0 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div class="w-20 shrink-0">
                    <label for="ch_order" class="block text-xs font-medium text-slate-600 uppercase tracking-wide mb-1.5">Order</label>
                    <input id="ch_order" type="number" name="order" value="{{ old('order', $chapter->order) }}" min="0"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div class="w-24 shrink-0">
                    <label for="ch_max_score" class="block text-xs font-medium text-slate-600 uppercase tracking-wide mb-1.5">Max score</label>
                    <input id="ch_max_score" type="number" name="max_score" value="{{ old('max_score', $chapter->max_score) }}" min="0"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div class="flex flex-wrap items-center gap-4 shrink-0">
                    <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer whitespace-nowrap">
                        <input type="hidden" name="is_open" value="0">
                        <input type="checkbox" name="is_open" value="1" {{ $chapter->is_open ? 'checked' : '' }}
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Open
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer whitespace-nowrap">
                        <input type="hidden" name="completed" value="0">
                        <input type="checkbox" name="completed" value="1" {{ $chapter->completed ? 'checked' : '' }}
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Completed
                    </label>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 whitespace-nowrap">
                        Save
                    </button>
                </div>
            </div>
        </form>
    </section>

    {{-- Submissions --}}
    <section class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
        <h2 class="text-sm font-semibold text-slate-800 uppercase tracking-wide mb-4">Submissions</h2>

        <form action="{{ route('dashboard.docu-mentor.submissions.store', [$project, $chapter->order]) }}" method="post" enctype="multipart/form-data" class="space-y-4 p-4 rounded-lg bg-slate-50 border border-slate-200 mb-6">
            @csrf
            <p class="text-xs text-slate-600">{{ $chapter->order === 6 ? 'ZIP only (no size limit).' : 'PDF, DOCX or TXT, max 1MB.' }}</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 items-end">
                <div class="lg:col-span-5">
                    <label for="sub_file" class="block text-xs font-medium text-slate-600 uppercase tracking-wide mb-1.5">File</label>
                    <input id="sub_file" type="file" name="file" accept="{{ $chapter->order === 6 ? '.zip' : '.pdf,.docx,.txt' }}" required
                        class="w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                </div>
                <div class="lg:col-span-4">
                    <label for="sub_comment" class="block text-xs font-medium text-slate-600 uppercase tracking-wide mb-1.5">Comment (optional)</label>
                    <input id="sub_comment" type="text" name="comment" placeholder="Brief comment"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div class="lg:col-span-3">
                    <button type="submit" class="w-full sm:w-auto px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Add submission
                    </button>
                </div>
            </div>
        </form>

        @if($chapter->submissions->isEmpty())
        <div class="rounded-lg border border-slate-200 border-dashed bg-slate-50/50 py-10 text-center">
            <p class="text-slate-500 text-sm">No submissions yet.</p>
            <p class="text-slate-400 text-xs mt-1">Use the form above to add one.</p>
        </div>
    @else
        <ul class="space-y-4">
            @foreach($chapter->submissions as $sub)
                <li class="p-4 rounded-xl border border-slate-200 bg-white space-y-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <span class="font-medium text-slate-900">Submission #{{ $sub->id }}</span>
                            <span class="text-slate-500 text-sm ml-2">{{ $sub->submitted_at?->format('M j, Y') }}</span>
                            @if($sub->score !== null)
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-sm">Score: {{ $sub->score }}</span>
                            @endif
                            @if($sub->comment)
                                <p class="text-sm text-slate-600 mt-1">{{ $sub->comment }}</p>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-2 items-center">
                            @php $aiCount = $sub->aiReviews->count(); @endphp
                            <form action="{{ route('dashboard.docu-mentor.ai.review-submission', [$project, $chapter->order, $sub]) }}" method="post" class="inline">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-lg border border-slate-300 text-indigo-600 text-sm hover:bg-indigo-50" {{ $aiCount >= 2 ? 'disabled' : '' }}>Review with AI</button>
                            </form>
                            <span class="text-xs text-slate-500">({{ $aiCount }}/2)</span>
                            <form action="{{ route('dashboard.docu-mentor.submissions.destroy', [$project, $chapter->order, $sub]) }}" method="post" class="inline" onsubmit="return confirm('Delete this submission?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1.5 rounded-lg border border-slate-300 text-red-600 text-sm hover:bg-red-50">Delete</button>
                            </form>
                        </div>
                    </div>
                    <form action="{{ route('dashboard.docu-mentor.submissions.update', [$project, $chapter->order, $sub]) }}" method="post" class="flex flex-wrap gap-3 items-end pt-3 border-t border-slate-100">
                        @csrf
                        @method('PUT')
                        <div class="w-24">
                            <label class="block text-xs font-medium text-slate-600 mb-1">Score</label>
                            <input type="number" name="score" value="{{ $sub->score }}" placeholder="0" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div class="flex-1 min-w-[160px]">
                            <label class="block text-xs font-medium text-slate-600 mb-1">Comment</label>
                            <input type="text" name="comment" value="{{ $sub->comment }}" placeholder="Comment" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="hidden" name="is_open" value="0">
                            <input type="checkbox" name="is_open" value="1" {{ $sub->is_open ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            Open
                        </label>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-medium hover:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Update</button>
                    </form>
                    @if($sub->aiReviews->isNotEmpty())
                        <div class="mt-3 pt-3 border-t border-slate-200 space-y-3">
                            <h4 class="text-xs font-semibold text-slate-600 uppercase tracking-wide">AI Reviews ({{ $sub->aiReviews->count() }})</h4>
                            @foreach($sub->aiReviews as $review)
                                @php $out = $review->ai_output ?? []; @endphp
                                <div class="bg-indigo-50 rounded-lg p-3 text-sm space-y-2 border border-indigo-100">
                                    @if(!empty($out['strengths']))<p><span class="font-medium text-slate-700">Strengths:</span> {{ $out['strengths'] }}</p>@endif
                                    @if(!empty($out['weaknesses']))<p><span class="font-medium text-slate-700">Weaknesses:</span> {{ $out['weaknesses'] }}</p>@endif
                                    @if(!empty($out['improvements']))<p><span class="font-medium text-slate-700">Improvements:</span> {{ $out['improvements'] }}</p>@endif
                                    @if(isset($out['score_suggestion']) && $out['score_suggestion'] !== '')<p><span class="font-medium text-slate-700">Score suggestion:</span> {{ $out['score_suggestion'] }}</p>@endif
                                    @if(!empty($out['raw']) && empty($out['strengths']))<pre class="whitespace-pre-wrap text-slate-600">{{ $out['raw'] }}</pre>@endif
                                    <p class="text-xs text-slate-500">{{ $review->created_at?->format('M j, Y g:i A') }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
    </section>

    @if(session('ai_review'))
        <div class="bg-indigo-50 rounded-xl border border-indigo-200 p-6 mb-6">
            <h3 class="font-semibold text-indigo-900 mb-2">AI Review</h3>
            <pre class="text-sm text-slate-700 whitespace-pre-wrap font-sans">{{ session('ai_review') }}</pre>
        </div>
    @endif

    @if(session('ai_summary'))
        <div class="bg-indigo-50 rounded-xl border border-indigo-200 p-6 mb-6">
            <h3 class="font-semibold text-indigo-900 mb-2">Project Summary</h3>
            <p class="text-slate-700">{{ session('ai_summary') }}</p>
        </div>
    @endif
</div>
@endsection
