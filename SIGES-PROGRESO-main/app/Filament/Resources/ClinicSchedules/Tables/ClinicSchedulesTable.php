<?php

namespace App\Filament\Resources\ClinicSchedules\Tables;

use App\Enums\Shift;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ClinicSchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->defaultSort('date', 'asc')
            ->columns([
                TextColumn::make('clinic_name')
                    ->label('Consultorio')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Médico')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('service.name')
                    ->label('Servicio')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('shift')
                    ->label('Turno')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return ucfirst(is_string($state) ? $state : ($state?->value ?? ''));
                    })
                    ->sortable(),
                TextColumn::make('date')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),
                BadgeColumn::make('is_shift_open')
                    ->label('Estado del Turno')
                    ->formatStateUsing(function ($state, $record) {
                        if ($state) {
                            return 'Abierto';
                        } elseif ($record->shift_closed_at) {
                            return 'Cerrado';
                        } else {
                            return 'Sin abrir';
                        }
                    })
                    ->colors([
                        'success' => fn ($state, $record) => $state === true,
                        'danger' => fn ($state, $record) => $state === false && $record->shift_closed_at,
                        'warning' => fn ($state, $record) => $state === false && !$record->shift_closed_at,
                    ]),
                TextColumn::make('shift_opened_at')
                    ->label('Apertura')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('shift_closed_at')
                    ->label('Cierre')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('openedBy.name')
                    ->label('Abierto por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('closedBy.name')
                    ->label('Cerrado por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('is_active')
                    ->label('Activo')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('shift')
                    ->label('Turno')
                    ->options(Shift::getOptions()),
                TernaryFilter::make('is_active')
                    ->label('Activo'),
                TernaryFilter::make('is_shift_open')
                    ->label('Turno Abierto')
                    ->placeholder('Todos los turnos')
                    ->trueLabel('Solo turnos abiertos')
                    ->falseLabel('Solo turnos cerrados/sin abrir'),
                SelectFilter::make('service_id')
                    ->label('Servicio')
                    ->relationship('service', 'name'),
                SelectFilter::make('user_id')
                    ->label('Médico')
                    ->relationship('user', 'name'),
            ])
            ->recordActions([
                Action::make('open_shift')
                    ->label('Abrir Turno')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn ($record) => $record->canBeOpened())
                    ->form([
                        Textarea::make('opening_notes')
                            ->label('Notas de apertura')
                            ->placeholder('Opcional: Agregar notas sobre la apertura del turno')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->openShift(Auth::user(), $data['opening_notes'] ?? null);
                        
                        Notification::make()
                            ->title('Turno abierto exitosamente')
                            ->success()
                            ->send();
                    }),
                
                Action::make('close_shift')
                    ->label('Cerrar Turno')
                    ->icon('heroicon-o-stop')
                    ->color('danger')
                    ->visible(fn ($record) => $record->canBeClosed())
                    ->form([
                        Textarea::make('closing_notes')
                            ->label('Notas de cierre')
                            ->placeholder('Opcional: Agregar notas sobre el cierre del turno')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->closeShift(Auth::user(), $data['closing_notes'] ?? null);
                        
                        Notification::make()
                            ->title('Turno cerrado exitosamente')
                            ->success()
                            ->send();
                    }),
                
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
