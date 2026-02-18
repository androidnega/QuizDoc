@extends('layouts.app')

@section('title', 'Reset password')

@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
        <div class="card p-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-900">Reset password</h1>
                <p class="text-sm text-gray-600 mt-1">Enter your new password</p>
            </div>

            {{-- session('error') shown once via layouts.app flash popup --}}
            @if($errors->any())
                <div class="mb-4 p-3 rounded-lg bg-danger-50 border border-danger-200 text-danger-800 text-sm">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('password.reset') }}" method="post" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New password</label>
                    <input type="password" name="password" id="password" required minlength="8"
                        class="input w-full" autocomplete="new-password"
                        placeholder="At least 8 characters, one letter and one number">
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required minlength="8"
                        class="input w-full" autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary w-full">Reset password</button>
            </form>
            <p class="mt-4 text-center">
                <a href="{{ route('login') }}" class="text-sm text-primary-600 hover:text-primary-800">Back to login</a>
            </p>
        </div>
    </div>
</div>
@endsection
