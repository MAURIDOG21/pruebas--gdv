<?php

namespace App\Enums;

enum EmployeeStatus: string
{
    case SINDICALIZADO = 'sindicalizado';
    case CONFIANZA = 'confianza';

    public static function getOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            // La clave es el valor que se guarda (ej: 'sindicalizado')
            // El valor es una etiqueta amigable para el usuario (ej: 'Sindicalizado')
            $options[$case->value] = ucfirst(str_replace('_', ' ', strtolower($case->name)));
        }
        return $options;
    }
}