<?php

namespace Database\Factories;

use App\Models\MedicalDocument;
use App\Models\MedicalRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicalDocumentFactory extends Factory
{
    protected $model = MedicalDocument::class;

    public function definition(): array
    {
        return [
            'medical_record_id' => MedicalRecord::query()->inRandomOrder()->value('id'),
            'user_id' => User::query()->inRandomOrder()->value('id'),
            'name' => $this->faker->randomElement(['Consentimiento','Estudio de laboratorio','Informe imagenológico','Referencia']),
            'file_path' => 'storage/documents/'.$this->faker->uuid().'.pdf',
        ];
    }
}

