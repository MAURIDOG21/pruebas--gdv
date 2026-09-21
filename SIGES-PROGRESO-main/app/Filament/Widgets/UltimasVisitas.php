<?php

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Appointment;
use Carbon\Carbon;
class UltimasVisitas extends TableWidget
{
    protected static ?string $heading = 'Últimas visitas (en vivo)';
    protected int | string | array $columnSpan = ['md' => 6];
    public function table(Table $table): Table
    {
        return $table
            // Consulta enfocada en información operativa del día
            ->query(fn (): Builder => Appointment::query()
                ->with(['medicalRecord.patient', 'service', 'doctor', 'clinicSchedule'])
                ->whereDate('created_at', now())
            )
            // Actualización en vivo para recepción
            ->poll('5s')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('medicalRecord.patient.full_name')
                    ->label('Paciente')
                    ->searchable()
                    ->size('lg')
                    ->placeholder('Sin asignar')
                    ->weight('bold')
                    ->description(fn ($record): ?string => $record->ticket_number)
                    ->tooltip(fn ($record): ?string => $record->reason_for_visit),

                TextColumn::make('service.name')
                    ->label('Servicio')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('clinicSchedule.clinic_name')
                    ->label('Consultorio')
                    ->placeholder('Sin asignar')
                    ->color(fn (?string $state): string => is_null($state) ? 'gray' : 'indigo')
                    ->weight(fn (?string $state): string => is_null($state) ? 'bold' : 'normal'),

                TextColumn::make('visit_type')
                    ->label('Tipo de visita')
                    ->badge()
                    ->placeholder('Sin asignar')
                    ->color(fn ($state): string => match ($state) {
                        'Primera Vez' => 'success',
                        'Subsecuente' => 'gray',
                        default => 'gray',
                    }),

                IconColumn::make('ticket_number')
                    ->label('Origen')
                    ->icon(fn ($record) => 
                        $record->isWalkIn()
                            ? 'heroicon-o-computer-desktop'     // Ícono si fue atendido directamente en recepción
                            : 'heroicon-o-ticket'    // Ícono si proviene del sistema de turnos
                    )
                    ->color(fn ($record) =>
                        $record->isWalkIn()
                            ? 'warning'  // Color si fue por recepción
                            : 'primary'  // Color si fue por turnos
                    )
                    ->tooltip(fn ($record) =>
                        $record->isWalkIn()
                            ? 'Directamente atendido en recepción'
                            : 'Programa de turnos'
                    ),


                TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => match ($state->value ?? $state) {
                        AppointmentStatus::PENDING->value, 'pending' => 'Recepción',
                        AppointmentStatus::IN_PROGRESS->value, 'in_progress' => 'En consulta',
                        AppointmentStatus::COMPLETED->value, 'completed' => 'Completada',
                        AppointmentStatus::CANCELLED->value, 'cancelled' => 'Cancelada',
                        default => (string) $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state->value ?? $state) {
                        AppointmentStatus::PENDING->value, 'pending' => 'icon',
                        AppointmentStatus::IN_PROGRESS->value, 'in_progress' => 'warning',
                        AppointmentStatus::COMPLETED->value, 'completed' => 'success',
                        AppointmentStatus::CANCELLED->value, 'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match ($state->value ?? $state) {
                        AppointmentStatus::PENDING->value, 'pending' => 'heroicon-o-user-plus',
                        AppointmentStatus::IN_PROGRESS->value, 'in_progress' => 'heroicon-o-clock',
                        AppointmentStatus::COMPLETED->value, 'completed' => 'heroicon-o-check-circle',
                        AppointmentStatus::CANCELLED->value, 'cancelled' => 'heroicon-o-x-circle',
                        default => '',
                    })
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Hace')
                    ->formatStateUsing(fn ($state): string => Carbon::parse($state)->diffForHumans())
                    ->description(fn ($state): string => Carbon::parse($state)->format('H:i'))
                    ->sortable(),
            ])
            ->filters([
                Filter::make('hoy')
                    ->label('Hoy')
                    ->default(true)
                    ->query(fn (Builder $query) => $query->whereDate('created_at', now())),

                Filter::make('recientes_30m')
                    ->label('Últimos 30 minutos')
                    ->query(fn (Builder $query) => $query->where('created_at', '>=', now()->subMinutes(30))),

                Filter::make('pendientes')
                    ->label('En recepción (pendientes)')
                    ->query(fn (Builder $query) => $query->where('status', AppointmentStatus::PENDING)),

                Filter::make('en_consulta')
                    ->label('En consulta')
                    ->query(fn (Builder $query) => $query->where('status', AppointmentStatus::IN_PROGRESS)),
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
