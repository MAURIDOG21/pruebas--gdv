<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Support\Exceptions\Halt;
use App\Models\ClinicSchedule;
use Illuminate\Support\Carbon;
use BackedEnum;

class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
           // ViewAction::make(),
            //DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')->action(fn () => $this->save())
                ->label('Guardar cambios')
                ->requiresConfirmation()
                ->modalHeading('Confirmar edición')
                ->modalSubheading('Se guardarán los cambios de la visita.')
                ->modalSubmitActionLabel('Sí, guardar')
                ->modalCancelActionLabel('Cancelar'),
            $this->getCancelFormAction(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        // Tras guardar, redirige al listado principal de visitas
        return AppointmentResource::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        // Notificación de confirmación al editar
        return Notification::make()
            ->title('Visita actualizada correctamente')
            ->body('Los cambios de la visita se han guardado exitosamente.')
            ->success();
    }
            public function getTitle(): string
    {
        // La variable $this->record contiene la visita que se está viendo.
        // Construimos un título más descriptivo.
        return "Editar detalles de la Visita #{$this->getRecord()->ticket_number}";
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Solo aplicamos restricción fuerte si la visita es para hoy.
        $scheduleId = $data['clinic_schedule_id'] ?? null;
        $date = $data['date'] ?? null;
        $shift = $data['shift'] ?? null;

        $schedule = $scheduleId ? ClinicSchedule::find($scheduleId) : null;
        // Normalizar turno: si viene como Enum, usar su valor; si no viene, usar el del consultorio
        if ($shift instanceof BackedEnum) {
            $shift = $shift->value;
        }
        if ($shift === null && $schedule) {
            $shift = $schedule->shift->value;
        }

        $isToday = $date ? Carbon::parse($date)->isToday() : false;
        $dateMatches = $schedule && $schedule->date && Carbon::parse($schedule->date)->toDateString() === Carbon::parse($date)->toDateString();
        $shiftMatches = $schedule && $schedule->shift->value === $shift;

        if ($isToday && (!$schedule || !$schedule->is_active || !$schedule->is_shift_open || !$dateMatches || !$shiftMatches)) {
            Notification::make()
                ->title('Turno no disponible')
                ->body('No se pueden guardar cambios que asignen la visita a un turno cerrado o diferente al seleccionado.')
                ->danger()
                ->send();

            throw new Halt();
        }
        // Sincronizar asignaciones derivadas del consultorio
        if ($schedule) {
            $data['service_id'] = $schedule->service_id;
            $data['doctor_id'] = $schedule->user_id;
            $data['shift'] = $shift ?? $schedule->shift->value;
            $data['date'] = $date ?? (optional($schedule->date)->toDateString() ?? $schedule->date);
        }

        return $data;
    }
}
