<?php

namespace App\Filament\Resources\ClinicSchedules\Schemas;

use App\Enums\Shift;
use App\Enums\UserRole;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ClinicScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('clinic_name')
                    ->label('Consultorio')
                    ->required(),
                Select::make('user_id')
                    ->label('Médico')
                    ->relationship('user', 'name', modifyQueryUsing: function ($query) {
                        $query->whereIn('role', [
                            UserRole::MEDICO_GENERAL->value,
                            UserRole::NUTRICIONISTA->value,
                            UserRole::PSICOLOGO->value,
                            UserRole::ENFERMERO->value,
                        ]);
                    })
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required(),
                Select::make('service_id')
                    ->label('Servicio')
                    ->relationship('service', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required(),
                Radio::make('shift')
                    ->label('Turno')
                    ->options(Shift::getOptions())
                    ->inline()
                    ->required(),
                DatePicker::make('date')
                    ->label('Fecha')
                    ->default(now())
                    ->required(),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(false),
            ]);
    }
}
