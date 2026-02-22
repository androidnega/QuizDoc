@extends('layouts.student-dashboard')

@section('title', 'Group: ' . $group->name)
@php $dashboardTitle = 'Group: ' . $group->name; @endphp

@section('dashboard_content')
<header class="mb-6">
    <h1 class="text-xl font-semibold text-slate-800 tracking-tight">{{ $group->name }}</h1>
    <p class="text-sm text-slate-500 mt-1">Academic year: {{ $group->academicYear?->year ?? '—' }}</p>
    @if($user->id === $group->leader_id)
    <p class="text-xs text-slate-500 mt-0.5">You are the group leader. Add members below.</p>
    @endif
</header>

@if(session('success') || session('error') || session('info'))
<section class="mb-6" aria-label="Notice">
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm {{ session('error') ? 'border-red-200 bg-red-50' : '' }}">
        @if(session('success'))<p class="text-sm font-medium text-slate-800">{{ session('success') }}</p>@endif
        @if(session('error'))<p class="text-sm font-medium text-red-600">{{ session('error') }}</p>@endif
        @if(session('info'))<p class="text-sm text-slate-600">{{ session('info') }}</p>@endif
    </div>
</section>
@endif

@if($user->id === $group->leader_id)
<section class="mb-8">
    <h2 class="text-sm font-medium text-slate-700 mb-3">Add member</h2>
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="p-5">
            <form action="{{ route('dashboard.group.add-member') }}" method="post" class="space-y-4">
                @csrf
                <input type="hidden" name="group_id" value="{{ $group->id }}">
                <div>
                    <label for="phone" class="block text-sm font-medium text-slate-700 mb-1.5">Phone number</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}" required placeholder="e.g. 0241234567" maxlength="20" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400" autocomplete="tel">
                    @error('phone')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    <p class="text-xs text-slate-500 mt-1">Enter the member’s phone number (used for their account).</p>
                </div>
                <button type="submit" class="inline-flex items-center justify-center px-5 py-2.5 rounded-lg text-sm font-medium bg-amber-500 text-slate-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-1">Add member</button>
            </form>
        </div>
    </div>
</section>
@endif

<section class="mb-8">
    <h2 class="text-sm font-medium text-slate-700 mb-3">Members</h2>
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        @forelse($group->members as $m)
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
        @empty
        <div class="px-4 py-6 text-center text-sm text-slate-500">No members yet.</div>
        @endforelse
    </div>
</section>
@endsection
