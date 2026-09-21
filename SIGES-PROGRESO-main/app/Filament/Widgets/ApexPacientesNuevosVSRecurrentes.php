<?php

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use App\Enums\VisitType;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ApexPacientesNuevosVSRecurrentes extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'apexPacientesNuevosVSRecurrentes';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Pacientes nuevos vs recurrentes';

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
            'all' => 'Año actual',
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
            'all' => 'año actual',
            default => 'periodo',
        };

        return 'Nuevos vs recurrentes — ' . $periodName . '';
    }

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $now = Carbon::now();
        $start = $now->copy()->startOfMonth();
        $end = $now->copy()->endOfMonth();
        $groupByMonth = false;

        if ($this->filter === 'week') {
            $start = $now->copy()->startOfWeek();
            $end = $now->copy()->endOfWeek();
        } elseif ($this->filter === 'all') {
            $start = $now->copy()->startOfYear();
            $end = $now->copy()->endOfYear();
            $groupByMonth = true;
        }
        $selectPeriod = $groupByMonth
            ? DB::raw("to_char(date, 'YYYY-MM') as period")
            : DB::raw("to_char(date, 'YYYY-MM-DD') as period");
        $rows = Appointment::query()
            ->select($selectPeriod)
            ->addSelect('visit_type')
            ->selectRaw('COUNT(*) as total')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where('status', '!=', AppointmentStatus::CANCELLED)
            ->groupBy('period', 'visit_type')
            ->orderBy('period')
            ->get();

        // Construir categorías (días o meses) completas en el rango
        $categories = [];
        $cursor = $start->copy();
        if ($groupByMonth) {
            while ($cursor->lte($end)) {
                $categories[] = $cursor->format('Y-m');
                $cursor->addMonth();
            }
        } else {
            while ($cursor->lte($end)) {
                $categories[] = $cursor->toDateString();
                $cursor->addDay();
            }
        }

        // Indexar resultados por periodo y tipo
        $indexed = collect($rows)->groupBy('period');

        // Series: Nuevos (Primera Vez) y Recurrentes (Subsecuente)
        $newData = [];
        $recurrentData = [];
        foreach ($categories as $period) {
            $group = $indexed->get($period, collect());
            $newCount = 0;
            $recCount = 0;
            foreach ($group as $row) {
                $type = is_string($row->visit_type) ? $row->visit_type : (string) $row->visit_type;
                if ($type === VisitType::PRIMERA_VEZ->value) {
                    $newCount += (int) $row->total;
                } elseif ($type === VisitType::SUBSECUENTE->value) {
                    $recCount += (int) $row->total;
                }
            }
            $newData[] = $newCount;
            $recurrentData[] = $recCount;
        }

        // Paleta de colores sugerida
        $colors = [
            '#10B981', // Nuevos
            '#687FE5', // Recurrentes
        ];

        return [
            'chart' => [
                'type' => 'bar',
                'height' => static::$contentHeight,
                'stacked' => true,
                'animations' => [
                    'enabled' => true,
                ],
            ],
            'series' => [
                [
                    'name' => 'Nuevos',
                    'data' => $newData,
                ],
                [
                    'name' => 'Recurrentes',
                    'data' => $recurrentData,
                ],
            ],
            'xaxis' => [
                'categories' => $categories,
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
                    'horizontal' => false,
                ],
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'legend' => [
                'show' => true,
            ],
            'tooltip' => [
                'enabled' => true,
            ],
        ];
    }
}
