@extends('layouts.student-dashboard')

@section('title', 'Select Your Level')
@php $dashboardTitle = 'Select Your Level'; @endphp

@section('dashboard_content')
<header class="mb-6">
    <h1 class="text-xl font-semibold text-slate-800 tracking-tight">Select your level</h1>
    <p class="text-sm text-slate-500 mt-1">Choose your current academic level. This helps us show you the right options (e.g. Docu Mentor when eligible).</p>
</header>

<section class="mb-8">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5 max-w-md">

        @if(session('error'))
        <p class="mb-4 text-sm font-medium text-red-600">{{ session('error') }}</p>
        @endif

        <form action="{{ route('student.select-level.store') }}" method="post" class="space-y-4">
            @csrf
            <div>
                <label for="level" class="text-xs font-medium text-slate-500 uppercase tracking-wide block mb-1">Level *</label>
                <select name="level" id="level" required class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
                    <option value="">— Select —</option>
                    @foreach($levels as $l)
                    <option value="{{ $l->value }}" {{ old('level') == $l->value ? 'selected' : '' }}>
                        {{ $l->label }}
                        @if($l->allows_docu_mentor)
                        <span class="text-slate-500">(Docu Mentor)</span>
                        @endif
                    </option>
                    @endforeach
                </select>
                @error('level')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex gap-3">
                <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium bg-slate-600 text-white hover:bg-slate-700 min-h-[44px] sm:min-h-0">Continue to dashboard</button>
            </div>
        </form>
    </div>
</section>
@endsection
