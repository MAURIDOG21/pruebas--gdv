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
class MedicalInitialAssessmentRelationManager extends RelationManager
{
    protected static string $relationship = 'medicalInitialAssessment';
    protected static ?string $title = 'Hoja Inicial Médica';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Textarea::make('allergies')->rows(3),
                Forms\Components\Textarea::make('personal_pathological_history')->rows(4),
                Forms\Components\Textarea::make('gyneco_obstetric_history')->rows(4),
                Forms\Components\Textarea::make('current_illness')->rows(5),
                Forms\Components\Textarea::make('physical_exam')->rows(5),
                Forms\Components\Textarea::make('diagnosis')->rows(3),
                Forms\Components\Textarea::make('treatment_note')->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Médico'),
                Tables\Columns\TextColumn::make('created_at')->label('Fecha')->dateTime()->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                ViewAction::make()->modalHeading('Hoja Inicial Médica')
                    ->infolist(function (\Filament\Schemas\Schema $schema) {
                        return $schema->schema([
                            Section::make('Datos del Registro')
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('user.name')->label('Médico'),
                                    TextEntry::make('created_at')->label('Fecha')->dateTime(),
                                ]),
                            Section::make('Hoja Inicial Médica')
                                ->schema([
                                    TextEntry::make('allergies')->label('Alergias')->columnSpanFull(),
                                    TextEntry::make('personal_pathological_history')->label('Antecedentes personales patológicos')->columnSpanFull(),
                                    TextEntry::make('gyneco_obstetric_history')->label('Antecedentes gineco-obstétricos')->columnSpanFull(),
                                    TextEntry::make('current_illness')->label('Padecimiento actual')->columnSpanFull(),
                                    TextEntry::make('physical_exam')->label('Exploración física')->columnSpanFull(),
                                    TextEntry::make('diagnosis')->label('Diagnóstico')->columnSpanFull(),
                                    TextEntry::make('treatment_note')->label('Tratamiento')->columnSpanFull(),
                                ]),
                        ]);
                    }),
                EditAction::make(),
            ])
            ->bulkActions([]);
    }
}