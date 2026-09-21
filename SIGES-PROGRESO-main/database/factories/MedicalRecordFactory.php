<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MedicalRecord>
 */
class MedicalRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => \App\Models\Patient::factory(),
            'patient_type' => $this->faker->randomElement(\App\Enums\PatientType::cases()),
            'employee_status' => $this->faker->randomElement(\App\Enums\EmployeeStatus::cases()),
        ];
    }

    /**
     * Estado: crear expediente para paciente menor de edad.
     */
    public function forMinor(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'patient_id' => \App\Models\Patient::factory()->isMinor(),
            ];
        });
    }
}


