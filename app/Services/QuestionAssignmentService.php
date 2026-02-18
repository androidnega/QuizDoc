<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Quiz;

class QuestionAssignmentService
{
    private const DISPLAY_KEYS = ['A', 'B', 'C', 'D'];

    /**
     * Assign randomized questions from approved questions only.
     * - Question order: random per student (shuffle then take).
     * - For MCQ: options are shuffled per student so the same question can have correct answer A for one student and B/C/D for another.
     * Returns [
     *   'question_ids' => [id, ...],
     *   'correct_answers' => [question_id => display_letter],
     *   'shuffled_options' => [question_id => [['key'=>'A','text'=>'...'], ...]],
     * ].
     */
    public function assignQuestions(Quiz $quiz): array
    {
        $count = $quiz->getQuestionsPerStudent();
        $pool = $quiz->questions()->get()->shuffle();
        if ($pool->count() < $count) {
            return ['question_ids' => [], 'correct_answers' => [], 'shuffled_options' => []];
        }
        $selected = $pool->take($count)->values();
        $questionIds = $selected->pluck('id')->all();
        $correctAnswers = [];
        $shuffledOptions = [];
        foreach ($selected as $question) {
            if ($question->type === 'mcq' && is_array($question->options) && !empty($question->options)) {
                $displayCorrect = $this->shuffleOptionsAndGetDisplayCorrect($question->options, $question->correct_answer);
                $correctAnswers[$question->id] = $displayCorrect['display_correct'];
                $shuffledOptions[$question->id] = $displayCorrect['options'];
            } else {
                $correctAnswers[$question->id] = $question->correct_answer;
                if (is_array($question->options) && !empty($question->options)) {
                    $shuffledOptions[$question->id] = $question->options;
                }
            }
        }
        return [
            'question_ids' => $questionIds,
            'correct_answers' => $correctAnswers,
            'shuffled_options' => $shuffledOptions,
        ];
    }

    /**
     * Shuffle MCQ options and re-key as A, B, C, D. Returns display correct letter and new options array.
     *
     * @param  array<int, array{key: string, text: string}>  $options
     * @return array{display_correct: string, options: array<int, array{key: string, text: string}>}
     */
    private function shuffleOptionsAndGetDisplayCorrect(array $options, ?string $originalCorrect): array
    {
        $list = [];
        $originalCorrectText = null;
        foreach ($options as $opt) {
            $key = $opt['key'] ?? $opt;
            $text = $opt['text'] ?? $opt;
            $list[] = ['key' => $key, 'text' => $text];
            if (trim((string) $originalCorrect) !== '' && (string) $key === trim((string) $originalCorrect)) {
                $originalCorrectText = $text;
            }
        }
        if ($originalCorrectText === null && trim((string) $originalCorrect) !== '') {
            foreach ($list as $item) {
                if ($item['key'] === $originalCorrect) {
                    $originalCorrectText = $item['text'];
                    break;
                }
            }
        }
        shuffle($list);
        $letters = array_slice(self::DISPLAY_KEYS, 0, count($list));
        $newOptions = [];
        $displayCorrect = $originalCorrect;
        foreach ($list as $i => $item) {
            $newKey = $letters[$i] ?? (string) ($i + 1);
            $newOptions[] = ['key' => $newKey, 'text' => $item['text']];
            if ($originalCorrectText !== null && ($item['text'] ?? '') === $originalCorrectText) {
                $displayCorrect = $newKey;
            }
        }
        return ['display_correct' => $displayCorrect ?? 'A', 'options' => $newOptions];
    }
}
