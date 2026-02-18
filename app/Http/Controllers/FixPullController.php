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
     * Run git checkout to discard local changes so pull can succeed (no SSH needed).
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
        $file = 'resources/views/admin/quizzes/create.blade.php';

        if (! is_dir($basePath . '/.git')) {
            return response("ERROR: .git not found in {$basePath}", 500, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        $git = '/usr/local/cpanel/3rdparty/bin/git';
        if (! is_executable($git)) {
            $git = 'git';
        }

        $cmd = sprintf(
            'cd %s && %s checkout -- %s 2>&1',
            escapeshellarg($basePath),
            escapeshellcmd($git),
            escapeshellarg($file)
        );
        $out = [];
        exec($cmd, $out, $code);

        $body = "Fix Git Pull (run)\n==================\n";
        $body .= "File: {$file}\n";
        $body .= "Output: " . implode("\n", $out) . "\n";
        $body .= "Exit code: {$code}\n\n";

        if ($code === 0) {
            $body .= "SUCCESS: Local changes discarded. Run Pull in cPanel Git now.\n";
        } else {
            $body .= "If that failed, fix manually: In cPanel File Manager, replace\n";
            $body .= "resources/views/admin/quizzes/create.blade.php with the file from GitHub,\n";
            $body .= "then run Pull again.\n";
        }

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
