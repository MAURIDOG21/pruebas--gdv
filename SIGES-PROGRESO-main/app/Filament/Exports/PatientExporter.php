<?php

namespace App\Filament\Exports;

use App\Models\Patient;
use App\Enums\ChronicDisease;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PatientExporter implements FromCollection, WithHeadings, WithMapping
{
    public function headings(): array
    {
        return [
            'Nombre Completo',
            'CURP',
            'Email',
            'Teléfono',
            'Localidad',
            'Colonia',
            'Enfermedades Crónicas',
        ];
    }

    public function collection(): Collection
    {
        return Patient::query()->select([
            'full_name',
            'curp',
            'contact_phone',
            'locality',
            'colonia',
            'chronic_diseases',
        ])->get();
    }

    public function map($patient): array
    {
        $diseases = (array) ($patient->chronic_diseases ?? []);
        $labels = array_map(function ($value) {
            $enum = ChronicDisease::tryFrom((string) $value);
            return $enum ? $enum->getLabel() : (string) $value;
        }, $diseases);

        return [
            $patient->full_name,
            $patient->curp,
            $patient->email ?? '',
            $patient->contact_phone,
            is_string($patient->locality) ? $patient->locality : (is_object($patient->locality) && property_exists($patient->locality, 'value') ? $patient->locality->value : (string) ($patient->locality ?? '')),
            $patient->colonia,
            implode(', ', $labels),
        ];
    }
}

