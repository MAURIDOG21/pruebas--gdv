<?php

namespace App\Filament\Resources\ClinicSchedules\Pages;

use App\Filament\Resources\ClinicSchedules\ClinicScheduleResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use App\Models\ClinicSchedule;
use Illuminate\Support\Carbon;

class CreateClinicSchedule extends CreateRecord
{
    protected static string $resource = ClinicScheduleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $clinic = $data['clinic_name'] ?? null;
        $shift = $data['shift'] ?? null;
        $date = $data['date'] ?? now()->toDateString();

        $exists = ClinicSchedule::query()
            ->where('clinic_name', $clinic)
            ->where('shift', $shift)
            ->whereDate('date', Carbon::parse($date)->toDateString())
            ->exists();

        if ($exists) {
            Notification::make()
                ->title('Asignación duplicada')
                ->body('Ya existe una asignación para ese consultorio, turno y fecha')
                ->danger()
                ->send();
            throw new Halt();
        }

        return $data;
    }
}
