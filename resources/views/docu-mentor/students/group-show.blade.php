@extends('layouts.student-dashboard')

@section('title', 'Group: ' . $group->name)
@php $dashboardTitle = 'Group: ' . $group->name; @endphp

@section('dashboard_content')
<header class="mb-6">
    <h1 class="text-xl font-semibold text-slate-800 tracking-tight">{{ $group->name }}</h1>
    <p class="text-sm text-slate-500 mt-1">Add members by phone below. You are the group leader and already in this group.</p>
    <p class="text-xs text-slate-500 mt-0.5">Academic year: {{ $group->academicYear?->year ?? '—' }}</p>
</header>

@if(session('success') || session('error') || session('info'))
<section class="mb-6" aria-label="Notice">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
        @if(session('success'))<p class="text-sm font-medium text-slate-800">{{ session('success') }}</p>@endif
        @if(session('error'))<p class="text-sm font-medium text-red-600">{{ session('error') }}</p>@endif
        @if(session('info'))<p class="text-sm text-slate-500">{{ session('info') }}</p>@endif
    </div>
</section>
@endif

<section class="mb-8">
    <h2 class="text-sm font-medium text-slate-700 mb-3">Members</h2>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        @foreach($group->members as $m)
        <div class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5 sm:py-4 border-b border-slate-100 last:border-b-0">
            <div class="min-w-0 flex-1">
                <span class="text-sm font-medium text-slate-800 block truncate">{{ $m->name ?? $m->username }}</span>
                @if($m->id === $group->leader_id)
                <span class="text-xs font-medium text-slate-500 uppercase tracking-wide mt-0.5 inline-block">Leader</span>
                @endif
            </div>
            @if($user->id === $group->leader_id && !$group->project && $m->id !== $group->leader_id)
            <form action="{{ route('dashboard.group.remove-member', [$group, $m]) }}" method="post" class="shrink-0" onsubmit="return confirm('Remove this member from the group?');">
                @csrf
                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-white border border-slate-300 text-slate-700 hover:bg-slate-50">Remove</button>
            </form>
            @endif
        </div>
        @endforeach
    </div>
</section>

@if($user->id === $group->leader_id && !$group->project)
<section class="mb-8">
    <h2 class="text-sm font-medium text-slate-700 mb-3">Add member</h2>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
        <form action="{{ route('dashboard.group.add-member') }}" method="post" class="flex flex-wrap items-end gap-2">
            @csrf
            <input type="hidden" name="group_id" value="{{ $group->id }}">
            <div class="min-w-0 flex-1">
                <label for="phone" class="text-xs font-medium text-slate-500 uppercase tracking-wide block mb-1">Add member by phone</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone') }}" required placeholder="e.g. 0241234567" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
            </div>
            <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium bg-slate-600 text-white hover:bg-slate-700 min-h-[44px] sm:min-h-0">Add member</button>
        </form>
    </div>
</section>
@endif
@endsection
