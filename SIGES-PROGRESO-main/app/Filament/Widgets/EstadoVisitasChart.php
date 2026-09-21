<?php

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EstadoVisitasChart extends ChartWidget
{
    protected ?string $heading = 'Estado Actual de las Visitas';
    
    public ?string $filter = 'today';
    
    protected function getFilters(): ?array
    {
        return [
            'today' => 'Hoy',
            'week' => 'Esta semana',
            'month' => 'Este mes',
        ];
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;
        
        // Definir el rango de fechas según el filtro
        $start = match($activeFilter) {
            'today' => Carbon::today(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            default => Carbon::today(),
        };
        
        $end = match($activeFilter) {
            'today' => Carbon::today()->endOfDay(),
            'week' => Carbon::now()->endOfWeek(),
            'month' => Carbon::now()->endOfMonth(),
            default => Carbon::today()->endOfDay(),
        };
        
        // Obtener conteo de citas por estado
        $appointmentsByStatus = DB::table('appointments')
            ->whereBetween('created_at', [$start, $end])
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->keyBy('status')
            ->map(fn ($item) => $item->total)
            ->toArray();
        
        // Asegurar que todos los estados estén representados
        $allStatuses = [
            AppointmentStatus::PENDING->value => 0,
            AppointmentStatus::IN_PROGRESS->value => 0,
            AppointmentStatus::COMPLETED->value => 0,
            AppointmentStatus::CANCELLED->value => 0,
        ];
        
        // Combinar con los datos reales
        $data = array_merge($allStatuses, $appointmentsByStatus);
        
        // Obtener etiquetas en español
        $labels = [
            AppointmentStatus::PENDING->value => 'Revisión',
            AppointmentStatus::IN_PROGRESS->value => 'En consulta',
            AppointmentStatus::COMPLETED->value => 'Completada',
            AppointmentStatus::CANCELLED->value => 'Cancelada',
        ];
        
        // Colores para cada estado
        $colors = [
            AppointmentStatus::PENDING->value => '#3b82f6', // Azul (icon)
            AppointmentStatus::IN_PROGRESS->value => '#f59e0b', // Naranja (warning)
            AppointmentStatus::COMPLETED->value => '#10b981', // Verde (success)
            AppointmentStatus::CANCELLED->value => '#ef4444', // Rojo (danger)
        ];
        
        // Preparar datos para el gráfico
        return [
            'datasets' => [
                [
                    'label' => 'Visitas',
                    'data' => array_values($data),
                    'backgroundColor' => array_values($colors),
                ],
            ],
            'labels' => array_values($labels),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
    
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
        ];
    }
}
