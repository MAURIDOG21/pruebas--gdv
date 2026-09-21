<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sistema de Turnos API
    |--------------------------------------------------------------------------
    |
    | Aquí se definen las credenciales para conectarse a la API del
    | sistema de turnos externo.
    |
    */

    'url' => env('TURNOS_API_URL'),

    'token' => env('TURNOS_API_TOKEN'),
    'enabled' => env('TURNOS_ENABLED', true), // Por defecto true en producción
];