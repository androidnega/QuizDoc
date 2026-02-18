@extends('layouts.student')

@section('title', 'Link Expired')
@section('body_class', 'bg-offwhite')

@section('content')
<div class="min-h-[100dvh] min-h-screen flex items-center justify-center px-4 py-8 pl-[max(1rem,env(safe-area-inset-left))] pr-[max(1rem,env(safe-area-inset-right))] pb-[max(1.5rem,env(safe-area-inset-bottom))]">
    <div class="max-w-md w-full text-center">
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
            <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
                <svg class="w-7 h-7 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-gray-900 mb-2">Invalid or expired link</h1>
            <p class="text-gray-600 text-sm mb-6">The quiz link is invalid or has expired. The quiz may have been closed, removed, ended by your examiner, or the time window has passed. You can no longer start or continue this quiz. Contact your examiner if you have questions.</p>
            <a href="{{ route('student.landing') }}" class="btn btn-action w-full py-2.5 text-sm font-semibold">
                Back to Home
            </a>
        </div>
    </div>
</div>
@endsection
