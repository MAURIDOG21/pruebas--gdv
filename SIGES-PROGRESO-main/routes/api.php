<?php

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
   /*
    Route::get('/patients/search', [ApiController::class, 'searchPatient']);
    Route::post('/appointments', [ApiController::class, 'storeAppointment']);
    Route::post('/patients/request', [ApiController::class, 'requestNewPatient']);
*/
   // La búsqueda sigue igual en su URL, pero ahora es más potente por dentro.
   Route::get('/patients/search', [ApiController::class, 'searchPatient']);

   // La nueva ruta "todo en uno" para registrar visitas.
   Route::post('/visits', [ApiController::class, 'storeVisit']);
});


