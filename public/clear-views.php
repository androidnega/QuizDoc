<?php

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
