<?php

namespace App\Http\Controllers;

use App\Models\DocuMentor\ProjectProposal;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Run Docu Mentor proposal storage diagnostics via URL (no SSH/terminal).
 * Visit: https://quizsnap.online/thetoken?key=YOUR_SECRET
 * Optional: ?project=ID&limit=10
 */
class DocuMentorProposalDiagnosticsController extends Controller
{
    private const DEFAULT_SECRET = 'QuizSnapMigrate2026Xp9k3m7';

    private function getExpectedKey(): string
    {
        $key = env('MIGRATION_RUN_KEY', self::DEFAULT_SECRET);
        return trim((string) $key) !== '' ? trim($key) : self::DEFAULT_SECRET;
    }

    private function checkKey(Request $request): bool
    {
        $key = $request->query('key');
        return is_string($key) && trim($key) !== '' && trim($key) === $this->getExpectedKey();
    }

    /** PHP 7 compatible: check if path is HTTP(S) URL. */
    private function isHttpUrl(string $path): bool
    {
        $lower = strtolower($path);
        return substr($lower, 0, 7) === 'http://' || substr($lower, 0, 8) === 'https://';
    }

    public function __invoke(Request $request): Response
    {
        if (! $this->checkKey($request)) {
            $expected = $this->getExpectedKey();
            $base = $request->getSchemeAndHttpHost();
            $url = $base . '/thetoken?key=' . urlencode($expected);
            return response(
                "Invalid or missing key. Add ?key= to the URL.\n\nUse: {$url}\n\nOr set MIGRATION_RUN_KEY in .env and use that value as key=.",
                403,
                ['Content-Type' => 'text/plain; charset=utf-8']
            );
        }

        try {
            $projectId = $request->query('project');
            $limit = (int) $request->query('limit', 10);
            $limit = $limit > 0 ? min($limit, 50) : 10;

            $lines = [];
            $lines[] = 'Docu Mentor proposal storage diagnostics';
            $lines[] = '=========================================';
            $lines[] = '';

            // 1. Cloudinary
            if (class_exists(CloudinaryService::class)) {
                $configured = CloudinaryService::isConfigured();
                $lines[] = 'Cloudinary configured: ' . ($configured ? 'YES' : 'NO');
                if ($configured) {
                    $lines[] = 'Testing Cloudinary connection...';
                    try {
                        $result = CloudinaryService::testConnection();
                        $lines[] = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                    } catch (\Throwable $e) {
                        $lines[] = 'ERROR: ' . $e->getMessage();
                    }
                }
            } else {
                $lines[] = 'CloudinaryService not found.';
            }

            $lines[] = '';
            $lines[] = 'Recent project proposals:';

            $query = ProjectProposal::query()->orderByDesc('uploaded_at')->orderByDesc('id');
            if ($projectId !== null && $projectId !== '') {
                $query->where('project_id', (int) $projectId);
                $lines[] = '  Filter: project_id = ' . (int) $projectId;
            }

            $proposals = $query->limit($limit)->get();
            if ($proposals->isEmpty()) {
                $lines[] = '  No proposals found.';
                return response(implode("\n", $lines), 200, [
                    'Content-Type' => 'text/plain; charset=utf-8',
                ]);
            }

            $disk = Storage::disk('public');

            foreach ($proposals as $p) {
                $path = $p->file ? trim($p->file, "/ \t\n\r") : null;
                $lines[] = '';
                $lines[] = "Proposal #{$p->id} (project_id={$p->project_id}, version={$p->version_number})";
                $lines[] = '  uploaded_at: ' . ($p->uploaded_at ? $p->uploaded_at->toDateTimeString() : 'null');
                $lines[] = '  file: ' . ($path ?: '(null)');

                if (! $path) {
                    $lines[] = '  Status: MISSING PATH (file column empty). Re-upload from student dashboard.';
                    continue;
                }

                if ($this->isHttpUrl($path)) {
                    $lines[] = '  Type: URL (Cloudinary). Coordinator download will redirect to this URL.';
                    $lines[] = '  Status: OK (remote URL).';
                    continue;
                }

                $exists = $disk->exists($path);
                $altPath = ltrim($path, '/');
                $existsAlt = ! $exists ? $disk->exists($altPath) : false;

                if ($exists || $existsAlt) {
                    $lines[] = '  Type: local storage (public disk).';
                    $lines[] = '  Status: OK (file exists).';
                } else {
                    $lines[] = '  Status: NOT FOUND on public disk.';
                    $lines[] = '  Checked: "' . $path . '" and "' . $altPath . '"';
                    $lines[] = '  Fix: Re-upload this proposal from the student project page, or ensure storage link: php artisan storage:link';
                }
            }

            $lines[] = '';
            $lines[] = '=========================================';
            $lines[] = 'End of diagnostics.';

            return response(implode("\n", $lines), 200, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        } catch (\Throwable $e) {
            $msg = 'Diagnostics failed: ' . $e->getMessage() . "\n\nFile: " . $e->getFile() . "\nLine: " . $e->getLine();
            return response($msg, 200, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }
    }
}
