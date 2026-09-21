<?php

namespace App\Filament\Widgets;

use App\Models\Patient;
use App\Enums\ChronicDisease;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ApexComorbilidadesChart extends ApexChartWidget
{
    protected static ?string $chartId = 'apexComorbilidadesChart';
    protected static ?string $heading = 'Comorbilidades en pacientes';
    protected static ?int $contentHeight = 300;

    public ?string $filter = 'top10';

    protected function getFilters(): ?array
    {
        return [
            'top5' => 'Top 5',
            'top10' => 'Top 10',
            'all' => 'Todas',
        ];
    }

    protected function getOptions(): array
    {
        $filters = session('demofilters', []);
        $query = Patient::query()->select(['chronic_diseases']);

        if (! empty($filters['sex'])) {
            $query->where('sex', $filters['sex']);
        }
        if (! empty($filters['locality'])) {
            $query->where('locality', $filters['locality']);
        }
        if (! empty($filters['colonia'])) {
            $query->where('colonia', $filters['colonia']);
        }

        $patients = $query->get();
        $counts = [];
        foreach (ChronicDisease::cases() as $case) {
            $counts[$case->value] = 0;
        }

        foreach ($patients as $p) {
            $arr = (array) ($p->chronic_diseases ?? []);
            foreach ($arr as $value) {
                $key = (string) $value;
                if (isset($counts[$key])) {
                    $counts[$key]++;
                } else {
                    $counts[$key] = 1;
                }
            }
        }

        // Ordenar por conteo desc
        arsort($counts);
        $limit = match ($this->filter) {
            'top5' => 5,
            'top10' => 10,
            default => null,
        };

        $labels = [];
        $data = [];
        $i = 0;
        foreach ($counts as $key => $val) {
            if ($limit !== null && $i >= $limit) {
                break;
            }
            $enum = ChronicDisease::tryFrom($key);
            $labels[] = $enum ? $enum->getLabel() : $key;
            $data[] = (int) $val;
            $i++;
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
            ],
            'colors' => ['#687FE5'],
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

