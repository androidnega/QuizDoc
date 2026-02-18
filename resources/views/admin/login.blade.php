@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="min-h-screen bg-offwhite flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-lg px-6 py-7 sm:px-8 sm:py-8">
            <div class="text-center mb-6">
                <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 tracking-tight">Staff login</h1>
                <p class="text-sm text-gray-600 mt-1">Enter your staff credentials to access the dashboard.</p>
            </div>

            {{-- Flash (error/info) is shown via layouts.app toast --}}

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
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('username') border-danger-500 focus:ring-danger-500 focus:border-danger-500 @enderror"
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
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('password') border-danger-500 focus:ring-danger-500 focus:border-danger-500 @enderror"
                    >
                    @error('password')
                        <p class="text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="w-full inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                >
                    Log in
                </button>
            </form>

            <div class="mt-4 text-center">
                <a href="{{ route('password.forgot') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">
                    Forgot password?
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
