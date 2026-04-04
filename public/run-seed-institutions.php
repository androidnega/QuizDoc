<?php
$secret = 'QuizSnap2026Xk9m2p7';

if (($_GET['key'] ?? '') !== $secret) {
    header('HTTP/1.1 403 Forbidden');
    exit('Invalid or missing key.');
}

if (($_GET['run'] ?? '') !== 'yes') {
    header('Content-Type: text/plain; charset=utf-8');
    exit('Add &run=yes to confirm: ' . parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) . '?key=YOUR_SECRET&run=yes');
}

define('LARAVEL_START', microtime(true));

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

ob_start();
try {
    Illuminate\Support\Facades\Artisan::call('db:seed', [
        '--class' => 'InstitutionSeeder',
        '--force' => true,
    ]);
    echo "Institutions seeded successfully.\n\n";

    if (@unlink(__FILE__)) {
        echo "[run-seed-institutions.php has been deleted.]\n";
    } else {
        echo "[Delete public/run-seed-institutions.php manually.]\n";
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
$body = ob_get_clean();
header('Content-Type: text/plain; charset=utf-8');
header('Content-Length: ' . (string) strlen($body));
echo $body;
