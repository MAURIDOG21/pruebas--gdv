<?php

namespace App\Filament\Resources\Appointments\Schemas;

use App\Models\Prescription;
use App\Enums\AppointmentStatus;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Infolist;
use Filament\Schemas\Schema;

class AppointmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Nota de Consulta')
                ->columns(2)
                ->visible(function ($record) {
                    $existsForAppointment = Prescription::where('appointment_id', $record->id)->exists();
                    $fallbackExists = Prescription::where('medical_record_id', $record->medical_record_id)
                        ->whereNull('appointment_id')
                        ->exists();
                    return $existsForAppointment || $fallbackExists;
                })
                ->schema([
                    TextEntry::make('diagnosis')
                        ->label('Diagnóstico')
                        ->state(function ($record) {
                            $pid = Prescription::where('appointment_id', $record->id)->orderByDesc('id')->value('id')
                                ?? Prescription::where('medical_record_id', $record->medical_record_id)->whereNull('appointment_id')->orderByDesc('id')->value('id');
                            return $pid ? Prescription::find($pid)->diagnosis : null;
                        })
                        ->columnSpanFull(),
                    TextEntry::make('treatment_plan')
                        ->label('Plan de Tratamiento')
                        ->state(function ($record) {
                            $pid = Prescription::where('appointment_id', $record->id)->orderByDesc('id')->value('id')
                                ?? Prescription::where('medical_record_id', $record->medical_record_id)->whereNull('appointment_id')->orderByDesc('id')->value('id');
                            return $pid ? Prescription::find($pid)->notes : null;
                        })
                        ->columnSpanFull(),
                    RepeatableEntry::make('items')
                        ->label('Receta')
                        ->state(function ($record) {
                            $pid = Prescription::where('appointment_id', $record->id)->orderByDesc('id')->value('id')
                                ?? Prescription::where('medical_record_id', $record->medical_record_id)->whereNull('appointment_id')->orderByDesc('id')->value('id');
                            return $pid ? (Prescription::find($pid)->items ?? []) : [];
                        })
                        ->schema([
                            TextEntry::make('drug')->label('Medicamento'),
                            TextEntry::make('dose')->label('Dosis'),
                            TextEntry::make('frequency')->label('Frecuencia'),
                            TextEntry::make('duration')->label('Duración'),
                            TextEntry::make('route')->label('Vía'),
                            TextEntry::make('instructions')->label('Indicaciones'),
                        ])
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
