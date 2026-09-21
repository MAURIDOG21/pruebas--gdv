<?php

namespace App\Filament\Resources\ClinicSchedules\Pages;

use App\Filament\Resources\ClinicSchedules\ClinicScheduleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use App\Models\ClinicSchedule;
use Illuminate\Support\Carbon;

class EditClinicSchedule extends EditRecord
{
    protected static string $resource = ClinicScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $clinic = $data['clinic_name'] ?? $this->record->clinic_name;
        $shift = $data['shift'] ?? $this->record->shift->value ?? $this->record->shift;
        $date = $data['date'] ?? ($this->record->date instanceof \Carbon\Carbon ? $this->record->date->toDateString() : (string) $this->record->date);

        $exists = ClinicSchedule::query()
            ->where('clinic_name', $clinic)
            ->where('shift', $shift)
            ->whereDate('date', Carbon::parse($date)->toDateString())
            ->where('id', '!=', $this->record->id)
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
