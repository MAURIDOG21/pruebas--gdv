<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\AppointmentStatus; // <-- Importar el nuevo Enum
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\DB;
use App\Models\ClinicSchedule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([\App\Observers\AppointmentObserver::class])]
class Appointment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'medical_record_id',
        'service_id',
        'doctor_id',
        'ticket_number',
        'shift',
        'visit_type',
        // eliminado: 'clinic_room_number'
        'clinic_schedule_id',
        'date',
        'reason_for_visit',
        'notes',
        'status',
    ];
    
    protected $casts = [
        // Conectamos el campo 'status' a nuestro Enum
        'status' => AppointmentStatus::class,
        'date' => 'date',
        'reason_for_visit' => 'encrypted',
        'notes' => 'encrypted',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('medical')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function descriptionForEvent(string $eventName): string
    {
        return "Visita {$eventName}";
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->properties = array_merge($activity->properties->toArray(), [
            'medical_record_id' => $this->medical_record_id,
            'ip' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 255),
            'route' => optional(request()->route())->getName(),
            'causer_role' => optional($activity->causer)->role?->value,
        ]);
    }

    /**
     * Lógica para generar tickets walk-in automáticamente
     */
    protected static function booted(): void
    {
        static::creating(function (Appointment $appointment) {
            // Si no se proporciona ticket_number, generar uno local (walk-in)
            if (empty($appointment->ticket_number)) {
                $appointment->ticket_number = self::generateWalkInTicket();
            }

            // Completar service_id, doctor_id y date desde el horario diario si falta alguno
            if ($appointment->clinic_schedule_id) {
                $schedule = ClinicSchedule::find($appointment->clinic_schedule_id);
                if ($schedule) {
                    if (empty($appointment->service_id)) {
                        $appointment->service_id = $schedule->service_id;
                    }
                    if (empty($appointment->doctor_id)) {
                        $appointment->doctor_id = $schedule->user_id;
                    }
                    if (empty($appointment->date)) {
                        $appointment->date = $schedule->date;
                    }
                }
            }
        });
/*
        static::updated(function (Appointment $appointment) {
            if ($appointment->wasChanged('status')) {
                if ($appointment->status === AppointmentStatus::IN_PROGRESS) {
                    self::sendTurnosEvent('visit_started', $appointment);
                } elseif ($appointment->status === AppointmentStatus::COMPLETED) {
                    self::sendTurnosEvent('visit_completed', $appointment);
                }
            }
        });
        */
    }

    /**
     * Genera un ticket local para pacientes walk-in
     */
    public static function generateWalkInTicket(): string
    {
        $year = now()->format('Y');
        $prefix = 'LOCAL-' . $year . '-';

        // Obtiene el último ticket del año y calcula el siguiente correlativo
        $lastTicket = self::where('ticket_number', 'like', $prefix . '%')
            ->orderBy('ticket_number', 'desc')
            ->value('ticket_number');

        $nextNumber = 1;
        if (! empty($lastTicket)) {
            $parts = explode('-', $lastTicket);
            $lastNumber = (int) ($parts[count($parts) - 1] ?? 0);
            $nextNumber = $lastNumber + 1;
        }

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    protected static function sendTurnosEvent(string $type, Appointment $a): void
    {
        $url = config('services.turnos.webhook_url');
        if (! $url) {
            return;
        }

        $a->loadMissing(['clinicSchedule', 'service', 'doctor']);

        $payload = [
            'type' => $type,
            'ticket_number' => $a->ticket_number,
            'status' => $a->status?->value,
            'date' => $a->date?->toDateString(),
            'shift' => is_string($a->shift) ? $a->shift : ($a->shift?->value ?? null),
            'clinic' => [
                'schedule_id' => $a->clinic_schedule_id,
                'clinic_name' => optional($a->clinicSchedule)->clinic_name,
                'service' => optional($a->service)->name,
                'doctor' => optional($a->doctor)->name,
                'schedule_date' => optional($a->clinicSchedule)->date?->toDateString(),
                'schedule_shift' => optional($a->clinicSchedule)->shift?->value,
            ],
        ];

        $request = Http::asJson();
        $token = config('services.turnos.api_token');
        if ($token) {
            $request = $request->withToken($token);
        }

        try {
            $response = $request->post($url, $payload);
            Log::info('Turnos webhook sent', [
                'url' => $url,
                'status' => $response->status(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Turnos webhook failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verifica si el ticket es de tipo walk-in
     */
    public function isWalkIn(): bool
    {
        return str_starts_with($this->ticket_number, 'LOCAL-');
    }

    /**
     * Relación: Una cita pertenece a un Expediente Médico.
     */
    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    /**
     * Relación: Una cita corresponde a un Servicio.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Relación: Una cita es atendida por un Doctor (Usuario).
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Relación: Una cita pertenece a un Horario Diario activo.
     */
    public function clinicSchedule(): BelongsTo
    {
        return $this->belongsTo(ClinicSchedule::class);
    }

    public function nursingEvolutions()
    {
        return $this->hasMany(NursingEvolution::class);
    }
}
