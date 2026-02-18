<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * @deprecated Use StaffSeeder and set ADMIN_USERNAME + ADMIN_PASSWORD in .env
 * This seeder now delegates to StaffSeeder so credentials come from env only.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(StaffSeeder::class);
    }
}
