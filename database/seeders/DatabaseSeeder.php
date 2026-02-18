<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Course::firstOrCreate(
            ['code' => 'CS101'],
            ['name' => 'Introduction to Programming']
        );
        $this->call(InstitutionSeeder::class);
        $this->call(TtuFacultiesDepartmentsSeeder::class);
        $this->call(StaffSeeder::class);
        $this->call(GroupNameSeeder::class);
    }
}
