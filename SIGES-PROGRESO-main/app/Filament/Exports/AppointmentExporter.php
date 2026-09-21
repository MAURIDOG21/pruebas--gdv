<?php

namespace App\Filament\Exports;

use App\Models\Appointment;
use App\Enums\AppointmentStatus;
use App\Enums\VisitType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AppointmentExporter implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected ?string $fromDate = null,
        protected ?string $toDate = null,
        protected ?array $sessionFilters = null,
    ) {
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Hora',
            'Ticket',
            'Paciente',
            'CURP',
            'Médico',
            'Servicio',
            'Consultorio',
            'Estatus',
            'Tipo de Visita',
        ];
    }

    public function collection(): Collection
    {
        $query = Appointment::query()
            ->join('medical_records', 'appointments.medical_record_id', '=', 'medical_records.id')
            ->join('patients', 'medical_records.patient_id', '=', 'patients.id')
            ->join('users', 'appointments.doctor_id', '=', 'users.id')
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->join('clinic_schedules', 'appointments.clinic_schedule_id', '=', 'clinic_schedules.id')
            ->select([
                'appointments.date',
                'appointments.created_at',
                'appointments.ticket_number',
                'patients.full_name as patient_name',
                'patients.curp',
                'users.name as doctor_name',
                'services.name as service_name',
                'clinic_schedules.clinic_name',
                'appointments.status',
                'appointments.visit_type',
            ]);

        if ($this->fromDate && $this->toDate) {
            $query->whereBetween('appointments.date', [$this->fromDate, $this->toDate]);
        }

        $filters = $this->sessionFilters ?? [];
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

        return $query->orderBy('appointments.date')->get();
    }

    public function map($row): array
    {
        $status = is_string($row->status) ? $row->status : (is_object($row->status) && property_exists($row->status, 'value') ? $row->status->value : (string) $row->status);
        $visit = is_string($row->visit_type) ? $row->visit_type : (is_object($row->visit_type) && property_exists($row->visit_type, 'value') ? $row->visit_type->value : (string) $row->visit_type);

        // Mapear etiquetas amigables
        $statusLabel = AppointmentStatus::tryFrom($status)?->getLabel() ?? $status;
        $visitLabel = $visit;
        if ($visit) {
             // Si el enum tiene método getLabel o si usamos el value directamente
             $enum = VisitType::tryFrom($visit);
             $visitLabel = $enum ? $enum->value : $visit;
        }

        return [
            \Carbon\Carbon::parse($row->date)->toDateString(),
            \Carbon\Carbon::parse($row->created_at)->format('H:i'),
            $row->ticket_number,
            $row->patient_name,
            $row->curp,
            $row->doctor_name,
            $row->service_name,
            $row->clinic_name,
            $statusLabel,
            $visitLabel,
        ];
    }
}

