<?php

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ApexEstadoVisitasChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'apexEstadoVisitasChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Estado de las visitas';

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
            'today' => 'Hoy',
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
            'today' => 'hoy',
            'week' => 'esta semana',
            'month' => 'este mes',
            'all' => 'todo el tiempo',
            default => 'periodo',
        };

        return 'Estado de las visitas — ' . $periodName . '';
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
        switch ($this->filter) {
            case 'today':
                $dateRange = [$now->copy()->toDateString(), $now->copy()->toDateString()];
                break;
            case 'week':
                $dateRange = [$now->copy()->startOfWeek()->toDateString(), $now->copy()->endOfWeek()->toDateString()];
                break;
            case 'month':
                $dateRange = [$now->copy()->startOfMonth()->toDateString(), $now->copy()->endOfMonth()->toDateString()];
                break;
            case 'all':
            default:
                $dateRange = null;
        }

        // Estados a mostrar y etiquetas
        $states = [
            AppointmentStatus::PENDING,
            AppointmentStatus::IN_PROGRESS,
            AppointmentStatus::COMPLETED,
            AppointmentStatus::CANCELLED,
        ];

        $labels = [
            'Pendiente',
            'En proceso',
            'Completada',
            'Cancelada',
        ];

        // Consulta: conteo por estado
        $query = Appointment::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status');

        if ($dateRange) {
            $query->whereBetween('date', $dateRange);
        }

        $rows = $query->get();

        // Mapear resultados por estado (manejar Enum correctamente)
        $totalsByStatus = collect($rows)->mapWithKeys(function ($row) {
            if ($row->status instanceof AppointmentStatus) {
                $key = $row->status->value; // usar valor respaldado del enum
            } else {
                // cuando Eloquent no castea (por agregaciones), puede venir como string/int
                $key = is_string($row->status) || is_int($row->status)
                    ? (string) $row->status
                    : (string) $row->status;
            }
            return [$key => (int) $row->total];
        });

        // Series en el orden definido
        $seriesData = [];
        foreach ($states as $state) {
            $seriesData[] = (int) ($totalsByStatus[$state->value] ?? 0);
        }

        // Paleta por estado
        $colors = [
            '#F59E0B', // Pendiente - amber
            '#36A2EB', // En proceso - blue
            '#10B981', // Completada - green
            '#EF4444', // Cancelada - red
        ];

        return [
            'chart' => [
                'type' => 'bar',
                'height' => static::$contentHeight,
            ],
            'series' => [
                [
                    'name' => 'Visitas',
                    'data' => $seriesData,
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
                    'borderRadius' => 3,
                    'horizontal' => true,
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
