<?php

namespace App\Services;

use App\Models\AiGenerationLog;
use App\Models\Question;
use App\Models\QuestionPool;
use App\Models\Quiz;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AiQuestionService
{
    private const GEMINI_MODEL_PRIMARY = 'gemini-2.5-flash';
    private const GEMINI_MODEL_FALLBACK = 'gemini-2.0-flash';

    /** Prefer database (Setting) over config/env so admin dashboard keys are used for generation. */
    private function getGeminiKey(): ?string
    {
        $key = Setting::getValue(Setting::KEY_GEMINI_API);
        if ($key !== null && $key !== '') {
            return $key;
        }
        return config('services.gemini.key', env('GEMINI_API_KEY')) ?: null;
    }

    /** Prefer database (Setting) over config/env so admin dashboard keys are used for generation. */
    private function getDeepSeekKey(): ?string
    {
        $key = Setting::getValue(Setting::KEY_DEEPSEEK_API);
        if ($key !== null && $key !== '') {
            return $key;
        }
        return config('services.deepseek.key', env('DEEPSEEK_API_KEY')) ?: null;
    }

    /**
     * Call Gemini API (Google AI). Tries primary model, then fallback on 404.
     * Returns ['text' => string|null, 'usage' => ['prompt_tokens' => int, 'completion_tokens' => int, 'total_tokens' => int]].
     */
    private function callGemini(string $apiKey, string $prompt): array
    {
        $emptyUsage = ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
        foreach ([self::GEMINI_MODEL_PRIMARY, self::GEMINI_MODEL_FALLBACK] as $model) {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . urlencode($apiKey);
            $response = Http::timeout(60)
                ->post($url, [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 16384,
                    ],
                ]);
            if ($response->status() === 404) {
                continue;
            }
            if (!$response->successful()) {
                return ['text' => null, 'usage' => $emptyUsage];
            }
            $body = $response->json();
            if (!is_array($body) || empty($body['candidates'][0])) {
                return ['text' => null, 'usage' => $emptyUsage];
            }
            $candidate = $body['candidates'][0];
            $text = $candidate['content']['parts'][0]['text'] ?? null;
            $usage = $emptyUsage;
            if (isset($body['usageMetadata']) && is_array($body['usageMetadata'])) {
                $um = $body['usageMetadata'];
                $usage = [
                    'prompt_tokens' => (int) ($um['promptTokenCount'] ?? $um['prompt_token_count'] ?? 0),
                    'completion_tokens' => (int) ($um['candidatesTokenCount'] ?? $um['candidates_token_count'] ?? $um['outputTokenCount'] ?? 0),
                    'total_tokens' => (int) ($um['totalTokenCount'] ?? $um['total_token_count'] ?? 0),
                ];
                if ($usage['total_tokens'] === 0 && ($usage['prompt_tokens'] > 0 || $usage['completion_tokens'] > 0)) {
                    $usage['total_tokens'] = $usage['prompt_tokens'] + $usage['completion_tokens'];
                }
            }
            if (is_string($text) && $text !== '') {
                return ['text' => $text, 'usage' => $usage];
            }
            return ['text' => null, 'usage' => $usage];
        }
        return ['text' => null, 'usage' => $emptyUsage];
    }

    /**
     * Build a user-facing detail string when Gemini returns 200 but no usable text (blocked, empty, etc.).
     */
    private function geminiFailureDetail(string $rawBody, $parsed): string
    {
        if (is_array($parsed)) {
            $candidates = $parsed['candidates'][0] ?? null;
            if (is_array($candidates)) {
                $finish = $candidates['finishReason'] ?? $candidates['finish_reason'] ?? null;
                if ($finish && strtolower((string) $finish) !== 'stop') {
                    return 'Model finish reason: ' . $finish . '. Try again or use a different prompt.';
                }
                $safety = $candidates['safetyRatings'] ?? $candidates['safety_ratings'] ?? null;
                if (!empty($safety)) {
                    return 'Response was blocked or filtered. Try again or check API safety settings.';
                }
            }
            if (isset($parsed['error']['message'])) {
                return (string) $parsed['error']['message'];
            }
        }
        return 'Empty or blocked response from Gemini. Check API key and quota, or try again.';
    }

    /**
     * Call DeepSeek API (OpenAI-compatible). Returns ['text' => string|null, 'usage' => [...]].
     */
    private function callDeepSeek(string $apiKey, string $prompt): array
    {
        $emptyUsage = ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
        $response = Http::withToken($apiKey)
            ->timeout(60)
            ->post('https://api.deepseek.com/v1/chat/completions', [
                'model' => 'deepseek-chat',
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'temperature' => 0.7,
            ]);
        if (!$response->successful()) {
            return ['text' => null, 'usage' => $emptyUsage];
        }
        $body = $response->json();
        if (!is_array($body) || empty($body['choices'][0]['message']['content'])) {
            return ['text' => null, 'usage' => $emptyUsage];
        }
        $usage = $emptyUsage;
        if (isset($body['usage']) && is_array($body['usage'])) {
            $u = $body['usage'];
            $usage = [
                'prompt_tokens' => (int) ($u['prompt_tokens'] ?? 0),
                'completion_tokens' => (int) ($u['completion_tokens'] ?? 0),
                'total_tokens' => (int) ($u['total_tokens'] ?? 0),
            ];
            if ($usage['total_tokens'] === 0 && ($usage['prompt_tokens'] > 0 || $usage['completion_tokens'] > 0)) {
                $usage['total_tokens'] = $usage['prompt_tokens'] + $usage['completion_tokens'];
            }
        }
        return ['text' => $body['choices'][0]['message']['content'], 'usage' => $usage];
    }

    /**
     * Test AI connection: try Gemini first, then DeepSeek. Returns result for API/UI.
     */
    public function testConnection(): array
    {
        $prompt = 'Reply with exactly: OK';
        $geminiKey = $this->getGeminiKey();
        if ($geminiKey !== null) {
            foreach ([self::GEMINI_MODEL_PRIMARY, self::GEMINI_MODEL_FALLBACK] as $model) {
                $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . urlencode($geminiKey);
                $response = Http::timeout(10)->post($url, [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => 0, 'maxOutputTokens' => 64],
                ]);
                if ($response->status() === 404) {
                    continue;
                }
                if ($response->successful()) {
                    $body = $response->json();
                    if (is_array($body) && !empty($body['candidates'][0]['content']['parts'][0]['text'])) {
                        $text = $body['candidates'][0]['content']['parts'][0]['text'];
                        if (is_string($text) && trim($text) !== '') {
                            return ['success' => true, 'provider' => 'gemini', 'message' => 'AI connection OK.', 'reply' => trim($text)];
                        }
                    }
                    // HTTP 200 but empty/blocked or unexpected structure
                    $detail = $this->geminiFailureDetail($response->body(), $response->json());
                } else {
                    $detail = 'HTTP ' . $response->status() . ': ' . (strlen($response->body()) > 500 ? substr($response->body(), 0, 500) . '…' : $response->body());
                }
                return ['success' => false, 'provider' => 'gemini', 'message' => 'Gemini request failed.', 'detail' => $detail];
            }
            return ['success' => false, 'provider' => 'gemini', 'message' => 'Gemini model not found (404). Try again later or check API updates.', 'detail' => null];
        }
        $deepseekKey = $this->getDeepSeekKey();
        if ($deepseekKey !== null) {
            $response = Http::withToken($deepseekKey)
                ->timeout(10)
                ->post('https://api.deepseek.com/v1/chat/completions', [
                    'model' => 'deepseek-chat',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0,
                    'max_tokens' => 10,
                ]);
            if ($response->successful()) {
                $body = $response->json();
                $text = $body['choices'][0]['message']['content'] ?? null;
                if (is_string($text) && trim($text) !== '') {
                    return ['success' => true, 'provider' => 'deepseek', 'message' => 'AI connection OK.', 'reply' => trim($text)];
                }
            }
            $detail = $response->successful() ? null : ('HTTP ' . $response->status() . ': ' . $response->body());
            return ['success' => false, 'provider' => 'deepseek', 'message' => 'DeepSeek request failed.', 'detail' => $detail];
        }
        return ['success' => false, 'provider' => null, 'message' => 'No API key set. Add a Gemini or DeepSeek key in Settings.', 'detail' => null];
    }

    /**
     * Whether any AI API key (Gemini or DeepSeek) is configured. When false, AI generation is blocked.
     */
    public function hasApiKey(): bool
    {
        return $this->getGeminiKey() !== null || $this->getDeepSeekKey() !== null;
    }

    /**
     * Maximum number of questions allowed per quiz generation (config + env).
     */
    public function getPerQuizLimit(): int
    {
        $limit = (int) config('quizsnap.ai.max_generation_per_quiz', 100);
        return $limit > 0 ? $limit : 100;
    }

    /**
     * Call AI and return text plus provider and usage for logging. Returns ['text' => string|null, 'provider' => string|null, 'usage' => [...]].
     */
    private function callAiWithUsage(string $prompt): array
    {
        $empty = ['text' => null, 'provider' => null, 'usage' => ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0]];
        $geminiKey = $this->getGeminiKey();
        if ($geminiKey !== null) {
            $result = $this->callGemini($geminiKey, $prompt);
            if (isset($result['text']) && $result['text'] !== null && $result['text'] !== '') {
                return ['text' => $result['text'], 'provider' => 'gemini', 'usage' => $result['usage'] ?? $empty['usage']];
            }
        }
        $deepseekKey = $this->getDeepSeekKey();
        if ($deepseekKey !== null) {
            $result = $this->callDeepSeek($deepseekKey, $prompt);
            if (isset($result['text']) && $result['text'] !== null && $result['text'] !== '') {
                return ['text' => $result['text'], 'provider' => 'deepseek', 'usage' => $result['usage'] ?? $empty['usage']];
            }
        }
        return $empty;
    }

    /**
     * Try Gemini first, then DeepSeek. Returns raw text or null (for non-generation use e.g. wrong-answer explanation).
     */
    private function callAi(string $prompt): ?string
    {
        $result = $this->callAiWithUsage($prompt);
        return $result['text'];
    }

    /**
     * Extract JSON array from model response (may be wrapped in markdown or text). Uses first [ to last ] for outer array.
     */
    private function parseJsonArray(string $content): ?array
    {
        $start = strpos($content, '[');
        if ($start === false) {
            $decoded = json_decode($content, true);
            return is_array($decoded) ? $decoded : null;
        }
        $end = strrpos($content, ']');
        if ($end === false || $end <= $start) {
            $decoded = json_decode(substr($content, $start), true);
            return is_array($decoded) ? $decoded : null;
        }
        $json = substr($content, $start, $end - $start + 1);
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Generate questions via Gemini (primary) or DeepSeek (fallback) and store in question_pools as unapproved.
     * Blocked when no API key: returns [] and no placeholders. Enforces per-quiz limit. Logs token usage per quiz.
     * If $sourceText is provided (e.g. from uploaded PDF/DOCX/TXT), the AI uses it as the primary material.
     * For large counts (>20), uses batching to avoid token limits and parsing issues.
     * Returns array of question_pool IDs.
     */
    public function generatePoolAndStore(Quiz $quiz, array $topics, int $count, ?string $sourceText = null): array
    {
        if (!$this->hasApiKey()) {
            return [];
        }
        $count = min($count, $this->getPerQuizLimit());
        if ($count < 1) {
            return [];
        }
        $topicNames = collect($topics)->pluck('name')->filter()->implode(', ');
        if (empty($topicNames)) {
            $topicNames = 'General knowledge';
        }
        
        // Use batching for large requests to avoid token limits
        $batchSize = 20; // Generate max 20 questions per API call
        if ($count > $batchSize) {
            return $this->generatePoolInBatches($quiz, $topics, $topicNames, $count, $sourceText, $batchSize);
        }
        
        // Single batch for smaller requests
        $context = '';
        if ($sourceText !== null && $sourceText !== '') {
            $context = "Use the following material as the primary source for generating exam questions. Base questions on this content.\n\n---\n" . mb_substr($sourceText, 0, 80000) . "\n---\n\n";
        }
        $prompt = $context
            . "Use ONLY these precise topics—do not add or substitute others: {$topicNames}. "
            . "Generate exactly {$count} multiple choice quiz questions (MCQ) that clearly align with these topics. "
            . "Base each question on information that is directly relevant to one or more of the listed topics. "
            . "For each question provide: question text, 4 options (A,B,C,D), the correct letter, and two short explanations: "
            . "\"explanation_wrong\" (why a wrong answer is wrong) and \"explanation_correct\" (why the correct answer is right). "
            . "Include a topic label per question (one of the listed topics). "
            . "Format as JSON array only, no other text: [{\"text\":\"...\",\"options\":{\"A\":\"...\",\"B\":\"...\",\"C\":\"...\",\"D\":\"...\"},\"correct\":\"A\",\"topic\":\"...\",\"explanation_wrong\":\"...\",\"explanation_correct\":\"...\"}]";
        $result = $this->callAiWithUsage($prompt);
        $content = $result['text'] ?? null;
        if ($content === null || $content === '') {
            return [];
        }
        $decoded = $this->parseJsonArray($content);
        if (!is_array($decoded)) {
            return [];
        }
        $ids = [];
        foreach (array_slice($decoded, 0, $count) as $item) {
            $text = $item['text'] ?? 'AI Question';
            $opts = $item['options'] ?? [];
            $correct = $item['correct'] ?? 'A';
            $topic = $item['topic'] ?? $topicNames;
            $explanationWrong = $item['explanation_wrong'] ?? null;
            $explanationCorrect = $item['explanation_correct'] ?? null;
            $options = [];
            foreach (['A', 'B', 'C', 'D'] as $k) {
                $options[] = ['key' => $k, 'text' => $opts[$k] ?? 'Option ' . $k];
            }
            $pool = QuestionPool::create([
                'quiz_id' => $quiz->id,
                'question_text' => $text,
                'options' => $options,
                'correct_answer' => $correct,
                'topic' => is_string($topic) ? $topic : $topicNames,
                'is_approved' => false,
                'explanation_wrong' => is_string($explanationWrong) ? $explanationWrong : null,
                'explanation_correct' => is_string($explanationCorrect) ? $explanationCorrect : null,
            ]);
            $ids[] = $pool->id;
        }
        $usage = $result['usage'] ?? ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
        AiGenerationLog::create([
            'quiz_id' => $quiz->id,
            'prompt_tokens' => $usage['prompt_tokens'],
            'completion_tokens' => $usage['completion_tokens'],
            'total_tokens' => $usage['total_tokens'] ?: ($usage['prompt_tokens'] + $usage['completion_tokens']),
            'provider' => $result['provider'] ?? null,
            'questions_generated' => count($ids),
            'generated_at' => now(),
        ]);
        return $ids;
    }

    /**
     * Generate questions in multiple batches to avoid token limits.
     * Each batch makes a separate API call, then combines results.
     */
    private function generatePoolInBatches(Quiz $quiz, array $topics, string $topicNames, int $totalCount, ?string $sourceText, int $batchSize): array
    {
        $allIds = [];
        $totalPromptTokens = 0;
        $totalCompletionTokens = 0;
        $totalTokens = 0;
        $provider = null;
        
        $context = '';
        if ($sourceText !== null && $sourceText !== '') {
            $context = "Use the following material as the primary source for generating exam questions. Base questions on this content.\n\n---\n" . mb_substr($sourceText, 0, 80000) . "\n---\n\n";
        }
        
        $batches = (int) ceil($totalCount / $batchSize);
        for ($i = 0; $i < $batches; $i++) {
            $remaining = $totalCount - count($allIds);
            $batchCount = min($batchSize, $remaining);
            
            if ($batchCount < 1) {
                break;
            }
            
            $batchNumber = $i + 1;
            $prompt = $context
                . "Use ONLY these precise topics—do not add or substitute others: {$topicNames}. "
                . "Generate exactly {$batchCount} multiple choice quiz questions (MCQ) that clearly align with these topics. "
                . "This is batch {$batchNumber} of {$batches}. Generate UNIQUE questions that differ from previous batches. "
                . "Base each question on information that is directly relevant to one or more of the listed topics. "
                . "For each question provide: question text, 4 options (A,B,C,D), the correct letter, and two short explanations: "
                . "\"explanation_wrong\" (why a wrong answer is wrong) and \"explanation_correct\" (why the correct answer is right). "
                . "Include a topic label per question (one of the listed topics). "
                . "Format as JSON array only, no other text: [{\"text\":\"...\",\"options\":{\"A\":\"...\",\"B\":\"...\",\"C\":\"...\",\"D\":\"...\"},\"correct\":\"A\",\"topic\":\"...\",\"explanation_wrong\":\"...\",\"explanation_correct\":\"...\"}]";
            
            $result = $this->callAiWithUsage($prompt);
            $content = $result['text'] ?? null;
            
            if ($content === null || $content === '') {
                continue; // Skip failed batch, try next one
            }
            
            $decoded = $this->parseJsonArray($content);
            if (!is_array($decoded)) {
                continue; // Skip invalid batch
            }
            
            // Store questions from this batch
            foreach (array_slice($decoded, 0, $batchCount) as $item) {
                $text = $item['text'] ?? 'AI Question';
                $opts = $item['options'] ?? [];
                $correct = $item['correct'] ?? 'A';
                $topic = $item['topic'] ?? $topicNames;
                $explanationWrong = $item['explanation_wrong'] ?? null;
                $explanationCorrect = $item['explanation_correct'] ?? null;
                $options = [];
                foreach (['A', 'B', 'C', 'D'] as $k) {
                    $options[] = ['key' => $k, 'text' => $opts[$k] ?? 'Option ' . $k];
                }
                $pool = QuestionPool::create([
                    'quiz_id' => $quiz->id,
                    'question_text' => $text,
                    'options' => $options,
                    'correct_answer' => $correct,
                    'topic' => is_string($topic) ? $topic : $topicNames,
                    'is_approved' => false,
                    'explanation_wrong' => is_string($explanationWrong) ? $explanationWrong : null,
                    'explanation_correct' => is_string($explanationCorrect) ? $explanationCorrect : null,
                ]);
                $allIds[] = $pool->id;
            }
            
            // Accumulate token usage
            $usage = $result['usage'] ?? ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
            $totalPromptTokens += $usage['prompt_tokens'];
            $totalCompletionTokens += $usage['completion_tokens'];
            $totalTokens += $usage['total_tokens'];
            if ($provider === null && isset($result['provider'])) {
                $provider = $result['provider'];
            }
            
            // Small delay between batches to avoid rate limits
            if ($i < $batches - 1) {
                usleep(500000); // 0.5 second delay
            }
        }
        
        // Log total usage for all batches
        if (!empty($allIds)) {
            AiGenerationLog::create([
                'quiz_id' => $quiz->id,
                'prompt_tokens' => $totalPromptTokens,
                'completion_tokens' => $totalCompletionTokens,
                'total_tokens' => $totalTokens ?: ($totalPromptTokens + $totalCompletionTokens),
                'provider' => $provider,
                'questions_generated' => count($allIds),
                'generated_at' => now(),
            ]);
        }
        
        return $allIds;
    }

    /**
     * Generate questions via AI and store in questions table (for runtime pool top-up).
     * Blocked when no API key (returns []). Enforces per-quiz limit. Logs token usage per quiz.
     * For large counts (>20), uses batching to avoid token limits.
     * Returns array of question IDs.
     */
    public function generateAndStore(Quiz $quiz, array $topics, int $count, array $excludeIds): array
    {
        if (!$this->hasApiKey()) {
            return [];
        }
        $count = min($count, $this->getPerQuizLimit());
        if ($count < 1) {
            return [];
        }
        $topicNames = collect($topics)->pluck('name')->filter()->implode(', ');
        if (empty($topicNames)) {
            $topicNames = 'General knowledge';
        }
        
        // Use batching for large requests
        $batchSize = 20;
        if ($count > $batchSize) {
            return $this->generateAndStoreInBatches($quiz, $topicNames, $count, $batchSize);
        }
        
        // Single batch for smaller requests
        $prompt = "Use ONLY these precise topics—do not add or substitute others: {$topicNames}. "
            . "Generate exactly {$count} multiple choice quiz questions (MCQ) that clearly align with these topics. "
            . "Base each question on information directly relevant to one or more of the listed topics. "
            . "For each question provide: question text, 4 options (A,B,C,D), and the correct letter. "
            . "Format as JSON array only: [{\"text\":\"...\",\"options\":{\"A\":\"...\",\"B\":\"...\",\"C\":\"...\",\"D\":\"...\"},\"correct\":\"A\"}]";
        $result = $this->callAiWithUsage($prompt);
        $content = $result['text'] ?? null;
        if ($content === null || $content === '') {
            return [];
        }
        $decoded = $this->parseJsonArray($content);
        if (!is_array($decoded)) {
            return [];
        }
        $ids = [];
        foreach (array_slice($decoded, 0, $count) as $item) {
            $text = $item['text'] ?? 'AI Question';
            $opts = $item['options'] ?? [];
            $correct = $item['correct'] ?? 'A';
            $options = [];
            foreach (['A', 'B', 'C', 'D'] as $k) {
                $options[] = ['key' => $k, 'text' => $opts[$k] ?? 'Option ' . $k];
            }
            $q = Question::create([
                'quiz_id' => $quiz->id,
                'text' => $text,
                'type' => 'mcq',
                'options' => $options,
                'correct_answer' => $correct,
                'topic' => $topicNames,
                'source' => 'ai',
                'points' => 1,
            ]);
            $ids[] = $q->id;
        }
        $usage = $result['usage'] ?? ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
        AiGenerationLog::create([
            'quiz_id' => $quiz->id,
            'prompt_tokens' => $usage['prompt_tokens'],
            'completion_tokens' => $usage['completion_tokens'],
            'total_tokens' => $usage['total_tokens'] ?: ($usage['prompt_tokens'] + $usage['completion_tokens']),
            'provider' => $result['provider'] ?? null,
            'questions_generated' => count($ids),
            'generated_at' => now(),
        ]);
        return $ids;
    }

    /**
     * Generate questions in batches for runtime pool (questions table).
     */
    private function generateAndStoreInBatches(Quiz $quiz, string $topicNames, int $totalCount, int $batchSize): array
    {
        $allIds = [];
        $totalPromptTokens = 0;
        $totalCompletionTokens = 0;
        $totalTokens = 0;
        $provider = null;
        
        $batches = (int) ceil($totalCount / $batchSize);
        for ($i = 0; $i < $batches; $i++) {
            $remaining = $totalCount - count($allIds);
            $batchCount = min($batchSize, $remaining);
            
            if ($batchCount < 1) {
                break;
            }
            
            $batchNumber = $i + 1;
            $prompt = "Use ONLY these precise topics—do not add or substitute others: {$topicNames}. "
                . "Generate exactly {$batchCount} multiple choice quiz questions (MCQ) that clearly align with these topics. "
                . "This is batch {$batchNumber} of {$batches}. Generate UNIQUE questions that differ from previous batches. "
                . "Base each question on information directly relevant to one or more of the listed topics. "
                . "For each question provide: question text, 4 options (A,B,C,D), and the correct letter. "
                . "Format as JSON array only: [{\"text\":\"...\",\"options\":{\"A\":\"...\",\"B\":\"...\",\"C\":\"...\",\"D\":\"...\"},\"correct\":\"A\"}]";
            
            $result = $this->callAiWithUsage($prompt);
            $content = $result['text'] ?? null;
            
            if ($content === null || $content === '') {
                continue;
            }
            
            $decoded = $this->parseJsonArray($content);
            if (!is_array($decoded)) {
                continue;
            }
            
            foreach (array_slice($decoded, 0, $batchCount) as $item) {
                $text = $item['text'] ?? 'AI Question';
                $opts = $item['options'] ?? [];
                $correct = $item['correct'] ?? 'A';
                $options = [];
                foreach (['A', 'B', 'C', 'D'] as $k) {
                    $options[] = ['key' => $k, 'text' => $opts[$k] ?? 'Option ' . $k];
                }
                $q = Question::create([
                    'quiz_id' => $quiz->id,
                    'text' => $text,
                    'type' => 'mcq',
                    'options' => $options,
                    'correct_answer' => $correct,
                    'topic' => $topicNames,
                    'source' => 'ai',
                    'points' => 1,
                ]);
                $allIds[] = $q->id;
            }
            
            $usage = $result['usage'] ?? ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
            $totalPromptTokens += $usage['prompt_tokens'];
            $totalCompletionTokens += $usage['completion_tokens'];
            $totalTokens += $usage['total_tokens'];
            if ($provider === null && isset($result['provider'])) {
                $provider = $result['provider'];
            }
            
            if ($i < $batches - 1) {
                usleep(500000); // 0.5 second delay between batches
            }
        }
        
        if (!empty($allIds)) {
            AiGenerationLog::create([
                'quiz_id' => $quiz->id,
                'prompt_tokens' => $totalPromptTokens,
                'completion_tokens' => $totalCompletionTokens,
                'total_tokens' => $totalTokens ?: ($totalPromptTokens + $totalCompletionTokens),
                'provider' => $provider,
                'questions_generated' => count($allIds),
                'generated_at' => now(),
            ]);
        }
        
        return $allIds;
    }

    /**
     * Generate a short, meaning-based explanation of why the student's answer is wrong.
     * Used on result page when the question has no stored explanation_wrong.
     * Returns null if AI is unavailable or fails.
     */
    public function generateWrongAnswerExplanation(Question $question, string $studentAnswer): ?string
    {
        $questionText = is_string($question->text) ? $question->text : '';
        $correct = trim((string) ($question->correct_answer ?? ''));
        $chosen = trim($studentAnswer);
        if ($questionText === '' || $correct === '' || $chosen === '') {
            return null;
        }
        $optionsLines = [];
        if (is_array($question->options)) {
            foreach ($question->options as $opt) {
                $key = $opt['key'] ?? $opt;
                $text = $opt['text'] ?? $opt;
                $optionsLines[] = $key . ': ' . (is_string($text) ? $text : '');
            }
        }
        $optionsStr = !empty($optionsLines) ? implode(' ', $optionsLines) : 'N/A';
        $prompt = "Question: " . mb_substr($questionText, 0, 600) . "\nOptions: " . mb_substr($optionsStr, 0, 400)
            . "\nCorrect answer: " . $correct . ". The student chose: " . $chosen . "."
            . "\nIn one short sentence (max 25 words), explain why the student's answer is wrong in the context of the question. Reply with only that sentence, no label or prefix.";
        $text = $this->callAi($prompt);
        if ($text === null || $text === '') {
            return null;
        }
        $text = trim(preg_replace('/^(Why your answer is wrong|Reason):\s*/i', '', $text));
        return mb_substr($text, 0, 300) ?: null;
    }
}
