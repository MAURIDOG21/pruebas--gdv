<?php

namespace Database\Factories;

use App\Models\MedicalLeave;
use App\Models\MedicalRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicalLeaveFactory extends Factory
{
    protected $model = MedicalLeave::class;

    public function definition(): array
    {
        return [
            'medical_record_id' => MedicalRecord::query()->inRandomOrder()->value('id'),
            'doctor_id' => User::query()->inRandomOrder()->value('id'),
            'issue_date' => now()->toDateString(),
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays($this->faker->numberBetween(1, 7))->toDateString(),
            'reason' => $this->faker->sentence(6),
            'issuing_department' => $this->faker->randomElement(['Clínica','Dirección','Recursos']),
            'status' => $this->faker->randomElement(['pending','approved','rejected']),
        ];
    }
}

