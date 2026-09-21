<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ApexDistribucionEdadesChart extends ApexChartWidget
{
    protected static ?string $chartId = 'apexDistribucionEdadesChart';
    protected static ?string $heading = 'Distribución por edades';
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
        $where = '';
        if ($this->filter === 'month') {
            $where = "WHERE DATE_PART('year', CURRENT_DATE) = DATE_PART('year', patients.created_at) AND DATE_PART('month', CURRENT_DATE) = DATE_PART('month', patients.created_at)";
        } elseif ($this->filter === 'year') {
            $where = "WHERE DATE_PART('year', CURRENT_DATE) = DATE_PART('year', patients.created_at)";
        }

        $sql = "
            SELECT
                SUM(CASE WHEN DATE_PART('year', age(patients.date_of_birth)) BETWEEN 0 AND 5 THEN 1 ELSE 0 END) AS r_0_5,
                SUM(CASE WHEN DATE_PART('year', age(patients.date_of_birth)) BETWEEN 6 AND 12 THEN 1 ELSE 0 END) AS r_6_12,
                SUM(CASE WHEN DATE_PART('year', age(patients.date_of_birth)) BETWEEN 13 AND 17 THEN 1 ELSE 0 END) AS r_13_17,
                SUM(CASE WHEN DATE_PART('year', age(patients.date_of_birth)) BETWEEN 18 AND 39 THEN 1 ELSE 0 END) AS r_18_39,
                SUM(CASE WHEN DATE_PART('year', age(patients.date_of_birth)) BETWEEN 40 AND 59 THEN 1 ELSE 0 END) AS r_40_59,
                SUM(CASE WHEN DATE_PART('year', age(patients.date_of_birth)) >= 60 THEN 1 ELSE 0 END) AS r_60_plus
            FROM patients
            {$where};
        ";
        $row = collect(DB::select($sql))->first();

        $labels = ['0–5', '6–12', '13–17', '18–39', '40–59', '60+'];
        $data = [
            (int) ($row->r_0_5 ?? 0),
            (int) ($row->r_6_12 ?? 0),
            (int) ($row->r_13_17 ?? 0),
            (int) ($row->r_18_39 ?? 0),
            (int) ($row->r_40_59 ?? 0),
            (int) ($row->r_60_plus ?? 0),
        ];

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
            ],
            'colors' => ['#92d4ee'],
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
        ];
    }
}

