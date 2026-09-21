<?php

namespace Database\Seeders;

use App\Models\MedicalRecord;
use App\Models\NursingAssessment;
use Illuminate\Database\Seeder;

class NursingAssessmentSeeder extends Seeder
{
    public function run(): void
    {
        MedicalRecord::query()
            ->doesntHave('nursingAssessment')
            ->chunk(200, function ($records) {
                foreach ($records as $record) {
                    NursingAssessment::factory()->create([
                        'medical_record_id' => $record->id,
                    ]);
                }
            });
    }
}

