<?php

namespace App\Console\Commands;

use App\Models\DocuMentor\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetCategoryIds extends Command
{
    protected $signature = 'app:reset-category-ids';

    protected $description = 'Renumber Docu Mentor category IDs to 1, 2, 3… and update project references.';

    public function handle(): int
    {
        $categories = Category::orderBy('id')->get();
        if ($categories->isEmpty()) {
            $this->info('No categories. Nothing to do.');
            return Command::SUCCESS;
        }

        $table = (new Category)->getTable();
        $driver = DB::getDriverName();
        $shift = 10000;
        $map = [];
        foreach ($categories as $i => $c) {
            $map[$c->id] = $i + 1;
        }

        $this->info('Renumbering ' . $categories->count() . ' category IDs to 1..' . $categories->count() . '.');

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        }
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        try {
            // 1. Shift category ids to temp range first (so projects can reference them)
            foreach ($categories as $c) {
                DB::table($table)->where('id', $c->id)->update(['id' => $c->id + $shift]);
            }
            // 2. Shift projects.category_id to temp range
            foreach ($categories as $c) {
                DB::table('projects')->where('category_id', $c->id)->update(['category_id' => $c->id + $shift]);
            }
            // 3. Assign category ids 1, 2, 3...
            foreach ($categories as $i => $c) {
                $newId = $i + 1;
                DB::table($table)->where('id', $c->id + $shift)->update(['id' => $newId]);
            }
            // 4. Point projects to new ids
            foreach ($categories as $c) {
                $newId = $map[$c->id];
                DB::table('projects')->where('category_id', $c->id + $shift)->update(['category_id' => $newId]);
            }

            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = " . ($categories->count() + 1));
            }
            if ($driver === 'sqlite') {
                DB::statement('DELETE FROM sqlite_sequence WHERE name = ?', [$table]);
            }
        } catch (\Throwable $e) {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            }
            if ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON');
            }
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }

        $this->info('Done. Category IDs are now 1, 2, …');
        return Command::SUCCESS;
    }
}
