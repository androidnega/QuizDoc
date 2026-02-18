@extends('layouts.dashboard')

@section('title', 'Reset password')
@section('dashboard_heading', 'Reset password')

@section('dashboard_content')
<div class="profile-page-full w-full min-h-full px-4 py-6 md:px-6 md:py-8 space-y-6">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="rounded-lg border border-gray-200 bg-white p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Change password</h2>
        <form action="{{ route('dashboard.profile.password.update') }}" method="post" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current password</label>
                <input type="password" name="current_password" id="current_password" required class="input" autocomplete="current-password">
                @error('current_password')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New password</label>
                <input type="password" name="password" id="password" required class="input" autocomplete="new-password">
                @error('password')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm new password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required class="input" autocomplete="new-password">
            </div>
            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="btn btn-primary">Update password</button>
                <a href="{{ route('dashboard.profile.show') }}" class="btn btn-secondary">Back to profile</a>
            </div>
        </form>
    </div>
</div>
@endsection
