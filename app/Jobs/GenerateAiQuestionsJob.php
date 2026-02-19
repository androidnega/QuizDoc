<?php

namespace App\Jobs;

use App\Models\Quiz;
use App\Services\AiQuestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GenerateAiQuestionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(
        public int $quizId,
        public int $userId,
        public int $targetCount,
        public array $topics,
        public string $sourceText = ''
    ) {}

    public function handle(AiQuestionService $aiService): void
    {
        $quiz = Quiz::find($this->quizId);
        if (! $quiz) {
            return;
        }

        $cacheKey = 'quiz_ai_generation:' . $this->quizId . ':' . $this->userId;
        $progressKey = 'quiz_ai_progress:' . $this->quizId;

        $this->writeProgress($progressKey, $this->quizId, $this->targetCount, 0, 'running');

        $generated = 0;
        $emptyInARow = 0;
        $batchSize = 5;
        $maxEmptyBatches = 40;

        while ($generated < $this->targetCount && $emptyInARow < $maxEmptyBatches) {
            $want = min($batchSize, $this->targetCount - $generated);
            $ids = $aiService->generatePoolAndStore($quiz, $this->topics, $want, $this->sourceText ?: null);
            $got = count($ids);

            if ($got > 0) {
                $generated += $got;
                $emptyInARow = 0;
                $this->writeProgress($progressKey, $this->quizId, $this->targetCount, $generated, 'running');
            } else {
                $emptyInARow++;
            }

            if ($generated >= $this->targetCount) {
                break;
            }

            usleep(500000);
        }

        $this->writeProgress($progressKey, $this->quizId, $this->targetCount, $generated, 'completed');
        Cache::forget($cacheKey);
    }

    public function failed(\Throwable $e): void
    {
        $progressKey = 'quiz_ai_progress:' . $this->quizId;
        $existing = Cache::get($progressKey);
        $generated = is_array($existing) ? (int) ($existing['generated_count'] ?? 0) : 0;
        $this->writeProgress($progressKey, $this->quizId, $this->targetCount, $generated, 'failed');
        Cache::forget('quiz_ai_generation:' . $this->quizId . ':' . $this->userId);
        Log::error('GenerateAiQuestionsJob failed: ' . $e->getMessage(), ['quiz_id' => $this->quizId]);
    }

    private function writeProgress(string $key, int $quizId, int $target, int $generated, string $status): void
    {
        Cache::put($key, [
            'quiz_id' => $quizId,
            'target_count' => $target,
            'generated_count' => $generated,
            'status' => $status,
            'updated_at' => now()->toIso8601String(),
        ], now()->addHours(2));
    }
}
