@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('dashboard_heading', 'Dashboard')

@section('dashboard_content')
<div class="w-full space-y-8">
    @php
        $examiner = auth()->user();
        $smsRemaining = $examiner && $examiner->isExaminer() ? $examiner->sms_remaining : 0;
        $showLowSmsWarning = $examiner && $examiner->isExaminer() && $smsRemaining < 100 && $smsRemaining > 0;
    @endphp
    
    {{-- Faculty/Department Notice --}}
    @if(isset($needsFacultyDepartment) && $needsFacultyDepartment)
    <div id="faculty-department-notice" class="rounded-lg border border-orange-300 bg-orange-50 p-4 flex items-start gap-3" role="alert">
        <div class="flex-shrink-0 mt-0.5">
            <svg class="w-5 h-5 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-orange-900">Complete Your Profile</p>
            <p class="mt-1 text-sm text-orange-800">Please select your faculty and department to continue. <a href="{{ route('dashboard.users.edit', ['user' => $examiner, 'complete_profile' => 1]) }}" class="font-semibold underline hover:text-orange-900">Update your profile here</a>.</p>
        </div>
        <button type="button" onclick="dismissFacultyDepartmentNotice()" class="flex-shrink-0 text-orange-600 hover:text-orange-800 transition-colors" aria-label="Dismiss">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
    @endif
    
    {{-- Low SMS Warning Banner --}}
    @if($showLowSmsWarning)
    <div id="low-sms-warning" class="rounded-lg border border-red-300 bg-red-50 p-4 flex items-start gap-3" role="alert">
        <div class="flex-shrink-0 mt-0.5">
            <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-red-900">Low SMS Balance</p>
            <p class="mt-1 text-sm text-red-800">You have <strong>{{ $smsRemaining }}</strong> SMS remaining. Please contact your administrator to reload your SMS allocation so you can continue sending login tokens to students via SMS.</p>
        </div>
        <button type="button" onclick="dismissLowSmsWarning()" class="flex-shrink-0 text-red-600 hover:text-red-800 transition-colors" aria-label="Dismiss">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
    @endif
    
    <div>
        <h2 class="text-xl font-semibold text-gray-900">Overview</h2>
        <p class="mt-1 text-sm text-gray-500">Manage class groups, quizzes, and view session results.</p>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <a href="{{ route('dashboard.quizzes.index') }}" class="flex flex-col rounded-lg border border-primary-200 bg-primary-100 p-5 hover:bg-primary-200 sm:p-6">
            <div class="flex flex-1 items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-primary-800">Quizzes</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-primary-900 sm:text-3xl">{{ $stats['quizzes'] }}</p>
                </div>
                <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-lg bg-primary-200 text-primary-700">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </span>
            </div>
            <p class="mt-3 text-xs font-medium text-primary-700">View quizzes</p>
        </a>
        <a href="{{ route('dashboard.class-groups.index') }}" class="flex flex-col rounded-lg border border-action-200 bg-action-100 p-5 hover:bg-action-200 sm:p-6">
            <div class="flex flex-1 items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-action-800">Class Groups</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-action-900 sm:text-3xl">{{ $classGroups->count() }}</p>
                </div>
                <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-lg bg-action-200 text-action-700">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </span>
            </div>
            <p class="mt-3 text-xs font-medium text-action-700">Manage students per group</p>
        </a>
        <div class="flex flex-col rounded-lg border border-primary-200 bg-primary-100 p-5 sm:p-6">
            <div class="flex flex-1 items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-primary-800">Sessions</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-primary-900 sm:text-3xl">{{ $stats['sessions'] }}</p>
                </div>
                <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-lg bg-primary-200 text-primary-700">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </span>
            </div>
            <p class="mt-3 text-xs font-medium text-primary-700">View from quiz pages</p>
        </div>
        <div class="flex flex-col rounded-lg border border-action-200 bg-action-100 p-5 sm:p-6">
            <div class="flex flex-1 items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-action-800">Results</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-action-900 sm:text-3xl">{{ $stats['results'] }}</p>
                </div>
                <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-lg bg-action-200 text-action-700">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </span>
            </div>
            <p class="mt-3 text-xs font-medium text-action-700">View from quiz pages</p>
        </div>
    </div>

    {{-- Quick actions (compact) --}}
    <section class="rounded-lg border border-gray-200 bg-white p-4">
        <h2 class="text-xs font-semibold text-gray-700 mb-3">Quick actions</h2>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('dashboard.quizzes.create') }}" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-white bg-primary-600 rounded border border-primary-600 hover:bg-primary-700">Create quiz</a>
            @can('create', \App\Models\ClassGroup::class)
            <a href="{{ route('dashboard.class-groups.create') }}" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-gray-800 bg-action-100 rounded border border-action-200 hover:bg-action-200">New class group</a>
            @endcan
            <a href="{{ route('dashboard.quizzes.index') }}" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 rounded border border-gray-200 hover:bg-gray-100">All quizzes</a>
        </div>
    </section>
</div>

@push('scripts')
<script>
(function() {
    // Low SMS Warning Dismissal (24 hours)
    const WARNING_KEY = 'low_sms_warning_dismissed';
    const DISMISS_HOURS = 24;
    
    function dismissLowSmsWarning() {
        const warning = document.getElementById('low-sms-warning');
        if (warning) {
            warning.style.display = 'none';
            const dismissUntil = Date.now() + (DISMISS_HOURS * 60 * 60 * 1000);
            localStorage.setItem(WARNING_KEY, dismissUntil.toString());
        }
    }
    
    function shouldShowWarning() {
        const dismissed = localStorage.getItem(WARNING_KEY);
        if (!dismissed) return true;
        const dismissUntil = parseInt(dismissed, 10);
        return Date.now() > dismissUntil;
    }
    
    // Hide warning if dismissed and still valid
    const warning = document.getElementById('low-sms-warning');
    if (warning && !shouldShowWarning()) {
        warning.style.display = 'none';
    }
    
    // Faculty/Department Notice Dismissal (24 hours)
    const FACULTY_NOTICE_KEY = 'faculty_department_notice_dismissed';
    const FACULTY_DISMISS_HOURS = 24;
    
    function dismissFacultyDepartmentNotice() {
        const notice = document.getElementById('faculty-department-notice');
        if (notice) {
            notice.style.display = 'none';
            const dismissUntil = Date.now() + (FACULTY_DISMISS_HOURS * 60 * 60 * 1000);
            localStorage.setItem(FACULTY_NOTICE_KEY, dismissUntil.toString());
        }
    }
    
    function shouldShowFacultyNotice() {
        const dismissed = localStorage.getItem(FACULTY_NOTICE_KEY);
        if (!dismissed) return true;
        const dismissUntil = parseInt(dismissed, 10);
        return Date.now() > dismissUntil;
    }
    
    // Hide notice if dismissed and still valid
    const facultyNotice = document.getElementById('faculty-department-notice');
    if (facultyNotice && !shouldShowFacultyNotice()) {
        facultyNotice.style.display = 'none';
    }
    
    // Auto-hide faculty notice if faculty and department are set (check on page load)
    @if(!$needsFacultyDepartment)
        localStorage.removeItem(FACULTY_NOTICE_KEY);
    @endif
    
    // Make dismiss functions global
    window.dismissLowSmsWarning = dismissLowSmsWarning;
    window.dismissFacultyDepartmentNotice = dismissFacultyDepartmentNotice;
})();
</script>
@endpush
@endsection
