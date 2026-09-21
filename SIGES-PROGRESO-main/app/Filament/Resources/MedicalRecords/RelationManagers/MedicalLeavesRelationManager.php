<?php

namespace App\Filament\Resources\MedicalRecords\RelationManagers;

use App\Enums\MedicalLeaveStatus;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Filament\Schemas\Components\Section;
use Carbon\Carbon;
use Dom\Text;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\BadgeColumn;

class MedicalLeavesRelationManager extends RelationManager
{
    protected static string $relationship = 'medicalLeaves';
    protected static ?string $title = 'Incapacidades Médicas'; // Título en español para la sección

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detalles de la Incapacidad')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        // Folio (solo visualización, se genera solo)
                        TextInput::make('folio')
                            ->label('Folio')
                            ->disabled()
                            ->dehydrated()
                            ->placeholder('Se generará al guardar'),

                        // Fecha de Emisión (cuando se crea el documento)
                        DatePicker::make('issue_date')
                            ->label('Fecha de Emisión')
                            ->default(now()) // Por defecto hoy
                            ->required(),

                        // --- UX Inteligente para Fechas ---
                        DatePicker::make('start_date')
                            ->label('Fecha de Inicio de Incapacidad')
                            ->required()
                            ->native(false)
                            ->live(), // Reactivo para el cálculo

                        TextInput::make('duration_in_days')
                            ->label('Duración (días)')
                            ->numeric()
                            ->required()
                            ->live(onBlur: true) // Se actualiza al salir del campo
                            ->afterStateUpdated(function ($get,$set) {
                                $startDate = $get('start_date');
                                $duration = $get('duration_in_days');
                                if ($startDate && $duration) {
                                    // Calcula y actualiza la fecha de fin
                                    $endDate = Carbon::parse($startDate)->addDays($duration - 1)->format('Y-m-d');
                                    $set('end_date', $endDate);
                                }
                            }),
                        DatePicker::make('end_date')
                            ->label('Fecha de Fin de Incapacidad')
                            ->required()
                            //->disabled()
                            ->native(false),

                        Select::make('status')
                            ->label('Estado Inicial')
                            ->options([
                                'pendiente_aprobacion' => 'Pendiente Aprovación',
                            ])
                            ->default('pendiente_aprobacion')
                            ->disabled() // El estado inicial es siempre Borrador
                            ->required(),

                        Textarea::make('reason')
                            ->label('Justificación / Diagnóstico Médico')
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('folio'),
                TextEntry::make('doctor_id')
                    ->numeric(),
                TextEntry::make('issue_date')
                    ->date(),
                TextEntry::make('start_date')
                    ->date(),
                TextEntry::make('end_date')
                    ->date(),
                TextEntry::make('issuing_department'),
                TextEntry::make('status'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->dateTime(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('folio')
            ->columns([
                TextColumn::make('folio')
                    ->label('Folio')
                    ->searchable(),
                TextColumn::make('start_date')
                    ->label('Inicio')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('doctor.name')
                    ->label('Médico Emisor')
                    ->getStateUsing(fn ($record) => $record->doctor?->name ?? 'N/A'), // <-- LA SOLUCIÓN,
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->colors([
                        'primary' => MedicalLeaveStatus::DRAFT->value,
                        'warning' => MedicalLeaveStatus::PENDING_APPROVAL->value,
                        'success' => MedicalLeaveStatus::APPROVED->value,
                        'danger' => MedicalLeaveStatus::REJECTED->value,
                        'secondary' => MedicalLeaveStatus::ARCHIVED->value,
                    ]),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
                // --- ACCIONES DE IMPRESIÓN ---
                Action::make('print_patient_copy')
                    ->label('Imprimir (Copia Paciente)')
                    ->icon('heroicon-o-printer')
                    ->url(fn ($record) => route('medical-leave.download', ['medicalLeaveId' => $record->id, 'copyType' => 'patient']))
                    ->openUrlInNewTab(),

                Action::make('print_institution_copy')
                    ->label('Imprimir (Copia Institución)')
                    ->icon('heroicon-o-document-duplicate')
                    ->url(fn ($record) => route('medical-leave.download', ['medicalLeaveId' => $record->id, 'copyType' => 'institution']))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                    //ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),

                ]),
            ])
            
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]));
    }
}
