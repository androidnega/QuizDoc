@extends('layouts.dashboard')

@section('title', 'Edit User – Docu Mentor')
@section('dashboard_heading', 'Edit User')

@section('dashboard_content')
<h1 class="text-2xl font-bold text-gray-900 mb-6">Edit User</h1>
<div class="bg-white rounded-lg border border-gray-200 p-6 max-w-lg">
    <form action="{{ route('dashboard.coordinators.users.update', $user) }}" method="post">
        @csrf
        @method('PUT')
        <div class="space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                    class="w-full rounded-lg border-gray-300 shadow-sm">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" name="username" id="username" value="{{ old('username', $user->username) }}" required
                    class="w-full rounded-lg border-gray-300 shadow-sm">
                @error('username')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
                    class="w-full rounded-lg border-gray-300 shadow-sm">
                @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}"
                    class="w-full rounded-lg border-gray-300 shadow-sm">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password (leave blank to keep)</label>
                <input type="password" name="password" id="password"
                    class="w-full rounded-lg border-gray-300 shadow-sm">
                @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation"
                    class="w-full rounded-lg border-gray-300 shadow-sm">
            </div>
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role" id="role" required class="w-full rounded-lg border-gray-300 shadow-sm">
                    <option value="student" {{ old('role', $user->role) === 'student' ? 'selected' : '' }}>Student</option>
                    <option value="leader" {{ old('role', $user->role) === 'leader' ? 'selected' : '' }}>Leader</option>
                    <option value="examiner" {{ old('role', $user->role) === 'examiner' ? 'selected' : '' }}>Examiner (Supervisor)</option>
                    <option value="coordinator" {{ old('role', $user->role) === 'coordinator' ? 'selected' : '' }}>Coordinator</option>
                    <option value="super_admin" {{ old('role', $user->role) === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                </select>
                @error('role')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="mt-6 flex gap-3">
            <button type="submit" class="px-4 py-2 rounded-lg btn btn-primary">Update</button>
            <a href="{{ route('dashboard.coordinators.users.index') }}" class="btn border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</div>
@endsection
