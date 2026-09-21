<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use App\Support\ColoniaCatalog;
use App\Enums\ChronicDisease;
use Maatwebsite\Excel\Facades\Excel;
use App\Filament\Exports\PatientExporter;
use App\Filament\Exports\AppointmentExporter;

class DemografiaReportes extends Page
{
    protected static ?string $title = 'Reportes — Demografía';
    protected static ?string $navigationLabel = 'Demografía';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    protected static string|\UnitEnum|null $navigationGroup = 'Reportes';
    protected static ?string $slug = 'reportes/demografia';

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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filtros')
                ->label('Filtros')
                ->icon('heroicon-o-funnel')
                ->color('primary')
                ->form([
                    Select::make('sex')
                        ->label('Sexo')
                        ->options([
                            'F' => 'Femenino',
                            'M' => 'Masculino',
                        ])
                        ->native(false),
                    Select::make('locality')
                        ->label('Localidad')
                        ->options(fn () => class_exists(\App\Enums\Locality::class) ? \App\Enums\Locality::getOptions() : [])
                        ->live()
                        ->native(false),
                    Select::make('colonia')
                        ->label('Colonia')
                        ->options(fn ($get) => ColoniaCatalog::getColonias($get('locality')))
                        ->native(false),
                    CheckboxList::make('diseases')
                        ->label('Comorbilidades')
                        ->options(function () {
                            $opts = [];
                            foreach (ChronicDisease::cases() as $case) {
                                $opts[$case->value] = $case->getLabel();
                            }
                            return $opts;
                        })
                        ->columns(2),
                ])
                ->action(function (array $data): void {
                    session()->put('demofilters', [
                        'sex' => $data['sex'] ?? null,
                        'locality' => $data['locality'] ?? null,
                        'colonia' => $data['colonia'] ?? null,
                        'diseases' => array_values($data['diseases'] ?? []),
                    ]);
                    $this->redirect(static::getUrl());
                }),
            Action::make('limpiar_filtros')
                ->label('Limpiar filtros')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->action(function (): void {
                    session()->forget('demofilters');
                    $this->redirect(static::getUrl());
                }),
            ActionGroup::make([
                Action::make('export_patients_filtered')
                    ->label('Pacientes Filtrados')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->form([
                        DatePicker::make('from_date')
                            ->label('Fecha de Registro (Desde)')
                            ->native(false),
                        DatePicker::make('to_date')
                            ->label('Fecha de Registro (Hasta)')
                            ->native(false),
                    ])
                    ->action(function (array $data) {
                        $filters = session('demofilters', []);
                        $query = \App\Models\Patient::query();
                        if (! empty($filters['sex'])) {
                            $query->where('sex', $filters['sex']);
                        }
                        if (! empty($filters['locality'])) {
                            $query->where('locality', $filters['locality']);
                        }
                        if (! empty($filters['colonia'])) {
                            $query->where('colonia', $filters['colonia']);
                        }
                        foreach (($filters['diseases'] ?? []) as $d) {
                            $query->whereJsonContains('chronic_diseases', $d);
                        }
                        if (! empty($data['from_date']) && ! empty($data['to_date'])) {
                            $query->whereBetween('created_at', [$data['from_date'], $data['to_date']]);
                        }
                        $filename = 'pacientes-filtrados-' . now()->format('Y-m-d-H-i-s') . '.xlsx';
                        return Excel::download(new PatientExporter(), $filename);
                    }),
                Action::make('export_appointments')
                    ->label('Visitas (Citas)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->form([
                        DatePicker::make('from_date')
                            ->label('Rango de Fechas (Desde)')
                            ->native(false),
                        DatePicker::make('to_date')
                            ->label('Rango de Fechas (Hasta)')
                            ->native(false),
                    ])
                    ->action(function (array $data) {
                        $filters = session('demofilters', []);
                        $from = $data['from_date'] ?? null;
                        $to = $data['to_date'] ?? null;
                        $filename = 'visitas-' . now()->format('Y-m-d-H-i-s') . '.xlsx';
                        return Excel::download(new AppointmentExporter($from, $to, $filters), $filename);
                    }),
            ])->label('Descargar Reportes')->icon('heroicon-o-document-arrow-down'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\ApexDistribucionSexoChart::class,
            \App\Filament\Widgets\ApexDistribucionEdadesChart::class,
            \App\Filament\Widgets\ApexLocalidadesTopChart::class,
            \App\Filament\Widgets\ApexColoniasTopChart::class,
            \App\Filament\Widgets\ApexComorbilidadesChart::class,
            \App\Filament\Widgets\ApexHeatmapVisitasChart::class,
        ];
    }
}
