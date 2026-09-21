<?php

namespace App\Models;

use App\Enums\MedicalLeaveStatus; // <-- Importar el Enum
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB; // <-- Importar DB
use Illuminate\Support\Facades\Auth; // <-- Importar Auth
use Illuminate\Database\Eloquent\SoftDeletes; // <-- 1. AÑADE ESTA LÍNEA

class MedicalLeave extends Model
{
    use SoftDeletes; // <-- 2. AÑADE ESTA LÍNEA
    protected $fillable = [
        // 'folio' no va aquí porque se genera automáticamente
        'medical_record_id',
        'doctor_id',
        'issue_date',
        'start_date',
        'end_date',
        'reason',
        'issuing_department',
        'status',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => MedicalLeaveStatus::class, // <-- ¡Conectamos el Enum!
    ];

    /**
     * Lógica para la generación automática del folio.
     */
    protected static function booted(): void
    {
        static::creating(function (MedicalLeave $leave) {
            // Asigna automáticamente el doctor_id si no está definido
            if (empty($leave->doctor_id)) {
                $user = Auth::user();
                if ($user) {
                    $leave->doctor_id = $user->id;
                }
            }
            
            // Se asegura de que el folio no se asigne si ya existe (aunque es improbable)
            if (empty($leave->folio)) {
                $nextVal = DB::select("select nextval('medical_leave_folio_seq')")[0]->nextval;
                // Formato de Folio: INC-AÑO-NUMERO (ej. INC-2025-00001)
                $leave->folio = 'INC-' . now()->year . '-' . str_pad($nextVal, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Relación: Una incapacidad pertenece a un Expediente Médico.
     */
    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    /**
     * Relación: Una incapacidad es emitida por un Doctor (Usuario).
     */
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}