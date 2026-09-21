<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class NursingEvolution extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'medical_record_id',
        'appointment_id',
        'user_id',
        'problem',
        'subjective',
        'objective',
        'analysis',
        'plan',
        'somatometric_reading_id',
    ];

    protected $casts = [
        'problem' => 'encrypted',
        'subjective' => 'encrypted',
        'objective' => 'encrypted',
        'analysis' => 'encrypted',
        'plan' => 'encrypted',
    ];

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function somatometricReading(): BelongsTo
    {
        return $this->belongsTo(SomatometricReading::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('medical')->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function descriptionForEvent(string $eventName): string
    {
        return match ($eventName) {
            'created' => 'Evolución de Enfermería creada',
            'updated' => 'Evolución de Enfermería actualizada',
            'deleted' => 'Evolución de Enfermería eliminada',
            default => "Evolución de Enfermería {$eventName}",
        };
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
}