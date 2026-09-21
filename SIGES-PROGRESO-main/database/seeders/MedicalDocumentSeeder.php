<?php

namespace Database\Seeders;

use App\Models\MedicalDocument;
use App\Models\MedicalRecord;
use Illuminate\Database\Seeder;

class MedicalDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $records = MedicalRecord::query()->inRandomOrder()->take(ceil(MedicalRecord::query()->count() * 0.2))->get();
        foreach ($records as $record) {
            MedicalDocument::factory()->count(random_int(1, 2))->create([
                'medical_record_id' => $record->id,
            ]);
        }
    }
}

