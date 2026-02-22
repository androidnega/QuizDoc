@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('dashboard_heading', 'Dashboard')

@section('dashboard_content')
<div class="w-full min-w-0 space-y-4 sm:space-y-6">
    <div class="min-w-0">
        <p class="text-sm sm:text-base text-gray-600">Courses, users, class groups (view only), and system settings</p>
    </div>

    {{-- Update mode: slim, clean, no card animation --}}
    <section class="rounded-md border px-3 py-2.5 min-w-0 {{ ($update_mode ?? false) ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50' }}">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-sm font-semibold {{ ($update_mode ?? false) ? 'text-green-900' : 'text-gray-900' }}">Update mode</h2>
                    <span class="text-xs font-medium px-2 py-0.5 rounded {{ ($update_mode ?? false) ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' }}">
                        {{ ($update_mode ?? false) ? 'ON' : 'OFF' }}
                    </span>
                    @if(($update_mode ?? false) && ($update_estimated_end ?? null))
                        <span class="text-xs text-green-900 font-semibold tabular-nums whitespace-nowrap overflow-hidden text-ellipsis max-w-full">
                            Time left: <span id="update-mode-countdown">--:--</span>
                        </span>
                    @endif
                </div>
                <p class="text-xs {{ ($update_mode ?? false) ? 'text-green-800' : 'text-gray-600' }} mt-0.5">Only Super Admins and Examiners can sign in at <code class="{{ ($update_mode ?? false) ? 'bg-green-100' : 'bg-gray-200' }} px-1 rounded">/login</code> while this is on.</p>
            </div>
            <form method="post" action="{{ route('dashboard.settings.update-mode') }}" class="flex items-center gap-2 flex-shrink-0">
                @csrf
                <button type="submit" class="relative inline-flex h-6 w-11 min-h-[36px] flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent focus:outline-none focus:ring-2 focus:ring-offset-1 {{ ($update_mode ?? false) ? 'bg-green-500 focus:ring-green-400' : 'bg-gray-300 focus:ring-gray-400' }}" role="switch" aria-checked="{{ ($update_mode ?? false) ? 'true' : 'false' }}">
                    <span class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow {{ ($update_mode ?? false) ? 'translate-x-5' : 'translate-x-0.5' }}"></span>
                </button>
            </form>
        </div>
        @if($update_mode ?? false)
            <div class="mt-2 pt-2 border-t border-green-200">
                <form method="post" action="{{ route('dashboard.settings.update-estimated-end') }}" class="flex flex-wrap items-end gap-2">
                    @csrf
                    <label class="text-xs text-green-800">Estimated end:</label>
                    <input type="datetime-local" name="estimated_end" value="{{ $update_estimated_end ? \Carbon\Carbon::parse($update_estimated_end)->format('Y-m-d\TH:i') : '' }}" class="text-xs rounded border border-green-300 px-2 py-1 min-w-0" />
                    <button type="submit" class="text-xs font-semibold text-green-900 hover:text-green-700">Save</button>
                </form>
            </div>
        @endif
    </section>

    <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-4 min-w-0">
        <div class="rounded-lg border border-gray-200 bg-white p-3 sm:p-4 shadow-sm min-w-0">
            <p class="text-xs sm:text-sm font-medium text-gray-500 truncate">Staff users</p>
            <p class="mt-1 text-xl sm:text-2xl font-bold tabular-nums text-gray-900">{{ $overview['users'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-3 sm:p-4 shadow-sm min-w-0">
            <p class="text-xs sm:text-sm font-medium text-gray-500 truncate">Courses</p>
            <p class="mt-1 text-xl sm:text-2xl font-bold tabular-nums text-gray-900">{{ $overview['courses'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-3 sm:p-4 shadow-sm min-w-0">
            <p class="text-xs sm:text-sm font-medium text-gray-500 truncate">Class groups</p>
            <p class="mt-1 text-xl sm:text-2xl font-bold tabular-nums text-gray-900">{{ $overview['class_groups'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-3 sm:p-4 shadow-sm min-w-0">
            <p class="text-xs sm:text-sm font-medium text-gray-500 truncate">Quiz sessions</p>
            <p class="mt-1 text-xl sm:text-2xl font-bold tabular-nums text-gray-900">{{ $overview['sessions'] ?? 0 }}</p>
        </div>
    </div>

    <section class="rounded-lg border border-gray-200 bg-white p-3 sm:p-4 min-w-0">
        <h2 class="text-xs font-semibold text-gray-700 mb-3">Quick links</h2>
        <div class="flex flex-wrap gap-2 sm:gap-2">
            <a href="{{ route('dashboard.settings.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 min-h-[44px] hover:bg-gray-100 hover:border-gray-300 transition-colors touch-manipulation" title="Configure app, mail, AI, and Cloudinary">
                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-md bg-white text-gray-500"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
                <span class="text-sm font-medium text-gray-900">Settings</span>
            </a>
            <a href="{{ route('dashboard.institutions.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 min-h-[44px] hover:bg-gray-100 hover:border-gray-300 transition-colors touch-manipulation" title="Manage institutions and assign examiners">
                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-md bg-white text-gray-500"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></span>
                <span class="text-sm font-medium text-gray-900">Institutions</span>
            </a>
            <a href="{{ route('dashboard.users.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 min-h-[44px] hover:bg-gray-100 hover:border-gray-300 transition-colors touch-manipulation" title="Manage staff (Super Admin and Examiners)">
                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-md bg-white text-gray-500"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg></span>
                <span class="text-sm font-medium text-gray-900">Users</span>
            </a>
            <a href="{{ route('dashboard.system.reset.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-danger-200 bg-danger-50 px-3 py-2.5 min-h-[44px] hover:bg-danger-100 hover:border-danger-300 transition-colors touch-manipulation" title="Clear data or full system reset (use with caution)">
                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-md bg-white text-danger-600"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></span>
                <span class="text-sm font-medium text-gray-900">Reset</span>
            </a>
        </div>
    </section>
</div>

@if(($update_mode ?? false) && ($update_estimated_end ?? null))
@push('scripts')
<script>
(function () {
    var el = document.getElementById('update-mode-countdown');
    if (!el) return;
    var endMs = new Date("{{ \Carbon\Carbon::parse($update_estimated_end)->toIso8601String() }}").getTime();
    if (!endMs || Number.isNaN(endMs)) return;
    function formatLeft(totalSeconds) {
        totalSeconds = Math.max(0, Math.floor(totalSeconds));
        var h = Math.floor(totalSeconds / 3600);
        var m = Math.floor((totalSeconds % 3600) / 60);
        var s = totalSeconds % 60;
        if (h > 0) {
            return String(h) + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        }
        return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    }
    function tick() {
        var left = Math.max(0, Math.ceil((endMs - Date.now()) / 1000));
        el.textContent = formatLeft(left);
        if (left <= 0) {
            clearInterval(timer);
        }
    }
    tick();
    var timer = setInterval(tick, 1000);
})();
</script>
@endpush
@endif
@endsection
