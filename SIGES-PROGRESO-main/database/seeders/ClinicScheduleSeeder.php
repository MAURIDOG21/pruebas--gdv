<?php

namespace Database\Seeders;

use App\Models\ClinicSchedule;
use App\Models\Service;
use App\Models\User;
use App\Enums\Shift;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;

class ClinicScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $date = Carbon::today()->toDateString();
        $doctorIds = User::query()->where('role', \App\Enums\UserRole::MEDICO_GENERAL->value)->pluck('id');
        if ($doctorIds->isEmpty()) {
            $doctorIds = User::query()->pluck('id');
        }

        $services = Service::query()->pluck('id', 'name');
        $clinicNames = ['Consultorio A','Consultorio B','Consultorio C','Consultorio D'];

        $plan = [
            ['name' => 'Psicología', 'counts' => [Shift::MATUTINO->value => 1, Shift::VESPERTINO->value => 1]],
            ['name' => 'Dentista',   'counts' => [Shift::MATUTINO->value => 1, Shift::VESPERTINO->value => 1]],
            ['name' => 'Medicina General', 'counts' => [Shift::MATUTINO->value => 4, Shift::VESPERTINO->value => 4]],
        ];

        foreach ($plan as $item) {
            $serviceId = $services[$item['name']] ?? null;
            if (! $serviceId) {
                continue;
            }
            foreach ($item['counts'] as $shift => $count) {
                for ($i = 0; $i < $count; $i++) {
                    $clinic = $clinicNames[$i % count($clinicNames)];
                    $attributes = [
                        'clinic_name' => $clinic,
                        'date' => $date,
                        'shift' => $shift,
                    ];
                    $values = [
                        'user_id' => Arr::random($doctorIds->all()),
                        'service_id' => $serviceId,
                        'is_active' => true,
                        'is_shift_open' => false,
                        'shift_opened_at' => null,
                        'opened_by' => null,
                    ];
                    ClinicSchedule::updateOrCreate($attributes, $values);
                }
            }
        }
    }
}
