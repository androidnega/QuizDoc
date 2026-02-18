@extends('layouts.app')

@section('title', 'Forgot password')

@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
        <div class="card p-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-900">Forgot password</h1>
                <p class="text-sm text-gray-600 mt-1">Enter your username to receive a reset link by email</p>
            </div>

            {{-- Flash shown once via layouts.app flash popup --}}

            <form action="{{ route('password.forgot.send') }}" method="post" class="space-y-4">
                @csrf
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" id="username" value="{{ old('username') }}" required autofocus
                        class="input w-full @error('username') border-danger-500 @enderror">
                    @error('username')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary w-full">Send reset link</button>
            </form>
            <p class="mt-4 text-center">
                <a href="{{ route('login') }}" class="text-sm text-primary-600 hover:text-primary-800">Back to login</a>
            </p>
        </div>
    </div>
</div>
@endsection
