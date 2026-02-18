@extends('layouts.dashboard')

@section('title', 'View/Reset Password')
@section('dashboard_heading', 'View/Reset Password')

@section('dashboard_content')
<div class="w-full max-w-md mx-auto">
    <div class="card p-6">
        <div class="mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-2">View/Reset Password</h2>
            <p class="text-sm text-gray-600">
                Enter your admin password to view or reset the password for <strong>{{ $user->username }}</strong>.
            </p>
            <p class="text-xs text-gray-500 mt-2">
                Note: Original passwords cannot be displayed as they are encrypted. You can reset the password below.
            </p>
        </div>

        @if(isset($password_verified) && $password_verified)
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-800">{{ $message ?? 'Password is set.' }}</p>
            </div>
        @endif

        @if(isset($temporary_password))
            <div class="mb-6 p-4 bg-success-50 border-2 border-success-400 rounded-lg">
                <p class="text-sm font-semibold text-success-900 mb-2">⚠️ Temporary Password Generated</p>
                <div class="bg-white border border-success-300 rounded-lg p-4 mb-3">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Password for {{ $user->username }}:</label>
                    <div class="flex items-center gap-2">
                        <input 
                            type="text" 
                            id="temp-password-display" 
                            value="{{ $temporary_password }}" 
                            readonly
                            class="flex-1 font-mono text-lg font-bold text-gray-900 bg-gray-50 border border-gray-300 rounded px-3 py-2"
                        >
                        <button 
                            type="button" 
                            onclick="copyPassword()" 
                            class="btn btn-success text-sm px-4 py-2"
                        >
                            Copy
                        </button>
                    </div>
                </div>
                <p class="text-xs text-success-700">
                    ⚠️ This password is shown only once. Copy it now - you won't be able to see it again!
                </p>
            </div>
        @endif

        <form action="{{ route('dashboard.users.view-password', $user) }}" method="post">
            @csrf
            
            @if(!isset($password_verified) || !$password_verified)
                <div class="mb-4">
                    <label for="admin_password" class="block text-sm font-medium text-gray-700 mb-1">
                        Your Admin Password
                    </label>
                    <input 
                        type="password" 
                        name="admin_password" 
                        id="admin_password" 
                        required 
                        autofocus
                        class="input w-full @error('admin_password') border-danger-500 @enderror"
                        placeholder="Enter your admin password"
                    >
                    @error('admin_password')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>
            @else
                <input type="hidden" name="admin_password" value="{{ old('admin_password') }}">
            @endif

            @if(isset($password_verified) && $password_verified && !isset($temporary_password))
                <div class="mb-4 space-y-4">
                    <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                        <p class="text-sm font-medium text-gray-700 mb-3">Generate Temporary Password</p>
                        <p class="text-xs text-gray-600 mb-3">Generate a secure random password that you can view once.</p>
                        <button type="submit" name="action" value="generate" class="btn btn-success w-full">
                            Generate Temporary Password
                        </button>
                    </div>
                    
                    <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                        <p class="text-sm font-medium text-gray-700 mb-3">Set Custom Password</p>
                        <div class="mb-3">
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">
                                New Password
                            </label>
                            <input 
                                type="password" 
                                name="new_password" 
                                id="new_password" 
                                minlength="8"
                                class="input w-full @error('new_password') border-danger-500 @enderror"
                                placeholder="Enter new password (min 8 characters)"
                            >
                            @error('new_password')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="new_password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                                Confirm New Password
                            </label>
                            <input 
                                type="password" 
                                name="new_password_confirmation" 
                                id="new_password_confirmation" 
                                minlength="8"
                                class="input w-full @error('new_password_confirmation') border-danger-500 @enderror"
                                placeholder="Confirm new password"
                            >
                            @error('new_password_confirmation')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" name="action" value="reset" class="btn btn-primary w-full">
                            Set Custom Password
                        </button>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 p-3 bg-danger-50 border border-danger-200 rounded-lg">
                    <p class="text-sm text-danger-600">{{ session('error') }}</p>
                </div>
            @endif

            <div class="flex gap-3">
                @if(!isset($password_verified) || !$password_verified)
                    <button type="submit" class="btn btn-primary flex-1">
                        Verify & Continue
                    </button>
                @endif
                <a href="{{ route('dashboard.users.index') }}" class="btn btn-secondary">
                    {{ isset($temporary_password) ? 'Done' : 'Cancel' }}
                </a>
            </div>
        </form>

        @if(isset($temporary_password))
        <script>
        function copyPassword() {
            const passwordInput = document.getElementById('temp-password-display');
            passwordInput.select();
            passwordInput.setSelectionRange(0, 99999); // For mobile devices
            document.execCommand('copy');
            
            const btn = event.target;
            const originalText = btn.textContent;
            btn.textContent = 'Copied!';
            btn.classList.add('bg-success-600');
            setTimeout(() => {
                btn.textContent = originalText;
                btn.classList.remove('bg-success-600');
            }, 2000);
        }
        </script>
        @endif
    </div>
</div>
@endsection
