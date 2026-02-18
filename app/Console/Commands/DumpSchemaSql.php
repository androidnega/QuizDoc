<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DumpSchemaSql extends Command
{
    protected $signature = 'schema:dump-sql {--file=schema.sql : Output file path}';

    protected $description = 'Dump database schema to SQL file for phpMyAdmin import (run migrate first locally)';

    public function handle(): int
    {
        $driver = config('database.default');
        if ($driver !== 'mysql') {
            $this->error('This command only supports MySQL. Your default connection is: ' . $driver);

            return Command::FAILURE;
        }

        $file = base_path($this->option('file'));
        $tables = $this->getTableNames();

        if (empty($tables)) {
            $this->warn('No tables found. Run: php artisan migrate --force');
            $this->info('Then run this command again to dump the schema.');

            return Command::FAILURE;
        }

        $sql = "-- QuizSnap schema dump for phpMyAdmin import\n";
        $sql .= "-- Generated at " . now()->toIso8601String() . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $create = DB::selectOne('SHOW CREATE TABLE `' . str_replace('`', '``', $table) . '`');
            $createSql = $create->{'Create Table'} ?? null;
            if ($createSql) {
                $sql .= "DROP TABLE IF EXISTS `" . str_replace('`', '``', $table) . "`;\n";
                $sql .= $createSql . ";\n\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        if (file_put_contents($file, $sql) === false) {
            $this->error('Could not write to: ' . $file);

            return Command::FAILURE;
        }

        $this->info('Schema written to: ' . $file);
        $this->info('Import this file in cPanel phpMyAdmin (Import tab) on your production database.');

        return Command::SUCCESS;
    }

    protected function getTableNames(): array
    {
        $db = config('database.connections.mysql.database');
        $results = DB::select('SHOW TABLES');
        $key = 'Tables_in_' . $db;

        return array_map(function ($row) use ($key) {
            return $row->{$key};
        }, $results);
    }
}
