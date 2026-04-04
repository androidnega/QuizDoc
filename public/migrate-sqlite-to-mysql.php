<?php

set_time_limit(600); // 10 minutes

$secret = 'QuizSnapMigrate2026Xp9k3m7';

if (($_GET['key'] ?? '') !== $secret) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: text/plain; charset=utf-8');
    exit('Invalid or missing key. Use: ' . parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) . '?key=YOUR_SECRET');
}

$sqlitePath = __DIR__ . '/../storage/app/migrate-from.sqlite';

if (!is_file($sqlitePath)) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "SQLite file not found.\n\n";
    echo "1. Upload your database.sqlite to: storage/app/migrate-from.sqlite\n";
    echo "   (same directory as storage/app/ – use FTP or cPanel File Manager).\n\n";
    echo "2. Then visit this URL again with ?key=YOUR_SECRET\n";
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';
define('LARAVEL_START', microtime(true));
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain; charset=utf-8');

$db = Illuminate\Support\Facades\DB::getPdo();
$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
if (strtolower($driver) !== 'mysql') {
    echo "ERROR: .env must use MySQL (DB_CONNECTION=mysql). Current driver: {$driver}\n";
    exit;
}

try {
    $sqlite = new PDO('sqlite:' . $sqlitePath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    echo "QuizSnap: SQLite → MySQL migration (on server)\n";
    echo "================================================\n\n";

    $tables = $sqlite->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

    // Order: parents before children (FK-safe insert order)
    $order = [
        'migrations', 'settings', 'courses', 'users', 'valid_indices', 'class_groups', 'class_group_course',
        'class_group_students', 'course_user', 'quizzes', 'questions', 'question_pools', 'quiz_acceptance',
        'quiz_sessions', 'answers', 'quiz_violations', 'results', 'ai_generation_logs', 'face_image_view_logs',
        'attendance_upload_logs', 'staff_password_resets', 'students',
    ];
    $ordered = [];
    foreach ($order as $t) {
        if (in_array($t, $tables, true)) {
            $ordered[] = $t;
        }
    }
    foreach ($tables as $t) {
        if (!in_array($t, $ordered, true)) {
            $ordered[] = $t;
        }
    }

    echo "Step 1: Run migrations on MySQL...\n";
    Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    echo "OK\n\n";

    echo "Step 2: Copy data from SQLite to MySQL (overwrite)...\n";
    Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS = 0');

    foreach ($ordered as $table) {
        $rows = $sqlite->query('SELECT * FROM ' . $sqlite->quote($table))->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) === 0) {
            echo "  {$table}: (empty)\n";
            continue;
        }

        // Get MySQL columns for this table
        $cols = Illuminate\Support\Facades\Schema::getColumnListing($table);
        if (empty($cols)) {
            echo "  {$table}: skip (table not in MySQL?)\n";
            continue;
        }
        $colSet = array_flip($cols);

        Illuminate\Support\Facades\DB::table($table)->truncate();

        $chunkSize = 200;
        $chunks = array_chunk($rows, $chunkSize);
        $total = count($rows);
        foreach ($chunks as $chunk) {
            $insertRows = [];
            foreach ($chunk as $row) {
                $filtered = array_intersect_key($row, $colSet);
                if (!empty($filtered)) {
                    $insertRows[] = $filtered;
                }
            }
            if (!empty($insertRows)) {
                Illuminate\Support\Facades\DB::table($table)->insert($insertRows);
            }
        }
        echo "  {$table}: {$total} rows\n";
    }

    Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    echo "OK\n\n";

    echo "Step 3: Run migrations again (e.g. backfill new columns)...\n";
    Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    echo "OK\n\n";

    echo "Step 4: Clear caches...\n";
    Illuminate\Support\Facades\Artisan::call('config:clear');
    Illuminate\Support\Facades\Artisan::call('route:clear');
    Illuminate\Support\Facades\Artisan::call('cache:clear');
    Illuminate\Support\Facades\Artisan::call('view:clear');
    echo "OK\n\n";

    if (@rename($sqlitePath, $sqlitePath . '.done')) {
        echo "Renamed migrate-from.sqlite to migrate-from.sqlite.done (so it is not run again).\n\n";
    }

    echo "================================================\n";
    echo "SUCCESS: Migration complete. Your app now uses MySQL with the migrated data.\n";
} catch (Throwable $e) {
    Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
