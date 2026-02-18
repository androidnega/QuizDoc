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
     */
    public function __invoke(Request $request): Response
    {
        $secret = env('MIGRATION_RUN_KEY', self::DEFAULT_SECRET);
        if ($request->query('key') !== $secret) {
            return response('Invalid or missing key. Add ?key=YOUR_SECRET to the URL. Set MIGRATION_RUN_KEY in .env to use your own secret.', 403, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        $output = "QuizSnap: Run pending Laravel migrations\n";
        $output .= "=======================================\n\n";

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
}
