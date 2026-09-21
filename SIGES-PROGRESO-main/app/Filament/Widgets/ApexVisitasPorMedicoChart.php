<?php

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ApexVisitasPorMedicoChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string|null
     */
    protected static ?string $chartId = 'apexVisitasPorMedicoChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Visitas por médico';

    /**
     * Preferred content height
     */
    protected static ?int $contentHeight = 300;

    /**
     * Active filter key
     */
    public ?string $filter = 'week';

    /**
     * Filters shown in widget header
     */
    protected function getFilters(): ?array
    {
        return [
            'today' => 'Hoy',
            'week' => 'Esta semana',
            'month' => 'Este mes',
        ];
    }

    /**
     * Optional dynamic heading reflecting current filter
     */
    protected function getHeading(): ?string
    {
        $periodName = match ($this->filter) {
            'today' => 'hoy',
            'week' => 'esta semana',
            'month' => 'este mes',
            default => 'periodo',
        };

        return 'Visitas atendidas por médico — ' . $periodName . '';
    }

    /**
     * Apex Charts options
     * @return array
     */
    protected function getOptions(): array
    {
        // Determinar rango de fechas según filtro activo
        [$start, $end] = match ($this->filter) {
            'today' => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
            'week' => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            default => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
        };

        // Consulta: visitas completadas por médico en el periodo
        $rows = Appointment::query()
            ->join('users', 'appointments.doctor_id', '=', 'users.id')
            ->select('users.name as doctor_name', DB::raw('COUNT(*) as total'))
            ->where('appointments.status', AppointmentStatus::COMPLETED)
            ->whereBetween('appointments.created_at', [$start, $end])
            ->groupBy('users.name')
            ->orderByDesc('total')
            ->get();

        // Paleta de colores
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
        $data = [];
        $colors = [];

        foreach ($rows as $index => $row) {
            $labels[] = $row->doctor_name ?? 'Sin asignar';
            $data[] = (int) $row->total;
            $colors[] = $palette[$index % count($palette)];
        }

        return [
            'chart' => [
                'type' => 'bar',
                'height' => static::$contentHeight,
                'animations' => [
                    'enabled' => true,
                ],
            ],
            'series' => [
                [
                    'name' => 'Visitas completadas',
                    'data' => $data,
                ],
            ],
            'xaxis' => [
                'categories' => $labels,
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                        'fontWeight' => 600,
                    ],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'colors' => $colors,
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 4,
                ],
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'legend' => [
                'show' => false,
            ],
            'tooltip' => [
                'enabled' => true,
            ],
        ];
    }
}