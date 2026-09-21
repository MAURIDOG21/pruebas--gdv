<?php

namespace App\Filament\Resources\Patients\Pages;

use App\Filament\Resources\Patients\PatientResource;
use App\Filament\Resources\MedicalRecords\MedicalRecordResource;
use App\Models\Patient;
use Filament\Resources\Pages\CreateRecord;

class CreatePatient extends CreateRecord
{
    protected static string $resource = PatientResource::class;
    protected static ?string $maxWidth = 'full';
    protected array $mrData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->mrData = [
            'patient_type' => $data['patient_type'] ?? null,
            'employee_status' => $data['employee_status'] ?? null,
        ];
        unset($data['patient_type'], $data['employee_status']);
        return $data;
    }

    protected function handleRecordCreation(array $data): Patient
    {
        $curp = $data['curp'] ?? null;
        $curpHash = $curp ? hash('sha256', strtoupper(trim($curp))) : null;

        if ($curpHash) {
            $existing = Patient::where('curp_hash', $curpHash)->first();
            if ($existing) {
                return $existing;
            }
        }

        try {
            return static::getModel()::create($data);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($curpHash) {
                $existing = Patient::where('curp_hash', $curpHash)->first();
                if ($existing) {
                    return $existing;
                }
            }
            throw $e;
        }
    }

    protected function getRedirectUrl(): string
    {
        return MedicalRecordResource::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $mr = \App\Models\MedicalRecord::where('patient_id', $record->id)->first();
        if (! $mr) {
            $mr = \App\Models\MedicalRecord::firstOrCreate(['patient_id' => $record->id], []);
        }
        $payload = array_filter([
            'patient_type' => $this->mrData['patient_type'] ?? null,
            'employee_status' => $this->mrData['employee_status'] ?? null,
            'consent_form_path' => $this->mrData['consent_form_path'] ?? null,
        ], fn ($v) => ! is_null($v));
        if (! empty($payload)) {
            $mr->update($payload);
        }
    }
}
