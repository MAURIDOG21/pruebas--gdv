<?php

namespace App\Exports;

use App\Models\Appointment;
use App\Enums\AppointmentStatus;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class VisitsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected ?string $fromDate = null,
        protected ?string $toDate = null,
        protected ?string $origin = 'todos',
        protected string $dateField = 'date',
    ) {
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Ticket',
            'Origen',
            'Paciente',
            'Servicio',
            'Médico',
            'Estado',
            'Motivo',
        ];
    }

    public function collection(): Collection
    {
        // Campo de fecha a usar para filtrar y ordenar (por defecto 'date')
        $field = $this->dateField === 'created_at' ? 'created_at' : 'date';

        $query = Appointment::query()
            ->with(['medicalRecord.patient', 'service', 'doctor'])
            ->when($this->fromDate, function ($q) use ($field) {
                $q->whereDate($field, '>=', $this->fromDate);
            })
            ->when($this->toDate, function ($q) use ($field) {
                $q->whereDate($field, '<=', $this->toDate);
            });

        if ($this->origin === 'local') {
            $query->where('ticket_number', 'LIKE', 'LOCAL-%');
        } elseif ($this->origin === 'programa') {
            $query->where('ticket_number', 'NOT LIKE', 'LOCAL-%');
        }

        return $query->orderBy($field, 'desc')->get();
    }

    public function map($appointment): array
    {
        // Mostrar fecha según el campo elegido
        if ($this->dateField === 'created_at') {
            $fecha = $appointment->created_at ? Carbon::parse($appointment->created_at)->format('Y-m-d H:i') : '';
        } else {
            $fecha = $appointment->date ? Carbon::parse($appointment->date)->format('Y-m-d') : '';
        }
        $ticket = $appointment->ticket_number ?? '';
        $origen = str_starts_with($ticket, 'LOCAL-') ? 'Local' : 'Turno';
        $paciente = optional(optional($appointment->medicalRecord)->patient)->full_name ?? '';
        $servicio = optional($appointment->service)->name ?? '';
        $medico = optional($appointment->doctor)->full_name ?? optional($appointment->doctor)->name ?? '';
        $statusKey = $appointment->status?->value ?? (string) $appointment->status;
        $estado = match ($statusKey) {
            AppointmentStatus::PENDING->value, 'pending' => 'Revisión',
            AppointmentStatus::IN_PROGRESS->value, 'in_progress' => 'En consulta',
            AppointmentStatus::COMPLETED->value, 'completed' => 'Completada',
            AppointmentStatus::CANCELLED->value, 'cancelled' => 'Cancelada',
            default => $statusKey,
        };
        $motivo = (string)($appointment->reason_for_visit ?? '');

        return [
            $fecha,
            $ticket,
            $origen,
            $paciente,
            $servicio,
            $medico,
            $estado,
            $motivo,
        ];
    }
}