<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use App\Models\ClinicSchedule;
use Illuminate\Support\Carbon;
use BackedEnum;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $scheduleId = $data['clinic_schedule_id'] ?? null;
        $date = $data['date'] ?? null;
        $shift = $data['shift'] ?? null;

        $schedule = $scheduleId ? ClinicSchedule::find($scheduleId) : null;

        // Normalizar turno si viene como Enum
        if ($shift instanceof BackedEnum) {
            $shift = $shift->value;
        }
        $dateMatches = $schedule && $schedule->date && Carbon::parse($schedule->date)->toDateString() === Carbon::parse($date)->toDateString();
        $shiftMatches = $schedule && $schedule->shift->value === $shift;

        if (!$schedule || !$schedule->is_active || !$schedule->is_shift_open || !$dateMatches || !$shiftMatches) {
            Notification::make()
                ->title('Turno no disponible')
                ->body('Para registrar una visita, debe existir un turno abierto que coincida con la fecha y turno seleccionados.')
                ->danger()
                ->send();

            throw new Halt();
        }

        // Sincronizar asignaciones derivadas del consultorio
        $data['service_id'] = $schedule->service_id;
        $data['doctor_id'] = $schedule->user_id;
        $data['shift'] = $shift ?? $schedule->shift->value;
        $data['date'] = $date ?? (optional($schedule->date)->toDateString() ?? $schedule->date);

        return $data;
    }

        protected function getRedirectUrl(): string
    {
        // Tras guardar, redirige al listado principal de visitas
        return AppointmentResource::getUrl('index');
    }

}
