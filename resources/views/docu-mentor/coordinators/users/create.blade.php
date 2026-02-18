@extends('layouts.dashboard')

@section('title', 'Add User – Docu Mentor')
@section('dashboard_heading', 'Add User')

@section('dashboard_content')
<h1 class="text-2xl font-bold text-gray-900 mb-6">Add User</h1>
<div class="bg-white rounded-lg border border-gray-200 p-6 max-w-lg">
    <form action="{{ route('dashboard.coordinators.users.store') }}" method="post">
        @csrf
        <div class="space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                    class="w-full rounded-lg border-gray-300 shadow-sm">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" name="username" id="username" value="{{ old('username') }}" required
                    class="w-full rounded-lg border-gray-300 shadow-sm">
                @error('username')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone (optional)</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                    class="w-full rounded-lg border-gray-300 shadow-sm">
                @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email (optional)</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}"
                    class="w-full rounded-lg border-gray-300 shadow-sm">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" id="password" required
                    class="w-full rounded-lg border-gray-300 shadow-sm">
                @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required
                    class="w-full rounded-lg border-gray-300 shadow-sm">
            </div>
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role" id="role" required class="w-full rounded-lg border-gray-300 shadow-sm">
                    <option value="student" {{ old('role') === 'student' ? 'selected' : '' }}>Student</option>
                    <option value="leader" {{ old('role') === 'leader' ? 'selected' : '' }}>Leader</option>
                    <option value="examiner" {{ old('role') === 'examiner' ? 'selected' : '' }}>Examiner (Supervisor)</option>
                    <option value="coordinator" {{ old('role') === 'coordinator' ? 'selected' : '' }}>Coordinator</option>
                </select>
                @error('role')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="mt-6 flex gap-3">
            <button type="submit" class="px-4 py-2 rounded-lg btn btn-primary">Create</button>
            <a href="{{ route('dashboard.coordinators.users.index') }}" class="btn border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</div>
@endsection
