<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Create admin and examiner accounts from .env only. No hardcoded credentials.
 * Set ADMIN_USERNAME + ADMIN_PASSWORD for an admin; EXAMINER_USERNAME + EXAMINER_PASSWORD for an examiner.
 * Optional: EXAMINER_NAME, EXAMINER_COURSE_ID (course code, e.g. CS101).
 * Multiple examiners: EXAMINER_1_USERNAME, EXAMINER_1_PASSWORD, EXAMINER_1_NAME, EXAMINER_1_COURSE_ID, etc.
 */
class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdmin();
        $this->seedCoordinator();
        $this->seedExaminerFromSingleEnv();
        $this->seedExaminersFromNumberedEnv();
    }

    /**
     * Create coordinator account (username: coordinator, password: coordinator).
     */
    private function seedCoordinator(): void
    {
        User::updateOrCreate(
            ['username' => 'coordinator'],
            [
                'name' => 'Coordinator',
                'role' => User::DM_ROLE_COORDINATOR,
                'password' => Hash::make('coordinator'),
            ]
        );
    }

    /**
     * Create or update Super Admin from .env. Password is only set when creating;
     * existing Super Admin password is never changed (e.g. after system reset).
     */
    private function seedAdmin(): void
    {
        $username = env('ADMIN_USERNAME');
        $password = env('ADMIN_PASSWORD');

        if ($username === null || $username === '') {
            return;
        }

        $existing = User::where('username', $username)->first();
        $data = [
            'name' => env('ADMIN_NAME', $username),
            'role' => User::ROLE_SUPER_ADMIN,
            'course_id' => null,
        ];
        if ($existing === null && $password !== null && $password !== '') {
            $data['password'] = Hash::make($password);
        }

        User::updateOrCreate(['username' => $username], $data);
    }

    private function seedExaminerFromSingleEnv(): void
    {
        $username = env('EXAMINER_USERNAME');
        $password = env('EXAMINER_PASSWORD');

        if ($username === null || $username === '' || $password === null || $password === '') {
            return;
        }

        $courseId = $this->resolveCourseId(env('EXAMINER_COURSE_ID'));

        User::updateOrCreate(
            ['username' => $username],
            [
                'name' => env('EXAMINER_NAME', $username),
                'role' => 'examiner',
                'course_id' => $courseId,
                'password' => Hash::make($password),
            ]
        );
    }

    private function seedExaminersFromNumberedEnv(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $username = env("EXAMINER_{$i}_USERNAME");
            $password = env("EXAMINER_{$i}_PASSWORD");

            if ($username === null || $username === '' || $password === null || $password === '') {
                continue;
            }

            $courseId = $this->resolveCourseId(env("EXAMINER_{$i}_COURSE_ID"));
            $name = env("EXAMINER_{$i}_NAME", $username);

            User::updateOrCreate(
                ['username' => $username],
                [
                    'name' => $name,
                    'role' => 'examiner',
                    'course_id' => $courseId,
                    'password' => Hash::make($password),
                ]
            );
        }
    }

    private function resolveCourseId(?string $courseRef): ?int
    {
        if ($courseRef === null || $courseRef === '') {
            return null;
        }

        $course = is_numeric($courseRef)
            ? Course::find((int) $courseRef)
            : Course::where('code', $courseRef)->first();

        return $course?->id;
    }
}
