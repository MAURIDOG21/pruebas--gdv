<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel, HasColor
{
    case ADMIN = 'administrador';
    case DIRECTOR = 'director';
    case MEDICO_GENERAL = 'medico general';
    case NUTRICIONISTA = 'nutricionista';
    case PSICOLOGO = 'psicologo';
    case FARMACIA = 'farmacia';
    case ENFERMERO = 'enfermero';
    case RECEPCIONISTA = 'recepcionista';

    public function getLabel(): ?string
    {
        return match($this) {
            self::ADMIN => 'Administrador',
            self::DIRECTOR => 'Director',
            self::MEDICO_GENERAL => 'Médico General',
            self::NUTRICIONISTA => 'Nutricionista',
            self::PSICOLOGO => 'Psicólogo',
            self::FARMACIA => 'Farmacia',
            self::ENFERMERO => 'Enfermero',
            self::RECEPCIONISTA => 'Recepcionista',
        };
    }

    public function getColor(): ?string
    {
        return match($this) {
            self::ADMIN => 'danger',
            self::DIRECTOR => 'danger',
            self::MEDICO_GENERAL => 'info',
            self::NUTRICIONISTA => 'success',
            self::PSICOLOGO => 'warning',
            self::FARMACIA => 'gray',
            self::ENFERMERO => 'primary',
            self::RECEPCIONISTA => 'gray',
        };
    }
}
