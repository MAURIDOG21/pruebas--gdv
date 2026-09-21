<?php

namespace App\Filament\Resources\ClinicSchedules\Pages;

use App\Filament\Resources\ClinicSchedules\ClinicScheduleResource;
use App\Filament\Resources\ClinicSchedules\Pages\DaySchedule;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use App\Enums\UserRole;
use Filament\Forms\Components\Radio;
use App\Enums\Shift;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use App\Models\ClinicSchedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Schemas\Components\Tabs\Tab;

class ListClinicSchedules extends ListRecords
{
    protected static string $resource = ClinicScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //CreateAction::make(),
            Action::make('Horario del Día')
                ->icon('heroicon-o-clock')
                ->url(DaySchedule::getUrl())
                ->color('primary'),
            Action::make('cerrar_turnos_abiertos')
                ->label('Cerrar Turnos Abiertos (Todos)')
                ->icon('heroicon-o-lock-closed')
                ->color('danger')
                ->form([
                    Textarea::make('closing_notes')
                        ->label('Notas de cierre (opcional)')
                        ->rows(3)
                        ->placeholder('Se aplicarán a todos los turnos cerrados.'),
                ])
                ->requiresConfirmation()
                ->action(function (array $data) {
                    $notes = $data['closing_notes'] ?? null;
                    $openShifts = ClinicSchedule::query()
                        ->where('is_shift_open', true)
                        ->get();

                    $closed = 0;
                    $errors = 0;
                    foreach ($openShifts as $schedule) {
                        try {
                            if ($schedule->closeShift(Auth::user(), $notes)) {
                                $closed++;
                            } else {
                                $errors++;
                            }
                        } catch (\Throwable $e) {
                            $errors++;
                        }
                    }

                    Notification::make()
                        ->title('Cierre de turnos')
                        ->body("Se cerraron {$closed} turno(s) abierto(s)." . ($errors > 0 ? " Errores: {$errors}." : ''))
                        ->success()
                        ->send();
                }),
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
                /*
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
                */
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('Todas las Asignaciones')
                ->icon('heroicon-o-rectangle-stack')
                ->badge(ClinicScheduleResource::getModel()::query()->count())
                ->badgeColor('gray'),

            'open' => Tab::make('Abiertos')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_shift_open', true))
                ->badge(ClinicScheduleResource::getModel()::query()->where('is_shift_open', true)->count())
                ->badgeColor('success'),

            'closed' => Tab::make('Cerrados')
                ->icon('heroicon-o-lock-closed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_shift_open', false))
                ->badge(ClinicScheduleResource::getModel()::query()->where('is_shift_open', false)->count())
                ->badgeColor('gray'),

            'active' => Tab::make('Activos')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true))
                ->badge(ClinicScheduleResource::getModel()::query()->where('is_active', true)->count())
                ->badgeColor('primary'),

            'inactive' => Tab::make('No Activos')
                ->icon('heroicon-o-no-symbol')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false))
                ->badge(ClinicScheduleResource::getModel()::query()->where('is_active', false)->count())
                ->badgeColor('danger'),
        ];

        return $tabs;
    }
}
