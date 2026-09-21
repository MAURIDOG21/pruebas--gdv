<?php

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ApexVisitasSemanalesChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'apexVisitasSemanalesChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Visitas semanales';

    /**
     * Preferred content height
     */
    protected static ?int $contentHeight = 300;

    /**
     * Active filter key
     */
    public ?string $filter = 'this_week';

    /**
     * Filters shown in widget header
     */
    protected function getFilters(): ?array
    {
        return [
            'this_week' => 'Esta semana',
            'last_week' => 'Semana pasada',
        ];
    }

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        // Calcular el rango de la semana objetivo
        $now = Carbon::now();
        if ($this->filter === 'last_week') {
            $start = $now->copy()->subWeek()->startOfWeek();
            $end = $now->copy()->subWeek()->endOfWeek();
        } else {
            $start = $now->copy()->startOfWeek();
            $end = $now->copy()->endOfWeek();
        }

        // Obtener conteo de visitas COMPLETADAS por día de la semana (usando appointments.date)
        // Agrupamos por fecha y luego ordenamos por fecha ascendente
        $rows = Appointment::query()
            ->select('date', DB::raw('COUNT(*) as total'))
            ->where('status', AppointmentStatus::COMPLETED)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Mapear la semana a etiquetas [Lun..Dom] y rellenar días sin datos con 0
        $days = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
        $labels = [];
        $data = [];

        $cursor = $start->copy();
        $totalsByDate = collect($rows)->keyBy(fn($r) => Carbon::parse($r->date)->toDateString());

        for ($i = 0; $i < 7; $i++) {
            $labels[] = $days[$i];
            $key = $cursor->toDateString();
            $data[] = (int) ($totalsByDate[$key]->total ?? 0);
            $cursor->addDay();
        }

        return [
            'chart' => [
                'type' => 'area',
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
            'colors' => ['#f59e0b'],
            'stroke' => [
                'curve' => 'smooth',
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'tooltip' => [
                'enabled' => true,
            ],
        ];
    }
}
