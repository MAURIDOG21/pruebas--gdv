<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment;
use App\Models\ClinicSchedule;
use App\Models\MedicalRecord;
use App\Models\Service;
use App\Enums\AppointmentStatus;
use App\Enums\VisitType;
use Illuminate\Support\Arr;

class AppointmentsBulkSeeder extends Seeder
{
    public function run(): void
    {
        $total = 4000;
        
        // 1. Obtener schedules existentes
        $schedules = ClinicSchedule::query()->where('is_active', true)->get();
        
        // 2. Obtener todos los servicios disponibles (IDs 1 al 5)
        $services = Service::all();
        if ($services->isEmpty()) {
            return;
        }

        // 3. ESTRATEGIA DE RELLENO: 
        // Si no hay un horario activo para algún servicio, creamos uno temporalmente
        // para que el seeder pueda generar citas de ese tipo.
        foreach ($services as $service) {
            if (!$schedules->contains('service_id', $service->id)) {
                $newSchedule = ClinicSchedule::factory()->create([
                    'service_id' => $service->id,
                    'is_active' => true,
                    // El factory llenará clinic_name, user_id, shift, etc.
                ]);
                $schedules->push($newSchedule);
            }
        }

        $records = MedicalRecord::query()->pluck('id');
        if ($schedules->isEmpty() || $records->isEmpty()) {
            return;
        }

        // Agrupar horarios por servicio para selección rápida
        $schedulesByService = $schedules->groupBy('service_id');
        $serviceIds = $schedulesByService->keys()->all();

        $statusWeights = [
            AppointmentStatus::COMPLETED->value => 95,
            AppointmentStatus::CANCELLED->value => 5,
        ];
        $typeWeights = [
            VisitType::PRIMERA_VEZ->value => 45,
            VisitType::SUBSECUENTE->value => 55,
        ];

        $pickWeighted = function (array $weights): string {
            $sum = array_sum($weights);
            $r = random_int(1, $sum);
            $acc = 0;
            foreach ($weights as $key => $w) {
                $acc += $w;
                if ($r <= $acc) {
                    return $key;
                }
            }
            return array_key_first($weights);
        };

        // Ejecutar sin disparar eventos (Webhooks, etc.)
        Appointment::withoutEvents(function () use ($total, $schedules, $records, $statusWeights, $typeWeights, $pickWeighted, $schedulesByService, $serviceIds) {
            
            // A. Generación Masiva Aleatoria
            for ($i = 0; $i < $total; $i++) {
                // Seleccionar un servicio aleatorio (ahora sí incluye del 1 al 5)
                $serviceId = Arr::random($serviceIds);
                $scheduleGroup = $schedulesByService->get($serviceId);
                
                if (!$scheduleGroup || $scheduleGroup->isEmpty()) continue;

                $schedule = $scheduleGroup->random();
                $recordId = $records->random();
                
                $daysBack = random_int(0, 7 * 24); // Últimos ~6 meses
                $hour = random_int(8, 19);
                $minute = [0, 15, 30, 45][array_rand([0, 1, 2, 3])];
                
                $createdAt = now()->subDays($daysBack)->setTime($hour, $minute);
                $date = $createdAt->toDateString();

                Appointment::factory()->create([
                    'ticket_number' => \App\Models\Appointment::generateWalkInTicket(),
                    'medical_record_id' => $recordId,
                    'clinic_schedule_id' => $schedule->id,
                    'service_id' => $schedule->service_id, // Variará entre 1 y 5
                    'doctor_id' => $schedule->user_id,
                    'date' => $date,
                    'shift' => $schedule->shift->value ?? 'matutino',
                    'status' => $pickWeighted($statusWeights),
                    'visit_type' => $pickWeighted($typeWeights),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt->copy()->addMinutes(rand(15, 60)),
                ]);
            }

            // B. Generar citas recientes (Hoy/Ayer) para pruebas de dashboard en vivo
            $recentDate = now()->toDateString();
            
            // Citas PENDING (En espera)
            for ($p = 0; $p < 10; $p++) {
                $serviceId = Arr::random($serviceIds);
                $schedule = $schedulesByService->get($serviceId)->random();
                $recordId = $records->random();
                
                Appointment::factory()->create([
                    'ticket_number' => \App\Models\Appointment::generateWalkInTicket(),
                    'medical_record_id' => $recordId,
                    'clinic_schedule_id' => $schedule->id,
                    'service_id' => $schedule->service_id,
                    'doctor_id' => $schedule->user_id,
                    'date' => $recentDate,
                    'shift' => $schedule->shift->value ?? 'matutino',
                    'status' => AppointmentStatus::PENDING->value,
                    'visit_type' => $pickWeighted($typeWeights),
                    'created_at' => now()->setTime(8, 0)->addMinutes($p * 15),
                    'updated_at' => now()->setTime(8, 0)->addMinutes($p * 15),
                ]);
            }

            // Citas IN_PROGRESS (En consulta)
            for ($c = 0; $c < 5; $c++) {
                $serviceId = Arr::random($serviceIds);
                $schedule = $schedulesByService->get($serviceId)->random();
                $recordId = $records->random();
                
                Appointment::factory()->create([
                    'ticket_number' => \App\Models\Appointment::generateWalkInTicket(),
                    'medical_record_id' => $recordId,
                    'clinic_schedule_id' => $schedule->id,
                    'service_id' => $schedule->service_id,
                    'doctor_id' => $schedule->user_id,
                    'date' => $recentDate,
                    'shift' => $schedule->shift->value ?? 'matutino',
                    'status' => AppointmentStatus::IN_PROGRESS->value,
                    'visit_type' => $pickWeighted($typeWeights),
                    'created_at' => now()->setTime(9, 0)->addMinutes($c * 20),
                    'updated_at' => now()->setTime(9, 0)->addMinutes($c * 20),
                ]);
            }

            // C. Relleno Uniforme Diario (Para asegurar que el heatmap no tenga huecos)
            $monthDays = now()->daysInMonth;
            foreach ($serviceIds as $sid) {
                $group = $schedulesByService->get($sid);
                if (!$group) continue;
                
                for ($day = 1; $day <= $monthDays; $day++) {
                    $schedule = $group->random();
                    $recordId = $records->random();
                    $dateObj = now()->setDay($day);
                    
                    if ($dateObj->isFuture()) continue;

                    $createdAt = $dateObj->setTime(rand(8, 18), 0);
                    
                    Appointment::factory()->create([
                        'ticket_number' => \App\Models\Appointment::generateWalkInTicket(),
                        'medical_record_id' => $recordId,
                        'clinic_schedule_id' => $schedule->id,
                        'service_id' => $sid,
                        'doctor_id' => $schedule->user_id,
                        'date' => $createdAt->toDateString(),
                        'shift' => $schedule->shift->value ?? 'matutino',
                        'status' => AppointmentStatus::COMPLETED->value,
                        'visit_type' => $pickWeighted($typeWeights),
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt->copy()->addMinutes(30),
                    ]);
                }
            }
        });
    }
}