<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'name' => fake()->name(),
            'index_number' => null,
            'course_id' => null,
            'role' => User::ROLE_EXAMINER,
            'password' => 'password',
            'avatar' => null,
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_SUPER_ADMIN,
        ]);
    }

    public function examiner(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_EXAMINER,
        ]);
    }
}
