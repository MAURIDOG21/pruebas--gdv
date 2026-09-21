<?php

namespace App\Filament\Widgets;

use App\Models\Patient;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ApexColoniasTopChart extends ApexChartWidget
{
    protected static ?string $chartId = 'apexColoniasTopChart';
    protected static ?string $heading = 'Colonias con más pacientes';
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

        $filters = session('demofilters', []);
        $query = Patient::query()
            ->select('colonia', DB::raw('COUNT(*) as total'));

        if (! empty($filters['sex'])) {
            $query->where('sex', $filters['sex']);
        }
        if (! empty($filters['locality'])) {
            $query->where('locality', $filters['locality']);
        }
        if (! empty($filters['colonia'])) {
            $query->where('colonia', $filters['colonia']);
        }
        foreach (($filters['diseases'] ?? []) as $d) {
            $query->whereJsonContains('chronic_diseases', $d);
        }

        $rows = $query
            ->groupBy('colonia')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        $labels = [];
        $data = [];
        foreach ($rows as $row) {
            $labels[] = $row->colonia ?? '—';
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
            'colors' => ['#9bd081'],
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

