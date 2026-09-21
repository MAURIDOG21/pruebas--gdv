<?php
namespace App\Filament\Resources\Patients\Schemas;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\IconEntry; 

class PatientInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Información del Expediente')
                    ->columns(3)
                    ->components([
                        // Accedemos a los datos a través de la nueva relación
                        TextEntry::make('medicalRecord.record_number')
                            ->label('Número de Expediente'),
                        TextEntry::make('medicalRecord.patient_type')
                            ->label('Tipo de Paciente')
                            ->badge()
                            ->formatStateUsing(fn ($state) => is_string($state) ? $state : (is_object($state) && property_exists($state, 'value') ? $state->value : (string) $state)),
                        TextEntry::make('medicalRecord.employee_status')
                            ->label('Estatus de Empleado')
                            ->badge()
                            ->formatStateUsing(fn ($state) => is_string($state) ? $state : (is_object($state) && property_exists($state, 'value') ? $state->value : (string) $state)),
                    ]),
                Section::make('Datos Personales del Paciente')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('full_name')->label('Nombre Completo'),
                        TextEntry::make('date_of_birth')->label('Fecha de Nacimiento')->date('d/m/Y'),
                        TextEntry::make('age')->label('Edad'), // El accesor 'age' en el modelo Patient sigue funcionando
                        TextEntry::make('sex')->label('Sexo'),
                        TextEntry::make('curp')->label('CURP'),
                        TextEntry::make('locality')->label('Localidad'),
                        TextEntry::make('tutor.full_name')->label('Tutor Asignado')
                            ->visible(fn ($record) => $record->tutor()->exists()),
                    ]),
                // --- SECCIÓN DE DISCAPACIDAD MEJORADA ---
                Section::make('Información de Discapacidad')
                    ->columns(2)
                    ->schema([
                        // 1. Un icono claro de Sí/No
                        IconEntry::make('has_disability')
                            ->label('¿Tiene alguna discapacidad registrada?')
                            ->boolean(),

                        // 2. Los detalles, que se muestran solo si el campo no está vacío
                        TextEntry::make('disability_details')
                            ->label('Detalles Específicos')
                            ->visible(fn ($state): bool => filled($state)), // Solo visible si hay texto
                    ]),
            ]);
    }
}
