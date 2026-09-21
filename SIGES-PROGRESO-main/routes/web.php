<?php
use App\Http\Controllers\PdfGeneratorController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/dashboard/login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/medical-leaves/{medicalLeaveId}/download/{copyType}', [PdfGeneratorController::class, 'downloadMedicalLeave'])
        ->name('medical-leave.download');

    Route::get('/prescriptions/{prescriptionId}/download/{copyType}', [PdfGeneratorController::class, 'downloadPrescription'])
        ->name('prescription.download');
});