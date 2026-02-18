<?php

namespace Database\Seeders;

use App\Models\Institution;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    public function run(): void
    {
        $institutions = [
            ['name' => 'Accra Technical University', 'region' => 'Greater Accra Region'],
            ['name' => 'Kumasi Technical University', 'region' => 'Ashanti Region'],
            ['name' => 'Takoradi Technical University', 'region' => 'Western Region'],
            ['name' => 'Ho Technical University', 'region' => 'Volta Region'],
            ['name' => 'Koforidua Technical University', 'region' => 'Eastern Region'],
            ['name' => 'Sunyani Technical University', 'region' => 'Bono Region'],
            ['name' => 'Cape Coast Technical University', 'region' => 'Central Region'],
            ['name' => 'Tamale Technical University', 'region' => 'Northern Region'],
            ['name' => 'Bolgatanga Technical University', 'region' => 'Upper East Region'],
            ['name' => 'Wa Technical University', 'region' => 'Upper West Region'],
        ];

        foreach ($institutions as $data) {
            Institution::firstOrCreate(
                ['name' => $data['name']],
                ['region' => $data['region']]
            );
        }
    }
}
