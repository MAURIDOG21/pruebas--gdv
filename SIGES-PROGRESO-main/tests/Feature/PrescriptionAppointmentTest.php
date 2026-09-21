<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use App\Models\Prescription;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\User;
use App\Models\Service;
use Database\Factories\AppointmentFactory;

class PrescriptionAppointmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_prescription_saves_appointment_id()
    {
        $doctor = User::factory()->doctor()->create();
        $service = Service::factory()->create();
        \App\Models\ClinicSchedule::factory()->create([
            'user_id' => $doctor->id,
            'service_id' => $service->id,
            'is_active' => true,
            'is_shift_open' => true,
            'shift_opened_at' => now(),
        ]);
        $record = MedicalRecord::factory()->create();
        $appointment = Appointment::factory()->create([
            'medical_record_id' => $record->id,
            'service_id' => $service->id,
            'doctor_id' => $doctor->id,
            'status' => \App\Enums\AppointmentStatus::IN_PROGRESS,
        ]);

        $prescription = Prescription::create([
            'medical_record_id' => $record->id,
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'issue_date' => now()->toDateString(),
            'diagnosis' => 'Diagnóstico de prueba',
            'notes' => 'Plan de tratamiento',
            'items' => [
                ['drug' => 'Paracetamol', 'dose' => '500 mg', 'frequency' => 'Cada 8 horas'],
            ],
        ]);

        $this->assertNotNull($prescription->id);
        $this->assertEquals($appointment->id, $prescription->appointment_id);
    }

    public function test_prescriptions_table_has_appointment_id_column()
    {
        $this->assertTrue(Schema::hasColumn('prescriptions', 'appointment_id'));
    }
}

