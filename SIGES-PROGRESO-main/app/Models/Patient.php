<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Cambiado
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

//enums
use App\Enums\Locality;

use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class Patient extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
        'tutor_id',
        'full_name',
        'date_of_birth',
        'sex',
        'curp',
        'locality',
        'colonia',
        'contact_phone',
        'address',
        'has_disability',
        'disability_details',
        'status',
        'chronic_diseases',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'has_disability' => 'boolean',
        'locality' => Locality::class,
        'status' => 'string',
        'full_name' => 'encrypted',
        'curp' => 'encrypted',
        'contact_phone' => 'encrypted',
        'address' => 'encrypted',
        'disability_details' => 'encrypted',
        'chronic_diseases' => 'array',

    ];
    // Este accesor ahora es la única fuente de verdad para la edad. ¡Perfecto!
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? Carbon::parse($this->date_of_birth)->age : null;
    }

    /**
     * Un Paciente ahora PERTENECE A un Tutor.
     */
    public function tutor(): BelongsTo
    {
        return $this->belongsTo(Tutor::class);
    }

    /**
     * Relación uno a uno con su expediente médico.
     */
    public function medicalRecord(): HasOne
    {
        return $this->hasOne(MedicalRecord::class);
    }
    protected static function booted(): void
    {

        static::created(function (Patient $patient) {
            try {
                \App\Models\MedicalRecord::firstOrCreate([
                    'patient_id' => $patient->id,
                ], []);
            } catch (\Illuminate\Database\QueryException $e) {
                \App\Models\MedicalRecord::where('patient_id', $patient->id)->first();
            }
        });

        static::saving(function (Patient $patient) {
            $curp = $patient->curp;
            $patient->curp_hash = $curp ? hash('sha256', strtoupper(trim($curp))) : null;
        });
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
        return "Paciente {$eventName}";
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->properties = array_merge($activity->properties->toArray(), [
            'medical_record_id' => optional($this->medicalRecord)->id,
            'ip' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 255),
            'route' => optional(request()->route())->getName(),
            'causer_role' => optional($activity->causer)->role?->value,
        ]);
    }
}
