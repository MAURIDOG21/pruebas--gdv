<?php

namespace App\Filament\Resources\ClinicSchedules\Pages;

use App\Enums\Shift;
use App\Enums\UserRole;
use App\Filament\Resources\ClinicSchedules\ClinicScheduleResource;
use App\Models\ClinicSchedule;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class DaySchedule extends ListRecords
{
    protected static string $resource = ClinicScheduleResource::class;

    protected static ?string $title = 'Horario del Día';

    public function mount(): void
    {
        // Mostrar notificaciones si el middleware las dejó en sesión
        if (session()->has('shift_required')) {
            Notification::make()
                ->title('Turno requerido')
                ->body(session('shift_required'))
                ->warning()
                ->send();
        }
        if (session()->has('no_shifts_available')) {
            Notification::make()
                ->title('Sin turnos disponibles')
                ->body(session('no_shifts_available'))
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('crear_asignacion')
                ->label('Crear Asignación')
                ->icon('heroicon-o-plus')
                ->form([
                    TextInput::make('clinic_name')
                        ->label('Consultorio')
                        ->required(),
                    Select::make('user_id')
                        ->label('Médico')
                        ->relationship('user', 'name', modifyQueryUsing: function (Builder $query) {
                            $query->whereIn('role', [
                                UserRole::MEDICO_GENERAL->value,
                                UserRole::NUTRICIONISTA->value,
                                UserRole::PSICOLOGO->value,
                                UserRole::ENFERMERO->value,
                            ]);
                        })
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->required(),
                    Select::make('service_id')
                        ->label('Servicio')
                        ->relationship('service', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->required(),
                    Radio::make('shift')
                        ->label('Turno')
                        ->options(Shift::getOptions())
                        ->inline()
                        ->required(),
                    DatePicker::make('date')
                        ->label('Fecha')
                        ->default(now())
                        ->required(),
                    Toggle::make('is_active')
                        ->label('Activo')
                        ->default(false),
                ])
                ->action(function (array $data) {
                    try {
                        ClinicSchedule::create($data);
                        Notification::make()
                            ->title('Asignación creada')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Asignación duplicada: mismo consultorio, turno y fecha')
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('cerrar_jornada')
                ->label('Cerrar Jornada')
                ->icon('heroicon-o-lock-closed')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    $count = ClinicSchedule::query()
                        ->whereDate('date', today())
                        ->where('is_active', true)
                        ->update(['is_active' => false]);

                    Notification::make()
                        ->title('Jornada cerrada')
                        ->body("Se desactivaron $count asignaciones del día.")
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return ClinicSchedule::query()
            ->with(['user', 'service'])
            ->whereDate('date', today());
    }
}