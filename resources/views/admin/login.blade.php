@extends('layouts.app')

@section('title', 'Login')

@section('content')
@php
    // Use Super Admin Settings → Login page hero image; fallback to public asset (same default as in Settings)
    $heroUrl = !empty(trim($loginHeroImage ?? '')) ? trim($loginHeroImage) : asset('assets/hero-section.jpg');
@endphp
<div class="min-h-screen flex items-center justify-center px-4 py-10" style="background-color: #eef1f4;">
    <div class="w-full max-w-[520px] bg-white rounded-xl shadow-lg overflow-hidden">
        {{-- Hero section inside card: image + overlay + SIGN IN --}}
        <div class="relative h-36 sm:h-44 overflow-hidden">
            <img
                src="{{ $heroUrl }}"
                alt=""
                class="absolute inset-0 w-full h-full object-cover object-top"
                fetchpriority="high"
                onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
            >
            <div class="absolute inset-0 bg-[rgba(47,83,93,0.82)]" aria-hidden="true"></div>
            <div class="absolute inset-0 flex items-center justify-center">
                <h1 class="text-white text-2xl sm:text-3xl font-bold tracking-widest uppercase">Sign in</h1>
            </div>
        </div>

        {{-- Form section --}}
        <div class="px-6 sm:px-8 py-4 sm:py-5">
            <form action="{{ route('login.post') }}" method="post" class="space-y-4">
                @csrf
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-600 mb-1">Username</label>
                    <input
                        type="text"
                        name="username"
                        id="username"
                        value="{{ old('username') }}"
                        required
                        autofocus
                        placeholder="Enter username"
                        class="w-full px-0 py-2.5 text-gray-900 placeholder-gray-400 bg-transparent border-0 border-b border-gray-300 rounded-none focus:outline-none focus:ring-0 focus:border-primary-600 @error('username') border-danger-500 @enderror"
                    >
                    @error('username')
                        <p class="text-sm text-danger-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-600 mb-1">Password</label>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        required
                        placeholder="Enter password"
                        class="w-full px-0 py-2.5 text-gray-900 placeholder-gray-400 bg-transparent border-0 border-b border-gray-300 rounded-none focus:outline-none focus:ring-0 focus:border-primary-600 @error('password') border-danger-500 @enderror"
                    >
                    @error('password')
                        <p class="text-sm text-danger-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between gap-4">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" value="1" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm text-gray-500">Remember me</span>
                    </label>
                    <a href="{{ route('password.forgot') }}" class="text-sm text-gray-500 hover:text-primary-600">Forgot Password?</a>
                </div>

                <button
                    type="submit"
                    class="w-full py-3 rounded-full text-gray-900 font-semibold uppercase tracking-wide text-sm shadow-sm hover:opacity-95 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-opacity"
                    style="background-color: #eab308;"
                >
                    Login
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
