<?php

namespace App\Filament\Widgets;

use App\Enums\PatientType;
use App\Models\MedicalRecord;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ApexTiposDePacienteChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'apexTiposDePacienteChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Tipos de Paciente';

    /**
     * Preferred content height
     */
    protected static ?int $contentHeight = 300;

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        // Consulta: conteo de expedientes por tipo de paciente
        $rows = MedicalRecord::query()
            ->select('patient_type', DB::raw('COUNT(*) as total'))
            ->groupBy('patient_type')
            ->get();

        // Orden y etiquetas deseadas basadas en Enum
        $types = [
            PatientType::EXTERNAL,
            PatientType::EMPLOYEE,
            PatientType::EMPLOYEE_DEPENDENT,
            PatientType::PEDIATRIC,
        ];

        // Paleta consistente con el mapeo visual usado en tablas
        $colorMap = [
            'EXTERNAL' => '#36A2EB', // info
            'EMPLOYEE' => '#10B981', // success
            'EMPLOYEE_DEPENDENT' => '#F59E0B', // warning
            'PEDIATRIC' => '#4F46E5', // primary
        ];

        $grouped = collect($rows)->mapWithKeys(function ($row) {
            if ($row->patient_type instanceof PatientType) {
                $key = $row->patient_type->name;
            } else {
                $case = PatientType::tryFrom((string) $row->patient_type);
                $key = $case ? $case->name : (string) $row->patient_type;
            }
            return [$key => (int) $row->total];
        });

        $labels = [];
        $series = [];
        $colors = [];

        foreach ($types as $type) {
            $labels[] = ucfirst($type->value); // El valor del enum ya es etiqueta en español
            $series[] = (int) ($grouped[$type->name] ?? 0);
            $colors[] = $colorMap[$type->name] ?? '#9CA3AF'; // gray por defecto
        }

        return [
            'chart' => [
                'type' => 'donut',
                'height' => static::$contentHeight,
                'animations' => [
                    'enabled' => true,
                ],
            ],
            'series' => $series,
            'labels' => $labels,
            'colors' => $colors,
            'legend' => [
                'labels' => [
                    'fontFamily' => 'inherit',
                ],
            ],
            'tooltip' => [
                'enabled' => true,
            ],
            'dataLabels' => [
                'enabled' => true,
            ],
        ];
    }
}
