<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetStaffPasswords extends Command
{
    protected $signature = 'app:reset-staff-passwords
                            {--show : Only show what would be reset}';

    protected $description = 'Reset staff account passwords to known values so you can log in (admin, coordinator, examiners).';

    public function handle(): int
    {
        $showOnly = $this->option('show');

        $adminUsername = env('ADMIN_USERNAME', 'admin');
        $adminPassword = env('ADMIN_PASSWORD', 'password');

        $updated = [];

        // Super Admin
        $admin = User::where('role', User::ROLE_SUPER_ADMIN)->first();
        if ($admin) {
            if (!$showOnly) {
                $admin->password = $adminPassword;
                $admin->save();
            }
            $updated[] = "Super Admin: username = {$admin->username}, password = (from ADMIN_PASSWORD in .env, default 'password')";
        }

        // Coordinator (any user with role coordinator)
        $coord = User::where('role', User::DM_ROLE_COORDINATOR)->first();
        if ($coord) {
            if (!$showOnly) {
                $coord->password = 'coordinator';
                $coord->save();
            }
            $updated[] = "Coordinator: username = coordinator, password = coordinator";
        }

        // All examiners → password 'password'
        $examiners = User::where('role', User::ROLE_EXAMINER)->get();
        foreach ($examiners as $e) {
            if (!$showOnly) {
                $e->password = 'password';
                $e->save();
            }
            $updated[] = "Examiner: username = {$e->username}, password = 'password'";
        }

        if (empty($updated)) {
            $this->warn('No staff users found. Run: php artisan db:seed');
            return Command::SUCCESS;
        }

        if ($showOnly) {
            $this->info('Would reset passwords for:');
            foreach ($updated as $line) {
                $this->line('  ' . $line);
            }
            $this->newLine();
            $this->info('Run without --show to apply.');
            return Command::SUCCESS;
        }

        $this->info('Passwords reset. You can log in with:');
        foreach ($updated as $line) {
            $this->line('  ' . $line);
        }
        return Command::SUCCESS;
    }
}
