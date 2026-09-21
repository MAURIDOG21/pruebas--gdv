<?php

namespace Database\Factories;

use App\Models\SomatometricReading;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SomatometricReadingFactory extends Factory
{
    protected $model = SomatometricReading::class;

    public function definition(): array
    {
        return [
            'medical_record_id' => MedicalRecord::query()->inRandomOrder()->value('id'),
            'appointment_id' => Appointment::query()->inRandomOrder()->value('id'),
            'user_id' => User::query()->inRandomOrder()->value('id'),
            'blood_pressure_systolic' => $this->faker->numberBetween(100, 140),
            'blood_pressure_diastolic' => $this->faker->numberBetween(60, 90),
            'heart_rate' => $this->faker->numberBetween(60, 100),
            'respiratory_rate' => $this->faker->numberBetween(12, 20),
            'temperature' => $this->faker->randomFloat(1, 36, 38),
            'weight' => $this->faker->randomFloat(1, 40, 100),
            'height' => $this->faker->randomFloat(2, 1.4, 1.9),
            'blood_glucose' => $this->faker->numberBetween(70, 140),
            'oxygen_saturation' => $this->faker->numberBetween(95, 100),
            'observations' => $this->faker->optional()->sentence(8),
        ];
    }
}

