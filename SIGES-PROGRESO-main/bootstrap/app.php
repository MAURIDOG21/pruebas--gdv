<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        // --- ¡LA LÍNEA CLAVE ESTÁ AQUÍ! ---
        // Le decimos a Laravel dónde encontrar nuestras rutas de API.
        api: __DIR__.'/../routes/api.php', 
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'check.shift' => \App\Http\Middleware\CheckShiftStatus::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
