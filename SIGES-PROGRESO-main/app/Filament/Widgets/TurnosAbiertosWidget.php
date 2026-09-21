<?php

namespace App\Filament\Widgets;

use App\Models\ClinicSchedule;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TurnosAbiertosWidget extends TableWidget
{
    protected static ?string $heading = 'Turnos abiertos';
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ClinicSchedule::query()
                ->with(['user', 'service', 'openedBy'])
                ->where('is_shift_open', true)
                ->orderByDesc('date')
            )
            ->poll('10s')
            ->columns([
                TextColumn::make('clinic_name')
                    ->label('Consultorio')
                    ->weight('bold'),
                TextColumn::make('service.name')
                    ->label('Servicio')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Médico')
                    ->sortable(),
                TextColumn::make('shift')
                    ->label('Turno')
                    ->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? $state->value : (is_string($state) ? $state : '')),
                TextColumn::make('date')
                    ->label('Fecha')
                    ->formatStateUsing(fn ($state) => Carbon::parse($state)->format('d/m/Y')),
                TextColumn::make('shift_opened_at')
                    ->label('Abierto hace')
                    ->formatStateUsing(fn ($state): string => Carbon::parse($state)->diffForHumans())
                    ->description(fn ($state): string => Carbon::parse($state)->format('H:i')),
                TextColumn::make('openedBy.name')
                    ->label('Abierto por')
                    ->placeholder('—'),
            ]);
    }
}
