@extends('layouts.dashboard')

@section('title', 'Study guide')
@section('dashboard_heading', 'Study guide')

@section('dashboard_content')
<div class="w-full max-w-3xl space-y-6">
    <p class="text-sm text-gray-600">Class group: <strong>{{ $classGroup->name }}</strong></p>

    @forelse($classGroup->quizzes as $quiz)
        <section class="border border-gray-200 rounded-lg p-4 bg-white">
            <h2 class="text-base font-semibold text-gray-900 mb-3">{{ $quiz->title }}</h2>

            @php
                $questionsFromTable = $quiz->questions;
                $pools = $quiz->questionPools;
            @endphp

            @if($questionsFromTable->isEmpty() && $pools->isEmpty())
                <p class="text-sm text-gray-500">No questions.</p>
            @else
                <ol class="list-decimal list-inside space-y-3 text-sm text-gray-800">
                    @foreach($questionsFromTable as $idx => $q)
                        @php
                            $answerText = $q->correct_answer ?? '—';
                            if ($q->options && is_array($q->options)) {
                                foreach ($q->options as $opt) {
                                    $key = $opt['key'] ?? $opt;
                                    if ((string)($key) === (string)($q->correct_answer ?? '')) {
                                        $answerText = $opt['text'] ?? $opt;
                                        break;
                                    }
                                }
                            }
                        @endphp
                        <li class="pl-1">
                            <span class="font-medium">{{ $q->text ? e(\Illuminate\Support\Str::limit($q->text, 400)) : '(no text)' }}</span>
                            <span class="block mt-1 text-gray-600">Answer: {{ e($answerText) }}</span>
                        </li>
                    @endforeach
                    @foreach($pools as $pool)
                        @php
                            $poolAnswerText = $pool->correct_answer ?? '—';
                            if ($pool->options && is_array($pool->options)) {
                                foreach ($pool->options as $opt) {
                                    $key = $opt['key'] ?? $opt;
                                    if ((string)($key) === (string)($pool->correct_answer ?? '')) {
                                        $poolAnswerText = $opt['text'] ?? $opt;
                                        break;
                                    }
                                }
                            }
                        @endphp
                        <li class="pl-1">
                            <span class="font-medium">{{ $pool->question_text ? e(\Illuminate\Support\Str::limit($pool->question_text, 400)) : '(no text)' }}</span>
                            <span class="block mt-1 text-gray-600">Answer: {{ e($poolAnswerText) }}</span>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    @empty
        <p class="text-sm text-gray-500">No quizzes in this group.</p>
    @endforelse
</div>
@endsection
