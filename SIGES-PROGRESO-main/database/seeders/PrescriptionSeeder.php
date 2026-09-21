<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Prescription;
use Illuminate\Database\Seeder;

class PrescriptionSeeder extends Seeder
{
    public function run(): void
    {
        $completed = Appointment::query()->where('status', AppointmentStatus::COMPLETED->value)->get();
        $inProgressMrIds = Appointment::query()
            ->where('status', AppointmentStatus::IN_PROGRESS->value)
            ->pluck('medical_record_id')
            ->all();
        $completed = $completed->filter(fn ($a) => ! in_array($a->medical_record_id, $inProgressMrIds, true));
        $target = (int) floor($completed->count() * 0.7);
        $selected = $completed->shuffle()->take($target);
        foreach ($selected as $appointment) {
            Prescription::factory()->create([
                'medical_record_id' => $appointment->medical_record_id,
                'appointment_id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'issue_date' => now()->toDateString(),
            ]);
        }
    }
}
