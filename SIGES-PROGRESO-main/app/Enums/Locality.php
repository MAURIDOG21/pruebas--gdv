<?php

namespace App\Enums;

enum Locality: string
{
    case CAMPESTRE_FLAMBOYANES = 'Campestre Flamboyanes';
    case CHELEM = 'Chelem';
    case CHICXULUB_PUERTO = 'Chicxulub Puerto';
    case CHUBURNA_PUERTO = 'Chuburná Puerto';
    case ELENA = 'Elená';
    case PROGRESO_DE_CASTRO = 'Progreso de Castro';
    case SAN_IGNACIO = 'San Ignacio';
    case XTUL = 'Xtul';

    public static function getOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->value;
        }
        return $options;
    }
}
