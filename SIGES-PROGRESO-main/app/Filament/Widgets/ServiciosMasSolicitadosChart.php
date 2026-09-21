<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ServiciosMasSolicitadosChart extends ChartWidget
{
    protected ?string $heading = 'Distribución de Visitas por Servicio';

    public ?string $filter = 'week';

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Hoy',
            'week'  => 'Semana actual',
            'last_week' => 'Semana pasada',
        ];
    }

    protected function getData(): array
    {
        $now = now();
        [$start, $end] = match ($this->filter) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'last_week' => [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()],
            default => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
        };

        $rows = DB::table('appointments')
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->whereBetween('appointments.created_at', [$start, $end])
            ->select('services.name as name', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('services.name')
            ->orderByDesc('aggregate')
            ->get();

        $labels = $rows->pluck('name')->all();
        $data = $rows->pluck('aggregate')->map(fn ($v) => (int) $v)->all();

        // Paleta de colores para segmentos
        $palette = ['#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#8DD17E', '#C44D58', '#00A6ED', '#F95700'];
        $backgroundColors = [];
        foreach ($labels as $i => $label) {
            $backgroundColors[] = $palette[$i % count($palette)];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Visitas por Servicio',
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                    'hoverOffset' => 8,
                ],
            ],
            'labels' => $labels,
        ];
    }

    public function getHeading(): ?string
    {
        [$start, $end] = match ($this->filter) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'last_week' => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            default => [now()->startOfWeek(), now()->endOfWeek()],
        };

        $total = Appointment::whereBetween('created_at', [$start, $end])->count();
        Carbon::setLocale('es');
        $rango = $start->isoFormat('D MMM') . ' - ' . $end->isoFormat('D MMM');
        $periodName = match ($this->filter) {
            'last_week' => 'semana pasada',
            'today' => 'hoy',
            default => 'semana actual',
        };

        return 'Distribución de Visitas por Servicio (' . $periodName . ') · Total: ' . $total . ' · ' . $rango;
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
