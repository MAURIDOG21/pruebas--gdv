<?php

namespace Database\Factories;

use App\Enums\Shift;
use App\Models\ClinicSchedule;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClinicScheduleFactory extends Factory
{
    protected $model = ClinicSchedule::class;

    public function definition(): array
    {
        $doctorId = User::query()->where('role', \App\Enums\UserRole::MEDICO_GENERAL->value)->inRandomOrder()->value('id') ?? User::query()->inRandomOrder()->value('id');
        $serviceId = Service::query()->inRandomOrder()->value('id');
        $isOpen = $this->faker->boolean(60);
        return [
            'clinic_name' => 'Consultorio '.$this->faker->randomDigitNotNull(),
            'user_id' => $doctorId,
            'service_id' => $serviceId,
            'shift' => $this->faker->randomElement(Shift::cases()),
            'date' => $this->faker->dateTimeBetween('-1 day', '+1 day')->format('Y-m-d'),
            'is_active' => true,
            'is_shift_open' => $isOpen,
            'shift_opened_at' => $isOpen ? now() : null,
            'opened_by' => $isOpen ? User::query()->inRandomOrder()->value('id') : null,
        ];
    }
}
