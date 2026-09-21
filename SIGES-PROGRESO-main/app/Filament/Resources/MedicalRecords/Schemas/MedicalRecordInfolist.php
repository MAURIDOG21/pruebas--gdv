<?php

namespace App\Filament\Resources\MedicalRecords\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class MedicalRecordInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del Expediente')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('record_number')->label('Número de Expediente'),
                        TextEntry::make('patient_type')->badge()->label('Tipo de Paciente'),
                        TextEntry::make('employee_status')->badge()->label('Estado del Empleado'),
                    ]),
                Section::make('Datos del Paciente')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('patient.full_name')->label('Nombre Completo'),
                        TextEntry::make('patient.age')->label('Edad'),
                        TextEntry::make('patient.curp')->label('CURP'),
                        TextEntry::make('patient.sex')->label('Sexo'),
                    ]),
            ]);
    }
}
