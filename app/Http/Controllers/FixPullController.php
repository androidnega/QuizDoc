<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FixPullController extends Controller
{
    /** Use same secret as run-migrations / clear-cache; set MIGRATION_RUN_KEY in .env. */
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

    /**
     * Reset ALL tracked files to HEAD then git pull, so the server always matches the repo.
     * Also clears all Laravel caches and stale AI progress cache entries.
     * Visit: https://quizsnap.online/fix-pull/run?key=YOUR_SECRET
     */
    public function run(Request $request): Response
    {
        if (! $this->checkKey($request)) {
            $expected = $this->getExpectedKey();
            $base = $request->getSchemeAndHttpHost();
            $url = $base . '/fix-pull/run?key=' . urlencode($expected);
            return response(
                "Invalid or missing key. Add ?key= to the URL.\n\nTry this (default key):\n{$url}\n\nOr set MIGRATION_RUN_KEY in .env and use that value as key=.",
                403,
                ['Content-Type' => 'text/plain; charset=utf-8']
            );
        }

        $basePath = base_path();

        if (! is_dir($basePath . '/.git')) {
            return response("ERROR: .git not found in {$basePath}", 500, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        $git = '/usr/local/cpanel/3rdparty/bin/git';
        if (! is_executable($git)) {
            $git = 'git';
        }

        $body = "QuizSnap: Reset + Update from remote (no merge)\n====================================\n\n";

        // Step 1: fetch from remote
        $cmdFetch = sprintf('cd %s && %s fetch origin 2>&1', escapeshellarg($basePath), escapeshellcmd($git));
        $outFetch = [];
        exec($cmdFetch, $outFetch, $codeFetch);
        $body .= "Step 1: git fetch origin\n";
        $body .= implode("\n", $outFetch) . "\n";
        $body .= "Exit code: {$codeFetch}\n\n";

        // Step 2: get current branch, then reset hard to origin (discards local changes so pull never conflicts)
        $outBranch = [];
        exec(sprintf('cd %s && %s rev-parse --abbrev-ref HEAD 2>&1', escapeshellarg($basePath), escapeshellcmd($git)), $outBranch, $codeBranch);
        $branch = trim(implode('', $outBranch)) ?: 'main';
        $cmdReset = sprintf('cd %s && %s reset --hard origin/%s 2>&1', escapeshellarg($basePath), escapeshellcmd($git), escapeshellarg($branch));
        $outReset = [];
        exec($cmdReset, $outReset, $codeReset);
        $body .= "Step 2: git reset --hard origin/{$branch}\n";
        $body .= implode("\n", $outReset) . "\n";
        $body .= "Exit code: {$codeReset}\n\n";

        // Step 3: clear Laravel caches (config, route, view, cache)
        $body .= "Step 3: Clear caches\n";
        try {
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Artisan::call('route:clear');
            \Illuminate\Support\Facades\Artisan::call('view:clear');
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
            $body .= "Caches cleared.\n\n";
        } catch (\Throwable $e) {
            $body .= "Cache clear error: " . $e->getMessage() . "\n\n";
        }

        $body .= "====================================\n";
        if ($codeFetch === 0 && $codeReset === 0) {
            $body .= "SUCCESS: Code matches remote (origin/{$branch}). Reload the site.\n";
        } else {
            $body .= "WARNING: One or more steps failed. Check output above.\n";
            $body .= "If this URL fails, set MIGRATION_RUN_KEY in .env and use that key in the URL.\n";
        }

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    /**
     * Show maintenance helper links (no key required). Use to verify routes are deployed.
     * Visit: https://quizsnap.online/maintenance
     */
    public function maintenance(Request $request): Response
    {
        $base = $request->getSchemeAndHttpHost();
        $key = 'QuizSnapMigrate2026Xp9k3m7';
        $clearCache = $base . '/clear-cache?key=' . urlencode($key);
        $fixPullRun = $base . '/fix-pull/run?key=' . urlencode($key);
        $thekey = $base . '/thekey?key=' . urlencode($key);
        $fixPullPage = $base . '/fix-pull?key=' . urlencode($key);

        $body = "QuizSnap maintenance routes are active.\n\n";
        $body .= "Use these URLs (same key in .env: MIGRATION_RUN_KEY):\n\n";
        $body .= "1. Clear caches (after deploy):\n   {$clearCache}\n\n";
        $body .= "2. Fix git pull (no SSH) – short link:\n   {$thekey}\n\n";
        $body .= "3. Fix git pull – long link:\n   {$fixPullRun}\n\n";
        $body .= "4. Fix-pull instructions + script download:\n   {$fixPullPage}\n";

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    /**
     * Show fix-pull instructions and link to download script.
     * Visit: https://quizsnap.online/fix-pull?key=YOUR_SECRET
     */
    public function show(Request $request): Response
    {
        if (! $this->checkKey($request)) {
            return response('Invalid or missing key. Use: /fix-pull?key=YOUR_SECRET (set MIGRATION_RUN_KEY in .env).', 403, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        $base = $request->getSchemeAndHttpHost();
        $key = $request->query('key');
        $scriptUrl = $base . '/fix-pull/script?key=' . urlencode($key);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix git pull – QuizSnap</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 640px; margin: 2rem auto; padding: 0 1rem; }
        h1 { font-size: 1.25rem; }
        pre { background: #f1f5f9; padding: 1rem; border-radius: 8px; overflow-x: auto; }
        a.dl { display: inline-block; margin-top: 0.5rem; padding: 0.5rem 1rem; background: #0ea5e9; color: #fff; text-decoration: none; border-radius: 6px; }
        a.dl:hover { background: #0284c7; }
        .link { word-break: break-all; color: #0369a1; }
    </style>
</head>
<body>
    <h1>Fix “would be overwritten by merge” (no SSH needed)</h1>
    <p>When cPanel <strong>Git → Pull</strong> fails with "Your local changes would be overwritten by merge", open this URL (same key as migrations):</p>
    <p><a class="dl" href="{$base}/fix-pull/run?key={$key}">Run fix-pull now</a></p>
    <p class="link">{$base}/fix-pull/run?key=YOUR_SECRET</p>
    <p>It runs <code>git fetch origin</code> and <code>git reset --hard origin/main</code>. Server local edits are discarded; then cPanel Pull works again.</p>
    <hr>
    <p><strong>If you have SSH</strong>, download and run the script:</p>
    <p><a class="dl" href="{$scriptUrl}">Download fix-pull-on-server.sh</a></p>
    <p class="link">{$scriptUrl}</p>
    <p>Then on the server: <code>chmod +x fix-pull-on-server.sh && ./fix-pull-on-server.sh</code></p>
</body>
</html>
HTML;

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Serve the fix-pull script for download (same key).
     * Visit: https://quizsnap.online/fix-pull/script?key=YOUR_SECRET
     */
    public function script(Request $request): Response
    {
        if (! $this->checkKey($request)) {
            return response('Invalid or missing key.', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $script = <<<'SH'
#!/bin/bash
# Run this on the SERVER when git pull fails with:
#   "Your local changes to the following files would be overwritten by merge"
set -e
echo "Stashing local changes..."
git stash push -m "pre-pull $(date +%Y%m%d-%H%M%S)" -- resources/views/admin/quizzes/create.blade.php 2>/dev/null || git stash push -m "pre-pull $(date +%Y%m%d-%H%M%S)"
echo "Pulling from remote..."
git pull
echo "Done. To reapply your stashed changes: git stash list && git stash pop"
SH;

        return response($script, 200, [
            'Content-Type' => 'application/x-sh; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="fix-pull-on-server.sh"',
        ]);
    }
}
