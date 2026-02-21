<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response;

/**
 * One-time URL to seed group_names (Docu Mentor project group name pairs).
 * Same key as run-migrations: MIGRATION_RUN_KEY or default QuizSnapMigrate2026Xp9k3m7.
 * Visit: https://quizsnap.online/seed-group-names?key=YOUR_SECRET
 */
class SeedGroupNamesController extends Controller
{
    private const DEFAULT_SECRET = 'QuizSnapMigrate2026Xp9k3m7';

    public function __invoke(Request $request): Response
    {
        $secret = trim((string) env('MIGRATION_RUN_KEY', self::DEFAULT_SECRET));
        if ($secret === '') {
            $secret = self::DEFAULT_SECRET;
        }
        if ($request->query('key') !== $secret) {
            $url = $request->getSchemeAndHttpHost() . '/seed-group-names?key=' . urlencode($secret);
            return response("Invalid or missing key. Use: " . $url . "\n\nSame key as /migration and /clear-cache (MIGRATION_RUN_KEY in .env).", 403, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        $output = "QuizSnap: Seed group_names (Docu Mentor)\n";
        $output .= "=========================================\n\n";

        try {
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\GroupNameSeeder', '--force' => true]);
            $output .= trim(Artisan::output()) . "\n\n";
            $output .= "SUCCESS: group_names seeded. Create-group page will now show varied random names.\n";
        } catch (\Throwable $e) {
            $output .= "ERROR: " . $e->getMessage() . "\n";
            $output .= $e->getTraceAsString();
        }

        return response($output, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
