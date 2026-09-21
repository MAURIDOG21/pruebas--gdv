<?php

namespace App\Http\Controllers;

// --- Imports necesarios ---
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\User;
use Filament\Actions\Action;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction; // Alias para la acción de la notificación
use App\Filament\Resources\Appointments\AppointmentResource;
class ApiController extends Controller
{
    /**
     * El "Traductor de Identidades" actualizado.
     * Ahora devuelve explícitamente el N° de Expediente.
     */
    public function searchPatient(Request $request): JsonResponse
    {
        $curp = $request->query('curp');
        // El otro sistema ahora buscará por 'record_number'
        $recordNumber = $request->query('record_number'); 

        if (!$curp && !$recordNumber) {
            return response()->json(['message' => 'Se requiere un CURP o número de expediente para la búsqueda.'], 400);
        }

        $patientQuery = Patient::query();

          if ($curp) {
              $curpHash = hash('sha256', strtoupper(trim($curp)));
              $patientQuery->where('curp_hash', $curpHash);
          }
        
        if ($recordNumber) {
            // Buscamos a través de la relación del expediente
            $patientQuery->whereHas('medicalRecord', function ($query) use ($recordNumber) {
                $query->where('record_number', $recordNumber);
            });
        }
        
        // Cargamos la relación para poder acceder al número de expediente
        $patient = $patientQuery->with('medicalRecord')->first();

        if (!$patient) {
            return response()->json(['message' => 'Paciente no encontrado en SIGES-PROGRESO.'], 404);
        }

        return response()->json([
            'id' => $patient->id,
            'full_name' => $patient->full_name,
            'curp' => $patient->curp,
            'contact_phone' => $patient->contact_phone,
            // Devolvemos el N° de Expediente, que es la clave para la siguiente llamada
            'record_number' => $patient->medicalRecord->record_number, 
        ]);
    }

    /**
     * El método "Todo en Uno" refactorizado para la nueva arquitectura.
     */
    public function storeVisit(Request $request): JsonResponse
{
    $data = $request->validate([
        'record_number' => ['nullable', 'string', 'exists:medical_records,record_number'],
        'medical_record_id' => ['nullable', 'integer', 'exists:medical_records,id'],
        'ticket_number' => ['required', 'string', 'max:255', 'unique:appointments,ticket_number'],
        'service_id' => ['required', 'integer', 'exists:services,id'],
        'reason_for_visit' => ['required', 'string'],
    ]);

    DB::beginTransaction();
    try {
        $medicalRecord = null;
        $newPatientCreated = false;

        if (!empty($data['medical_record_id'])) {
            $medicalRecord = MedicalRecord::find($data['medical_record_id']);
        } elseif (!empty($data['record_number'])) {
            $medicalRecord = MedicalRecord::where('record_number', $data['record_number'])->first();
        } else {
            $patient = Patient::create(['status' => 'pending_review']);
            $medicalRecord = MedicalRecord::where('patient_id', $patient->id)->first();
            if (! $medicalRecord) {
                try {
                    $medicalRecord = MedicalRecord::firstOrCreate(['patient_id' => $patient->id], []);
                } catch (\Illuminate\Database\QueryException $e) {
                    $medicalRecord = MedicalRecord::where('patient_id', $patient->id)->first();
                }
            }
            $newPatientCreated = true;
        }

        if (!$medicalRecord) {
            DB::rollBack();
            return response()->json(['message' => 'No se pudo resolver o crear el expediente médico.'], 422);
        }

        // Crear usando ID explícito; evita nulos en relaciones
        $appointment = Appointment::create([
            'medical_record_id' => $medicalRecord->id,
            'service_id' => $data['service_id'],
            'ticket_number' => $data['ticket_number'],
            'reason_for_visit' => $data['reason_for_visit'],
            'status' => \App\Enums\AppointmentStatus::PENDING,
            'date' => now()->toDateString(),
        ]);

        DB::commit();

        // Notificación opcional (ajusta destinatarios según tu flujo)
        $recipients = User::all();
        \Filament\Notifications\Notification::make()
            ->title('Nueva Visita Registrada')
            ->body("Ticket #{$appointment->ticket_number} • Servicio '{$appointment->service->name}' • Motivo '{$appointment->reason_for_visit}'")
            ->icon('heroicon-s-ticket')
            ->info()
            ->duration(8000)
            ->actions([
                Action::make('view')
                    ->label('Ver Visita')
                    ->url(AppointmentResource::getUrl('view', ['record' => $appointment]))
                    ->markAsRead()
                    ->button(),
            ])
            ->sendToDatabase($recipients);

        return response()->json([
            'message' => 'Visita registrada exitosamente.',
            'appointment_id' => $appointment->id,
            'medical_record_id' => $medicalRecord->id,
            'new_patient_created' => $newPatientCreated,
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $ve) {
        DB::rollBack();
        return response()->json(['message' => 'Datos inválidos', 'errors' => $ve->errors()], 422);
    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json(['message' => 'Error interno al procesar la visita.', 'error' => $e->getMessage()], 500);
    }
}
}
