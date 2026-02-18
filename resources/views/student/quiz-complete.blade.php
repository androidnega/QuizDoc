@extends('layouts.student')

@section('title', 'Quiz submitted')
@section('body_class', 'bg-offwhite')

@section('content')
<div class="min-h-[100dvh] min-h-screen flex items-center justify-center px-4 py-8">
    <div class="max-w-md w-full text-center">
        <div class="bg-white border border-gray-200 rounded-xl p-8 shadow-sm">
            <div class="w-16 h-16 rounded-full bg-success-100 flex items-center justify-center mx-auto mb-4">
                <span class="text-3xl" aria-hidden="true">✓</span>
            </div>
            <h1 class="text-xl font-bold text-gray-900 mb-2">Quiz submitted</h1>
            @if(!empty($resultUrl))
            <p class="text-gray-600 text-sm mb-6">Your answers have been recorded. You can view your result now.</p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ $resultUrl }}" class="btn btn-action py-2.5 px-5 text-sm font-semibold inline-flex items-center justify-center gap-2">
                    See your result
                </a>
                <a href="{{ route('student.landing') }}" class="btn btn-secondary py-2.5 px-5 text-sm font-medium inline-flex items-center justify-center">
                    Back to home
                </a>
            </div>
            @elseif(!empty($isLoggedIn))
            <p class="text-gray-600 text-sm mb-6">Your answers have been recorded. See your result in My Quizzes.</p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('dashboard.my-quizzes') }}" class="btn btn-action py-2.5 px-5 text-sm font-semibold inline-flex items-center justify-center gap-2">
                    See your result
                </a>
                <a href="{{ route('student.landing') }}" class="btn btn-secondary py-2.5 px-5 text-sm font-medium inline-flex items-center justify-center">
                    Back to home
                </a>
            </div>
            @else
            <p class="text-gray-600 text-sm mb-6">Your answers have been recorded. Log in with your index number and phone to see your marks, review your answers, and what you got right or wrong.</p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('student.account.login.form') }}" class="btn btn-action py-2.5 px-5 text-sm font-semibold inline-flex items-center justify-center gap-2">
                    Log in to see results
                </a>
                <a href="{{ route('student.landing') }}" class="btn btn-secondary py-2.5 px-5 text-sm font-medium inline-flex items-center justify-center">
                    Back to home
                </a>
            </div>
            <p class="mt-4 text-sm text-gray-500">Use the same index and phone you used when starting the quiz. We'll send you a code by SMS.</p>
            @endif
        </div>
    </div>
</div>
@endsection
