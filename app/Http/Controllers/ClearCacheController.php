<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response;

class ClearCacheController extends Controller
{
    /** Use same secret as run-migrations; set MIGRATION_RUN_KEY in .env on live. */
    private const DEFAULT_SECRET = 'QuizSnapMigrate2026Xp9k3m7';

    /**
     * Clear Laravel caches via URL with a secret key.
     * Visit: https://YOUR-LIVE-SITE.com/clear-cache?key=YOUR_SECRET
     */
    public function __invoke(Request $request): Response
    {
        $secret = env('MIGRATION_RUN_KEY', self::DEFAULT_SECRET);
        if ($request->query('key') !== $secret) {
            return response('Invalid or missing key. Use: ?key=YOUR_SECRET (set MIGRATION_RUN_KEY in .env on server).', 403, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        $lines = [];
        $lines[] = 'QuizSnap: Clear cache (fix deploy / not showing changes)';
        $lines[] = '========================================================';
        $lines[] = '';

        try {
            $lines[] = 'Running: config:clear';
            Artisan::call('config:clear');
            $lines[] = 'Running: route:clear';
            Artisan::call('route:clear');
            $lines[] = 'Running: view:clear';
            Artisan::call('view:clear');
            $lines[] = 'Running: cache:clear';
            Artisan::call('cache:clear');
            $lines[] = '';
            $lines[] = '========================================================';
            $lines[] = 'SUCCESS: All caches cleared. Reload your site.';
        } catch (\Throwable $e) {
            $lines[] = 'ERROR: ' . $e->getMessage();
            $lines[] = $e->getTraceAsString();
        }

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
