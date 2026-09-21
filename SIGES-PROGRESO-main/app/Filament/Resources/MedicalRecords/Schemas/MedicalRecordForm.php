<?php

namespace App\Filament\Resources\MedicalRecords\Schemas;

use App\Enums\EmployeeStatus;
use App\Enums\PatientType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MedicalRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('patient_id')
                    ->required()
                    ->numeric(),
                TextInput::make('record_number')
                    ->required(),
                Select::make('patient_type')
                    ->options(PatientType::class),
                Select::make('employee_status')
                    ->options(EmployeeStatus::class),
            ]);
    }
}
