<?php

namespace App\Enums;

enum PatientType: string
{
    case EXTERNAL = 'Externo';
    case EMPLOYEE = 'Empleado';
    case EMPLOYEE_DEPENDENT = 'Hijo de Empleado';
    case PEDIATRIC = 'Pediátrico';
    public static function getOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            // La clave es el valor que se guarda (ej: 'empleado')
            // El valor es una etiqueta amigable para el usuario (ej: 'Empleado')
            $options[$case->value] = $case->value;
        }
        return $options;
    }
}