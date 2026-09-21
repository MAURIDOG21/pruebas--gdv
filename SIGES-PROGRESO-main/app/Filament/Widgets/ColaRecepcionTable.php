<?php

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;

class ColaRecepcionTable extends TableWidget
{
    protected static ?string $heading = 'Cola en recepción (hoy)';
    protected int | string | array $columnSpan = ['md' => 6];

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Appointment::query()
                ->with(['medicalRecord.patient', 'service', 'doctor', 'clinicSchedule'])
                ->whereDate('created_at', now())
                ->where('status', AppointmentStatus::PENDING)
            )
            ->poll('5s')
            ->defaultSort('created_at', 'asc')
            ->columns([
                TextColumn::make('medicalRecord.patient.full_name')
                    ->label('Paciente')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn ($record): ?string => $record->ticket_number),
                TextColumn::make('service.name')
                    ->label('Servicio')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('clinicSchedule.clinic_name')
                    ->label('Consultorio')
                    ->placeholder('Sin asignar'),
                TextColumn::make('created_at')
                    ->label('Hace')
                    ->formatStateUsing(fn ($state): string => Carbon::parse($state)->diffForHumans())
                    ->description(fn ($state): string => Carbon::parse($state)->format('H:i'))
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('start')
                    ->label('Iniciar consulta')
                    ->icon('heroicon-m-play')
                    ->color('warning')
                    ->action(function (Appointment $record) {
                        $record->update(['status' => AppointmentStatus::IN_PROGRESS]);
                    }),
                Action::make('cancel')
                    ->label('Cancelar')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Appointment $record) {
                        $record->update(['status' => AppointmentStatus::CANCELLED]);
                    }),
            ]);
    }
}

