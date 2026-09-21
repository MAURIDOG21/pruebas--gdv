<?php

namespace App\Filament\Resources\MedicalRecords\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Grid;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PrescriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'prescriptions';
    protected static ?string $title = 'Recetas';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detalles de la Receta')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('folio')
                            ->label('Folio')
                            ->disabled()
                            ->dehydrated()
                            ->placeholder('Se generará al guardar'),

                        DatePicker::make('issue_date')
                            ->label('Fecha de Emisión')
                            ->default(now())
                            ->required(),

                        Textarea::make('diagnosis')
                            ->label('Diagnóstico')
                            ->rows(2)
                            ->columnSpanFull(),

                        Repeater::make('items')
                            ->label('Medicamentos')
                            ->addActionLabel('Agregar medicamento')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('medication')->label('Medicamento')->required(),
                                    TextInput::make('dose')->label('Dosis')->placeholder('500 mg'),
                                    TextInput::make('frequency')->label('Frecuencia')->placeholder('cada 8 horas'),
                                ]),
                                Grid::make(3)->schema([
                                    TextInput::make('duration')->label('Duración')->placeholder('5 días'),
                                    TextInput::make('route')->label('Vía')->placeholder('oral'),
                                    TextInput::make('notes')->label('Notas')->placeholder('Antes de comidas'),
                                ]),
                            ])
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->label('Notas generales')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('folio')
            ->columns([
                TextColumn::make('folio')->label('Folio')->searchable(),
                TextColumn::make('issue_date')->label('Fecha')->date('d/m/Y')->sortable(),
                TextColumn::make('doctor.name')->label('Médico Emisor')->getStateUsing(fn ($record) => $record->doctor?->name ?? 'N/A'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                Action::make('print_patient_copy')
                    ->label('Imprimir (Copia Paciente)')
                    ->icon('heroicon-o-printer')
                    ->url(fn ($record) => route('prescription.download', ['prescriptionId' => $record->id, 'copyType' => 'patient']))
                    ->openUrlInNewTab(),
                Action::make('print_institution_copy')
                    ->label('Imprimir (Copia Institución)')
                    ->icon('heroicon-o-document-duplicate')
                    ->url(fn ($record) => route('prescription.download', ['prescriptionId' => $record->id, 'copyType' => 'institution']))
                    ->openUrlInNewTab(),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]));
    }
}