<?php

namespace App\Exports;

use App\Enums\AppointmentStatus;
use App\Enums\PatientType;
use App\Enums\VisitType;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PatientsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        private string $fromDate,
        private string $toDate,
        private string $dateField = 'date'
    ) {}

    public function headings(): array
    {
        return [
            'Tipo de Paciente',
            'Total Expedientes',
            'Visitas Nuevas',
            'Visitas Recurrentes',
            'Total Visitas Activas',
            'Visitas Canceladas',
            'Local (Activas)',
            'Programa (Activas)',
        ];
    }

    public function collection(): Collection
    {
        // 1. Obtener conteo de expedientes por tipo de paciente
        $medicalRecordCounts = MedicalRecord::query()
            ->select('patient_type', DB::raw('COUNT(*) as total_records'))
            ->groupBy('patient_type')
            ->get()
            ->mapWithKeys(function ($row) {
                $type = $row->patient_type instanceof PatientType 
                    ? $row->patient_type->name 
                    : (PatientType::tryFrom((string) $row->patient_type)?->name ?? (string) $row->patient_type);
                return [$type => (int) $row->total_records];
            });

        // 2. Obtener métricas de visitas por tipo de paciente en el rango de fechas
        $visitMetrics = Appointment::query()
            ->join('medical_records', 'appointments.medical_record_id', '=', 'medical_records.id')
            ->select('medical_records.patient_type')
            ->selectRaw('
                SUM(CASE WHEN appointments.visit_type = ? AND appointments.status != ? THEN 1 ELSE 0 END) as nuevas_activas,
                SUM(CASE WHEN appointments.visit_type = ? AND appointments.status != ? THEN 1 ELSE 0 END) as recurrentes_activas,
                SUM(CASE WHEN appointments.status != ? THEN 1 ELSE 0 END) as total_activas,
                SUM(CASE WHEN appointments.status = ? THEN 1 ELSE 0 END) as canceladas,
                SUM(CASE WHEN appointments.status != ? AND appointments.ticket_number LIKE ? THEN 1 ELSE 0 END) as local_activas,
                SUM(CASE WHEN appointments.status != ? AND appointments.ticket_number NOT LIKE ? THEN 1 ELSE 0 END) as programa_activas
            ', [
                VisitType::PRIMERA_VEZ->value, AppointmentStatus::CANCELLED->value,
                VisitType::SUBSECUENTE->value, AppointmentStatus::CANCELLED->value,
                AppointmentStatus::CANCELLED->value,
                AppointmentStatus::CANCELLED->value,
                AppointmentStatus::CANCELLED->value, 'LOCAL-%',
                AppointmentStatus::CANCELLED->value, 'LOCAL-%'
            ])
            ->whereBetween("appointments.{$this->dateField}", [$this->fromDate, $this->toDate])
            ->groupBy('medical_records.patient_type')
            ->get()
            ->mapWithKeys(function ($row) {
                $type = $row->patient_type instanceof PatientType 
                    ? $row->patient_type->name 
                    : (PatientType::tryFrom((string) $row->patient_type)?->name ?? (string) $row->patient_type);
                return [$type => [
                    'nuevas_activas' => (int) $row->nuevas_activas,
                    'recurrentes_activas' => (int) $row->recurrentes_activas,
                    'total_activas' => (int) $row->total_activas,
                    'canceladas' => (int) $row->canceladas,
                    'local_activas' => (int) $row->local_activas,
                    'programa_activas' => (int) $row->programa_activas,
                ]];
            });

        // 3. Combinar datos por tipo de paciente
        $types = [
            PatientType::EXTERNAL->name,
            PatientType::EMPLOYEE->name,
            PatientType::EMPLOYEE_DEPENDENT->name,
            PatientType::PEDIATRIC->name,
        ];

        $result = collect();
        foreach ($types as $type) {
            $recordCount = $medicalRecordCounts->get($type, 0);
            $metrics = $visitMetrics->get($type, [
                'nuevas_activas' => 0,
                'recurrentes_activas' => 0,
                'total_activas' => 0,
                'canceladas' => 0,
                'local_activas' => 0,
                'programa_activas' => 0,
            ]);

            $result->push((object) [
                'patient_type' => $type,
                'total_records' => $recordCount,
                'nuevas_activas' => $metrics['nuevas_activas'],
                'recurrentes_activas' => $metrics['recurrentes_activas'],
                'total_activas' => $metrics['total_activas'],
                'canceladas' => $metrics['canceladas'],
                'local_activas' => $metrics['local_activas'],
                'programa_activas' => $metrics['programa_activas'],
            ]);
        }

        return $result;
    }

    public function map($row): array
    {
        // Mapear nombres de tipos de paciente a español
        $typeLabels = [
            'EXTERNAL' => 'Externo',
            'EMPLOYEE' => 'Empleado',
            'EMPLOYEE_DEPENDENT' => 'Dependiente de Empleado',
            'PEDIATRIC' => 'Pediátrico',
        ];

        return [
            $typeLabels[$row->patient_type] ?? $row->patient_type,
            $row->total_records,
            $row->nuevas_activas,
            $row->recurrentes_activas,
            $row->total_activas,
            $row->canceladas,
            $row->local_activas,
            $row->programa_activas,
        ];
    }
}