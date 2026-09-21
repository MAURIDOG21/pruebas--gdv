<?php

namespace Database\Factories;

use App\Models\Prescription;
use App\Models\MedicalRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrescriptionFactory extends Factory
{
    protected $model = Prescription::class;

    public function definition(): array
    {
        $items = [];
        $count = $this->faker->numberBetween(1, 3);
        for ($i = 0; $i < $count; $i++) {
            $items[] = [
                'drug' => $this->faker->randomElement(['Paracetamol','Ibuprofeno','Amoxicilina','Omeprazol']),
                'dose' => $this->faker->randomElement(['500 mg','250 mg','1 tab','2 ml']),
                'frequency' => $this->faker->randomElement(['Cada 8 horas','Cada 12 horas','Diario']),
                'duration' => $this->faker->randomElement(['3 días','5 días','7 días']),
                'route' => $this->faker->randomElement(['Oral','IM','IV','Tópica']),
                'instructions' => $this->faker->sentence(6),
            ];
        }
        return [
            'medical_record_id' => MedicalRecord::query()->inRandomOrder()->value('id'),
            'doctor_id' => User::query()->inRandomOrder()->value('id'),
            'issue_date' => now()->toDateString(),
            'diagnosis' => $this->faker->sentence(6),
            'notes' => $this->faker->optional()->sentence(8),
            'items' => $items,
        ];
    }
}

