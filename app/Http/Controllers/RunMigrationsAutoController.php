<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response;

/**
 * Run migrations when a marker file exists — no query key, no new .env vars.
 *
 * 1. Create empty file: storage/app/quizsnap-allow-migration (FTP / cPanel File Manager)
 * 2. Visit once: https://yoursite.com/run-migrations-auto
 * 3. File is removed after success; on failure it stays so you can fix and retry.
 *
 * Also available without any file if you use the default migration URL key (see RunMigrationsController).
 */
class RunMigrationsAutoController extends Controller
{
    private const ALLOW_FILENAME = 'quizsnap-allow-migration';

    public function __invoke(Request $request): Response
    {
        $allow = storage_path('app/'.self::ALLOW_FILENAME);

        if (! is_file($allow) || ! is_readable($allow)) {
            $host = $request->getSchemeAndHttpHost();

            return response(
                "QuizSnap: migrations not enabled for this request.\n\n"
                ."Create this empty file on the server (same machine as Laravel), then open this URL again:\n"
                ."  {$allow}\n\n"
                ."Steps (cPanel / FTP):\n"
                ."  • Go to your project root (folder that contains `artisan`).\n"
                ."  • Open `storage/app/`\n"
                ."  • Create new file named: ".self::ALLOW_FILENAME."\n"
                ."  • Save (contents can be empty).\n\n"
                ."Then visit:\n"
                ."  {$host}/run-migrations-auto\n\n"
                ."The file is deleted automatically after migrations succeed.\n\n"
                ."---\n"
                ."Alternative (no file): if your deploy includes the default key, you can use:\n"
                ."  {$host}/migration?key=QuizSnapMigrate2026Xp9k3m7\n"
                ."Override with MIGRATION_RUN_KEY in .env when you want a custom secret.\n",
                403,
                ['Content-Type' => 'text/plain; charset=utf-8', 'X-Robots-Tag' => 'noindex, nofollow']
            );
        }

        $pretend = $request->query('pretend') === '1';
        $options = ['--force' => true];
        if ($pretend) {
            $options['--pretend'] = true;
        }

        $output = "QuizSnap: run-migrations-auto\n";
        $output .= str_repeat('=', 40)."\n";
        if ($pretend) {
            $output .= "(pretend mode — no DB changes)\n\n";
        }

        try {
            $output .= "Step 1: migrate".($pretend ? ' --pretend' : ' --force')."...\n";
            $code = Artisan::call('migrate', $options);
            $output .= trim(Artisan::output())."\n\n";

            if ($code !== 0) {
                $output .= "Migrate exit code: {$code}\n";
                $output .= "Allow file kept at: {$allow}\n";

                return response($output, 500, [
                    'Content-Type' => 'text/plain; charset=utf-8',
                    'X-Robots-Tag' => 'noindex, nofollow',
                ]);
            }

            if (! $pretend) {
                $output .= "Step 2: Clear caches...\n";
                Artisan::call('config:clear');
                Artisan::call('route:clear');
                Artisan::call('cache:clear');
                Artisan::call('view:clear');
                $output .= "Caches cleared.\n\n";

                if (@unlink($allow)) {
                    $output .= "Allow file removed.\n";
                } else {
                    $output .= "Warning: could not delete allow file; remove manually:\n  {$allow}\n";
                }
            } else {
                $output .= "Pretend run: allow file not deleted.\n";
            }

            $output .= str_repeat('=', 40)."\nSUCCESS.\n";

            return response($output, 200, [
                'Content-Type' => 'text/plain; charset=utf-8',
                'X-Robots-Tag' => 'noindex, nofollow',
            ]);
        } catch (\Throwable $e) {
            $output .= "ERROR: ".$e->getMessage()."\n";
            $output .= "Allow file kept at: {$allow}\n";

            return response($output, 500, [
                'Content-Type' => 'text/plain; charset=utf-8',
                'X-Robots-Tag' => 'noindex, nofollow',
            ]);
        }
    }
}
