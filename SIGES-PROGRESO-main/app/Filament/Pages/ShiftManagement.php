<?php

namespace App\Filament\Pages;

use App\Models\ClinicSchedule;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ShiftManagement extends Page
{
    protected static bool $shouldRegisterNavigation = false;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';
    protected string $view = 'filament.pages.shift-management';
    protected static ?string $title = 'Gestión de Turnos';
    protected static ?string $navigationLabel = 'Gestión de Turnos';
    protected static ?int $navigationSort = 1;

    // Esta página no utiliza un formulario de Page; las acciones definen sus propios formularios.

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Actualizar')
                ->icon('heroicon-m-arrow-path')
                ->action(fn () => $this->redirect(request()->header('Referer'))),
        ];
    }

    public function openShiftAction(): Action
    {
        return Action::make('openShift')
            ->label('Abrir Turno')
            ->icon('heroicon-m-play')
            ->color('success')
            ->form([
                Select::make('schedule_id')
                    ->label('Seleccionar Turno')
                    ->options(function () {
                        return ClinicSchedule::where('is_active', true)
                            ->where('is_shift_open', false)
                            ->orderByDesc('date')
                            ->with(['user', 'service'])
                            ->get()
                            ->mapWithKeys(function ($schedule) {
                                return [
                                    $schedule->id => sprintf(
                                        '%s - %s - %s (%s)',
                                        $schedule->clinic_name,
                                        $schedule->shift->value,
                                        $schedule->service->name,
                                        $schedule->user->name
                                    )
                                ];
                            });
                    })
                    ->required()
                    ->searchable(),
                Textarea::make('opening_notes')
                    ->label('Notas de Apertura')
                    ->placeholder('Observaciones al abrir el turno (opcional)')
                    ->rows(3),
            ])
            ->action(function (array $data): void {
                $schedule = ClinicSchedule::find($data['schedule_id']);
                
                if ($schedule && $schedule->openShift(Auth::user(), $data['opening_notes'] ?? null)) {
                    Notification::make()
                        ->title('Turno Abierto')
                        ->body("El turno {$schedule->shift->value} en {$schedule->clinic_name} ha sido abierto exitosamente.")
                        ->success()
                        ->send();

                    // Redirigir a la URL original si existe
                    $intendedUrl = session()->pull('intended_url');
                    if ($intendedUrl) {
                        $this->redirect($intendedUrl);
                    } else {
                        // Redirigir al dashboard del panel usando el path configurado
                        $this->redirect(url('/dashboard'));
                    }
                } else {
                    Notification::make()
                        ->title('Error')
                        ->body('No se pudo abrir el turno. Verifique que esté disponible.')
                        ->danger()
                        ->send();
                }
            });
    }

    public function closeShiftAction(): Action
    {
        return Action::make('closeShift')
            ->label('Cerrar Turno')
            ->icon('heroicon-m-stop')
            ->color('danger')
            ->form([
                Select::make('schedule_id')
                    ->label('Seleccionar Turno a Cerrar')
                    ->options(function () {
                        return ClinicSchedule::where('is_shift_open', true)
                            ->orderByDesc('date')
                            ->with(['user', 'service'])
                            ->get()
                            ->mapWithKeys(function ($schedule) {
                                return [
                                    $schedule->id => sprintf(
                                        '%s - %s - %s (%s)',
                                        $schedule->clinic_name,
                                        $schedule->shift->value,
                                        $schedule->service->name,
                                        $schedule->user->name
                                    )
                                ];
                            });
                    })
                    ->required()
                    ->searchable(),
                Textarea::make('closing_notes')
                    ->label('Notas de Cierre')
                    ->placeholder('Observaciones al cerrar el turno (opcional)')
                    ->rows(3),
            ])
            ->requiresConfirmation()
            ->modalHeading('Confirmar Cierre de Turno')
            ->modalDescription('¿Está seguro de que desea cerrar este turno? Esta acción no se puede deshacer.')
            ->action(function (array $data): void {
                $schedule = ClinicSchedule::find($data['schedule_id']);
                
                if ($schedule && $schedule->closeShift(Auth::user(), $data['closing_notes'] ?? null)) {
                    Notification::make()
                        ->title('Turno Cerrado')
                        ->body("El turno {$schedule->shift->value} en {$schedule->clinic_name} ha sido cerrado exitosamente.")
                        ->success()
                        ->send();

                    // Redirigir al dashboard del panel usando el path configurado
                    $this->redirect(url('/dashboard'));
                } else {
                    Notification::make()
                        ->title('Error')
                        ->body('No se pudo cerrar el turno.')
                        ->danger()
                        ->send();
                }
            });
    }

    public function getOpenShifts()
    {
        return ClinicSchedule::where('is_shift_open', true)
            ->orderByDesc('date')
            ->with(['user', 'service', 'openedBy'])
            ->get();
    }

    public function getAvailableShifts()
    {
        return ClinicSchedule::where('is_active', true)
            ->where('is_shift_open', false)
            ->orderByDesc('date')
            ->with(['user', 'service'])
            ->get();
    }
}