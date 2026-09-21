<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\VisitsExport;
use Illuminate\Support\Facades\Auth;

class IndicadoresVisitas extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    protected static string|\UnitEnum|null $navigationGroup = 'Reportes';
    protected static ?string $title = 'Indicadores — Visitas';
    protected static ?string $navigationLabel = 'Indicadores (Visitas)';
    protected static ?string $slug = 'reportes/indicadores-visitas';

    public static function shouldRegisterNavigation(): bool
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

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\ApexEstadoVisitasChart::class,
            \App\Filament\Widgets\ApexVisitasSemanalesChart::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportar')
                ->label('Exportar')
                ->icon('heroicon-o-arrow-down-tray')
                ->form([
                    DatePicker::make('from_date')
                        ->label('Desde')
                        ->native(false),
                    DatePicker::make('to_date')
                        ->label('Hasta')
                        ->native(false),
                    Select::make('date_field')
                        ->label('Fecha por')
                        ->options([
                            'date' => 'Atención (appointments.date)',
                            'created_at' => 'Registro (appointments.created_at)',
                        ])
                        ->default('date'),
                    Select::make('origin')
                        ->label('Origen')
                        ->options([
                            'todos' => 'Todos',
                            'local' => 'Local (recepción)',
                            'programa' => 'Turnos (programa)',
                        ])
                        ->default('todos'),
                    Select::make('format')
                        ->label('Formato')
                        ->options([
                            'xlsx' => 'Excel (.xlsx)',
                            'csv' => 'CSV (.csv)',
                        ])
                        ->default('xlsx'),
                ])
                ->action(function (array $data) {
                    $from = $data['from_date'] ?? null;
                    $to = $data['to_date'] ?? null;
                    $dateField = $data['date_field'] ?? 'date';
                    $origin = $data['origin'] ?? 'todos';
                    $format = $data['format'] ?? 'xlsx';

                    $filename = 'visitas_' . now()->format('Ymd_His') . '.' . $format;

                    return Excel::download(
                        new VisitsExport($from, $to, $origin, $dateField),
                        $filename,
                        $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX,
                    );
                }),
        ];
    }
}
