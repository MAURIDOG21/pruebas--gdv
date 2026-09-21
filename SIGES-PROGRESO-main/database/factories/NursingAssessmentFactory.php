<?php

namespace Database\Factories;

use App\Models\NursingAssessment;
use App\Models\MedicalRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NursingAssessmentFactory extends Factory
{
    protected $model = NursingAssessment::class;

    public function definition(): array
    {
        return [
            'medical_record_id' => MedicalRecord::query()->inRandomOrder()->value('id'),
            'user_id' => User::query()->inRandomOrder()->value('id'),
            'allergies' => $this->faker->optional()->sentence(6),
            'personal_pathological_history' => $this->faker->optional()->sentence(10),
        ];
    }
}

