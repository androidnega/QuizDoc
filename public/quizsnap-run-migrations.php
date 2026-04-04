<?php

/**
 * Run Laravel migrations via HTTP (for hosts without SSH). Protect with a strong secret.
 *
 * Key resolution (first match wins):
 * 1) config quizsnap.migrate_key (QUIZSNAP_MIGRATE_KEY in .env + config clear/cache)
 * 2) getenv('QUIZSNAP_MIGRATE_KEY')
 * 3) Line in base_path('.env') — flexible spacing: QUIZSNAP_MIGRATE_KEY=value
 * 4) File base_path('storage/app/quizsnap-migrate.key') — single line, same secret as ?key=
 *
 * If .env is not readable by the web user, use (4): upload that file via FTP/cPanel.
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

/**
 * Read KEY=value from .env (handles quotes, comments, optional spaces around =).
 */
function quizsnap_env_file_value(string $envPath, string $key): string
{
    if (! is_readable($envPath)) {
        return '';
    }
    $raw = file_get_contents($envPath);
    if ($raw === false || $raw === '') {
        return '';
    }
    if (str_starts_with($raw, "\xEF\xBB\xBF")) {
        $raw = substr($raw, 3);
    }
    $pattern = '/^\s*'.preg_quote($key, '/').'\s*=\s*(.*)$/';
    foreach (explode("\n", str_replace("\r\n", "\n", $raw)) as $line) {
        $line = rtrim($line, "\r");
        $t = trim($line);
        if ($t === '' || str_starts_with($t, '#')) {
            continue;
        }
        if (! preg_match($pattern, $line, $m)) {
            continue;
        }
        $v = trim($m[1]);
        if ($v !== '' && $v[0] === '"' && str_ends_with($v, '"')) {
            return stripcslashes(substr($v, 1, -1));
        }
        if ($v !== '' && $v[0] === "'" && str_ends_with($v, "'")) {
            return substr($v, 1, -1);
        }

        return trim((string) (preg_replace('/\s+#.*$/', '', $v) ?? $v));
    }

    return '';
}

function quizsnap_read_key_file(string $path): string
{
    if (! is_readable($path)) {
        return '';
    }
    $raw = file_get_contents($path);
    if ($raw === false) {
        return '';
    }
    $line = strtok(str_replace("\r\n", "\n", $raw), "\n");

    return $line !== false ? trim($line) : '';
}

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$base = $app->basePath();
$envPath = $base.'/.env';
$keyFilePath = $base.'/storage/app/quizsnap-migrate.key';

$secret = (string) config('quizsnap.migrate_key', '');
if ($secret === '') {
    $secret = (string) (getenv('QUIZSNAP_MIGRATE_KEY') ?: '');
}
if ($secret === '') {
    $secret = quizsnap_env_file_value($envPath, 'QUIZSNAP_MIGRATE_KEY');
}
if ($secret === '') {
    $secret = quizsnap_read_key_file($keyFilePath);
}

if ($secret === '') {
    $envExists = file_exists($envPath);
    $envReadable = $envExists && is_readable($envPath);
    $keyFileExists = file_exists($keyFilePath);
    $keyFileReadable = $keyFileExists && is_readable($keyFilePath);

    exit(
        "QUIZSNAP_MIGRATE_KEY is empty — the server never found a secret.\n\n"
        ."Laravel base path (where artisan lives):\n  {$base}\n\n"
        .".env expected at:\n  {$envPath}\n"
        .'  exists: '.($envExists ? 'yes' : 'no').', readable by PHP: '.($envReadable ? 'yes' : 'no')."\n\n"
        ."Alternative — one-line secret file (create via FTP if .env is not readable):\n  {$keyFilePath}\n"
        .'  exists: '.($keyFileExists ? 'yes' : 'no').', readable: '.($keyFileReadable ? 'yes' : 'no')."\n\n"
        ."Fix A — in .env add (no spaces around =):\n"
        ."  QUIZSNAP_MIGRATE_KEY=YourSecretHere\n\n"
        ."Fix B — create the file above with only your secret on the first line (same value as ?key= in the URL).\n\n"
        ."Then run:\n  https://yoursite.com/quizsnap-run-migrations.php?key=YourSecretHere\n\n"
        ."After it works, delete quizsnap-run-migrations.php or remove the key from the server.\n"
    );
}

$secret = trim($secret);
$given = trim((string) ($_GET['key'] ?? ''));
if ($given === '' || ! hash_equals($secret, $given)) {
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
