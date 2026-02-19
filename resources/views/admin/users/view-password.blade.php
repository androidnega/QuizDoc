@extends('layouts.dashboard')

@section('title', 'View/Reset Password')
@section('dashboard_heading', 'View/Reset Password')

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6 bg-slate-50/80 rounded-xl p-4 sm:p-6">
    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-600 mb-4">
        <a href="{{ route('dashboard') }}" class="hover:text-primary-600 shrink-0">Dashboard</a>
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('dashboard.users.index') }}" class="hover:text-primary-600 shrink-0">User management</a>
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-slate-900 font-medium">View/Reset Password</span>
    </div>

    <div class="w-full min-w-0 max-w-full">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 w-full min-w-0 max-w-full overflow-hidden">
            <h2 class="text-xl font-semibold text-slate-900 mb-2">View/Reset Password</h2>
            <p class="text-sm text-slate-600 mb-1">
                Enter your admin password to view or reset the password for <strong>{{ $user->username }}</strong>.
            </p>
            <p class="text-xs text-slate-500 mt-2">
                Original passwords cannot be displayed (encrypted). You can reset the password below.
            </p>

            @if(isset($password_verified) && $password_verified)
                <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-sm text-blue-800">{{ $message ?? 'Password is set.' }}</p>
                </div>
            @endif

            @if(isset($temporary_password))
                <div class="mt-6 p-5 bg-success-50 border-2 border-success-300 rounded-xl">
                    <p class="text-sm font-semibold text-success-900 mb-3">Temporary password generated</p>
                    <div class="bg-white border border-success-200 rounded-lg p-4 mb-3">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Password for {{ $user->username }}</label>
                        <div class="flex flex-wrap items-center gap-2">
                            <input type="text" id="temp-password-display" value="{{ $temporary_password }}" readonly
                                class="flex-1 min-w-0 font-mono text-lg font-bold text-slate-900 bg-slate-50 border border-slate-300 rounded-lg px-3 py-2">
                            <button type="button" onclick="copyPassword()"
                                class="inline-flex items-center justify-center rounded-lg bg-success-600 px-4 py-2 text-sm font-medium text-white hover:bg-success-700 shrink-0">
                                Copy
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-success-700">This password is shown only once. Copy it now — you won't see it again.</p>
                </div>
            @endif

            <form action="{{ route('dashboard.users.view-password', $user) }}" method="post" class="mt-6 space-y-5">
                @csrf

                @if(!isset($password_verified) || !$password_verified)
                    <div>
                        <label for="admin_password" class="block text-sm font-medium text-slate-700 mb-1">Your admin password</label>
                        <input type="password" name="admin_password" id="admin_password" required autofocus
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 @error('admin_password') border-red-500 @enderror"
                            placeholder="Enter your admin password">
                        @error('admin_password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @else
                    <input type="hidden" name="admin_password" value="{{ old('admin_password') }}">
                @endif

                @if(isset($password_verified) && $password_verified && !isset($temporary_password))
                    <div class="space-y-5">
                        <div class="rounded-lg border border-slate-200 bg-slate-50/50 p-4">
                            <p class="text-sm font-medium text-slate-700 mb-2">Generate temporary password</p>
                            <p class="text-xs text-slate-600 mb-3">Generate a secure random password that you can view once.</p>
                            <button type="submit" name="action" value="generate"
                                class="w-full rounded-lg bg-success-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-success-700">
                                Generate temporary password
                            </button>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50/50 p-4">
                            <p class="text-sm font-medium text-slate-700 mb-3">Set custom password</p>
                            <div class="space-y-3">
                                <div>
                                    <label for="new_password" class="block text-sm font-medium text-slate-700 mb-1">New password</label>
                                    <input type="password" name="new_password" id="new_password" minlength="8"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 @error('new_password') border-red-500 @enderror"
                                        placeholder="Min 8 characters">
                                    @error('new_password')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="new_password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirm new password</label>
                                    <input type="password" name="new_password_confirmation" id="new_password_confirmation" minlength="8"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 @error('new_password_confirmation') border-red-500 @enderror"
                                        placeholder="Re-enter new password">
                                    @error('new_password_confirmation')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                            <button type="submit" name="action" value="reset"
                                class="mt-3 w-full rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">
                                Set custom password
                            </button>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-sm text-red-700">{{ session('error') }}</p>
                    </div>
                @endif

                <div class="flex flex-wrap gap-3 pt-2 border-t border-slate-200">
                    @if(!isset($password_verified) || !$password_verified)
                        <button type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 shrink-0">
                            Verify & continue
                        </button>
                    @endif
                    <a href="{{ route('dashboard.users.index') }}"
                        class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 shrink-0">
                        {{ isset($temporary_password) ? 'Done' : 'Cancel' }}
                    </a>
                </div>
            </form>

            @if(isset($temporary_password))
            <script>
            function copyPassword() {
                var el = document.getElementById('temp-password-display');
                el.select();
                el.setSelectionRange(0, 99999);
                navigator.clipboard.writeText(el.value).then(function() {
                    var btn = event.target;
                    var orig = btn.textContent;
                    btn.textContent = 'Copied!';
                    setTimeout(function() { btn.textContent = orig; }, 2000);
                });
            }
            </script>
            @endif
        </div>
    </div>
</div>
@endsection
