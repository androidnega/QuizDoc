@extends('layouts.app')

@section('title', 'Docu Mentor – Login')

@section('content')
<div class="min-h-screen bg-slate-100 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-xl shadow-lg p-8 border border-slate-200">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-slate-900">Docu Mentor</h1>
                <p class="text-sm text-slate-600 mt-1">Project documentation management</p>
            </div>

            {{-- Flash shown once via layouts.app flash popup --}}

            <form action="{{ route('docu-mentor.login.post') }}" method="post" class="space-y-4">
                @csrf
                <div>
                    <label for="login" class="block text-sm font-medium text-slate-700 mb-1">Phone or Username</label>
                    <input type="text" name="login" id="login" value="{{ old('login') }}" required autofocus
                        class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('login')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                    <input type="password" name="password" id="password" required
                        class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="w-full py-2.5 px-4 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Log in
                </button>
            </form>
            <p class="mt-4 text-center text-sm text-slate-500">
                <a href="{{ url('/') }}" class="text-indigo-600 hover:text-indigo-800">← Back to QuizDoc</a>
            </p>
        </div>
    </div>
</div>
@endsection
