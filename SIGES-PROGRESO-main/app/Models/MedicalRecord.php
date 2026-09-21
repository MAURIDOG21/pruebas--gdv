<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Database\Eloquent\Relations\MorphMany;

// Enums
use App\Enums\PatientType;
use App\Enums\EmployeeStatus;

class MedicalRecord extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'patient_id',
        'record_number',
        'patient_type',
        'employee_status',
        'consent_form_path',
    ];

    protected $casts = [
        'patient_type' => PatientType::class,
        'employee_status' => EmployeeStatus::class,
        'consent_form_path' => 'encrypted',
    ];

    protected static function booted(): void
    {
        static::creating(function (MedicalRecord $record) {
            if (empty($record->record_number)) {
                $nextValResult = DB::select("select nextval('medical_record_number_seq')");
                $nextVal = $nextValResult[0]->nextval;
                $record->record_number = 'EXP-' . str_pad($nextVal, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    // Relaciones
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function medicalLeaves()
    {
        return $this->hasMany(MedicalLeave::class);
    }

    public function somatometricReadings()
    {
        return $this->hasMany(SomatometricReading::class);
    }

    public function nursingAssessment() {
    return $this->hasOne(NursingAssessment::class);
    }
    public function medicalDocuments() {
        return $this->hasMany(MedicalDocument::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function nursingAssessmentInitial()
    {
        return $this->hasOne(NursingAssessmentInitial::class);
    }

    public function medicalInitialAssessment()
    {
        return $this->hasOne(MedicalInitialAssessment::class);
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

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
        return "Expediente {$eventName}";
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->properties = array_merge($activity->properties->toArray(), [
            'medical_record_id' => $this->id,
            'ip' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 255),
            'route' => optional(request()->route())->getName(),
            'causer_role' => optional($activity->causer)->role?->value,
        ]);
    }
}
