<?php

namespace App\Filament\Resources\MedicalRecords\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use App\Enums\AppointmentStatus;
use App\Models\Prescription;


class AppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'appointments';
    protected static ?string $title = 'Historial de Visitas';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('ticket_number')
                    ->label('Número de Ticket')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Select::make('service_id')
                    ->relationship('service', 'name')
                    ->label('Servicio Requerido')
                    ->required(),
                Forms\Components\Select::make('doctor_id')
                    ->relationship('doctor', 'name')
                    ->label('Asignar Médico'),
                Forms\Components\Textarea::make('reason_for_visit')
                    ->label('Motivo de la Visita (reportado por el paciente)')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('notes')
                    ->label('Notas Internas (Recepcionista/Médico)')
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->label('Estado de la Visita')
                    ->options(AppointmentStatus::class) // Usamos el Enum
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('ticket_number')
            ->columns([
                TextColumn::make('ticket_number')
                    ->label('Ticket')
                    ->searchable(),
                TextColumn::make('service.name')
                    ->label('Servicio'),
                TextColumn::make('doctor.name')
                    ->label('Médico Asignado')
                    ->placeholder('Sin asignar'),
                    TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->colors([
                        'primary' => AppointmentStatus::PENDING->value,
                        'warning' => AppointmentStatus::IN_PROGRESS->value,
                        'success' => AppointmentStatus::COMPLETED->value,
                        'danger' => AppointmentStatus::CANCELLED->value,
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Registro')
                    ->dateTime('d/m/Y H:i a')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('view_consultation_patient')
                    ->label('Ver Consulta (Paciente)')
                    ->icon('heroicon-o-clipboard-document')
                    ->visible(fn ($record) => $record->status === AppointmentStatus::COMPLETED && Prescription::where('medical_record_id', $record->medical_record_id)->exists())
                    ->url(fn ($record) => route('prescription.download', [
                        'prescriptionId' => Prescription::where('medical_record_id', $record->medical_record_id)->orderByDesc('id')->value('id'),
                        'copyType' => 'patient',
                    ]))
                    ->button(),
                Action::make('view_consultation_institution')
                    ->label('Ver Consulta (Institución)')
                    ->icon('heroicon-o-clipboard-document')
                    ->color('gray')
                    ->visible(fn ($record) => $record->status === AppointmentStatus::COMPLETED && Prescription::where('medical_record_id', $record->medical_record_id)->exists())
                    ->url(fn ($record) => route('prescription.download', [
                        'prescriptionId' => Prescription::where('medical_record_id', $record->medical_record_id)->orderByDesc('id')->value('id'),
                        'copyType' => 'institution',
                    ]))
                    ->button(),
            ]);
    }
}
