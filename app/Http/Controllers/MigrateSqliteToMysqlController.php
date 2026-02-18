<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class MigrateSqliteToMysqlController extends Controller
{
    private const SECRET = 'QuizSnapMigrate2026Xp9k3m7';

    private const TABLE_ORDER = [
        'migrations', 'settings', 'courses', 'users', 'valid_indices', 'class_groups', 'class_group_course',
        'class_group_students', 'course_user', 'quizzes', 'questions', 'question_pools', 'quiz_acceptance',
        'quiz_sessions', 'answers', 'quiz_violations', 'results', 'ai_generation_logs', 'face_image_view_logs',
        'attendance_upload_logs', 'staff_password_resets', 'students',
    ];

    /**
     * Run SQLite → MySQL migration via URL (no SSH).
     * Visit: /migrate-sqlite-to-mysql?key=YOUR_SECRET
     * Upload storage/app/migrate-from.sqlite first.
     */
    public function __invoke(Request $request): Response
    {
        set_time_limit(600);

        if ($request->query('key') !== self::SECRET) {
            return response('Invalid or missing key. Use: /migrate-sqlite-to-mysql?key=YOUR_SECRET', 403, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        $sqlitePath = storage_path('app/migrate-from.sqlite');

        if (!is_file($sqlitePath)) {
            return response(
                "SQLite file not found.\n\n" .
                "1. Upload your database.sqlite to: storage/app/migrate-from.sqlite\n" .
                "   (use FTP or cPanel File Manager).\n\n" .
                "2. Then visit this URL again with ?key=YOUR_SECRET\n",
                200,
                ['Content-Type' => 'text/plain; charset=utf-8']
            );
        }

        $driver = DB::getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if (strtolower($driver) !== 'mysql') {
            return response("ERROR: .env must use MySQL (DB_CONNECTION=mysql). Current: {$driver}", 200, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        $output = $this->runMigration($sqlitePath);

        return response($output, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    private function runMigration(string $sqlitePath): string
    {
        $out = '';
        $out .= "QuizSnap: SQLite → MySQL migration (on server)\n";
        $out .= "================================================\n\n";

        try {
            $sqlite = new \PDO('sqlite:' . $sqlitePath, null, null, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);

            $tables = $sqlite->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")
                ->fetchAll(\PDO::FETCH_COLUMN);

            $ordered = [];
            foreach (self::TABLE_ORDER as $t) {
                if (in_array($t, $tables, true)) {
                    $ordered[] = $t;
                }
            }
            foreach ($tables as $t) {
                if (!in_array($t, $ordered, true)) {
                    $ordered[] = $t;
                }
            }

            $out .= "Step 1: Run migrations on MySQL...\n";
            Artisan::call('migrate', ['--force' => true]);
            $out .= "OK\n\n";

            $out .= "Step 2: Copy data from SQLite to MySQL (overwrite)...\n";
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');

            foreach ($ordered as $table) {
                $rows = $sqlite->query('SELECT * FROM ' . $sqlite->quote($table))->fetchAll(\PDO::FETCH_ASSOC);
                if (count($rows) === 0) {
                    $out .= "  {$table}: (empty)\n";
                    continue;
                }

                $cols = Schema::getColumnListing($table);
                if (empty($cols)) {
                    $out .= "  {$table}: skip (table not in MySQL?)\n";
                    continue;
                }

                DB::table($table)->truncate();

                $chunkSize = 200;
                $chunks = array_chunk($rows, $chunkSize);
                $total = count($rows);
                foreach ($chunks as $chunk) {
                    $insertRows = [];
                    foreach ($chunk as $row) {
                        // Map SQLite row to MySQL columns case-insensitively (fixes "Field 'TEXT' doesn't have a default value")
                        $filtered = [];
                        foreach ($cols as $col) {
                            foreach ($row as $k => $v) {
                                if (strcasecmp((string) $k, (string) $col) === 0) {
                                    $filtered[$col] = $v;
                                    break;
                                }
                            }
                        }
                        if (!empty($filtered)) {
                            $insertRows[] = $filtered;
                        }
                    }
                    if (!empty($insertRows)) {
                        DB::table($table)->insert($insertRows);
                    }
                }
                $out .= "  {$table}: {$total} rows\n";
            }

            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            $out .= "OK\n\n";

            $out .= "Step 3: Run migrations again (e.g. backfill)...\n";
            Artisan::call('migrate', ['--force' => true]);
            $out .= "OK\n\n";

            $out .= "Step 4: Clear caches...\n";
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            $out .= "OK\n\n";

            if (@rename($sqlitePath, $sqlitePath . '.done')) {
                $out .= "Renamed migrate-from.sqlite to migrate-from.sqlite.done\n\n";
            }

            $out .= "================================================\n";
            $out .= "SUCCESS: Migration complete. Your app now uses MySQL with the migrated data.\n";
        } catch (\Throwable $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            $out .= "ERROR: " . $e->getMessage() . "\n";
            $out .= $e->getTraceAsString();
        }

        return $out;
    }
}
