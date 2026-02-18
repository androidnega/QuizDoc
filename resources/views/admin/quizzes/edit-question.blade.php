@extends('layouts.dashboard')

@section('title', 'Edit Question - ' . $quiz->title)
@section('dashboard_heading', 'Edit Question')

@section('dashboard_content')
@php
    $opts = collect($question->options ?? [])->keyBy('key');
    $optionA = old('option_a', $opts->get('A')['text'] ?? '');
    $optionB = old('option_b', $opts->get('B')['text'] ?? '');
    $optionC = old('option_c', $opts->get('C')['text'] ?? '');
    $optionD = old('option_d', $opts->get('D')['text'] ?? '');
@endphp
<div class="w-full space-y-6">
    <div class="bg-white rounded-lg border border-gray-200 p-6 md:p-8">
            <form action="{{ route('dashboard.quizzes.questions.update', [$quiz, $question]) }}" method="post" class="space-y-6">
                @csrf
                @method('PUT')
                <div>
                    <label for="text" class="block text-sm font-medium text-gray-700 mb-2">Question text *</label>
                    <textarea id="text" name="text" rows="4" required class="input">{{ old('text', $question->text) }}</textarea>
                </div>
                <div>
                    <label for="topic" class="block text-sm font-medium text-gray-700 mb-2">Topic (optional)</label>
                    <input type="text" id="topic" name="topic" value="{{ old('topic', $question->topic) }}" class="input" placeholder="e.g. Algebra">
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label for="option_a" class="block text-sm font-medium text-gray-700 mb-2">Option A *</label>
                        <input type="text" id="option_a" name="option_a" required value="{{ $optionA }}" class="input">
                    </div>
                    <div>
                        <label for="option_b" class="block text-sm font-medium text-gray-700 mb-2">Option B *</label>
                        <input type="text" id="option_b" name="option_b" required value="{{ $optionB }}" class="input">
                    </div>
                    <div>
                        <label for="option_c" class="block text-sm font-medium text-gray-700 mb-2">Option C *</label>
                        <input type="text" id="option_c" name="option_c" required value="{{ $optionC }}" class="input">
                    </div>
                    <div>
                        <label for="option_d" class="block text-sm font-medium text-gray-700 mb-2">Option D *</label>
                        <input type="text" id="option_d" name="option_d" required value="{{ $optionD }}" class="input">
                    </div>
                </div>
                <div>
                    <label for="correct_answer" class="block text-sm font-medium text-gray-700 mb-2">Correct answer *</label>
                    <select id="correct_answer" name="correct_answer" required class="input">
                        @foreach(['A','B','C','D'] as $letter)
                            <option value="{{ $letter }}" {{ old('correct_answer', $question->correct_answer) === $letter ? 'selected' : '' }}>{{ $letter }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-4 pt-4">
                    <button type="submit" class="btn btn-primary">Save changes</button>
                    <a href="{{ route('dashboard.quizzes.show', $quiz) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
    </div>
</div>
@endsection
