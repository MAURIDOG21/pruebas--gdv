<?php

namespace App\Filament\Resources\MedicalRecords\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Support\Facades\Auth;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\TextInput;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
class NursingAssessmentRelationManager extends RelationManager
{
    protected static string $relationship = 'nursingAssessment';
    protected static ?string $title = 'Valoración Inicial de Enfermería';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos Somatométricos')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('blood_pressure_systolic')
                                    ->label('Presión Sistólica (mmHg)')
                                    ->numeric(),
                                Forms\Components\TextInput::make('blood_pressure_diastolic')
                                    ->label('Presión Diastólica (mmHg)')
                                    ->numeric(),
                                Forms\Components\TextInput::make('heart_rate')
                                    ->label('Frecuencia Cardíaca (lpm)')
                                    ->numeric(),
                                Forms\Components\TextInput::make('temperature')
                                    ->label('Temperatura (°C)')
                                    ->numeric(),
                                Forms\Components\TextInput::make('weight')
                                    ->label('Peso (kg)')
                                    ->numeric(),
                                Forms\Components\TextInput::make('height')
                                    ->label('Talla (m)')
                                    ->numeric(),
                            ]),
                    ]),
                Section::make('Información Clínica')
                    ->schema([
                        Forms\Components\Textarea::make('allergies')
                            ->label('Alergias Conocidas'),
                        Forms\Components\Textarea::make('personal_pathological_history')
                            ->label('Antecedentes Personales Patológicos'),
                    ]),
            ])
            ->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha de Registro')
                    ->dateTime('d/m/Y H:i a')
                    ->sortable(),
                    TextColumn::make('allergies')
                        ->label('Alergias')
                        ->sortable(),
                        TextColumn::make('personal_pathological_history')
                            ->label('Antecedentes Personales Patológicos')
                            ->sortable(),
                TextColumn::make('user.name')
                    ->label('Registrado por'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = Auth::id();
                        return $data;
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}