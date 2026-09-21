<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => '1234',
            'role' => UserRole::RECEPCIONISTA,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => UserRole::ADMIN]);
    }

    public function doctor(): static
    {
        return $this->state(fn () => ['role' => UserRole::MEDICO_GENERAL]);
    }

    public function nurse(): static
    {
        return $this->state(fn () => ['role' => UserRole::ENFERMERO]);
    }

    public function receptionist(): static
    {
        return $this->state(fn () => ['role' => UserRole::RECEPCIONISTA]);
    }
}
