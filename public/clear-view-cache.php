<?php

$secret = 'QuizSnap2026Xk9m2p7';

if (($_GET['key'] ?? '') !== $secret) {
    header('HTTP/1.1 403 Forbidden');
    exit('Invalid or missing key.');
}

// Prevent running in production without explicit intent
if (($_GET['run'] ?? '') !== 'yes') {
    header('Content-Type: text/plain; charset=utf-8');
    exit('Add &run=yes to the URL to confirm: ' . parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) . '?key=YOUR_SECRET&run=yes');
}

define('LARAVEL_START', microtime(true));

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

ob_start();
try {
    // Clear view cache
    Illuminate\Support\Facades\Artisan::call('view:clear');
    $output = Illuminate\Support\Facades\Artisan::output();
    echo "View cache cleared successfully.\n\n";
    echo $output;
    
    // Also clear config cache if it exists (optional but helpful)
    try {
        Illuminate\Support\Facades\Artisan::call('config:clear');
        echo "\nConfig cache also cleared.\n";
    } catch (Exception $e) {
        // Ignore if config:clear fails
    }

    // Delete this file after successful run (one-time use)
    if (@unlink(__FILE__)) {
        echo "\n[clear-view-cache.php has been deleted. Do not visit again.]\n";
    } else {
        echo "\n[Delete public/clear-view-cache.php manually for security.]\n";
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
$body = ob_get_clean();
header('Content-Type: text/plain; charset=utf-8');
header('Content-Length: ' . (string) strlen($body));
echo $body;
