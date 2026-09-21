<?php

namespace App\Filament\Resources\Services\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
class ServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('department')
                    ->label('Departamento')
                    ->searchable(),
                TextColumn::make('responsible.name') // Usamos la relación para mostrar el nombre
                    ->label('Responsable')
                    ->searchable(),
                TextColumn::make('cost')
                    ->label('Costo')
                    ->money('MXN') // Formato de moneda
                    ->sortable(),
                IconColumn::make('is_active') // <-- MEJORA DE UX
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
