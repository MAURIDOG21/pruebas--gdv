<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\ClinicSchedule;
use App\Models\MedicalRecord;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $schedules = ClinicSchedule::query()->where('is_active', true)->where('is_shift_open', true)->get();
        if ($schedules->isEmpty()) {
            $schedules = ClinicSchedule::query()->where('is_active', true)->get();
        }
        $records = MedicalRecord::query()->pluck('id');
        // Solo queremos sembrar visitas completadas

        if ($schedules->isEmpty() || $records->isEmpty()) {
            return;
        }
        $count = 10;
        Appointment::withoutEvents(function () use ($count, $schedules, $records) {
            for ($i = 0; $i < $count; $i++) {
                $schedule = $schedules->random();
                $recordId = $records->random();
                Appointment::factory()->create([
                    'ticket_number' => \App\Models\Appointment::generateWalkInTicket(),
                    'medical_record_id' => $recordId,
                    'clinic_schedule_id' => $schedule->id,
                    'service_id' => $schedule->service_id,
                    'doctor_id' => $schedule->user_id,
                    'date' => $schedule->date,
                    'shift' => $schedule->shift->value,
                    'status' => AppointmentStatus::COMPLETED->value,
                ]);
            }
        });
    }
}
