@extends('layouts.app')
@section('title', 'Session ended')
@section('body_class', 'bg-offwhite')
@section('content')
<div class="min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 max-w-md w-full text-center">
        <h1 class="text-xl font-semibold text-slate-800 mb-2">Session ended</h1>
        <p class="text-slate-600 text-sm mb-6">Your session has ended. Please log in again to continue.</p>
        <a href="{{ url('/login') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium bg-amber-500 text-black hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-600 focus:ring-offset-2">
            Log in again
        </a>
    </div>
</div>
<meta http-equiv="refresh" content="3;url={{ url('/login') }}">
@endsection
