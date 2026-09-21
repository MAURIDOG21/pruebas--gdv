<?php

namespace App\Filament\Resources\MedicalRecords\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ActivityLogRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';
    protected static ?string $title = 'Historial de Cambios';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('description')->label('Acción')->wrap(),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Usuario')
                    ->getStateUsing(fn ($record) => optional($record->causer)->name)
                    ->tooltip(fn ($record) => 'Rol: ' . ($record->properties['causer_role'] ?? '—') . ' • IP: ' . ($record->properties['ip'] ?? '—')),
                Tables\Columns\TextColumn::make('created_at')->label('Fecha')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('changes')
                    ->label('Cambios Realizados')
                    ->wrap()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $new = (array) ($record->properties['attributes'] ?? []);
                        $old = (array) ($record->properties['old'] ?? []);
                        $lines = [];
                        foreach ($new as $key => $newVal) {
                            $oldVal = $old[$key] ?? null;
                            $lines[] = sprintf("%s: %s -> %s", $key, $this->maskField($key, $oldVal), $this->maskField($key, $newVal));
                        }
                        return implode("\n", $lines);
                    }),
                Tables\Columns\TextColumn::make('properties.ip')
                    ->label('IP')
                    ->getStateUsing(fn ($record) => $record->properties['ip'] ?? null),
                Tables\Columns\TextColumn::make('properties.user_agent')
                    ->label('Agente')
                    ->wrap()
                    ->limit(60)
                    ->getStateUsing(fn ($record) => $record->properties['user_agent'] ?? null),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }

    private function maskField(string $key, $value): string
    {
        if ($value === null) return '';
        $v = is_scalar($value) ? (string) $value : json_encode($value);
        $sensitive = [
            'full_name','curp','contact_phone','address',
            'disability_details','diagnosis','notes',
            'reason_for_visit','file_path',
        ];
        if (in_array($key, $sensitive, true)) {
            $len = mb_strlen($v);
            return $len <= 4 ? str_repeat('*', $len) : str_repeat('*', max(0, $len - 4)) . mb_substr($v, -4);
        }
        return $v;
    }
}
