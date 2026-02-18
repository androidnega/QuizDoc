<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\Institution;
use Illuminate\Database\Seeder;

class TtuFacultiesDepartmentsSeeder extends Seeder
{
    /**
     * Seed faculties and departments for Takoradi Technical University (TTU)
     */
    public function run(): void
    {
        // Find Takoradi Technical University
        $institution = Institution::where('name', 'Takoradi Technical University')->first();

        if (!$institution) {
            if ($this->command) {
                $this->command->error('Takoradi Technical University not found. Please run InstitutionSeeder first.');
            }
            throw new \Exception('Takoradi Technical University not found. Please run InstitutionSeeder first.');
        }

        // Define faculties and their departments
        $facultiesData = [
            'Faculty of Applied Arts and Technology' => [
                'Graphic Design Technology',
                'Ceramics Technology',
                'Sculpture and Industrial Crafts',
                'Industrial Painting and Design',
                'Textile Design and Technology',
                'Fashion Design and Technology',
            ],
            'Faculty of Applied Sciences' => [
                'Hospitality Management',
                'Tourism Management',
                'Mathematics, Statistics and Actuarial Science',
                'Computer Science',
            ],
            'Faculty of Business Studies' => [
                'Accounting and Finance',
                'Procurement and Supply',
                'Marketing and Strategy',
                'Secretaryship and Management Studies',
                'Professional Studies',
            ],
            'Faculty of Built and Natural Environment' => [
                'Building Technology',
                'Interior Design Technology',
                'Estate Management',
            ],
            'Faculty of Engineering' => [
                'Civil Engineering',
                'Electricals/Electronics Engineering',
                'Mechanical Engineering',
                'Oil & Natural Gas',
                'Renewable Energy Engineering',
            ],
            'Faculty of Health and Allied Sciences' => [
                'Medical Laboratory Sciences',
                'Industrial Laboratory Sciences',
                'Pharmaceutical Sciences',
            ],
            'Faculty of Maritime and Nautical Studies' => [
                'Marine Engineering',
                'Nautical Studies',
                'Maritime Transport',
            ],
            'Faculty of Media Technology and Liberal Studies (FAMTELS)' => [
                'Media and Digital Technology',
                'Communication Technology',
            ],
        ];

        foreach ($facultiesData as $facultyName => $departments) {
            // Create or get faculty
            $faculty = Faculty::firstOrCreate(
                [
                    'name' => $facultyName,
                    'institution_id' => $institution->id,
                ]
            );

            if ($this->command) {
                $this->command->info("Processing faculty: {$facultyName}");
            }

            // Create departments for this faculty
            foreach ($departments as $departmentName) {
                Department::firstOrCreate(
                    [
                        'name' => $departmentName,
                        'faculty_id' => $faculty->id,
                    ]
                );
                if ($this->command) {
                    $this->command->line("  ✓ Created department: {$departmentName}");
                }
            }
        }

        if ($this->command) {
            $this->command->info("\n✅ Successfully seeded all TTU faculties and departments!");
        }
    }
}
