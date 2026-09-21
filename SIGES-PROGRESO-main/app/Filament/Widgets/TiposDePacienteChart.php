<?php

namespace App\Filament\Widgets;

use App\Enums\PatientType;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TiposDePacienteChart extends ChartWidget
{
    protected ?string $heading = 'Distribución de Pacientes por Tipo';
    
    public ?string $filter = 'all';
    
    protected function getFilters(): ?array
    {
        return [
            'all' => 'Todos',
            'month' => 'Este mes',
            'year' => 'Este año',
        ];
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;
        
        // Definir el rango de fechas según el filtro
        $query = DB::table('medical_records')
            ->select('patient_type', DB::raw('count(*) as total'))
            ->groupBy('patient_type');
            
        // Aplicar filtros de tiempo si es necesario
        if ($activeFilter === 'month') {
            $query->join('patients', 'medical_records.patient_id', '=', 'patients.id')
                  ->whereMonth('patients.created_at', Carbon::now()->month)
                  ->whereYear('patients.created_at', Carbon::now()->year);
        } elseif ($activeFilter === 'year') {
            $query->join('patients', 'medical_records.patient_id', '=', 'patients.id')
                  ->whereYear('patients.created_at', Carbon::now()->year);
        }
        
        // Ejecutar la consulta
        $patientsByType = $query->get()
            ->keyBy('patient_type')
            ->map(fn ($item) => $item->total)
            ->toArray();
        
        // Asegurar que todos los tipos estén representados
        $allTypes = [
            PatientType::EXTERNAL->value => 0,
            PatientType::EMPLOYEE->value => 0,
            PatientType::EMPLOYEE_DEPENDENT->value => 0,
            PatientType::PEDIATRIC->value => 0,
        ];
        
        // Combinar con los datos reales
        $data = array_merge($allTypes, $patientsByType);
        
        // Colores para cada tipo de paciente
        $colors = [
            PatientType::EXTERNAL->value => '#3b82f6', // Azul
            PatientType::EMPLOYEE->value => '#10b981', // Verde
            PatientType::EMPLOYEE_DEPENDENT->value => '#f59e0b', // Naranja
            PatientType::PEDIATRIC->value => '#8b5cf6', // Púrpura
        ];
        
        // Preparar datos para el gráfico
        return [
            'datasets' => [
                [
                    'label' => 'Pacientes',
                    'data' => array_values($data),
                    'backgroundColor' => array_values($colors),
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
        ];
    }
}
