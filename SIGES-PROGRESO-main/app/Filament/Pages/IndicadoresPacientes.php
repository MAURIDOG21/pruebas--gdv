<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Exports\PatientsExport;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;

class IndicadoresPacientes extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';
    protected static string|\UnitEnum|null $navigationGroup = 'Reportes';
    protected static ?string $title = 'Indicadores — Pacientes';
    protected static ?string $navigationLabel = 'Indicadores (Pacientes)';
    protected static ?string $slug = 'reportes/indicadores-pacientes';

    public  static function shouldRegisterNavigation(): bool
    {
        $role = Auth::user()?->role?->value;
        return in_array($role, [UserRole::ADMIN->value, UserRole::DIRECTOR->value]);
    }

    public function mount(): void
    {
        $role = Auth::user()?->role?->value;
        if (! in_array($role, [UserRole::ADMIN->value, UserRole::DIRECTOR->value])) {
            abort(403);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Exportar')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->form([
                    DatePicker::make('from_date')
                        ->label('Desde')
                        ->default(Carbon::now()->startOfMonth())
                        ->required(),
                    DatePicker::make('to_date')
                        ->label('Hasta')
                        ->default(Carbon::now()->endOfMonth())
                        ->required(),
                    Select::make('date_field')
                        ->label('Fecha por')
                        ->options([
                            'date' => 'Atención (appointments.date)',
                            'created_at' => 'Registro (appointments.created_at)',
                        ])
                        ->default('date')
                        ->required(),
                    Select::make('format')
                        ->label('Formato')
                        ->options([
                            'xlsx' => 'Excel (.xlsx)',
                            'csv' => 'CSV (.csv)',
                        ])
                        ->default('xlsx')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $filename = 'indicadores-pacientes-' . Carbon::now()->format('Y-m-d-H-i-s');
                    
                    return Excel::download(
                        new PatientsExport(
                            $data['from_date'],
                            $data['to_date'],
                            $data['date_field']
                        ),
                        $filename . '.' . $data['format']
                    );
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\ApexTiposDePacienteChart::class,
            \App\Filament\Widgets\ApexPacientesNuevosVSRecurrentes::class,
        ];
    }
}
