<?php
/**
 * Clear Laravel caches via browser (no SSH needed).
 *
 * 1. Visit this URL (use your own secret if you change $secret below):
 *    https://quizsnap.ausweblabs.com/clear-cache.php?key=YOUR_SECRET
 *
 * 2. Bookmark the link to run it anytime after deploy or .env changes.
 *
 * Security: Only requests with the correct ?key= are run. Change $secret and keep it private.
 */
$secret = 'QuizSnapClear2026Kp9m2x7';

if (($_GET['key'] ?? '') !== $secret) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: text/plain; charset=utf-8');
    exit('Invalid or missing key. Use: ' . parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) . '?key=YOUR_SECRET');
}

require_once __DIR__ . '/../vendor/autoload.php';

define('LARAVEL_START', microtime(true));

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain; charset=utf-8');

echo "QuizSnap Cache Clearing\n";
echo "=======================\n\n";

try {
    echo "Clearing config cache...\n";
    Illuminate\Support\Facades\Artisan::call('config:clear');
    echo "✓ Config cleared\n\n";

    echo "Clearing route cache...\n";
    Illuminate\Support\Facades\Artisan::call('route:clear');
    echo "✓ Route cleared\n\n";

    echo "Clearing application cache...\n";
    Illuminate\Support\Facades\Artisan::call('cache:clear');
    echo "✓ Cache cleared\n\n";

    echo "Clearing view cache...\n";
    Illuminate\Support\Facades\Artisan::call('view:clear');
    echo "✓ View cleared\n\n";

    echo "=======================\n";
    echo "SUCCESS: All caches cleared. Refresh your site.\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
