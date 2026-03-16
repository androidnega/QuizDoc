@extends('layouts.app')

@section('title', 'QuizSnap')
@section('body_class', 'landing-page')

@push('styles')
<style>
    body, .home-page-wrap { background: #f8fafc !important; }
    .home-main { display: flex; flex: 1 1 0; min-height: 0; flex-direction: column; align-items: center; justify-content: center; padding: 0.75rem 1rem; }
    .site-container { width: 100%; max-width: 960px; margin: 0 auto; padding: 0 1rem; }
    .home-input { outline: none; border: 1px solid #e2e8f0; background: #fff; transition: border-color 0.2s, box-shadow 0.2s; -webkit-user-select: text; user-select: text; }
    .home-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15); }
    .home-input.token-valid { background-color: #f0fdf4 !important; border-color: #22c55e !important; color: #15803d; }
    .home-input.token-invalid { background-color: #fef2f2 !important; border-color: #ef4444 !important; color: #dc2626; }
    .home-input.token-loading { background-color: #fffbeb !important; border-color: #f59e0b !important; color: #d97706; }
    .btn-home-cta.bt–––––n-cta-disabled, .btn-home-cta:disabled { background: #cbd5e1 !important; color: #fff !important; cursor: not-allowed; pointer-events: none; }
    .btn-home-cta:not(.btn-cta-disabled):not(:disabled) { background: #3b82f6; color: #fff !important; font-weight: 600; border: none; }
    .btn-home-cta:not(.btn-cta-disabled):not(:disabled):hover { background: #2563eb; }
    .logo-text { font-size: 1.25rem; font-weight: 700; letter-spacing: -0.02em; display: inline-flex; align-items: center; gap: 0.5rem; }
    .logo-mark { width: 3rem; height: 3rem; flex-shrink: 0; }
    .home-container { width: 100%; max-width: 720px; margin: 0 auto; }
    .home-hero { width: 100%; text-align: center; }
    .home-quiz-row { display: flex; flex-wrap: wrap; gap: 0; justify-content: center; align-items: stretch; max-width: 480px; margin: 0 auto; }
    .home-quiz-row .home-input { border-top-right-radius: 0; border-bottom-right-radius: 0; flex: 1; min-width: 200px; }
    .home-quiz-row .btn-home-cta { border-top-left-radius: 0; border-bottom-left-radius: 0; white-space: nowrap; }
    .home-features-row { display: grid; gap: 1rem; width: 100%; max-width: 720px; margin: 0 auto; grid-template-columns: 1fr; }
    .home-feature-card { text-align: center; padding: 0.75rem 0.75rem; border-radius: 0.75rem; border: none; }
    .home-feature-card .feature-icon { width: 32px; height: 32px; margin: 0 auto 0.5rem; display: block; }
    .home-feature-card .feature-title { font-size: 0.875rem; font-weight: 700; color: #1e293b; margin-bottom: 0.25rem; }
    .home-feature-card .feature-desc { font-size: 0.75rem; color: #64748b; line-height: 1.35; margin: 0; }
    /* Desktop: bigger hero; feature cards with reduced height + space below welcome */
    @media (min-width: 769px) {
        .home-hero h1 { font-size: 2.75rem; line-height: 1.2; }
        .home-hero p { font-size: 1.375rem; line-height: 1.5; }
        .home-features-row { gap: 1rem; max-width: 880px; margin-top: 2rem; }
        .home-feature-card { padding: 0.5rem 0.75rem; border-radius: 0.75rem; }
        .home-feature-card .feature-icon { width: 28px; height: 28px; margin: 0 auto 0.35rem; }
        .home-feature-card .feature-title { font-size: 0.9375rem; margin-bottom: 0.25rem; }
        .home-feature-card .feature-desc { font-size: 0.8125rem; color: #475569; line-height: 1.35; }
    }
    /* Desktop: deeper card colors */
    .home-feature-card.card-secure { background: #93c5fd; }
    .home-feature-card.card-secure .feature-icon { color: #1d4ed8; }
    .home-feature-card.card-fast { background: #c4b5fd; }
    .home-feature-card.card-fast .feature-icon { color: #5b21b6; }
    .home-feature-card.card-reliable { background: #6ee7b7; }
    .home-feature-card.card-reliable .feature-icon { color: #047857; }
    @media (min-width: 640px) {
        .home-features-row { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 639px) {
        .home-quiz-row { flex-direction: column; }
        .home-quiz-row .home-input { border-radius: 0.5rem; border-bottom-left-radius: 0; }
        .home-quiz-row .btn-home-cta { border-radius: 0.5rem; border-top-left-radius: 0; }
    }
    /* Mobile: app-like — welcome + feature cards in column; no quiz field; fixed viewport, no scroll; no gap under hero */
    @media (max-width: 768px) {
        .header-nav-desktop { display: none !important; }
        .home-page-wrap { height: 100dvh; min-height: 100dvh; max-height: 100dvh; overflow: hidden; display: flex; flex-direction: column; -webkit-tap-highlight-color: transparent; }
        body.landing-page { overflow: hidden; position: fixed; width: 100%; }
        .home-hero-image-mobile-wrap { display: block !important; width: 100%; overflow: hidden; flex-shrink: 0; margin: 0 !important; padding: 0 !important; font-size: 0; line-height: 0; }
        .home-hero-image-mobile { display: block; width: 100%; max-height: 180px; height: 180px; object-fit: cover; vertical-align: top; }
        .home-main { margin: 0 !important; padding: 2.0rem 1rem 0.75rem !important; flex: 1; min-height: 0; overflow: hidden; display: flex; align-items: flex-start; justify-content: flex-start !important; }
        .landing-no-hero .home-main { margin-top: 0 !important; padding-top: 0.5rem !important; }
        .home-main .site-container { margin: 0 !important; padding: 0 0 0 0 !important; width: 100%; max-width: 100%; }
        .home-main .home-container { margin: 0 !important; padding: 0 !important; width: 100%; max-width: 100%; }
        .home-hero { padding: 0 !important; margin: 0 !important; }
        .home-hero h1 { font-size: 1.5rem; font-weight: 700; margin: 0 0 0.25rem 0 !important; letter-spacing: -0.02em; color: #0f172a; line-height: 1.25; }
        .home-hero p { font-size: 0.8125rem; color: #64748b; margin-bottom: 0; line-height: 1.4; }
        .home-quiz-form-mobile-hide { display: none !important; }
        .home-features-row { grid-template-columns: 1fr; gap: 0.5rem; margin-top: 0.5rem !important; max-width: 100%; }
        .home-feature-card { padding: 0.625rem 0.75rem; border-radius: 0.75rem; text-align: left; }
        .home-feature-card .feature-icon { width: 24px; height: 24px; margin: 0 0 0.375rem 0; display: block; }
        .home-feature-card .feature-title { font-size: 0.875rem; font-weight: 700; margin-bottom: 0.125rem; line-height: 1.2; color: #1e293b; }
        .home-feature-card .feature-desc { font-size: 0.75rem; line-height: 1.35; color: #475569; margin: 0; }
        .home-page-wrap header .flex { min-height: 2.75rem; height: auto; }
        .home-page-wrap footer { padding-top: 0.5rem; padding-bottom: max(0.5rem, env(safe-area-inset-bottom)); flex-shrink: 0; }
        .home-page-wrap footer p { font-size: 0.6875rem; }
    }
    .home-hero-image-mobile-wrap { display: none; }
    @media (min-width: 769px) {
        .home-hero-image-mobile-wrap { display: none !important; }
    }
    @media (min-width: 769px) { .mobile-landing-sidebar-wrap { display: none !important; } .landing-mobile-menu-btn { display: none !important; } }
    .mobile-landing-sidebar-wrap.is-open { width: 100%; overflow: visible; }
    .mobile-landing-sidebar-wrap.is-open #landing-mobile-sidebar-overlay { opacity: 1; }
    .mobile-landing-sidebar-wrap.is-open #landing-mobile-sidebar { transform: translateX(0); }
</style>
@endpush

@section('content')
<div class="home-page-wrap min-h-screen flex flex-col font-sans antialiased @if(!($landingHeroEnabled ?? true)) landing-no-hero @endif">
    <header class="shrink-0 bg-white border-b border-slate-200 z-50">
        <div class="site-container">
            <div class="flex h-16 md:h-20 items-center justify-between gap-3">
                <a href="{{ route('student.landing') }}" class="logo-text no-underline text-xl md:text-2xl flex items-center gap-2">
                    <img src="{{ asset('assets/mainlogo-quizsnap.png') }}" alt="QuizSnap" class="logo-mark h-12 w-auto md:h-14 object-contain">
                </a>
                <button type="button" id="landing-mobile-menu-btn" class="landing-mobile-menu-btn flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-300 md:hidden" aria-label="Open menu" aria-expanded="false">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <nav class="header-nav-desktop flex items-center gap-6">
                    <a href="{{ route('about-system') }}" class="text-sm font-medium text-slate-700 hover:text-blue-600 transition-colors no-underline" style="text-decoration: none; color: #475569;">
                        About System
                    </a>
                    @if(isset($student) && $student)
                        <a href="{{ route('dashboard') }}" style="display: inline-block; padding: 0.625rem 1.5rem; background-color: #2563eb; color: #ffffff !important; font-weight: 600; font-size: 0.875rem; border-radius: 0.5rem; text-decoration: none; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#1d4ed8'" onmouseout="this.style.backgroundColor='#2563eb'">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('student.account.login.form') }}" style="display: inline-block; padding: 0.625rem 1.5rem; background-color: #2563eb; color: #ffffff !important; font-weight: 600; font-size: 0.875rem; border-radius: 0.5rem; text-decoration: none; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#1d4ed8'" onmouseout="this.style.backgroundColor='#2563eb'">
                            Student Login
                        </a>
                    @endif
                </nav>
            </div>
        </div>
        {{-- Mobile: slide-out sidebar with breadcrumb (Dashboard + About system) — only on homepage mobile --}}
        <div id="landing-mobile-sidebar-wrap" class="mobile-landing-sidebar-wrap fixed inset-y-0 left-0 z-[60] w-0 overflow-hidden md:hidden" aria-hidden="true">
            <div id="landing-mobile-sidebar-overlay" class="absolute inset-0 bg-black/40 opacity-0 transition-opacity duration-200" aria-hidden="true"></div>
            <aside id="landing-mobile-sidebar" class="absolute left-0 top-0 bottom-0 w-64 max-w-[85vw] bg-white border-r border-slate-200 shadow-xl flex flex-col -translate-x-full transition-transform duration-200 ease-out">
                <div class="flex h-14 items-center justify-between px-4 border-b border-slate-100">
                    <span class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Menu</span>
                    <button type="button" id="landing-mobile-sidebar-close" class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Close menu">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <nav class="flex-1 px-3 py-4" aria-label="Breadcrumb">
                    <ol class="space-y-1 text-sm" role="list">
                        <li><a href="{{ route('student.landing') }}" class="block py-2.5 px-3 rounded-lg text-slate-500 font-medium">Home</a></li>
                        @if(isset($student) && $student)
                            <li><a href="{{ route('dashboard') }}" class="block py-2.5 px-3 rounded-lg text-slate-700 hover:bg-slate-50 hover:text-blue-600 font-medium no-underline">Dashboard</a></li>
                        @else
                            <li><a href="{{ route('student.account.login.form') }}" class="block py-2.5 px-3 rounded-lg text-slate-700 hover:bg-slate-50 hover:text-blue-600 font-medium no-underline">Student login</a></li>
                        @endif
                        <li><a href="{{ route('about-system') }}" class="block py-2.5 px-3 rounded-lg text-slate-700 hover:bg-slate-50 hover:text-blue-600 font-medium no-underline">About the system</a></li>
                    </ol>
                </nav>
            </aside>
        </div>
    </header>

    @if($landingHeroEnabled ?? true)
    @php
        $mobileHeroImage = $landingHeroImage ?? 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=800';
    @endphp
    <div class="home-hero-image-mobile-wrap">
        <img src="{{ e(trim($mobileHeroImage)) }}" alt="" class="home-hero-image-mobile" width="800" height="140" loading="eager" decoding="async" referrerpolicy="no-referrer">
    </div>
    @endif

    <main class="home-main flex-1 min-h-0 flex items-center justify-start md:justify-center">
        <div class="site-container">
            <div class="home-container max-w-xl mx-auto">
                <div class="home-hero">
                    <h1 class="text-xl sm:text-2xl md:text-5xl font-bold text-slate-900 mb-2 md:mb-3 tracking-tight">Welcome to QuizSnap</h1>
                    <p class="text-sm md:text-xl text-slate-500 mb-4 md:mb-6">A modern platform for secure and efficient online assessments</p>
                    @if($landingShowQuizToken ?? false)
                    <form action="{{ route('student.start-quiz') }}" method="post" id="start-quiz-form" class="mb-4 home-quiz-form-mobile-hide">
                        @csrf
                        <div class="home-quiz-row">
                            <label for="quiz-token" class="sr-only">Quiz token</label>
                            <input type="text" id="quiz-token" name="link" placeholder="Enter quiz token (e.g. KTdie54-3Sx9)" required autocomplete="off"
                                class="home-input rounded-l-lg px-3 py-2.5 text-sm min-h-[44px] border border-slate-300">
                            <button type="submit" id="start-quiz-btn" disabled class="btn-home-cta btn-cta-disabled rounded-r-lg px-4 py-2.5 text-sm font-semibold min-h-[44px]">
                                Start Quiz →
                            </button>
                        </div>
                        <div id="token-message" class="text-xs min-h-[1rem] text-center font-medium mt-1"></div>
                        @error('link')
                            <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                        @enderror
                    </form>
                    @endif
                    <div class="home-features-row">
                        <div class="home-feature-card card-secure">
                            <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            <div class="feature-title">Secure</div>
                            <p class="feature-desc">Proctored environment with advanced security measures</p>
                        </div>
                        <div class="home-feature-card card-fast">
                            <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                            </svg>
                            <div class="feature-title">Fast</div>
                            <p class="feature-desc">Instant access and seamless experience</p>
                        </div>
                        <div class="home-feature-card card-reliable">
                            <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M9 12l2 2 4-4"/>
                            </svg>
                            <div class="feature-title">Reliable</div>
                            <p class="feature-desc">Desktop optimized for consistent performance</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="shrink-0 border-t border-slate-200 bg-white py-3">
        <div class="site-container text-center">
            <p class="text-xs text-slate-400">
                &copy; {{ date('Y') }} QuizSnap. All rights reserved -
                <a href="https://www.ausweblabs.com" target="_blank" rel="noopener noreferrer" class="no-underline hover:no-underline text-slate-400 hover:text-slate-500">ausweblabs</a>
            </p>
        </div>
    </footer>
</div>
@endsection

@push('scripts')
<script>
(function() {
    var sidebarWrap = document.getElementById('landing-mobile-sidebar-wrap') || document.querySelector('.mobile-landing-sidebar-wrap');
    var menuBtn = document.getElementById('landing-mobile-menu-btn');
    var sidebarClose = document.getElementById('landing-mobile-sidebar-close');
    var overlay = document.getElementById('landing-mobile-sidebar-overlay');
    if (sidebarWrap && menuBtn) {
        function openSidebar() {
            sidebarWrap.classList.add('is-open');
            if (sidebarWrap.getAttribute('aria-hidden') === 'true') sidebarWrap.setAttribute('aria-hidden', 'false');
            if (menuBtn) menuBtn.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
        }
        function closeSidebar() {
            sidebarWrap.classList.remove('is-open');
            sidebarWrap.setAttribute('aria-hidden', 'true');
            if (menuBtn) menuBtn.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }
        menuBtn.addEventListener('click', openSidebar);
        if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
        if (overlay) overlay.addEventListener('click', closeSidebar);
    }
})();
</script>
@if($landingShowQuizToken ?? false)
<script>
(function() {
    var DEBOUNCE_MS = 350;
    var input = document.getElementById('quiz-token');
    var messageEl = document.getElementById('token-message');
    var form = document.getElementById('start-quiz-form');
    var validateUrl = '{{ route("student.validate-token") }}';
    var csrf = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content;
    var debounceTimer = null;
    var lastToken = '';
    var btn = document.getElementById('start-quiz-btn');

    function setButtonState(enable) {
        if (!btn) return;
        btn.disabled = !enable;
        btn.classList.toggle('btn-cta-disabled', !enable);
    }

    function setState(klass, text) {
        input.classList.remove('token-valid', 'token-invalid', 'token-loading');
        if (klass) input.classList.add(klass);
        if (messageEl) {
            messageEl.textContent = text || '';
            messageEl.className = 'text-sm min-h-[1.25rem] text-center font-medium';
            if (text) {
                if (klass === 'token-valid') messageEl.classList.add('text-green-600', 'font-medium');
                else if (klass === 'token-invalid') messageEl.classList.add('text-red-600');
                else messageEl.classList.add('text-amber-600');
            }
        }
        setButtonState(klass === 'token-valid');
    }

    function runValidation(tokenValue) {
        if (!tokenValue || tokenValue.length < 8) {
            setState('token-invalid', 'Please enter a valid quiz token.');
            setButtonState(false);
            return;
        }
        setState('token-loading', 'Checking…');
        setButtonState(false);
        var fd = new FormData();
        fd.append('_token', csrf);
        fd.append('token', tokenValue);
        fetch(validateUrl, { method: 'POST', body: fd, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.valid) {
                    setState('token-valid', 'Valid token, proceed.');
                } else {
                    setState('token-invalid', data.message || 'Invalid token.');
                }
            })
            .catch(function() {
                setState('token-invalid', 'Could not validate. Try again.');
            });
    }

    function onTokenInput() {
        var raw = (input && input.value) ? input.value.trim() : '';
        if (debounceTimer) clearTimeout(debounceTimer);
        if (!raw || raw.length < 8) {
            setState('', '');
            setButtonState(false);
            lastToken = '';
            return;
        }
        lastToken = raw;
        debounceTimer = setTimeout(function() {
            debounceTimer = null;
            runValidation(raw);
        }, DEBOUNCE_MS);
    }

    if (input) {
        input.addEventListener('input', onTokenInput);
        input.addEventListener('paste', function() {
            setTimeout(function() {
                var raw = (input && input.value) ? input.value.trim() : '';
                if (raw.length >= 8) {
                    if (debounceTimer) clearTimeout(debounceTimer);
                    debounceTimer = null;
                    runValidation(raw);
                } else {
                    onTokenInput();
                }
            }, 50);
        });
        input.addEventListener('blur', function() {
            if (lastToken && !input.value.trim()) {
                if (debounceTimer) clearTimeout(debounceTimer);
                debounceTimer = null;
                setState('', '');
                setButtonState(false);
                lastToken = '';
            }
        });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            if (!input.classList.contains('token-valid')) {
                e.preventDefault();
                var raw = (input && input.value) ? input.value.trim() : '';
                if (raw.length >= 8) {
                    if (debounceTimer) clearTimeout(debounceTimer);
                    debounceTimer = null;
                    runValidation(raw);
                } else {
                    setState('token-invalid', 'Please enter a valid quiz token (e.g. KTdie54-3Sx9).');
                    setButtonState(false);
                }
                return false;
            }
        });
    }
})();
</script>
@endif
@endpush
