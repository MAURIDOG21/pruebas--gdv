<?php

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ApexServiciosMasSolicitadosChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'apexServiciosMasSolicitadosChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Servicios más solicitados';

    /**
     * Preferred content height
     */
    protected static ?int $contentHeight = 300;

    /**
     * Active filter key
     */
    public ?string $filter = 'month';

    /**
     * Filters shown in widget header
     */
    protected function getFilters(): ?array
    {
        return [
            'week' => 'Esta semana',
            'month' => 'Este mes',
            'all' => 'Todo',
        ];
    }

    /**
     * Optional dynamic heading reflecting current filter
     */
    protected function getHeading(): ?string
    {
        $periodName = match ($this->filter) {
            'week' => 'esta semana',
            'month' => 'este mes',
            'all' => 'todo el tiempo',
            default => 'periodo',
        };

        return 'Servicios más solicitados — ' . $periodName . '';
    }

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        // Determinar rango de fechas según filtro activo (usando appointments.date)
        $now = Carbon::now();
        $dateRange = null;
        if ($this->filter === 'week') {
            $dateRange = [$now->copy()->startOfWeek()->toDateString(), $now->copy()->endOfWeek()->toDateString()];
        } elseif ($this->filter === 'month') {
            $dateRange = [$now->copy()->startOfMonth()->toDateString(), $now->copy()->endOfMonth()->toDateString()];
        }

        // Consulta: servicios más solicitados
        // Contamos todas las citas que NO estén canceladas para reflejar demanda
        $query = Appointment::query()
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->select('services.name as service_name', DB::raw('COUNT(*) as total'))
            ->whereIn('appointments.status', [
                AppointmentStatus::PENDING,
                AppointmentStatus::IN_PROGRESS,
                AppointmentStatus::COMPLETED,
            ])
            ->groupBy('services.name')
            ->orderByDesc('total');

        if ($dateRange) {
            $query->whereBetween('appointments.date', $dateRange);
        }

        // Limitamos a top 8 para mejor legibilidad
        $rows = $query->limit(8)->get();

        // Paleta de colores consistente
        $palette = [
            '#36A2EB', // Azul
            '#FF6384', // Rosa
            '#FFCE56', // Amarillo
            '#4BC0C0', // Turquesa
            '#9966FF', // Morado
            '#FF9F40', // Naranja
            '#10B981', // Verde
            '#EF4444', // Rojo
        ];

        $labels = [];
        $series = [];
        $colors = [];

        foreach ($rows as $index => $row) {
            $labels[] = $row->service_name ?? 'Sin servicio';
            $series[] = (int) $row->total;
            $colors[] = $palette[$index % count($palette)];
        }

        return [
            'chart' => [
                'type' => 'donut',
                'height' => static::$contentHeight,
                'animations' => [
                    'enabled' => true,
                ],
            ],
            'series' => $series,
            'labels' => $labels,
            'colors' => $colors,
            'legend' => [
                'labels' => [
                    'fontFamily' => 'inherit',
                ],
            ],
            'tooltip' => [
                'enabled' => true,
            ],
            'dataLabels' => [
                'enabled' => true,
            ],
        ];
    }
}
