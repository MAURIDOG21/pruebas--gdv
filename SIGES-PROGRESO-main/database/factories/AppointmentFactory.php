<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Enums\Shift;
use App\Enums\VisitType;
use App\Models\Appointment;
use App\Models\ClinicSchedule;
use App\Models\MedicalRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $schedule = ClinicSchedule::query()->where('is_active', true)->where('is_shift_open', true)->inRandomOrder()->first();
        $medicalRecordId = MedicalRecord::query()->inRandomOrder()->value('id');
        $shift = $schedule?->shift->value ?? $this->faker->randomElement(array_map(fn($c) => $c->value, Shift::cases()));
        return [
            'medical_record_id' => $medicalRecordId,
            'clinic_schedule_id' => $schedule?->id,
            'service_id' => $schedule?->service_id,
            'doctor_id' => $schedule?->user_id,
            'date' => $schedule?->date ?? now()->toDateString(),
            'shift' => $shift,
            'visit_type' => $this->faker->randomElement([VisitType::PRIMERA_VEZ->value, VisitType::SUBSECUENTE->value]),
            'reason_for_visit' => $this->faker->sentence(6),
            'notes' => $this->faker->optional()->sentence(10),
            'status' => $this->faker->randomElement([
                AppointmentStatus::PENDING->value,
                AppointmentStatus::IN_PROGRESS->value,
                AppointmentStatus::COMPLETED->value,
                AppointmentStatus::CANCELLED->value,
            ]),
        ];
    }
}
