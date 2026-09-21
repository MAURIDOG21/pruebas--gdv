<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Prescription;
use App\Models\MedicalLeave;

class MedicalLeavesBulkSeeder extends Seeder
{
    public function run(): void
    {
        $prescriptions = Prescription::query()->inRandomOrder()->get();
        foreach ($prescriptions as $p) {
            if (random_int(1, 100) <= 8) {
                $issue = \Carbon\Carbon::parse($p->issue_date ?? now()->toDateString())->toDateString();
                $end = \Carbon\Carbon::parse($issue)->addDays(random_int(1, 7))->toDateString();
                MedicalLeave::create([
                    'medical_record_id' => $p->medical_record_id,
                    'doctor_id' => $p->doctor_id,
                    'issue_date' => $issue,
                    'start_date' => $issue,
                    'end_date' => $end,
                    'reason' => 'Descanso médico',
                    'status' => \App\Enums\MedicalLeaveStatus::PENDING_APPROVAL->value,
                ]);
            }
        }
    }
}
