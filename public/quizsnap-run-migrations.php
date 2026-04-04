<?php

/**
 * Run Laravel migrations via HTTP (for hosts without SSH). Protect with a strong secret.
 *
 * 1. In .env set: QUIZSNAP_MIGRATE_KEY=your_long_secret
 *    Example value (generate your own): QuizSnapMigrate2026Xp9k3m7
 * 2. If you use config:cache: php artisan config:clear after changing .env
 * 3. Visit (GET):
 *    https://yoursite.com/quizsnap-run-migrations.php?key=YOUR_SECRET
 * 4. Optional dry run (prints SQL, does not migrate): add &pretend=1
 * 5. Remove this file from public/ when you no longer need remote migrations, or keep the key private.
 */

declare(strict_types=1);

/**
 * Read a single key from .env when config is cached (config() / env() often empty for new vars).
 */
function quizsnap_env_file_value(string $envPath, string $key): string
{
    if (! is_readable($envPath)) {
        return '';
    }
    $raw = file_get_contents($envPath);
    if ($raw === false) {
        return '';
    }
    foreach (explode("\n", $raw) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (! str_starts_with($line, $key.'=')) {
            continue;
        }
        $v = trim(substr($line, strlen($key) + 1));
        if ($v !== '' && $v[0] === '"' && str_ends_with($v, '"')) {
            return stripcslashes(substr($v, 1, -1));
        }
        if ($v !== '' && $v[0] === "'" && str_ends_with($v, "'")) {
            return substr($v, 1, -1);
        }

        return preg_replace('/\s+#.*$/', '', $v) ?? $v;
    }

    return '';
}

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$secret = (string) config('quizsnap.migrate_key', '');
if ($secret === '') {
    $secret = (string) (getenv('QUIZSNAP_MIGRATE_KEY') ?: '');
}
if ($secret === '') {
    $secret = quizsnap_env_file_value(dirname(__DIR__).'/.env', 'QUIZSNAP_MIGRATE_KEY');
}
if ($secret === '') {
    exit(
        "QUIZSNAP_MIGRATE_KEY is not set.\n\n"
        ."On the server, edit the .env file in the app root (same folder as artisan) and add one line, for example:\n"
        ."QUIZSNAP_MIGRATE_KEY=QuizSnapMigrate2026Xp9k3m7\n\n"
        ."No spaces around =. Then open again:\n"
        ."https://yoursite.com/quizsnap-run-migrations.php?key=YOUR_SAME_VALUE\n\n"
        ."If you use php artisan config:cache, run config:clear after changing .env (optional; this script also reads .env directly).\n"
    );
}

if (($_GET['key'] ?? '') !== $secret) {
    header('HTTP/1.1 403 Forbidden');
    exit("Invalid or missing key.\n");
}

$pretend = isset($_GET['pretend']) && (string) $_GET['pretend'] === '1';

$options = ['--force' => true];
if ($pretend) {
    $options['--pretend'] = true;
}

try {
    $code = \Illuminate\Support\Facades\Artisan::call('migrate', $options);
    echo \Illuminate\Support\Facades\Artisan::output();
    if ($code !== 0) {
        header('HTTP/1.1 500 Internal Server Error');
        echo "\nArtisan exit code: {$code}\n";
        exit;
    }
    echo $pretend ? "\nPretend run finished (no changes applied).\n" : "\nMigrations finished.\n";
} catch (\Throwable $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo 'Error: '.$e->getMessage()."\n";
}
