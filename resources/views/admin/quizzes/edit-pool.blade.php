@extends('layouts.dashboard')

@section('title', 'Edit Pool Question - ' . $quiz->title)
@section('dashboard_heading', 'Edit Question (Pool)')

@push('styles')
<style>
#pool-edit-form .input,
#pool-edit-form input[type="text"],
#pool-edit-form select,
#pool-edit-form textarea {
    border: 1px solid #e5e7eb;
    background-color: #fff;
    color: #374151;
    font-size: 1rem;
    font-weight: 400;
    padding: 0.5rem 0.75rem;
    min-height: 44px;
    border-radius: 0.5rem;
    width: 100%;
}
#pool-edit-form .input:focus,
#pool-edit-form input:focus,
#pool-edit-form select:focus,
#pool-edit-form textarea:focus {
    border-color: #93c5fd;
    outline: none;
    box-shadow: 0 0 0 2px rgba(147, 197, 253, 0.35);
}
#pool-edit-form label {
    font-weight: 500;
    color: #4b5563;
    font-size: 0.875rem;
    display: block;
    margin-bottom: 0.5rem;
}
#pool-edit-form textarea {
    min-height: 6rem;
    resize: vertical;
}
</style>
@endpush

@section('dashboard_content')
@php
    $opts = collect($pool->options ?? [])->keyBy('key');
    $optionA = old('option_a', $opts->get('A')['text'] ?? '');
    $optionB = old('option_b', $opts->get('B')['text'] ?? '');
    $optionC = old('option_c', $opts->get('C')['text'] ?? '');
    $optionD = old('option_d', $opts->get('D')['text'] ?? '');
@endphp
<div class="w-full min-w-0 space-y-6">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 md:p-8">
            <form id="pool-edit-form" action="{{ route('dashboard.quizzes.pool.update', [$quiz, $pool]) }}" method="post" class="space-y-6">
                @csrf
                @method('PUT')
                <div>
                    <label for="question_text" class="block text-sm font-medium text-gray-700 mb-2">Question text *</label>
                    <textarea id="question_text" name="question_text" rows="4" required class="input" placeholder="Enter the question text…">{{ old('question_text', $pool->question_text) }}</textarea>
                </div>
                <div>
                    <label for="topic" class="block text-sm font-medium text-gray-700 mb-2">Topic (optional)</label>
                    <input type="text" id="topic" name="topic" value="{{ old('topic', $pool->topic) }}" class="input" placeholder="e.g. Algebra">
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label for="option_a" class="block text-sm font-medium text-gray-700 mb-2">Option A *</label>
                        <input type="text" id="option_a" name="option_a" required value="{{ $optionA }}" class="input" placeholder="Option A">
                    </div>
                    <div>
                        <label for="option_b" class="block text-sm font-medium text-gray-700 mb-2">Option B *</label>
                        <input type="text" id="option_b" name="option_b" required value="{{ $optionB }}" class="input" placeholder="Option B">
                    </div>
                    <div>
                        <label for="option_c" class="block text-sm font-medium text-gray-700 mb-2">Option C *</label>
                        <input type="text" id="option_c" name="option_c" required value="{{ $optionC }}" class="input" placeholder="Option C">
                    </div>
                    <div>
                        <label for="option_d" class="block text-sm font-medium text-gray-700 mb-2">Option D *</label>
                        <input type="text" id="option_d" name="option_d" required value="{{ $optionD }}" class="input" placeholder="Option D">
                    </div>
                </div>
                <div>
                    <label for="correct_answer" class="block text-sm font-medium text-gray-700 mb-2">Correct answer *</label>
                    <select id="correct_answer" name="correct_answer" required class="input">
                        @foreach(['A','B','C','D'] as $letter)
                            <option value="{{ $letter }}" {{ old('correct_answer', $pool->correct_answer) === $letter ? 'selected' : '' }}>{{ $letter }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-gray-200">
                    <button type="submit" class="inline-flex items-center justify-center px-6 py-3 rounded-lg font-semibold min-h-[48px] bg-yellow-400 hover:bg-yellow-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2">Save changes</button>
                    <a href="{{ route('dashboard.quizzes.show', $quiz) }}" class="inline-flex items-center justify-center px-6 py-3 rounded-lg font-semibold min-h-[48px] bg-red-600 hover:bg-red-700 text-white focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Cancel</a>
                </div>
            </form>
    </div>
</div>
@endsection
