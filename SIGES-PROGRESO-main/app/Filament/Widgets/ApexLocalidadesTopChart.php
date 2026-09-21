<?php

namespace App\Filament\Widgets;

use App\Models\Patient;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ApexLocalidadesTopChart extends ApexChartWidget
{
    protected static ?string $chartId = 'apexLocalidadesTopChart';
    protected static ?string $heading = 'Localidades con más pacientes';
    protected static ?int $contentHeight = 300;

    public ?string $filter = 'top10';

    protected function getFilters(): ?array
    {
        return [
            'top5' => 'Top 5',
            'top10' => 'Top 10',
            'top15' => 'Top 15',
        ];
    }

    protected function getOptions(): array
    {
        $limit = match ($this->filter) {
            'top5' => 5,
            'top15' => 15,
            default => 10,
        };

        $rows = Patient::query()
            ->select('locality', DB::raw('COUNT(*) as total'))
            ->groupBy('locality')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        $labels = [];
        $data = [];
        foreach ($rows as $row) {
            $loc = $row->locality;
            if (is_string($loc)) {
                $label = $loc;
            } elseif (is_object($loc) && property_exists($loc, 'value')) {
                $label = $loc->value;
            } else {
                $label = (string) ($loc ?? 'Sin localidad');
            }
            $labels[] = $label ?: 'Sin localidad';
            $data[] = (int) $row->total;
        }

        return [
            'chart' => [
                'type' => 'bar',
                'height' => static::$contentHeight,
            ],
            'series' => [
                [
                    'name' => 'Pacientes',
                    'data' => $data,
                ],
            ],
            'xaxis' => [
                'categories' => $labels,
                'labels' => [
                    'rotate' => -30,
                    'trim' => true,
                ],
            ],
            'colors' => ['#f4b857'],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => true,
                    'borderRadius' => 4,
                ],
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'legend' => [
                'show' => false,
            ],
        ];
    }
}
