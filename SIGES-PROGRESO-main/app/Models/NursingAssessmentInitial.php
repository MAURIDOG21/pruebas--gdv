<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class NursingAssessmentInitial extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'nursing_assessments_initial';

    protected $fillable = [
        'medical_record_id',
        'user_id',
        'somatometric_reading_id',
        'notes',
    ];

    protected $casts = [
        'notes' => 'encrypted',
    ];

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function somatometricReading(): BelongsTo
    {
        return $this->belongsTo(SomatometricReading::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('medical')->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function descriptionForEvent(string $eventName): string
    {
        return match ($eventName) {
            'created' => 'Hoja Inicial de Enfermería creada',
            'updated' => 'Hoja Inicial de Enfermería actualizada',
            'deleted' => 'Hoja Inicial de Enfermería eliminada',
            default => "Hoja Inicial de Enfermería {$eventName}",
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