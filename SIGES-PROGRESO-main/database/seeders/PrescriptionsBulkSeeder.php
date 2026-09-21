<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment;
use App\Models\Prescription;

class PrescriptionsBulkSeeder extends Seeder
{
    public function run(): void
    {
        $appointments = Appointment::query()
            ->where('status', \App\Enums\AppointmentStatus::COMPLETED->value)
            ->inRandomOrder()
            ->limit(2000)
            ->get();

        foreach ($appointments as $appt) {
            if (random_int(1, 100) <= 35) {
                $presc = Prescription::factory()->make([
                    'medical_record_id' => $appt->medical_record_id,
                    'doctor_id' => $appt->doctor_id,
                ]);
                $data = $presc->getAttributes();
                $data['appointment_id'] = $appt->id;
                $data['issue_date'] = $appt->date;
                Prescription::create($data);
            }
        }
    }
}

