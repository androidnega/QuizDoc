<!DOCTYPE html>
<html lang="en">
<head>
    <script>document.documentElement.classList.add('quizsnap-js');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#fafaf9">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="QuizSnap">
    <meta name="format-detection" content="telephone=no">
    <title>@yield('title', 'QuizSnap')</title>
    {{-- No favicon on admin/dashboard pages — remove Laravel default appearance --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    {{-- Tailwind via CDN, with project theme config (no local Tailwind build) --}}
    <script>
        tailwind = window.tailwind || {};
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        display: ['Outfit', 'Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        offwhite: '#fafaf9',
                        homeBlue: '#0055FF',
                        homeYellow: '#FFD500',
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        action: {
                            DEFAULT: '#eab308',
                            50: '#fefce8',
                            100: '#fef9c3',
                            200: '#fef08a',
                            300: '#fde047',
                            400: '#facc15',
                            500: '#eab308',
                            600: '#ca8a04',
                            700: '#a16207',
                            800: '#854d0e',
                            900: '#713f12',
                        },
                        success: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        },
                        danger: {
                            50: '#fef2f2',
                            100: '#fee2e2',
                            200: '#fecaca',
                            300: '#fca5a5',
                            400: '#f87171',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                            800: '#991b1b',
                            900: '#7f1d1d',
                        },
                        warning: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            800: '#92400e',
                            900: '#78350f',
                        },
                    },
                },
            },
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Non-Tailwind custom styles --}}
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">

    {{-- Shared dashboard/examiner sidebar styles (active link highlighting) --}}
    <style>
        .examiner-nav-link {
            color: #4b5563; /* gray-600 */
        }
        .examiner-nav-link:hover {
            background-color: #f3f4f6; /* gray-100 */
            color: #111827; /* gray-900 */
        }
        .examiner-nav-link--active {
            background-color: rgba(234, 179, 8, 0.12); /* action/amber tint */
            color: #111827;
        }
        .examiner-nav-link--active .examiner-nav-text {
            color: #111827;
        }
        .examiner-nav-link--active svg {
            color: #eab308; /* action-500 */
        }
        /* When sidebar is collapsed on desktop, show only icons (no text labels) */
        @media (min-width: 768px) {
            .examiner-sidebar--collapsed .examiner-nav-text,
            .examiner-sidebar--collapsed .examiner-sidebar-brand-text {
                display: none !important;
            }
        }

        /* Hide horizontal scrollbar for student dashboard chips while keeping scroll */
        .student-chip-scroll {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;     /* Firefox */
        }
        .student-chip-scroll::-webkit-scrollbar {
            display: none;             /* Chrome, Safari, Opera */
        }

        /* Mobile: prevent horizontal scroll, fluid app-like layout */
        @media (max-width: 767px) {
            html, body {
                overflow-x: hidden;
                -webkit-overflow-scrolling: touch;
            }
            .examiner-wrap {
                overflow-x: hidden;
                min-height: 100vh;
                min-height: 100dvh;
            }
            /* Sidebar: fixed drawer, off-screen when collapsed */
            .examiner-sidebar {
                position: fixed !important;
                top: 0;
                left: 0;
                bottom: 0;
                z-index: 40;
                width: min(85vw, 18rem) !important;
                min-width: 0 !important;
                transition: transform 0.25s ease-out, box-shadow 0.25s ease-out;
                box-shadow: none;
            }
            .examiner-sidebar.examiner-sidebar--collapsed {
                transform: translateX(-100%);
                pointer-events: none;
            }
            .examiner-sidebar:not(.examiner-sidebar--collapsed) {
                transform: translateX(0);
                box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            }
            .examiner-overlay {
                transition: opacity 0.2s ease-out;
            }
            .examiner-main,
            .examiner-main-content {
                overflow-x: hidden;
                min-width: 0;
            }
            .examiner-page {
                overflow-x: hidden;
                max-width: 100%;
            }
            .examiner-dashboard-content {
                overflow-x: hidden;
                max-width: 100%;
            }
            .examiner-overlay:not(.hidden) {
                display: block !important;
                pointer-events: auto;
            }
            .examiner-sidebar-nav a {
                min-height: 44px;
                -webkit-tap-highlight-color: transparent;
            }
        }

        /* Quiz create/edit: single scrollbar (window only), no inline scroll, no grey gap at bottom */
        .examiner-wrap--doc-scroll {
            min-height: 100vh;
            height: auto;
            overflow: visible;
        }
        .examiner-wrap--doc-scroll .examiner-sidebar {
            position: sticky;
            top: 0;
            align-self: flex-start;
            max-height: 100vh;
        }
        .examiner-wrap--doc-scroll .examiner-main {
            min-height: 0;
            flex: 1 1 auto;
        }
        .examiner-main-content--doc-scroll {
            overflow: visible !important;
            min-height: auto;
            flex: none;
            background: #fff !important;
        }
    </style>
    <style>
        /* No flash: key off html.quizsnap-js (set by head script before body paints). When JS is off, head script does not run. */
        .quizsnap-noscript-msg { display: none !important; }
        html.quizsnap-js .quizsnap-noscript-msg { display: none !important; }
        html:not(.quizsnap-js) #quizsnap-app { display: none !important; }
        html:not(.quizsnap-js) .quizsnap-noscript-msg { display: flex !important; }
        .quizsnap-blocked #quizsnap-app { display: none !important; }
        .quizsnap-app--hidden { display: none !important; }
        .quizsnap-blocked html, .quizsnap-blocked body { overflow: hidden !important; position: fixed !important; width: 100% !important; height: 100% !important; }
        #quizsnap-block-overlay { display: none; position: fixed; inset: 0; z-index: 99999; background: #fafaf9; align-items: center; justify-content: center; padding: 1.5rem; box-sizing: border-box; overflow: hidden; }
        .quizsnap-blocked #quizsnap-block-overlay { display: flex !important; }
        #quizsnap-block-overlay .quizsnap-block-inner { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; max-width: 90vw; max-height: 90vh; }
        #quizsnap-block-overlay .quizsnap-block-icon { font-size: 5rem; line-height: 1; font-weight: 700; color: #dc2626; margin-bottom: 1rem; }
        #quizsnap-block-overlay #quizsnap-block-message { font-size: 1.125rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem; }
        #quizsnap-block-overlay .quizsnap-block-sub { font-size: 0.9375rem; color: #6b7280; margin-bottom: 1.5rem; }
        #quizsnap-block-overlay .quizsnap-block-footer { margin-top: 1.5rem; font-size: 0.875rem; color: #6b7280; }
        .quizsnap-select-none { -webkit-user-select: none; user-select: none; }
        .quizsnap-select-none input, .quizsnap-select-none textarea { -webkit-user-select: text; user-select: text; }
    </style>
    @stack('copy_restrict_styles')
    @stack('styles')
</head>
@php
    // Only enforce the quiz screen-size guard on actual quiz-taking pages.
    $shouldGuardQuiz = request()->routeIs('student.quiz.show') || request()->routeIs('student.quiz.ready');
    $skipGuard = ! $shouldGuardQuiz;
@endphp
<body class="font-sans text-gray-800 quizsnap-nojs @yield('body_extra_class') @yield('body_class', 'bg-offwhite')" data-skip-guard="{{ $skipGuard ? 'true' : 'false' }}">
    <noscript>
        <div class="fixed inset-0 z-[99999] flex items-center justify-center bg-offwhite p-6" role="alert">
            <div class="bg-white border border-gray-200 rounded-xl p-8 max-w-md text-center shadow-lg">
                <h1 class="text-xl font-bold text-gray-900 mb-2">JavaScript required</h1>
                <p class="text-gray-600 mb-4">Please enable JavaScript to use this website. Do not use extensions that disable JavaScript or allow copying—doing so may result in losing your quiz.</p>
                <p class="text-sm text-gray-500">Reload the page after enabling JavaScript.</p>
            </div>
        </div>
    </noscript>
    <!-- No-JS fallback (shown via CSS when body has quizsnap-nojs) -->
    <div class="quizsnap-noscript-msg hidden fixed inset-0 z-[99999] bg-offwhite items-center justify-center p-6" aria-live="polite">
        <div class="bg-white border border-gray-200 rounded-xl p-8 max-w-md text-center shadow-lg">
            <h1 class="text-xl font-bold text-gray-900 mb-2">JavaScript required</h1>
            <p class="text-gray-600 mb-4">Please enable JavaScript to use this website. Do not use extensions that disable JavaScript or allow copying—doing so may result in losing your quiz.</p>
            <p class="text-sm text-gray-500">Reload the page after enabling JavaScript.</p>
        </div>
    </div>
    {{-- Legacy mobile-only block overlay removed to allow full mobile support --}}
    @yield('copy_restriction_modal')
    <!-- Main content (staff/docu-mentor: always visible; quiz: shown when JS allows and device allowed) -->
    {{-- Always show main app container (no JS/viewport gating on students' pages) --}}
    <div id="quizsnap-app" class="quizsnap-app quizsnap-app--contain">
    {{-- Flash messages: one @if / @endif only to avoid PHP 8.4 parse error in compiled view --}}
    @php
        $hasFlash = session()->has('success') || session()->has('error') || session()->has('warning') || session()->has('info');
    @endphp
    @if($hasFlash)
    <div id="flash-container" class="fixed top-[4.25rem] md:top-4 right-3 left-3 sm:left-auto sm:max-w-sm z-[90] flex flex-col gap-3 pointer-events-none" role="status" aria-label="Notification">
        @php
            if (session('success')) { echo '<div class="toast toast-success flex items-start gap-3 rounded-lg border px-4 py-3 shadow-lg bg-white pointer-events-auto animate-toast-in"><svg class="w-5 h-5 flex-shrink-0 text-success-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg><span class="text-sm font-medium text-gray-900">'.e(session('success')).'</span></div>'; }
            if (session('error')) { echo '<div class="toast toast-error flex items-start gap-3 rounded-lg border px-4 py-3 shadow-lg bg-white pointer-events-auto animate-toast-in"><svg class="w-5 h-5 flex-shrink-0 text-danger-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg><span class="text-sm font-medium text-gray-900">'.e(session('error')).'</span></div>'; }
            if (session('warning')) { echo '<div class="toast toast-warning flex items-start gap-3 rounded-lg border px-4 py-3 shadow-lg bg-white pointer-events-auto animate-toast-in"><svg class="w-5 h-5 flex-shrink-0 text-warning-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg><span class="text-sm font-medium text-gray-900">'.e(session('warning')).'</span></div>'; }
            if (session('info')) { echo '<div class="toast toast-info flex items-start gap-3 rounded-lg border px-4 py-3 shadow-lg bg-white pointer-events-auto animate-toast-in"><svg class="w-5 h-5 flex-shrink-0 text-primary-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg><span class="text-sm font-medium text-gray-900">'.e(session('info')).'</span></div>'; }
        @endphp
    </div>
    @endif

    {{-- Mobile notice: red exclamation and info when on phone (hidden on md and up) --}}
    @include('partials.mobile-notice')

    @yield('content')
    
    </div><!-- /#quizsnap-app -->
    <script src="{{ asset('js/quizsnap-guard.js') }}"></script>
    @yield('copy_restriction_script')
    <script src="{{ asset('js/app.js') }}" defer></script>
    @stack('scripts')

    @if(config('broadcasting.default') === 'reverb' && config('broadcasting.connections.reverb.app_id'))
    <!-- Real-time: Reverb WebSocket - no auto-reload to keep pages light -->
    <script>
    window.REVERB_CONFIG = {
        key: @json(config('broadcasting.connections.reverb.key')),
        host: @json(config('broadcasting.connections.reverb.options.host')),
        port: @json(config('broadcasting.connections.reverb.options.port')),
        scheme: @json(config('broadcasting.connections.reverb.options.scheme') ?? 'http')
    };
    </script>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js" crossorigin="anonymous" defer></script>
    <script>
    (function() {
        var c = window.REVERB_CONFIG;
        if (!c || !c.key) return;
        function init() {
            try {
                var pusher = new Pusher(c.key, {
                    wsHost: c.host,
                    wsPort: parseInt(c.port, 10) || 8080,
                    wssPort: 443,
                    forceTLS: (c.scheme || 'http') === 'https',
                    disableStats: true,
                    enabledTransports: ['ws', 'wss'],
                    cluster: 'mt1'
                });
                pusher.subscribe('quizsnap').bind('DataUpdated', function(data) {
                    window.dispatchEvent(new CustomEvent('quizsnap-data-updated', { detail: data || {} }));
                });
            } catch (e) { console.warn('Reverb:', e); }
        }
        if (typeof Pusher !== 'undefined') init(); else window.addEventListener('load', init);
    })();
    </script>
    @endif

    <!-- Auto-dismiss toast notifications after 4s -->
    <script>
    (function() {
        var container = document.getElementById('flash-container');
        if (!container) return;
        setTimeout(function() { container.remove(); }, 4000);
    })();
    </script>
</body>
</html>
