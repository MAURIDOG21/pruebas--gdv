<?php

namespace App\Filament\Resources\Services\Schemas;

use Filament\Schemas\Schema;
use App\Filament\Resources\ServiceResource\Pages;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Enums\Shift; // Importar el Enum de Turno

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Components\Section;
class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre del Servicio')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),
/*
                // --- MEJORAS DE UX ---
                Select::make('shift')
                    ->label('Turno')
                    ->options(Shift::getOptions()), // Usamos nuestro Enum

                Select::make('responsible_id')
                    ->label('Responsable')
                    ->relationship('responsible', 'name') // Le decimos que use la relación
                    ->searchable()
                    ->preload(),
*/
                Toggle::make('is_active')
                    ->label('Servicio Activo')
                    ->required(),
            ]);
    }
}
