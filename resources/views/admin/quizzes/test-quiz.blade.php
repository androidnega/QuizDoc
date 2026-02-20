@extends('layouts.dashboard')

@section('title', 'Test Quiz')
@section('dashboard_heading', 'Test Quiz')

@section('dashboard_content')
<div class="w-full max-w-2xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 md:p-8">
        @if(session('success'))
            <div class="alert alert-success mb-6">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-error mb-6">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293z" clip-rule="evenodd"/></svg>
                {{ session('error') }}
            </div>
        @endif

        <h2 class="text-xl font-semibold text-gray-900 mb-2">Create a test quiz</h2>
        <p class="text-gray-600 mb-6">
            This creates a minimal quiz (10 questions, 30 min, topic: General knowledge) so you can try the
            <strong>Generate questions with Gemini</strong> flow on the quiz overview page.
        </p>

        @if($classGroups->isEmpty())
            <div class="rounded-lg border-2 border-amber-200 bg-amber-50 p-4 text-amber-900">
                <p class="font-medium">No class group available</p>
                <p class="text-sm mt-1">Create a class group, attach a course, and add at least one student. Then return here.</p>
                <a href="{{ route('dashboard.class-groups.index') }}" class="inline-flex items-center mt-3 px-4 py-2 text-sm font-medium rounded-lg bg-amber-600 text-white hover:bg-amber-700">Go to Class Groups</a>
            </div>
        @else
            @if(!($hasAiKey ?? false))
                <div class="rounded-lg border-2 border-amber-200 bg-amber-50 p-4 text-amber-900 mb-6">
                    <p class="font-medium">No AI API key set</p>
                    <p class="text-sm mt-1">Add Gemini (primary), OpenAI or DeepSeek in Dashboard → Settings → AI so generation works.</p>
                </div>
            @endif
            <form action="{{ route('dashboard.quizzes.create-test') }}" method="post">
                @csrf
                <p class="text-sm text-gray-500 mb-4">
                    Quiz will use: <strong>{{ $classGroups->first()?->name ?? 'first group' }}</strong>,
                    course <strong>{{ $classGroups->first()?->courses->first()?->name ?? 'first course' }}</strong>.
                </p>
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-3 text-sm font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Create test quiz and open it
                </button>
            </form>
        @endif

        <hr class="my-8 border-gray-200">
        <p class="text-sm text-gray-500">
            <a href="{{ route('dashboard.quizzes.index') }}" class="text-indigo-600 hover:underline">Back to Quizzes</a>
            &middot;
            <a href="{{ route('dashboard.quizzes.create') }}" class="text-indigo-600 hover:underline">Create quiz (full form)</a>
        </p>
    </div>
</div>
@endsection
