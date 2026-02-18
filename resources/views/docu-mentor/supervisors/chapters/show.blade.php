@extends('docu-mentor.layout')

@section('title', $chapter->title . ' – Docu Mentor')

@section('content')
<div class="max-w-5xl mx-auto w-full pt-4 sm:pt-6">
<div class="mb-6">
    <a href="{{ route('dashboard.docu-mentor.projects.show', $project) }}" class="text-indigo-600 hover:text-indigo-800 text-sm mb-2 inline-block">← {{ $project->title }}</a>
    <h1 class="text-2xl font-bold text-slate-900">{{ $chapter->title }}</h1>
    <p class="text-slate-500 text-sm mt-1">Max score: {{ $chapter->max_score }} · {{ $chapter->is_open ? 'Open' : 'Closed' }} · {{ $chapter->completed ? 'Completed' : 'In progress' }}</p>
</div>

{{-- Chapter controls --}}
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
    <h2 class="font-semibold text-slate-900 mb-4">Chapter Controls</h2>
    <div class="flex flex-wrap gap-2">
        <form action="{{ route('dashboard.docu-mentor.chapters.toggle-open', [$project, $chapter]) }}" method="post" class="inline">
            @csrf
            <button type="submit" class="px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-50 text-sm">
                {{ $chapter->is_open ? 'Close' : 'Open' }} for submission
            </button>
        </form>
        <form action="{{ route('dashboard.docu-mentor.chapters.mark-completed', [$project, $chapter]) }}" method="post" class="inline">
            @csrf
            <button type="submit" class="px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-50 text-sm">
                {{ $chapter->completed ? 'Mark in progress' : 'Complete Chapter' }}
            </button>
        </form>
        <form action="{{ route('dashboard.docu-mentor.chapters.toggle-submissions', [$project, $chapter]) }}" method="post" class="inline">
            @csrf
            <button type="submit" class="px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-50 text-sm">
                Toggle all submissions
            </button>
        </form>
    </div>

    <form action="{{ route('dashboard.docu-mentor.chapters.update', [$project, $chapter]) }}" method="post" class="mt-6 grid md:grid-cols-4 gap-4">
        @csrf
        @method('PUT')
        <div>
            <label class="block text-xs text-slate-600 mb-1">Title</label>
            <input type="text" name="title" value="{{ old('title', $chapter->title) }}" required class="w-full rounded border-slate-300 text-sm">
        </div>
        <div>
            <label class="block text-xs text-slate-600 mb-1">Order</label>
            <input type="number" name="order" value="{{ old('order', $chapter->order) }}" min="0" class="w-full rounded border-slate-300 text-sm">
        </div>
        <div>
            <label class="block text-xs text-slate-600 mb-1">Max Score</label>
            <input type="number" name="max_score" value="{{ old('max_score', $chapter->max_score) }}" min="0" class="w-full rounded border-slate-300 text-sm">
        </div>
        <div class="flex items-end gap-2">
            <label class="flex items-center gap-1 text-sm">
                <input type="checkbox" name="is_open" value="1" {{ $chapter->is_open ? 'checked' : '' }}>
                Open
            </label>
            <label class="flex items-center gap-1 text-sm">
                <input type="checkbox" name="completed" value="1" {{ $chapter->completed ? 'checked' : '' }}>
                Completed
            </label>
            <button type="submit" class="px-3 py-1.5 rounded bg-indigo-600 text-white text-sm">Save</button>
        </div>
    </form>
</div>

{{-- Submissions --}}
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
    <h2 class="font-semibold text-slate-900 mb-4">Submissions</h2>

    <form action="{{ route('dashboard.docu-mentor.submissions.store', [$project, $chapter]) }}" method="post" enctype="multipart/form-data" class="mb-6 flex gap-2 items-end">
        @csrf
        <div class="flex-1 min-w-0">
            <input type="file" name="file" accept="{{ $chapter->order === 6 ? '.zip' : '.pdf,.docx,.txt' }}" required class="text-sm w-full">
            <p class="text-xs text-slate-500 mt-1">{{ $chapter->order === 6 ? 'ZIP only (no size limit).' : 'PDF, DOCX or TXT, max 1MB.' }}</p>
        </div>
        <input type="text" name="comment" placeholder="Comment" class="rounded border-slate-300 text-sm flex-1 min-w-[120px]">
        <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm">Add Submission</button>
    </form>

    @if($chapter->submissions->isEmpty())
        <p class="text-slate-500 text-sm">No submissions yet.</p>
    @else
        <ul class="space-y-4">
            @foreach($chapter->submissions as $sub)
                <li class="p-4 rounded-lg border border-slate-200 space-y-3">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="font-medium">Submission #{{ $sub->id }}</span>
                            <span class="text-slate-500 text-sm ml-2">{{ $sub->submitted_at?->format('M j, Y') }}</span>
                            @if($sub->score !== null)
                                <span class="ml-2 px-2 py-0.5 rounded bg-slate-100 text-sm">Score: {{ $sub->score }}</span>
                            @endif
                            @if($sub->comment)
                                <p class="text-sm text-slate-600 mt-1">{{ $sub->comment }}</p>
                            @endif
                        </div>
                        <div class="flex gap-2 items-center">
                            @php $aiCount = $sub->aiReviews->count(); @endphp
                            <form action="{{ route('dashboard.docu-mentor.ai.review-submission', [$project, $chapter, $sub]) }}" method="post" class="inline">
                                @csrf
                                <button type="submit" class="text-indigo-600 hover:text-indigo-800 text-sm" {{ $aiCount >= 2 ? 'disabled' : '' }}>Review with AI</button>
                            </form>
                            <span class="text-xs text-slate-500">({{ $aiCount }}/2)</span>
                            <form action="{{ route('dashboard.docu-mentor.submissions.destroy', [$project, $chapter, $sub]) }}" method="post" class="inline" onsubmit="return confirm('Delete?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Delete</button>
                            </form>
                        </div>
                    </div>
                    <form action="{{ route('dashboard.docu-mentor.submissions.update', [$project, $chapter, $sub]) }}" method="post" class="flex flex-wrap gap-2 items-center">
                        @csrf
                        @method('PUT')
                        <input type="number" name="score" value="{{ $sub->score }}" placeholder="Score" min="0" class="w-20 rounded border-slate-300 text-sm">
                        <input type="text" name="comment" value="{{ $sub->comment }}" placeholder="Comment" class="rounded border-slate-300 text-sm flex-1 min-w-[120px]">
                        <label class="text-sm flex items-center gap-1"><input type="checkbox" name="is_open" value="1" {{ $sub->is_open ? 'checked' : '' }}> Open</label>
                        <button type="submit" class="px-3 py-1 rounded bg-slate-100 text-sm">Update</button>
                    </form>
                    @if($sub->aiReviews->isNotEmpty())
                        <div class="mt-3 pt-3 border-t border-slate-200 space-y-3">
                            <h4 class="text-xs font-semibold text-slate-600 uppercase">AI Reviews ({{ $sub->aiReviews->count() }})</h4>
                            @foreach($sub->aiReviews as $review)
                                @php $out = $review->ai_output ?? []; @endphp
                                <div class="bg-indigo-50 rounded-lg p-3 text-sm space-y-2">
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
</div>

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
