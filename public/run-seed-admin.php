<?php

$secret = 'CHANGE_ME_BEFORE_UPLOAD';

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
    $user = \App\Models\User::updateOrCreate(
        ['username' => 'admin'],
        [
            'name' => 'Admin',
            'role' => \App\Models\User::ROLE_SUPER_ADMIN,
            'course_id' => null,
            'password' => 'admin123',
        ]
    );
    echo "Super admin account created/updated.\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
    echo "\nYou can log in at: https://quizsnap.ausweblabs.com/login\n";
    echo "\nChange the password after first login for security.\n";

    if (@unlink(__FILE__)) {
        echo "\n[run-seed-admin.php has been deleted. Do not visit again.]\n";
    } else {
        echo "\n[Delete public/run-seed-admin.php manually for security.]\n";
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
$body = ob_get_clean();
header('Content-Type: text/plain; charset=utf-8');
header('Content-Length: ' . (string) strlen($body));
echo $body;
