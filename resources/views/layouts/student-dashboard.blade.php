@extends('layouts.app')

@section('title', $dashboardTitle ?? 'My Dashboard')
@section('body_class', 'bg-slate-50')
@section('body_extra_class', 'min-h-screen')

@section('content')
@php
    $studentNavHome = request()->routeIs('dashboard') && !request()->routeIs('dashboard.my-*') && !request()->routeIs('dashboard.projects*') && !request()->routeIs('dashboard.public-projects') && !request()->routeIs('dashboard.group*') && !request()->routeIs('dashboard.course-materials') && !request()->routeIs('dashboard.calendar') && !request()->routeIs('dashboard.class-results*');
    $breadcrumbLabel = 'Dashboard';
    if (request()->routeIs('dashboard.projects*') || request()->routeIs('dashboard.public-projects') || request()->routeIs('dashboard.group*')) { $breadcrumbLabel = 'Projects'; }
    elseif (request()->routeIs('dashboard.my-quizzes*')) { $breadcrumbLabel = 'Quizzes'; }
    elseif (request()->routeIs('dashboard.course-materials')) { $breadcrumbLabel = 'Materials'; }
    elseif (request()->routeIs('dashboard.calendar')) { $breadcrumbLabel = 'Calendar'; }
    elseif (request()->routeIs('dashboard.my-profile')) { $breadcrumbLabel = 'Profile'; }
    elseif (request()->routeIs('dashboard.class-results*')) { $breadcrumbLabel = 'Class Results'; }
@endphp
{{-- Student dashboard: yellow header; mobile = breadcrumb + sidebar, desktop = no sidebar --}}
<div class="min-h-screen flex flex-col bg-slate-50" id="student-dashboard-wrap">
    {{-- Header: yellow on all screens --}}
    <header class="sticky top-0 z-30 bg-amber-400 border-b border-amber-500/30 shadow-sm">
        <div class="mx-auto flex h-14 max-w-4xl w-full items-center justify-between gap-4 px-4 sm:px-6">
            {{-- Mobile: breadcrumb (tappable) opens sidebar; desktop has no sidebar --}}
            <div class="flex sm:hidden items-center min-w-0 flex-1">
                <button type="button" id="student-mobile-menu-btn" class="flex items-center gap-2 min-w-0 flex-1 text-left rounded-lg py-2 pr-2 focus:outline-none focus:ring-2 focus:ring-slate-700 focus:ring-offset-2 focus:ring-offset-amber-400" aria-label="Open menu" aria-expanded="false" aria-controls="student-mobile-sidebar">
                    <span class="shrink-0 flex h-9 w-9 items-center justify-center rounded-lg bg-amber-500/20 text-slate-800"><i class="fas fa-bars text-sm"></i></span>
                    <span class="text-sm font-semibold text-slate-900 truncate" id="student-breadcrumb-label">{{ $breadcrumbLabel }}</span>
                    <i class="fas fa-chevron-down text-slate-600 text-xs shrink-0 ml-0.5 transition-transform duration-200 student-mobile-chevron" aria-hidden="true"></i>
                </button>
            </div>
            {{-- Desktop: logo --}}
            <a href="{{ route('dashboard') }}" class="hidden sm:flex items-center gap-2 shrink-0 no-underline text-slate-900" title="Dashboard">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-500/20 text-slate-800"><i class="fas fa-graduation-cap text-sm"></i></span>
                <span class="text-sm font-bold text-slate-900">QuizSnap</span>
            </a>

            {{-- Desktop nav (no sidebar on desktop) --}}
            <nav class="hidden sm:flex items-center gap-1 flex-1 justify-center min-w-0" aria-label="Dashboard navigation">
                <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ $studentNavHome ? 'bg-blue-600 text-white' : 'text-slate-800 hover:bg-amber-500/20' }}"><i class="fas fa-home mr-1.5 text-slate-700 text-xs"></i>Home</a>
                @if($hasProjectAccess ?? false)
                @if(isset($student) && $student)
                <a href="{{ route('student.enter-documentor', ['redirect' => 'dashboard.projects.index']) }}" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('dashboard.projects*') || request()->routeIs('dashboard.public-projects') || request()->routeIs('dashboard.group*') ? 'bg-blue-600 text-white' : 'text-slate-800 hover:bg-amber-500/20' }}"><i class="fas fa-folder-open mr-1.5 text-slate-700 text-xs"></i>Projects</a>
                @else
                <a href="{{ route('dashboard.projects.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('dashboard.projects*') || request()->routeIs('dashboard.public-projects') || request()->routeIs('dashboard.group*') ? 'bg-blue-600 text-white' : 'text-slate-800 hover:bg-amber-500/20' }}"><i class="fas fa-folder-open mr-1.5 text-slate-700 text-xs"></i>Projects</a>
                @endif
                @if($isClassRep ?? false)
                <a href="{{ route('dashboard.class-results.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('dashboard.class-results*') ? 'bg-blue-600 text-white' : 'text-slate-800 hover:bg-amber-500/20' }}"><i class="fas fa-file-download mr-1.5 text-slate-700 text-xs"></i>Class Results</a>
                @endif
                @endif
                @if($hasQuizAccess ?? true)
                <a href="{{ route('dashboard.my-quizzes') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('dashboard.my-quizzes*') ? 'bg-blue-600 text-white' : 'text-slate-800 hover:bg-amber-500/20' }}"><i class="fas fa-clipboard-list mr-1.5 text-slate-700 text-xs"></i>Quizzes</a>
                <a href="{{ route('dashboard.calendar') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('dashboard.calendar') ? 'bg-blue-600 text-white' : 'text-slate-800 hover:bg-amber-500/20' }}"><i class="fas fa-calendar-alt mr-1.5 text-slate-700 text-xs"></i>Calendar</a>
                <a href="{{ route('dashboard.course-materials') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('dashboard.course-materials') ? 'bg-blue-600 text-white' : 'text-slate-800 hover:bg-amber-500/20' }}"><i class="fas fa-book mr-1.5 text-slate-700 text-xs"></i>Materials</a>
                @endif
            </nav>

            @if(isset($student) && $student)
            <div class="relative shrink-0" id="student-profile-menu">
                <button type="button" id="student-profile-btn" class="flex items-center gap-2 rounded-full sm:rounded-lg py-2 pl-2 pr-2 sm:pr-3 hover:bg-amber-500/20 focus:outline-none focus:ring-2 focus:ring-slate-700 focus:ring-offset-2 focus:ring-offset-amber-400 transition-colors" aria-expanded="false" aria-haspopup="true" aria-controls="student-profile-dropdown">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-amber-500/30 text-slate-800 font-medium text-sm"><i class="fas fa-user text-xs"></i></span>
                    <span class="hidden sm:block text-left max-w-[100px] truncate">
                        <span class="block text-sm font-semibold text-slate-900 truncate">{{ $student->first_name }}</span>
                        <span class="block text-xs text-slate-700 font-mono truncate">{{ $student->index_number }}</span>
                    </span>
                    <i class="fas fa-chevron-down text-slate-700 text-xs hidden sm:block"></i>
                </button>
                <div id="student-profile-dropdown" class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white py-1 z-50 hidden shadow-lg" role="menu">
                    <div class="px-4 py-3 border-b border-slate-100">
                        <p class="text-sm font-medium text-slate-800 truncate">{{ $student->display_name }}</p>
                        <p class="text-xs text-slate-500 font-mono">{{ $student->index_number }}</p>
                    </div>
                    <a href="{{ route('dashboard.my-profile') }}" class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors" role="menuitem"><i class="fas fa-user mr-2 text-slate-400 text-xs"></i>Profile</a>
                    <form action="{{ route('student.account.logout') }}" method="post" class="block">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors" role="menuitem"><i class="fas fa-sign-out-alt mr-2 text-slate-400 text-xs"></i>Log out</button>
                    </form>
                </div>
            </div>
            <script>
            (function(){var btn=document.getElementById('student-profile-btn');var drop=document.getElementById('student-profile-dropdown');if(!btn||!drop)return;function open(){drop.classList.remove('hidden');btn.setAttribute('aria-expanded','true');}function close(){drop.classList.add('hidden');btn.setAttribute('aria-expanded','false');}btn.addEventListener('click',function(e){e.stopPropagation();if(drop.classList.contains('hidden'))open();else close();});document.addEventListener('click',function(){close();});drop.addEventListener('click',function(e){e.stopPropagation();});})();
            </script>
            @elseif(isset($user) && $user)
            <div class="relative shrink-0" id="student-profile-menu">
                <button type="button" id="student-profile-btn" class="flex items-center gap-2 rounded-full sm:rounded-lg py-2 pl-2 pr-2 sm:pr-3 hover:bg-amber-500/20 focus:outline-none focus:ring-2 focus:ring-slate-700 focus:ring-offset-2 focus:ring-offset-amber-400 transition-colors" aria-expanded="false" aria-haspopup="true" aria-controls="student-profile-dropdown">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-amber-500/30 text-slate-800 font-medium text-sm"><i class="fas fa-user text-xs"></i></span>
                    <span class="hidden sm:block text-left max-w-[100px] truncate text-sm font-semibold text-slate-900 truncate">{{ $user->name ?? $user->username }}</span>
                    <i class="fas fa-chevron-down text-slate-700 text-xs hidden sm:block"></i>
                </button>
                <div id="student-profile-dropdown" class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white py-1 z-50 hidden shadow-lg" role="menu">
                    <div class="px-4 py-3 border-b border-slate-100">
                        <p class="text-sm font-medium text-slate-800 truncate">{{ $user->name ?? $user->username }}</p>
                    </div>
                    <form action="{{ route('logout') }}" method="post" class="block">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors" role="menuitem"><i class="fas fa-sign-out-alt mr-2 text-slate-400 text-xs"></i>Log out</button>
                    </form>
                </div>
            </div>
            <script>
            (function(){var btn=document.getElementById('student-profile-btn');var drop=document.getElementById('student-profile-dropdown');if(!btn||!drop)return;function open(){drop.classList.remove('hidden');btn.setAttribute('aria-expanded','true');}function close(){drop.classList.add('hidden');btn.setAttribute('aria-expanded','false');}btn.addEventListener('click',function(e){e.stopPropagation();if(drop.classList.contains('hidden'))open();else close();});document.addEventListener('click',function(){close();});drop.addEventListener('click',function(e){e.stopPropagation();});})();
            </script>
            @else
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('dashboard.my-profile') }}" class="px-3 py-2 rounded-lg text-sm font-semibold text-slate-900 hover:bg-amber-500/20 transition-colors">Profile</a>
                <form action="{{ route('student.account.logout') }}" method="post" class="inline">@csrf<button type="submit" class="px-3 py-2 rounded-lg text-sm font-semibold text-slate-900 hover:bg-amber-500/20 transition-colors">Log out</button></form>
            </div>
            @endif
        </div>
    </header>

    {{-- Mobile: slide-out sidebar (breadcrumb menu opens this); start closed --}}
    <aside id="student-mobile-sidebar" class="fixed top-0 left-0 z-40 h-full w-72 max-w-[85vw] bg-white border-r border-slate-200 shadow-xl transition-transform duration-200 ease-out sm:hidden" style="transform: translateX(-100%);" aria-label="Mobile menu" aria-hidden="true">
        <div class="flex items-center justify-between h-14 px-4 border-b border-slate-200 bg-amber-400">
            <span class="text-sm font-bold text-slate-900">Menu</span>
            <button type="button" id="student-mobile-sidebar-close" class="p-2 rounded-lg text-slate-800 hover:bg-amber-500/20" aria-label="Close menu"><i class="fas fa-times"></i></button>
        </div>
        <nav class="p-4 space-y-1" aria-label="Dashboard navigation">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-800 no-underline {{ $studentNavHome ? 'bg-blue-600 text-white' : 'hover:bg-slate-100' }}"><i class="fas fa-home w-5 text-center text-slate-600"></i><span>Home</span></a>
            @if($hasProjectAccess ?? false)
            @if(isset($student) && $student)
            <a href="{{ route('student.enter-documentor', ['redirect' => 'dashboard.projects.index']) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-800 no-underline {{ request()->routeIs('dashboard.projects*') || request()->routeIs('dashboard.public-projects') || request()->routeIs('dashboard.group*') ? 'bg-blue-600 text-white' : 'hover:bg-slate-100' }}"><i class="fas fa-folder-open w-5 text-center text-slate-600"></i><span>Projects</span></a>
            @else
            <a href="{{ route('dashboard.projects.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-800 no-underline {{ request()->routeIs('dashboard.projects*') || request()->routeIs('dashboard.public-projects') || request()->routeIs('dashboard.group*') ? 'bg-blue-600 text-white' : 'hover:bg-slate-100' }}"><i class="fas fa-folder-open w-5 text-center text-slate-600"></i><span>Projects</span></a>
            @endif
            @if($isClassRep ?? false)
            <a href="{{ route('dashboard.class-results.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-800 no-underline {{ request()->routeIs('dashboard.class-results*') ? 'bg-blue-600 text-white' : 'hover:bg-slate-100' }}"><i class="fas fa-file-download w-5 text-center text-slate-600"></i><span>Class Results</span></a>
            @endif
            @endif
            @if($hasQuizAccess ?? true)
            <a href="{{ route('dashboard.my-quizzes') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-800 no-underline {{ request()->routeIs('dashboard.my-quizzes*') ? 'bg-blue-600 text-white' : 'hover:bg-slate-100' }}"><i class="fas fa-clipboard-list w-5 text-center text-slate-600"></i><span>Quizzes</span></a>
            <a href="{{ route('dashboard.calendar') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-800 no-underline {{ request()->routeIs('dashboard.calendar') ? 'bg-blue-600 text-white' : 'hover:bg-slate-100' }}"><i class="fas fa-calendar-alt w-5 text-center text-slate-600"></i><span>Calendar</span></a>
            <a href="{{ route('dashboard.course-materials') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-800 no-underline {{ request()->routeIs('dashboard.course-materials') ? 'bg-blue-600 text-white' : 'hover:bg-slate-100' }}"><i class="fas fa-book w-5 text-center text-slate-600"></i><span>Materials</span></a>
            @endif
            <a href="{{ route('dashboard.my-profile') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-800 no-underline {{ request()->routeIs('dashboard.my-profile') ? 'bg-blue-600 text-white' : 'hover:bg-slate-100' }}"><i class="fas fa-user w-5 text-center text-slate-600"></i><span>Profile</span></a>
        </nav>
    </aside>
    <div id="student-mobile-sidebar-overlay" class="fixed inset-0 z-30 bg-slate-900/40 sm:hidden cursor-pointer pointer-events-none" aria-hidden="true" role="button" tabindex="-1" aria-label="Close menu" style="visibility: hidden;"></div>

    <main class="flex-1 w-full min-w-0 overflow-x-hidden pb-6 sm:pb-10">
        <div class="mx-auto w-full max-w-4xl min-w-0 px-4 py-5 sm:px-6 sm:py-8 space-y-4 sm:space-y-6">
            {{-- Desktop-only back to main dashboard for all subpages --}}
            @if(!request()->routeIs('dashboard'))
                <div class="hidden sm:flex items-center text-xs font-medium text-slate-500 gap-1">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 px-2 py-1 rounded-full border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 text-slate-700 no-underline">
                        <i class="fas fa-arrow-left text-[10px]"></i>
                        <span>Back to dashboard</span>
                    </a>
                </div>
            @endif

            @yield('dashboard_content')
        </div>
    </main>
</div>
<script>
(function(){
    function run() {
        var btn = document.getElementById('student-mobile-menu-btn');
        var sidebar = document.getElementById('student-mobile-sidebar');
        var overlay = document.getElementById('student-mobile-sidebar-overlay');
        var closeBtn = document.getElementById('student-mobile-sidebar-close');
        if (!btn || !sidebar || !overlay) return;

        function isOpen() {
            return sidebar.getAttribute('data-sidebar-open') === '1';
        }
        function setOpen(open) {
            sidebar.setAttribute('data-sidebar-open', open ? '1' : '0');
            sidebar.style.transform = open ? 'translateX(0)' : 'translateX(-100%)';
            sidebar.setAttribute('aria-hidden', open ? 'false' : 'true');
            overlay.style.visibility = open ? 'visible' : 'hidden';
            overlay.style.pointerEvents = open ? 'auto' : 'none';
            overlay.setAttribute('aria-hidden', open ? 'false' : 'true');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            var chevron = btn.querySelector('.student-mobile-chevron');
            if (chevron) chevron.style.transform = open ? 'rotate(180deg)' : '';
            document.body.style.overflow = open ? 'hidden' : '';
        }
        function openSidebar() {
            setOpen(true);
        }
        function closeSidebar() {
            setOpen(false);
        }
        function toggleSidebar(e) {
            if (e) { e.preventDefault(); e.stopPropagation(); }
            setOpen(!isOpen());
        }

        setOpen(false);

        btn.addEventListener('click', toggleSidebar);
        if (closeBtn) closeBtn.addEventListener('click', function(e) { e.preventDefault(); closeSidebar(); });
        overlay.addEventListener('click', function(e) { e.preventDefault(); closeSidebar(); });
        overlay.addEventListener('touchend', function(e) { e.preventDefault(); closeSidebar(); }, { passive: false });
        var navLinks = document.querySelectorAll('#student-mobile-sidebar nav a');
        for (var i = 0; i < navLinks.length; i++) { navLinks[i].addEventListener('click', closeSidebar); }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && isOpen()) { e.preventDefault(); closeSidebar(); }
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
</script>

@if(($hasQuizAccess ?? false) && isset($student) && $student && !empty($vapidPublicKey ?? null))
@push('scripts')
<script>
(function() {
    var vapidPublicKey = @json($vapidPublicKey);
    var subscribeUrl = @json(route('dashboard.push-subscribe'));
    var csrfToken = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function urlBase64ToUint8Array(base64String) {
        var padLen = (4 - base64String.length % 4) % 4;
        var padding = '';
        for (var p = 0; p < padLen; p++) padding += '=';
        var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        var rawData = atob(base64);
        var outputArray = new Uint8Array(rawData.length);
        for (var i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
        return outputArray;
    }

    function subscribePush(registration) {
        if (!registration.pushManager || !vapidPublicKey) return Promise.resolve();
        return registration.pushManager.getSubscription().then(function(existing) {
            if (existing) return existing;
            return registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
            });
        }).then(function(subscription) {
            var payload = subscription.toJSON();
            if (!payload.endpoint || !payload.keys) return;
            var body = JSON.stringify({
                endpoint: payload.endpoint,
                keys: payload.keys
            });
            var xhr = new XMLHttpRequest();
            xhr.open('POST', subscribeUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken || '');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(body);
        }).catch(function(err) { console.warn('Push subscribe:', err); });
    }

    if ('serviceWorker' in navigator && 'PushManager' in window) {
        navigator.serviceWorker.register('{{ asset('sw.js') }}', { scope: '/' }).then(function(registration) {
            if (Notification.permission === 'granted') {
                subscribePush(registration);
            } else if (Notification.permission === 'default') {
                Notification.requestPermission().then(function(perm) {
                    if (perm === 'granted') subscribePush(registration);
                });
            }
        }).catch(function(err) { console.warn('SW register:', err); });
    }
})();
</script>
@endpush
@endif
@endsection
