<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FixPullController extends Controller
{
    /** Use same secret as run-migrations / clear-cache; set MIGRATION_RUN_KEY in .env. */
    private const DEFAULT_SECRET = 'QuizSnapMigrate2026Xp9k3m7';

    private function checkKey(Request $request): bool
    {
        $secret = env('MIGRATION_RUN_KEY', self::DEFAULT_SECRET);
        return $request->query('key') === $secret;
    }

    /**
     * Reset ALL tracked files to HEAD then git pull, so the server always matches the repo.
     * Also clears all Laravel caches and stale AI progress cache entries.
     * Visit: https://quizsnap.online/fix-pull/run?key=YOUR_SECRET
     */
    public function run(Request $request): Response
    {
        if (! $this->checkKey($request)) {
            return response('Invalid or missing key. Use: /fix-pull/run?key=YOUR_SECRET', 403, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
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

        $body = "QuizSnap: Reset + Pull latest code\n====================================\n\n";

        // Step 1: reset all tracked files (discard any server-side edits so pull succeeds)
        $cmdReset = sprintf('cd %s && %s reset --hard HEAD 2>&1', escapeshellarg($basePath), escapeshellcmd($git));
        $outReset = [];
        exec($cmdReset, $outReset, $codeReset);
        $body .= "Step 1: git reset --hard HEAD\n";
        $body .= implode("\n", $outReset) . "\n";
        $body .= "Exit code: {$codeReset}\n\n";

        // Step 2: pull latest from remote
        $cmdPull = sprintf('cd %s && %s pull 2>&1', escapeshellarg($basePath), escapeshellcmd($git));
        $outPull = [];
        exec($cmdPull, $outPull, $codePull);
        $body .= "Step 2: git pull\n";
        $body .= implode("\n", $outPull) . "\n";
        $body .= "Exit code: {$codePull}\n\n";

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
        if ($codeReset === 0 && $codePull === 0) {
            $body .= "SUCCESS: Code is up to date. Reload the site.\n";
        } else {
            $body .= "WARNING: One or more steps failed. Check output above.\n";
            $body .= "If git pull fails, run it manually in cPanel → Git Version Control → Pull.\n";
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
        $fixPullPage = $base . '/fix-pull?key=' . urlencode($key);

        $body = "QuizSnap maintenance routes are active.\n\n";
        $body .= "Use these URLs (same key in .env: MIGRATION_RUN_KEY):\n\n";
        $body .= "1. Clear caches (after deploy):\n   {$clearCache}\n\n";
        $body .= "2. Fix git pull conflict (discard local changes to create.blade.php):\n   {$fixPullRun}\n\n";
        $body .= "3. Fix-pull instructions + script download:\n   {$fixPullPage}\n";

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
    <h1>Fix “would be overwritten by merge” on server</h1>
    <p>When <code>git pull</code> fails because of local changes to <code>resources/views/admin/quizzes/create.blade.php</code>, run this <strong>on the server</strong> (SSH):</p>
    <pre>git stash push -m "server local changes"
git pull</pre>
    <p>To reapply your stashed changes later: <code>git stash list</code> then <code>git stash pop</code>.</p>
    <hr>
    <p><strong>Or download and run the script:</strong></p>
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
