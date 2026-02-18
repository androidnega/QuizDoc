<?php
/**
 * LIVE SERVER: Verify which database the app is using and whether it has data.
 *
 * 1. Deploy this file to public/ on the live server (e.g. with git pull).
 * 2. Visit: https://yourdomain.com/check-database.php?key=QuizSnap2026Xk9m2p7
 *    (Change the key in this file if you prefer; same as run-migrate.php secret.)
 * 3. The page shows: connection, host, database name, and row counts per table.
 * 4. If "Database name" is not the DB you see in cPanel/phpMyAdmin, fix .env on the server
 *    (DB_DATABASE, DB_HOST, DB_USERNAME, DB_PASSWORD) and clear config cache: php artisan config:clear
 * 5. Delete or restrict this file after use for security.
 */
$secret = 'QuizSnap2026Xk9m2p7'; // change if desired

if (($_GET['key'] ?? '') !== $secret) {
    header('HTTP/1.1 403 Forbidden');
    exit('Invalid or missing key.');
}

define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

header('Content-Type: text/plain; charset=utf-8');

$driver = config('database.default');
$dbName = config('database.connections.' . $driver . '.database');
$dbHost = config('database.connections.' . $driver . '.host');
$dbUser = config('database.connections.' . $driver . '.username');

echo "Database check\n";
echo "==============\n\n";
echo "Connection: " . $driver . "\n";
echo "Host: " . ($dbHost ?? 'n/a') . "\n";
echo "Database name: " . ($dbName ?? 'n/a') . "\n";
echo "Username: " . ($dbUser ?? 'n/a') . "\n\n";

try {
    $pdo = Illuminate\Support\Facades\DB::connection()->getPdo();
    echo "Connection: OK\n\n";

    $driver = config('database.default');
    if ($driver === 'mysql') {
        $rows = Illuminate\Support\Facades\DB::select('SHOW TABLES');
        $key = 'Tables_in_' . ($dbName ?? '');
        $tables = array_map(function ($r) use ($key) {
            return $r->{$key} ?? array_values((array) $r)[0] ?? '';
        }, $rows);
    } else {
        $tables = Illuminate\Support\Facades\Schema::getTableListing();
    }
    $tables = array_values(array_filter($tables));

    if (empty($tables)) {
        echo "Tables: (none – run migrations on live: visit run-migrate.php?key=SECRET&run=yes)\n";
        exit;
    }

    echo "Tables and row counts:\n";
    echo "----------------------\n";
    $total = 0;
    foreach ($tables as $table) {
        try {
            $count = Illuminate\Support\Facades\DB::table($table)->count();
        } catch (Throwable $e) {
            $count = '?';
        }
        if (is_numeric($count)) {
            $total += $count;
        }
        echo sprintf("  %-30s %s\n", $table . ':', (string) $count);
    }
    echo "----------------------\n";
    echo "Total rows: " . $total . "\n\n";

    if ($total === 0) {
        echo "LIVE: This app IS using the database above, but it is empty.\n";
        echo "  - Run migrations: " . (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/run-migrate.php') . "?key=SECRET&run=yes\n";
        echo "  - Then seed admin (or run-seed-admin.php) and add data via Dashboard.\n";
    } else {
        echo "LIVE: The app is using the database shown above (" . $dbName . ").\n";
        echo "If the site still shows no data, check:\n";
        echo "  - .env on the server: DB_DATABASE must be this same database name.\n";
        echo "  - You are visiting the same domain where this script runs.\n";
    }
} catch (Throwable $e) {
    echo "Connection FAILED: " . $e->getMessage() . "\n\n";
    echo "On LIVE server, fix .env:\n";
    echo "  - DB_HOST (often localhost or 127.0.0.1 on cPanel)\n";
    echo "  - DB_DATABASE = the database name you created in phpMyAdmin\n";
    echo "  - DB_USERNAME / DB_PASSWORD = the MySQL user that can access that database\n";
    echo "Create the database in cPanel → MySQL® Databases if it doesn't exist.\n";
}
