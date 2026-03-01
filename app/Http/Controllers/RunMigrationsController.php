<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response;

class RunMigrationsController extends Controller
{
    /** Default secret; override with MIGRATION_RUN_KEY in .env for production. */
    private const DEFAULT_SECRET = 'QuizSnapMigrate2026Xp9k3m7';

    /**
     * Run pending Laravel migrations via URL with a secret key.
     * Visit: https://yoursite.com/run-migrations?key=YOUR_SECRET
     * Fix git pull (no SSH): same URL with &action=fixpull
     * Visit: https://quizsnap.online/migration?key=YOUR_SECRET&action=fixpull
     */
    public function __invoke(Request $request): Response
    {
        $secret = trim((string) env('MIGRATION_RUN_KEY', self::DEFAULT_SECRET));
        if ($secret === '') {
            $secret = self::DEFAULT_SECRET;
        }
        if ($request->query('key') !== $secret) {
            $path = str_contains($request->path(), 'migrationcode') ? 'migrationcode' : 'migration';
            $hint = $request->getSchemeAndHttpHost() . '/' . $path . '?key=' . urlencode($secret);
            return response('Invalid or missing key. Use this link to run migrations: ' . $hint . "\n\nSet MIGRATION_RUN_KEY in .env to use your own secret.", 403, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        if ($request->query('action') === 'fixpull') {
            return $this->runFixPull();
        }

        $path = str_contains($request->path(), 'migrationcode') ? 'migrationcode' : 'migration';
        $output = "QuizSnap: Run pending Laravel migrations\n";
        $output .= "=======================================\n";
        $output .= "Link: " . $request->getSchemeAndHttpHost() . '/' . $path . "?key=***\n\n";

        try {
            $output .= "Step 1: Run migrate --force...\n";
            Artisan::call('migrate', ['--force' => true]);
            $output .= trim(Artisan::output()) . "\n\n";

            $output .= "Step 2: Clear caches...\n";
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            $output .= "Caches cleared.\n\n";

            $output .= "=======================================\n";
            $output .= "SUCCESS: Pending migrations executed.\n";
        } catch (\Throwable $e) {
            $output .= "ERROR: " . $e->getMessage() . "\n";
            $output .= $e->getTraceAsString();
        }

        return response($output, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    /** Same logic as FixPullController::run – reset to origin, clear caches. */
    private function runFixPull(): Response
    {
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
        $run = function (string $cmd) use ($basePath, $git): string {
            $full = 'cd ' . escapeshellarg($basePath) . ' && ' . $git . ' ' . $cmd . ' 2>&1';
            return trim((string) shell_exec($full));
        };
        $body = "QuizSnap: Fix pull (reset to remote)\n====================================\n\n";
        $body .= "Step 1: git fetch origin\n" . $run('fetch origin') . "\n\n";
        $branch = $run('rev-parse --abbrev-ref HEAD') ?: 'main';
        $body .= "Step 2: git reset --hard origin/{$branch}\n" . $run('reset --hard origin/' . $branch) . "\n\n";
        $body .= "Step 3: Clear caches\n";
        try {
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            Artisan::call('cache:clear');
            $body .= "Caches cleared.\n\n";
        } catch (\Throwable $e) {
            $body .= $e->getMessage() . "\n\n";
        }
        $body .= "====================================\nSUCCESS: Code matches remote (origin/{$branch}).\n";
        return response($body, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
