<?php
/**
 * Standalone fix-pull: run from browser when /thekey route returns 404 (no deploy yet).
 * Upload this file to your server's public folder, then open:
 *   https://quizsnap.online/thekey.php?key=QuizSnapMigrate2026Xp9k3m7
 * Delete this file after use.
 */
header('Content-Type: text/plain; charset=utf-8');

$secret = 'QuizSnapMigrate2026Xp9k3m7';
$key = isset($_GET['key']) ? trim((string) $_GET['key']) : '';
if ($key === '' || $key !== $secret) {
    http_response_code(403);
    echo "Invalid or missing key. Use: ?key=QuizSnapMigrate2026Xp9k3m7\n";
    exit;
}

$publicDir = __DIR__;
$basePath = dirname($publicDir);

if (!is_dir($basePath . '/.git')) {
    http_response_code(500);
    echo "ERROR: .git not found in {$basePath}\n";
    exit;
}

$git = '/usr/local/cpanel/3rdparty/bin/git';
if (!is_executable($git)) {
    $git = 'git';
}

$run = function ($cmd) use ($basePath, $git) {
    $full = 'cd ' . escapeshellarg($basePath) . ' && ' . $cmd . ' 2>&1';
    return trim((string) shell_exec($full));
};

$body = "QuizSnap: Reset + Update from remote (no merge)\n====================================\n\n";

$body .= "Step 1: git fetch origin\n";
$body .= $run($git . ' fetch origin') . "\n\n";

$branch = $run($git . ' rev-parse --abbrev-ref HEAD') ?: 'main';
$body .= "Step 2: git reset --hard origin/{$branch}\n";
$body .= $run($git . ' reset --hard origin/' . $branch) . "\n\n";

$body .= "Step 3: Clear Laravel caches\n";
$artisan = $basePath . '/artisan';
if (is_file($artisan)) {
    $body .= $run('php ' . escapeshellarg($artisan) . ' config:clear') . "\n";
    $body .= $run('php ' . escapeshellarg($artisan) . ' route:clear') . "\n";
    $body .= $run('php ' . escapeshellarg($artisan) . ' view:clear') . "\n";
    $body .= $run('php ' . escapeshellarg($artisan) . ' cache:clear') . "\n";
} else {
    $body .= "(artisan not found, skip)\n";
}

$body .= "\n====================================\n";
$body .= "SUCCESS: Code matches remote (origin/{$branch}). Delete this file (thekey.php) after use.\n";

echo $body;
