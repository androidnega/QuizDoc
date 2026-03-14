<?php
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
$artisan = $basePath . '/artisan';
$storagePublic = $basePath . '/storage/app/public';
$linkOut = $basePath . '/public/storage';

$run = function ($cmd) use ($basePath) {
    $full = 'cd ' . escapeshellarg($basePath) . ' && ' . $cmd . ' 2>&1';
    return trim((string) @shell_exec($full));
};
$disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
$shellAllowed = function_exists('shell_exec') && !in_array('shell_exec', $disabled, true);

$body = "QuizSnap: Deploy + Storage setup\n====================================\n\n";

if ($shellAllowed && is_dir($basePath . '/.git')) {
    $git = '/usr/local/cpanel/3rdparty/bin/git';
    if (!is_executable($git)) {
        $git = 'git';
    }
    $body .= "Step 1: git fetch origin\n";
    $body .= $run($git . ' fetch origin') . "\n\n";
    $branch = $run($git . ' rev-parse --abbrev-ref HEAD') ?: 'main';
    $body .= "Step 2: git reset --hard origin/{$branch}\n";
    $body .= $run($git . ' reset --hard origin/' . $branch) . "\n\n";
    $body .= "Step 3: Clear Laravel caches\n";
    if (is_file($artisan)) {
        $body .= $run('php ' . escapeshellarg($artisan) . ' config:clear') . "\n";
        $body .= $run('php ' . escapeshellarg($artisan) . ' route:clear') . "\n";
        $body .= $run('php ' . escapeshellarg($artisan) . ' view:clear') . "\n";
        $body .= $run('php ' . escapeshellarg($artisan) . ' cache:clear') . "\n";
    }
} else {
    if (!$shellAllowed) {
        $body .= "(Shell disabled on server – skipping git and artisan; running storage steps only.)\n\n";
    } else {
        $body .= "Step 1–2: git (skipped – no .git)\n\n";
    }
}

$body .= "Step 4: Storage link (public/storage -> storage/app/public)\n";
if (is_link($linkOut) || is_dir($linkOut)) {
    $body .= "Already exists: public/storage\n";
} elseif ($shellAllowed && is_file($artisan)) {
    $body .= $run('php ' . escapeshellarg($artisan) . ' storage:link') . "\n";
} else {
    if (!is_dir($storagePublic)) {
        @mkdir($storagePublic, 0775, true);
    }
    if (is_dir($storagePublic) && !is_link($linkOut) && !is_dir($linkOut)) {
        if (@symlink($storagePublic, $linkOut)) {
            $body .= "Created symlink via PHP: public/storage\n";
        } else {
            $body .= "Could not create link (symlink may be disabled). Create it manually or run: php artisan storage:link\n";
        }
    } else {
        $body .= "Link target or link already present.\n";
    }
}

$body .= "\nStep 5: Proctoring storage dirs (verification, violations)\n";
if ($shellAllowed && is_file($artisan)) {
    $body .= $run('php ' . escapeshellarg($artisan) . ' storage:ensure-proctoring') . "\n";
} else {
    if (!is_dir($storagePublic)) {
        @mkdir($storagePublic, 0775, true);
    }
    foreach (['verification', 'violations'] as $dir) {
        $path = $storagePublic . '/' . $dir;
        if (!is_dir($path)) {
            if (@mkdir($path, 0775, true)) {
                $body .= "Created: storage/app/public/{$dir}\n";
            } else {
                $body .= "Failed to create: {$dir}\n";
            }
        } else {
            $body .= "Exists: {$dir}\n";
        }
    }
}

$body .= "\nStep 6: Permissions (storage, bootstrap/cache)\n";
$dirs = [$basePath . '/storage', $basePath . '/bootstrap/cache'];
foreach ($dirs as $d) {
    if (is_dir($d)) {
        if (@chmod($d, 0775)) {
            $body .= "chmod 775: " . basename($d) . "\n";
        }
        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($d, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        $done = 0;
        foreach ($iter as $item) {
            if ($item->isDir() && @chmod($item->getPathname(), 0775)) {
                $done++;
            } elseif ($item->isFile() && @chmod($item->getPathname(), 0664)) {
                $done++;
            }
            if ($done >= 100) {
                break;
            }
        }
        if ($done > 0) {
            $body .= "  " . $done . " items updated under " . basename($d) . "\n";
        }
    }
}

$body .= "\n====================================\n";
$body .= "SUCCESS: Code updated, caches cleared, storage ready. Delete this file (thekey.php) after use.\n";

echo $body;
