<?php
/**
 * ONE-TIME: Clear compiled Blade views so Laravel recompiles from fixed templates.
 * Use after deploy when you see "expecting elseif or else or endif" ParseError.
 *
 * 1. Set the secret below (e.g. $secret = 'clearviews123';).
 * 2. Visit: https://yoursite.com/clear-views.php?key=YOUR_SECRET&run=yes
 * 3. Delete this file after use (or leave; it only clears cache).
 */
$secret = 'CHANGE_ME_BEFORE_USE';

if (($_GET['key'] ?? '') !== $secret) {
    header('HTTP/1.1 403 Forbidden');
    exit('Invalid or missing key.');
}

if (($_GET['run'] ?? '') !== 'yes') {
    header('Content-Type: text/plain; charset=utf-8');
    exit('Add &run=yes to confirm.');
}

$baseDir = dirname(__DIR__);
if (!is_file($baseDir . '/vendor/autoload.php')) {
    header('Content-Type: text/plain; charset=utf-8');
    exit('Laravel not found at ' . $baseDir);
}

require $baseDir . '/vendor/autoload.php';
$app = require_once $baseDir . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$viewsPath = $baseDir . '/storage/framework/views';
$cleared = 0;
if (is_dir($viewsPath)) {
    $files = glob($viewsPath . '/*.php');
    foreach ($files ?: [] as $f) {
        if (is_file($f) && basename($f) !== '.gitignore') {
            @unlink($f);
            $cleared++;
        }
    }
}

header('Content-Type: text/plain; charset=utf-8');
echo "Cleared {$cleared} compiled view(s). Reload the dashboard; the ParseError should be gone.\n";
