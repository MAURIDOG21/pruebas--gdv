<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\SomatometricReading;
use Illuminate\Database\Seeder;

class SomatometricReadingSeeder extends Seeder
{
    public function run(): void
    {
        $targets = Appointment::query()
            ->whereIn('status', [AppointmentStatus::IN_PROGRESS->value, AppointmentStatus::COMPLETED->value])
            ->get();
        foreach ($targets as $appointment) {
            SomatometricReading::factory()->create([
                'medical_record_id' => $appointment->medical_record_id,
                'appointment_id' => $appointment->id,
            ]);
        }
    }
}

