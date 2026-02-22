@extends('layouts.app')

@section('title', 'Login')

@section('content')
@php
    $heroUrl = !empty(trim($loginHeroImage ?? '')) ? trim($loginHeroImage) : asset('assets/hero-section.jpg');
@endphp
<div class="min-h-screen bg-offwhite flex flex-col lg:flex-row">
    {{-- Hero section: image left (desktop) or top (mobile) --}}
    <div class="relative w-full lg:w-1/2 min-h-[220px] sm:min-h-[280px] lg:min-h-screen flex-shrink-0 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-primary-900/90 via-primary-800/85 to-slate-900/90 z-10"></div>
        <img
            src="{{ $heroUrl }}"
            alt=""
            class="absolute inset-0 w-full h-full object-cover"
            fetchpriority="high"
            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
        >
        <div class="absolute inset-0 hidden items-center justify-center bg-gradient-to-br from-primary-700 to-primary-900 z-0" aria-hidden="true">
            <div class="text-center px-6">
                <span class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/10 text-white/90 mb-4">
                    <i class="fas fa-graduation-cap text-3xl"></i>
                </span>
                <p class="text-white/90 font-display font-semibold text-lg">QuizSnap</p>
                <p class="text-white/70 text-sm mt-1">Secure assessments</p>
            </div>
        </div>
        <div class="absolute inset-0 z-20 flex flex-col justify-end p-6 sm:p-8 lg:p-10 text-white">
            <p class="font-display font-semibold text-xl sm:text-2xl text-white drop-shadow-lg">Welcome back</p>
            <p class="text-white/90 text-sm sm:text-base mt-1 max-w-sm">Sign in with your staff credentials to access the dashboard.</p>
        </div>
    </div>

    {{-- Form section --}}
    <div class="flex-1 flex items-center justify-center px-4 py-10 sm:py-12 lg:py-16">
        <div class="w-full max-w-md">
            <div class="bg-white border border-gray-200 rounded-2xl shadow-lg shadow-gray-200/50 px-6 py-7 sm:px-8 sm:py-8">
                <div class="text-center mb-6 lg:text-left">
                    <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 tracking-tight">Staff login</h1>
                    <p class="text-sm text-gray-600 mt-1">Enter your staff credentials to access the dashboard.</p>
                </div>

                <form action="{{ route('login.post') }}" method="post" class="space-y-4">
                    @csrf
                    <div class="space-y-1">
                        <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                        <input
                            type="text"
                            name="username"
                            id="username"
                            value="{{ old('username') }}"
                            required
                            autofocus
                            placeholder="Username"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('username') border-danger-500 focus:ring-danger-500 focus:border-danger-500 @enderror"
                        >
                        @error('username')
                            <p class="text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1">
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            required
                            placeholder="Password"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('password') border-danger-500 focus:ring-danger-500 focus:border-danger-500 @enderror"
                        >
                        @error('password')
                            <p class="text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="w-full inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors"
                    >
                        Log in
                    </button>
                </form>

                <div class="mt-4 text-center lg:text-left">
                    <a href="{{ route('password.forgot') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">
                        Forgot password?
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
