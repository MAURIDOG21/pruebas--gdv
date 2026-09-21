<?php

namespace App\Exports;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ServicesExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected ?string $fromDate = null,
        protected ?string $toDate = null,
        protected string $dateField = 'date',
    ) {
    }

    public function headings(): array
    {
        return [
            'Servicio',
            'Total (todas)',
            'Activas (no canceladas)',
            'Canceladas',
            'Local (activas)',
            'Programa (activas)',
        ];
    }

    public function collection(): Collection
    {
        $field = $this->dateField === 'created_at' ? 'appointments.created_at' : 'appointments.date';

        // Estados activos (no cancelados)
        $activeValues = [
            AppointmentStatus::PENDING->value,
            AppointmentStatus::IN_PROGRESS->value,
            AppointmentStatus::COMPLETED->value,
        ];
        $inList = "'" . implode("','", $activeValues) . "'";

        $totalActiveExpr = "SUM(CASE WHEN appointments.status IN ($inList) THEN 1 ELSE 0 END) AS total_active";
        $totalCancelledExpr = "SUM(CASE WHEN appointments.status = '" . AppointmentStatus::CANCELLED->value . "' THEN 1 ELSE 0 END) AS total_cancelled";
        $localActiveExpr = "SUM(CASE WHEN appointments.status IN ($inList) AND appointments.ticket_number LIKE 'LOCAL-%' THEN 1 ELSE 0 END) AS local_active";
        $programActiveExpr = "SUM(CASE WHEN appointments.status IN ($inList) AND appointments.ticket_number NOT LIKE 'LOCAL-%' THEN 1 ELSE 0 END) AS program_active";

        $query = Appointment::query()
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->select([
                DB::raw('services.name as service_name'),
                DB::raw('COUNT(*) as total_all'),
                DB::raw($totalActiveExpr),
                DB::raw($totalCancelledExpr),
                DB::raw($localActiveExpr),
                DB::raw($programActiveExpr),
            ])
            ->groupBy('services.name')
            ->orderByDesc('total_active');

        if ($this->fromDate) {
            $query->whereDate($field, '>=', $this->fromDate);
        }
        if ($this->toDate) {
            $query->whereDate($field, '<=', $this->toDate);
        }

        return $query->get();
    }

    public function map($row): array
    {
        return [
            $row->service_name,
            (int) ($row->total_all ?? 0),
            (int) ($row->total_active ?? 0),
            (int) ($row->total_cancelled ?? 0),
            (int) ($row->local_active ?? 0),
            (int) ($row->program_active ?? 0),
        ];
    }
}