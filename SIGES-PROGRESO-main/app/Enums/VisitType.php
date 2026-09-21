<?php

namespace App\Enums;

enum VisitType: string
{
    case PRIMERA_VEZ = 'Primera Vez';
    case SUBSECUENTE = 'Subsecuente';

    // MÉTODO CORREGIDO
    public static function getOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            // La clave es el valor que se guarda (ej: 'primera_vez')
            // El valor es una etiqueta amigable para el usuario (ej: 'Primera vez')
            $options[$case->value] = ucfirst(str_replace('_', ' ', strtolower($case->name)));
        }
        return $options;
    }
}