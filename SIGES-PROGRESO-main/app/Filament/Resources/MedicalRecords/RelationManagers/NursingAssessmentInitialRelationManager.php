<?php

namespace App\Filament\Resources\MedicalRecords\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Schemas\Components\Section;

class NursingAssessmentInitialRelationManager extends RelationManager
{
    protected static string $relationship = 'nursingAssessmentInitial';
    protected static ?string $title = 'Hoja Inicial de Enfermería';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('somatometric_reading_id')
                    ->relationship('somatometricReading', 'id')
                    ->createOptionForm([
                        Forms\Components\TextInput::make('blood_pressure_systolic')->numeric(),
                        Forms\Components\TextInput::make('blood_pressure_diastolic')->numeric(),
                        Forms\Components\TextInput::make('heart_rate')->numeric(),
                        Forms\Components\TextInput::make('respiratory_rate')->numeric(),
                        Forms\Components\TextInput::make('temperature')->numeric(),
                        Forms\Components\TextInput::make('weight')->numeric(),
                        Forms\Components\TextInput::make('height')->numeric(),
                        Forms\Components\TextInput::make('blood_glucose')->numeric(),
                        Forms\Components\TextInput::make('oxygen_saturation')->numeric(),
                        Forms\Components\Textarea::make('observations')->rows(3),
                    ]),
                Forms\Components\Textarea::make('notes')->rows(4),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Enfermero'),
                Tables\Columns\TextColumn::make('created_at')->label('Fecha')->dateTime()->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                ViewAction::make()->modalHeading('Hoja Inicial de Enfermería')
                    ->infolist(function (\Filament\Schemas\Schema $schema) {
                        return $schema->schema([
                            Section::make('Hoja Inicial de Enfermería')
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('user.name')->label('Enfermero'),
                                    TextEntry::make('created_at')->label('Fecha')->dateTime(),
                                ]),
                            Section::make('Signos vitales')
                                ->visible(fn ($record) => filled($record->somatometricReading))
                                ->columns(3)
                                ->schema([
                                    TextEntry::make('somatometricReading.blood_pressure_systolic')->label('PA sistólica'),
                                    TextEntry::make('somatometricReading.blood_pressure_diastolic')->label('PA diastólica'),
                                    TextEntry::make('somatometricReading.heart_rate')->label('FC'),
                                    TextEntry::make('somatometricReading.respiratory_rate')->label('FR'),
                                    TextEntry::make('somatometricReading.temperature')->label('Temp (°C)'),
                                    TextEntry::make('somatometricReading.weight')->label('Peso (kg)'),
                                    TextEntry::make('somatometricReading.height')
                                        ->label('Talla (cm)')
                                        ->state(fn ($record) => $record->somatometricReading?->height ? (int) round($record->somatometricReading->height * 100) : null),
                                    TextEntry::make('somatometricReading.blood_glucose')->label('Glucosa'),
                                    TextEntry::make('somatometricReading.oxygen_saturation')->label('SpO2 (%)'),
                                    TextEntry::make('somatometricReading.observations')->label('Observaciones')->columnSpanFull(),
                                ]),
                            Section::make('Notas de enfermería')
                                ->schema([
                                    TextEntry::make('notes')->label('Notas')->columnSpanFull(),
                                ]),
                        ]);
                    }),
                EditAction::make(),
            ])
            ->bulkActions([]);
    }
}