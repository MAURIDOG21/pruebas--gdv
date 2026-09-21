<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ApexHeatmapVisitasChart extends ApexChartWidget
{
    protected static ?string $chartId = 'apexHeatmapVisitasChart';
    protected static ?string $heading = 'Heatmap: visitas por día x hora';
    protected static ?int $contentHeight = 300;

    public ?string $filter = 'this_week';

    protected function getFilters(): ?array
    {
        return [
            'this_week' => 'Esta semana',
            'last_week' => 'Semana pasada',
        ];
    }

    protected function getOptions(): array
    {
        $filters = session('demofilters', []);

        // Rango de fechas
        $now = now();
        if ($this->filter === 'last_week') {
            $start = $now->copy()->subWeek()->startOfWeek();
            $end = $now->copy()->subWeek()->endOfWeek();
        } else {
            $start = $now->copy()->startOfWeek();
            $end = $now->copy()->endOfWeek();
        }

        // Construir consulta con joins para segmentación
        $query = Appointment::query()
            ->join('medical_records', 'appointments.medical_record_id', '=', 'medical_records.id')
            ->join('patients', 'medical_records.patient_id', '=', 'patients.id')
            ->selectRaw("EXTRACT(DOW FROM appointments.date) AS dow")
            ->selectRaw("EXTRACT(HOUR FROM appointments.created_at) AS hour")
            ->selectRaw('COUNT(*) as total')
            ->whereBetween('appointments.date', [$start->toDateString(), $end->toDateString()]);

        if (! empty($filters['sex'])) {
            $query->where('patients.sex', $filters['sex']);
        }
        if (! empty($filters['locality'])) {
            $query->where('patients.locality', $filters['locality']);
        }
        if (! empty($filters['colonia'])) {
            $query->where('patients.colonia', $filters['colonia']);
        }
        foreach (($filters['diseases'] ?? []) as $d) {
            $query->whereJsonContains('patients.chronic_diseases', $d);
        }

        $rows = $query
            ->groupBy('dow', 'hour')
            ->orderBy('dow')
            ->orderBy('hour')
            ->get();

        // Mapear resultados a matriz [día][hora] => total
        $matrix = [];
        for ($d = 0; $d < 7; $d++) {
            $matrix[$d] = array_fill(0, 24, 0);
        }
        foreach ($rows as $row) {
            $d = (int) $row->dow; // 0 dom .. 6 sáb (Postgres)
            $h = (int) $row->hour;
            if (isset($matrix[$d][$h])) {
                $matrix[$d][$h] = (int) $row->total;
            }
        }

        $dayLabels = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        $series = [];
        foreach ($dayLabels as $idx => $day) {
            $series[] = [
                'name' => $day,
                'data' => $matrix[$idx],
            ];
        }

        return [
            'chart' => [
                'type' => 'heatmap',
                'height' => static::$contentHeight,
            ],
            'series' => $series,
            'xaxis' => [
                'categories' => range(0, 23),
                'title' => ['text' => 'Hora del día'],
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'colors' => ['#00A100'],
        ];
    }
}
