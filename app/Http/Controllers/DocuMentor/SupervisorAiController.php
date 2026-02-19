<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use App\Models\DocuMentor\Chapter;
use App\Models\DocuMentor\DocumentAiReview;
use App\Models\DocuMentor\Project;
use App\Models\DocuMentor\Submission;
use App\Services\DocumentTextExtractor;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;

/**
 * E. AI REVIEW FLOW: Supervisor triggers "Review with AI". Max 2 AI reviews per submission.
 * Flow: upload/access file → extract text → send to AI → store JSON in DocumentAIReview.
 * AI output: strengths, weaknesses, improvements, score_suggestion.
 */
class SupervisorAiController extends Controller
{
    private const MAX_AI_REVIEWS_PER_SUBMISSION = 2;

    public function reviewSubmission(Request $request, Project $project, int $chapterRef, Submission $submission): RedirectResponse
    {
        $chapter = $project->resolveChapterByRef($chapterRef) ?? abort(404);
        $this->authorize('view', $submission);
        if ($submission->chapter_id !== $chapter->id) {
            abort(404);
        }

        $aiCount = DocumentAiReview::where('submission_id', $submission->id)->count();
        if ($aiCount >= self::MAX_AI_REVIEWS_PER_SUBMISSION) {
            return back()->with('error', 'Maximum ' . self::MAX_AI_REVIEWS_PER_SUBMISSION . ' AI reviews per submission.');
        }

        $apiKey = config('services.openai.key') ?? env('OPENAI_API_KEY');
        if (empty($apiKey)) {
            return back()->with('error', 'OpenAI API key not configured. Set OPENAI_API_KEY in .env.');
        }

        $filePath = storage_path('app/public/' . $submission->file);
        if (!file_exists($filePath)) {
            return back()->with('error', 'Submission file not found.');
        }

        $ext = strtolower(pathinfo($submission->file, PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf', 'docx', 'txt'], true)) {
            return back()->with('error', 'AI review supports PDF, DOCX, or TXT only.');
        }

        $extractor = app(DocumentTextExtractor::class);
        $text = $extractor->extractFromPath($filePath, $ext);
        if (empty(trim($text))) {
            return back()->with('error', 'Could not extract text from file.');
        }

        $prompt = <<<PROMPT
Review this chapter submission from an academic project. Reply with a single JSON object (no markdown, no code block) with exactly these keys:
- "strengths": string, positive aspects of the submission
- "weaknesses": string, areas where it falls short
- "improvements": string, concrete suggestions for improvement
- "score_suggestion": number or string, suggested score/grade (e.g. a number out of 100 or brief note)

Be constructive and specific.

---
Submission text (excerpt):

PROMPT;
        $prompt .= mb_substr($text, 0, 12000);

        $response = Http::withToken($apiKey)
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => env('OPENAI_MODEL', 'gpt-3.5-turbo'),
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 1500,
            ]);

        if (!$response->successful()) {
            return back()->with('error', 'AI API error: ' . ($response->json('error.message') ?? $response->body()));
        }

        $content = $response->json('choices.0.message.content', '');
        $structured = $this->parseStructuredReview($content);
        $aiOutput = array_merge($structured, [
            'model' => $response->json('model'),
            'generated_at' => now()->toIso8601String(),
        ]);

        DocumentAiReview::create([
            'source_type' => 'submission',
            'source_id' => $submission->id,
            'ai_output' => $aiOutput,
            'project_id' => $project->id,
            'chapter_id' => $submission->chapter_id,
            'submission_id' => $submission->id,
        ]);

        return back()->with('success', 'AI review saved.')->with('ai_review', $content);
    }

    private function parseStructuredReview(string $content): array
    {
        $content = trim($content);
        if (preg_match('/\{[\s\S]*\}/', $content, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return [
                    'strengths' => $decoded['strengths'] ?? '',
                    'weaknesses' => $decoded['weaknesses'] ?? '',
                    'improvements' => $decoded['improvements'] ?? '',
                    'score_suggestion' => $decoded['score_suggestion'] ?? '',
                ];
            }
        }
        return [
            'strengths' => '',
            'weaknesses' => '',
            'improvements' => '',
            'score_suggestion' => '',
            'raw' => $content,
        ];
    }

    public function projectSummary(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('view', $project);

        $apiKey = config('services.openai.key') ?? env('OPENAI_API_KEY');
        if (empty($apiKey)) {
            return back()->with('error', 'OpenAI API key not configured.');
        }

        $context = "Project: {$project->title}\n";
        $context .= "Description: " . ($project->description ?? 'N/A') . "\n";
        $context .= "Features: " . $project->features->pluck('name')->join(', ') . "\n";

        $prompt = "Generate a brief executive summary (3-5 sentences) of this academic project based on the following:\n\n" . $context;

        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => env('OPENAI_MODEL', 'gpt-3.5-turbo'),
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'max_tokens' => 500,
            ]);

        if (!$response->successful()) {
            return back()->with('error', 'AI API error: ' . ($response->json('error.message') ?? $response->body()));
        }

        $content = $response->json('choices.0.message.content', '');
        $aiOutput = [
            'model' => $response->json('model'),
            'summary' => $content,
            'generated_at' => now()->toIso8601String(),
        ];

        DocumentAiReview::create([
            'source_type' => 'project',
            'source_id' => $project->id,
            'ai_output' => $aiOutput,
            'created_at' => now(),
            'project_id' => $project->id,
        ]);

        return back()->with('success', 'Project summary generated.')->with('ai_summary', $content);
    }

}
