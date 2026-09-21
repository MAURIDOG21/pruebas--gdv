<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\MedicalLeave;
use App\Models\Prescription;
use Barryvdh\DomPDF\Facade\Pdf; // Importar la clase PDF

class PdfGeneratorController extends Controller
{
    public function downloadMedicalLeave($medicalLeaveId, $copyType)
        {
            // Validar que el tipo de copia sea uno de los permitidos
            if (!in_array($copyType, ['patient', 'institution'])) {
                abort(404, 'Tipo de copia no válido.');
            }

            // Cargar la incapacidad con sus relaciones para optimizar consultas
            $medicalLeave = MedicalLeave::with(['medicalRecord.patient', 'doctor'])->findOrFail($medicalLeaveId);

            // Cargar la vista y pasarle los datos
            $pdf = Pdf::loadView('pdf.medical_leave', [
                'medicalLeave' => $medicalLeave,
                'copyType' => $copyType,
            ]);

            // Generar un nombre de archivo dinámico
            $fileName = "incapacidad-{$medicalLeave->folio}-{$copyType}.pdf";

            // Descargar el PDF en el navegador del usuario
            return $pdf->download($fileName);
        }

    public function downloadPrescription($prescriptionId, $copyType)
        {
            if (!in_array($copyType, ['patient', 'institution'])) {
                abort(404, 'Tipo de copia no válido.');
            }

            $prescription = Prescription::with(['medicalRecord.patient', 'doctor'])->findOrFail($prescriptionId);

            $pdf = Pdf::loadView('pdf.prescription', [
                'prescription' => $prescription,
                'copyType' => $copyType,
            ]);

            $fileName = "receta-{$prescription->folio}-{$copyType}.pdf";

            return $pdf->download($fileName);
        }
}





    