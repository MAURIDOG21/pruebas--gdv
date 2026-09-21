<?php

namespace App\Enums;

enum Shift: string
{
    case MATUTINO = 'matutino';
    case VESPERTINO = 'vespertino';
    case NOCTURNO = 'nocturno';
    case FIN_DE_SEMANA = 'fin_de_semana';

    // MÉTODO CORREGIDO
    public static function getOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            // La clave es el valor que se guarda (ej: 'matutino')
            // El valor es una etiqueta amigable para el usuario (ej: 'Matutino')
            $options[$case->value] = ucfirst(str_replace('_', ' ', strtolower($case->name)));
        }
        return $options;
    }
}