<?php

/**
 * OTP / examiner-code diagnostic (read-only). DELETE after debugging.
 *
 * 1. Add to .env: QUIZSNAP_OTP_DIAGNOSE_KEY=your_long_random_secret
 *    Then: php artisan config:clear   (if you use config cache)
 * 2. Visit (GET):
 *    https://yoursite.com/quizsnap-otp-diagnose.php?key=YOUR_SECRET&index=BC%2FITS%2F24%2F047&code=123456
 *    (code is optional; use the 6 digits the student typed)
 * 3. Copy the plain-text output and share for analysis.
 * 4. Remove this file from public/ when done.
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$secret = (string) config('quizsnap.otp_diagnose_key', '');
if ($secret === '') {
    exit("Set QUIZSNAP_OTP_DIAGNOSE_KEY in .env (see config/quizsnap.php). If you use config:cache, run php artisan config:clear after changing .env.\n");
}

if (($_GET['key'] ?? '') !== $secret) {
    header('HTTP/1.1 403 Forbidden');
    exit("Invalid or missing key.\n");
}

$index = isset($_GET['index']) ? (string) $_GET['index'] : '';
$code = isset($_GET['code']) ? (string) $_GET['code'] : null;
if ($code === '') {
    $code = null;
}

echo \App\Services\OtpDiagnostics::buildReport($index, $code);
echo "\n";
