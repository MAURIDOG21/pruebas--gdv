<?php

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\ClinicSchedule;
use App\Models\Patient;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Patients\PatientResource;
use App\Filament\Resources\ClinicSchedules\ClinicScheduleResource;
class RecepcionStats extends BaseWidget
{
    protected ?string $heading = 'Indicadores de hoy';
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // --- 1. CONSULTAS DE KPIs (Estas estaban bien) ---
        $todayStats = Appointment::whereDate('created_at', today())
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $pendientes = $todayStats->get(AppointmentStatus::PENDING->value, 0);
        $totalHoy = $todayStats->sum();

        $pacientesNuevos = Patient::where('status', 'pending_review')->count();
        $turnosAbiertos = ClinicSchedule::where('is_shift_open', true)->count();

        // --- 2. CONSULTA PARA LA MINI-GRÁFICA (AQUÍ ESTÁ LA CORRECCIÓN) ---
        $visitasSemanales = Appointment::query()
            ->where('created_at', '>=', now()->subDays(6))
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
            ])
            // --- ¡LA CORRECCIÓN ESTÁ AQUÍ! ---
            // Le decimos a Eloquent que agrupe por la *función* DATE(created_at), no por el alias 'date'.
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy(DB::raw('DATE(created_at)'), 'ASC') // Hacemos lo mismo para el orderBy
            ->get()
            ->pluck('count', 'date'); // Esto sigue funcionando bien

        $sparklineData = collect(range(0, 6))->map(fn ($day) => 
            $visitasSemanales->get(now()->subDays(6 - $day)->format('Y-m-d'), 0)
        )->toArray();

        $appointmentsWithPatients = Appointment::query()
            ->whereDate('created_at', today())
            ->with('medicalRecord.patient')
            ->get();

        $ninos = $appointmentsWithPatients->filter(function ($a) {
            $p = $a->medicalRecord->patient ?? null;
            if (! $p || ! $p->date_of_birth) return false;
            return Carbon::parse($p->date_of_birth)->age < 18;
        })->count();

        $mujeres = $appointmentsWithPatients->filter(function ($a) {
            $p = $a->medicalRecord->patient ?? null;
            return $p && $p->sex === 'F';
        })->count();

        $hombres = $appointmentsWithPatients->filter(function ($a) {
            $p = $a->medicalRecord->patient ?? null;
            return $p && $p->sex === 'M';
        })->count();


        // --- 3. CONSTRUCCIÓN DE LAS TARJETAS (Sin cambios) ---
        return [
            Stat::make('Expedientes Pendientes', $pacientesNuevos)
                ->description('Pacientes que requieren completar datos')
                ->descriptionIcon('heroicon-m-identification')
                ->color($pacientesNuevos > 0 ? 'warning' : 'success')
                ->url(PatientResource::getUrl('index', ['tab' => 'pending_review'])),

            Stat::make('Visitas en Recepción', $pendientes)
                ->description('Pacientes en espera de asignación')
                ->descriptionIcon('heroicon-m-users')
                ->color($pendientes > 0 ? 'danger' : 'success')
                ->url(AppointmentResource::getUrl('index', ['tableTabs' => ['status' => AppointmentStatus::PENDING->value]])),
                
            Stat::make('Consultorios Abiertos', $turnosAbiertos)
                ->description('Consultorios abiertos actualmente')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('info')
                ->url(ClinicScheduleResource::getUrl('index', ['tab' => 'open'])),

            Stat::make('Total de Visitas Hoy', $totalHoy)
                ->description('Tendencia de los últimos 7 días')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($sparklineData)
                ->color('success'),

            Stat::make('Niños', $ninos)
                ->description('Menores de 18 años')
                ->descriptionIcon('heroicon-m-user')
                ->color('info'),

            Stat::make('Mujeres', $mujeres)
                ->description('Visitas de hoy')
                ->descriptionIcon('heroicon-m-user')
                ->color('primary'),

            Stat::make('Hombres', $hombres)
                ->description('Visitas de hoy')
                ->descriptionIcon('heroicon-m-user')
                ->color('gray'),
        ];
    }

    protected ?string $pollingInterval = '10s';
}
