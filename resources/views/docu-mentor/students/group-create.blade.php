@extends('layouts.student-dashboard')

@section('title', 'Name your group')
@php $dashboardTitle = 'Name your group'; @endphp

@section('dashboard_content')
<header class="mb-6">
    <h1 class="text-xl font-semibold text-slate-800 tracking-tight">Name your group</h1>
    <p class="text-sm text-slate-500 mt-1">Pick a name below, then add your first member by phone.</p>
</header>

<section class="mb-8">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5 max-w-md">
        @if(session('info'))
        <p class="mb-4 text-sm text-slate-500">{{ session('info') }}</p>
        @endif
        @if($errors->any())
        <ul class="mb-4 text-sm text-red-600 list-disc list-inside">
            @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
            @endforeach
        </ul>
        @endif

        <form action="{{ route('dashboard.group.store') }}" method="post" class="space-y-6">
            @csrf
            <div>
                <span class="text-xs font-medium text-slate-500 uppercase tracking-wide block mb-3">Choose a name for your group</span>
                <div class="space-y-3">
                    @foreach($nameOptions as $option)
                    <label class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer transition-colors">
                        <input type="radio" name="group_name" value="{{ $option->display_name }}" required {{ old('group_name') === $option->display_name ? 'checked' : '' }} class="text-slate-600 border-slate-300 focus:ring-slate-400">
                        <span class="text-lg" aria-hidden="true">{{ $option->emoji ?? '✨' }}</span>
                        <span class="text-sm font-medium text-slate-800">{{ $option->display_name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            <div>
                <label for="phone" class="text-xs font-medium text-slate-500 uppercase tracking-wide block mb-1">Member phone number</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone', $phone ?? ($pendingMember->phone ?? '')) }}" required placeholder="e.g. 0241234567" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
                @if($pendingMember)
                <p class="text-xs text-slate-500 mt-1">Adding: {{ $pendingMember->name ?? $pendingMember->username }}</p>
                @endif
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium bg-slate-600 text-white hover:bg-slate-700 min-h-[44px] sm:min-h-0">Create group & add member</button>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 min-h-[44px] sm:min-h-0">Cancel</a>
            </div>
        </form>
    </div>
</section>
@endsection
