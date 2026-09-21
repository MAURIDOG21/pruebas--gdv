<?php



namespace App\Filament\Widgets;



use Filament\Widgets\ChartWidget;

use App\Models\Appointment;

use Carbon\Carbon;

use Carbon\CarbonPeriod;

use Illuminate\Support\Facades\DB;



class VisitasSemanalesChart extends ChartWidget

{

    protected ?string $heading = 'Visitas por día (semana actual)';



    public ?string $filter = 'week';



    protected string $color = 'icon';



    protected function getFilters(): ?array

    {

        return [

            'today' => 'Hoy',

            'week' => 'Semana actual',

            'last_week' => 'Semana pasada',

        ];

    }



    protected function getData(): array

    {

        Carbon::setLocale('es');



        $now = now();

        [$start, $end] = match ($this->filter) {

            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],

            'last_week' => [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()],

            default => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],

        };



        // Obtener recuentos por fecha (Y-m-d) dentro del rango. Compatibilidad PostgreSQL.
        $results = Appointment::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('created_at::date as day, COUNT(*) as aggregate')
            ->groupByRaw('created_at::date')
            ->orderBy('day')
            ->get()
            ->keyBy('day');



        // Construir periodo día a día.

        $period = CarbonPeriod::create($start->copy()->startOfDay(), '1 day', $end->copy()->startOfDay());



        $labels = [];

        $data = [];



        foreach ($period as $date) {

            $labels[] = $date->isoFormat('ddd D/MM'); // ej. "lun 21/10"

            $key = $date->format('Y-m-d');

            $data[] = (int) ($results[$key]->aggregate ?? 0);

        }



        return [

            'datasets' => [

                [

                    'label' => 'Visitas',

                    'data' => $data,

                    'backgroundColor' => '#36A2EB',

                    'borderColor' => '#1E40AF',

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

        $rango = $start->isoFormat('D MMM') . ' - ' . $end->isoFormat('D MMM');

       

        $periodName = match ($this->filter) {

            'last_week' => 'semana pasada',

            'today' => 'hoy',

            default => 'semana actual',

        };



        return 'Visitas por día (' . $periodName . ') · Total: ' . $total . ' · ' . $rango;

    }



    protected function getType(): string

    {

        return 'bar';

    }

}