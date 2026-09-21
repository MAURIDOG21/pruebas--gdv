<?php

namespace App\Filament\Widgets;

use App\Models\Patient;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ApexDistribucionSexoChart extends ApexChartWidget
{
    protected static ?string $chartId = 'apexDistribucionSexoChart';
    protected static ?string $heading = 'Distribución por sexo';
    protected static ?int $contentHeight = 300;

    public ?string $filter = 'all';

    protected function getFilters(): ?array
    {
        return [
            'all' => 'Todos',
            'month' => 'Este mes',
            'year' => 'Este año',
        ];
    }

    protected function getOptions(): array
    {
        $query = Patient::query();
        if ($this->filter === 'month') {
            $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        } elseif ($this->filter === 'year') {
            $query->whereYear('created_at', now()->year);
        }

        $rows = $query
            ->select('sex')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('sex')
            ->get()
            ->mapWithKeys(function ($row) {
                $key = is_string($row->sex) ? strtoupper($row->sex) : (string) $row->sex;
                return [$key => (int) $row->total];
            });

        $labels = ['Femenino', 'Masculino'];
        $data = [
            (int) ($rows->get('F', 0) + $rows->get('FEMENINO', 0)),
            (int) ($rows->get('M', 0) + $rows->get('MASCULINO', 0)),
        ];

        return [
            'chart' => [
                'type' => 'donut',
                'height' => static::$contentHeight,
            ],
            'series' => $data,
            'labels' => $labels,
            'colors' => ['#F7CFD8', '#687FE5'],
            'legend' => [
                'position' => 'bottom',
            ],
            'dataLabels' => [
                'enabled' => true,
            ],
        ];
    }
}

