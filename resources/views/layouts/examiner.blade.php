@extends('layouts.app')

@section('title', $examinerTitle ?? 'Examiner')
@section('body_class', 'bg-offwhite')

@section('content')
<div class="examiner-wrap flex min-h-screen bg-offwhite">
    {{-- Mobile overlay: tap to close sidebar --}}
    <div id="examiner-overlay" class="examiner-overlay fixed inset-0 z-30 bg-black/40 md:hidden hidden" aria-hidden="true"></div>

    {{-- Sidebar: collapsible via data-collapsed (JS toggles) --}}
    <aside id="examiner-sidebar" class="examiner-sidebar" aria-label="Examiner navigation" data-collapsed="false">
        <div class="examiner-sidebar-inner flex flex-col h-full">
            {{-- Brand + collapse toggle --}}
            <div class="examiner-sidebar-header flex h-16 flex-shrink-0 items-center justify-between gap-2 px-4">
                <a href="{{ route('dashboard') }}" class="examiner-sidebar-brand flex min-w-0 flex-shrink-0 items-center gap-3 overflow-hidden transition-opacity hover:opacity-80">
                    @php $user = auth()->user(); $inst = $user?->institution; @endphp
                    @if($inst && $inst->logo_url)
                        <img src="{{ $inst->logo_url }}" alt="{{ $inst->name }}" class="h-10 w-10 flex-shrink-0 object-contain rounded-lg border border-gray-200 bg-white">
                    @else
                        <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-primary-600 text-white font-bold text-lg shadow-sm">Q</span>
                    @endif
                    <span class="examiner-sidebar-brand-text truncate text-lg font-bold">QuizSnap</span>
                </a>
                <button type="button" id="examiner-sidebar-toggle-inner" data-examiner-collapse class="examiner-sidebar-chevron flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-primary-300 md:flex" aria-label="Collapse sidebar" title="Collapse sidebar (desktop)">
                    <svg class="h-5 w-5 transition-transform hidden md:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                    </svg>
                    <svg class="h-6 w-6 md:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Nav: Dashboard, Class Groups (students/attendance), Quizzes. Profile is in header dropdown. --}}
            <nav class="examiner-sidebar-nav flex-1 overflow-y-auto px-3 py-4 space-y-1">
                <ul class="space-y-1.5" role="list">
                    <li>
                        <a href="{{ route('dashboard') }}" class="examiner-nav-link {{ request()->routeIs('dashboard') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            <span class="examiner-nav-text truncate">Dashboard</span>
                        </a>
                    </li>
                    {{-- Class Groups: Coordinator-only (academic structure); Examiner selects, does not manage --}}
                    <li>
                        <a href="{{ route('dashboard.quizzes.index') }}" class="examiner-nav-link {{ request()->routeIs('dashboard.quizzes.*') ? 'examiner-nav-link--active' : '' }} group flex items-center gap-3 rounded-lg py-3 px-3 text-sm font-medium min-w-0 transition-all">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span class="examiner-nav-text truncate">Quizzes</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="examiner-main">
        {{-- Top bar: fixed height, no overflow --}}
        <header class="flex h-14 flex-shrink-0 items-center border-b border-gray-200 bg-white z-10 min-w-0">
            <div class="examiner-page flex h-14 w-full items-center gap-3 px-4 md:px-6">
                {{-- Mobile: hamburger to open sidebar. Desktop when collapsed: same button to expand --}}
                <button type="button" id="examiner-sidebar-toggle" class="examiner-mobile-menu flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700 md:hidden" aria-label="Open menu">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <button type="button" id="examiner-sidebar-expand" class="hidden h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-300" aria-label="Expand sidebar" title="Expand sidebar">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="min-w-0 flex-1 truncate text-lg font-semibold text-gray-900">@yield('examiner_heading', 'Examiner')</h1>
                @php
                    $examiner = auth()->user();
                    $smsRemaining = $examiner && $examiner->isExaminer() ? $examiner->sms_remaining : 0;
                    $smsAllocation = $examiner && $examiner->isExaminer() ? ($examiner->sms_allocation ?? 0) : 0;
                    $smsColorClass = $smsRemaining >= 100 ? 'text-green-600' : 'text-red-600';
                @endphp
                @if($examiner && $examiner->isExaminer())
                <div class="flex flex-shrink-0 items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1.5 text-sm {{ $smsColorClass }}" title="SMS balance for login tokens">
                    <span class="text-gray-500">SMS:</span>
                    <span class="font-semibold">{{ $smsRemaining }}</span>
                </div>
                @endif
                <div class="relative flex flex-shrink-0 items-center ml-2" id="profile-menu-wrap">
                    <button type="button" class="flex items-center gap-2 rounded-full p-0.5 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2" aria-expanded="false" aria-haspopup="true" id="profile-menu-btn" title="Profile">
                        @php $user = auth()->user(); @endphp
                        @if($user && $user->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="Profile" class="h-9 w-9 rounded-full object-cover flex-shrink-0 border border-gray-200" />
                        @else
                            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-gray-200 text-gray-600 text-sm font-semibold border border-gray-200">{{ $user ? strtoupper(substr($user->name ?? $user->username ?? 'U', 0, 1)) : 'U' }}</span>
                        @endif
                        <svg class="h-4 w-4 flex-shrink-0 text-gray-500 hidden sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="profile-menu-dropdown" class="absolute right-0 top-full z-50 mt-1.5 w-48 sm:w-56 rounded-lg border border-gray-200 bg-white py-1 shadow-lg hidden">
                        <a href="{{ route('dashboard.profile.show') }}" class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-100 whitespace-nowrap">Profile &amp; info</a>
                        <a href="{{ route('dashboard.profile.password') }}" class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-100 whitespace-nowrap">Reset password</a>
                        <form action="{{ route('logout') }}" method="post" class="border-t border-gray-100 mt-1">
                            @csrf
                            <button type="submit" class="block w-full px-4 py-2.5 text-left text-sm font-medium text-gray-700 hover:bg-gray-100 whitespace-nowrap">Log out</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="examiner-main-content flex-1 min-h-0 overflow-y-auto overflow-x-hidden bg-offwhite">
            <div class="examiner-page w-full px-4 py-6 md:px-6 md:py-8">
                @yield('examiner_content')
            </div>
        </main>
    </div>
</div>
<script>
(function() {
    var KEY = 'examinerSidebar';
    var sidebar = document.getElementById('examiner-sidebar');
    var overlay = document.getElementById('examiner-overlay');
    var toggle = document.getElementById('examiner-sidebar-toggle');
    var toggleInner = document.getElementById('examiner-sidebar-toggle-inner');
    var expandBtn = document.getElementById('examiner-sidebar-expand');
    if (!sidebar) return;

    var isDesktop = function() { return window.innerWidth >= 768; };
    var collapsed = localStorage.getItem(KEY) === 'collapsed';
    
    function setCollapsed(c) {
        collapsed = c;
        localStorage.setItem(KEY, c ? 'collapsed' : 'expanded');
        sidebar.setAttribute('data-collapsed', c ? 'true' : 'false');
        sidebar.classList.toggle('examiner-sidebar--collapsed', c);
        if (isDesktop()) {
            sidebar.style.width = c ? '4.5rem' : '';
            sidebar.style.minWidth = c ? '4.5rem' : '';
        } else {
            sidebar.style.width = '';
            sidebar.style.minWidth = '';
        }
        if (overlay) overlay.classList.toggle('hidden', c);
        if (toggleInner) {
            toggleInner.setAttribute('aria-label', c ? 'Expand sidebar' : 'Collapse sidebar');
            toggleInner.setAttribute('title', c ? 'Expand sidebar' : 'Collapse sidebar');
        }
        if (expandBtn) {
            expandBtn.style.setProperty('display', (isDesktop() && c) ? 'flex' : 'none');
        }
    }
    
    function init() {
        if (isDesktop()) {
            setCollapsed(collapsed);
            if (expandBtn) expandBtn.style.setProperty('display', collapsed ? 'flex' : 'none');
        } else {
            setCollapsed(true);
            if (expandBtn) expandBtn.style.setProperty('display', 'none');
        }
    }
    function runInit() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    }
    runInit();

    if (toggle) toggle.addEventListener('click', function(e) { e.preventDefault(); setCollapsed(false); });
    if (expandBtn) expandBtn.addEventListener('click', function(e) { e.preventDefault(); setCollapsed(false); });
    if (overlay) overlay.addEventListener('click', function() { setCollapsed(true); });

    document.addEventListener('click', function(e) {
        var collapseBtn = e.target && e.target.closest && e.target.closest('[data-examiner-collapse]');
        if (collapseBtn) {
            e.preventDefault();
            e.stopPropagation();
            if (isDesktop()) {
                setCollapsed(!collapsed);
            } else {
                setCollapsed(true);
            }
        }
    }, true);

    window.addEventListener('resize', function() {
        var desktop = isDesktop();
        if (!desktop) setCollapsed(true);
        if (expandBtn) expandBtn.style.setProperty('display', (desktop && collapsed) ? 'flex' : 'none');
    });

    var profileBtn = document.getElementById('profile-menu-btn');
    var profileDropdown = document.getElementById('profile-menu-dropdown');
    var profileWrap = document.getElementById('profile-menu-wrap');
    if (profileBtn && profileDropdown) {
        profileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            var open = !profileDropdown.classList.contains('hidden');
            profileDropdown.classList.toggle('hidden', open);
            profileBtn.setAttribute('aria-expanded', !open);
        });
        document.addEventListener('click', function() {
            profileDropdown.classList.add('hidden');
            profileBtn.setAttribute('aria-expanded', 'false');
        });
        if (profileWrap) profileWrap.addEventListener('click', function(e) { e.stopPropagation(); });
    }
})();
</script>
{{-- Scripts are rendered once in layouts.app via @stack('scripts') to avoid duplicate run --}}
@endsection
