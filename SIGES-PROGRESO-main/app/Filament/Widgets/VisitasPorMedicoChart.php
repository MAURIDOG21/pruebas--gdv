<?php

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\ChartWidget;
use App\Models\Appointment;
class VisitasPorMedicoChart extends ChartWidget
{
    protected ?string $heading = 'Visitas atendidas por médico';

    public ?string $filter = 'week';

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Hoy',
            'week' => 'Esta semana',
            'month' => 'Este mes',
        ];
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;

        // Definir el rango de fechas
        [$start, $end] = match ($activeFilter) {
            'today' => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
            'week' => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            default => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
        };

        // Usamos Eloquent para una consulta más limpia
        $query = Appointment::query()
            ->join('users', 'appointments.doctor_id', '=', 'users.id')
            ->select('users.name as doctor_name', DB::raw('COUNT(*) as total'))
            ->where('appointments.status', AppointmentStatus::COMPLETED)
            ->whereBetween('appointments.created_at', [$start, $end]) // Asumiendo que filtramos por fecha de visita
            ->groupBy('users.name')
            ->orderByDesc('total');

        $rows = $query->get();

        // --- ¡AQUÍ ESTÁ LA NUEVA LÓGICA DE COLOR! ---

        // 1. Definimos una paleta de colores variada
        $palette = [
            '#36A2EB', // Azul
            '#FF6384', // Rosa
            '#FFCE56', // Amarillo
            '#4BC0C0', // Turquesa
            '#9966FF', // Morado
            '#FF9F40', // Naranja
            '#10B981', // Verde (de tu otra gráfica)
            '#EF4444', // Rojo (de tu otra gráfica)
        ];

        $labels = [];
        $data = [];
        $backgroundColors = [];
        $borderColors = [];

        // 2. Recorremos los resultados y asignamos un color a cada médico
        foreach ($rows as $index => $row) {
            $labels[] = $row->doctor_name ?? 'Sin asignar';
            $data[] = (int) $row->total;
            
            // Usamos el operador "módulo" (%) para ciclar la paleta
            // si hay más médicos que colores.
            $color = $palette[$index % count($palette)];
            
            $backgroundColors[] = $color . '80'; // 80 es la transparencia en hexadecimal (aprox 50%)
            $borderColors[] = $color;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Visitas completadas',
                    'data' => $data,
                    'backgroundColor' => $backgroundColors, // <-- Ahora es un array
                    'borderColor' => $borderColors,         // <-- Bordes sólidos
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    public function getHeading(): ?string
    {
        $periodName = match ($this->filter) {
            'today' => 'hoy',
            'week' => 'esta semana',
            'month' => 'este mes',
            default => 'periodo',
        };

        return 'Visitas atendidas por médico — ' . $periodName;
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
        ];
    }
}
