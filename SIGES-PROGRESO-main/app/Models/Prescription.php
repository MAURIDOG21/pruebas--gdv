<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class Prescription extends Model
{
    use SoftDeletes, HasFactory, LogsActivity;

    protected $fillable = [
        'medical_record_id',
        'appointment_id',
        'doctor_id',
        'folio',
        'issue_date',
        'diagnosis',
        'notes',
        'items',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'diagnosis' => 'encrypted',
        'notes' => 'encrypted',
        'items' => 'encrypted:json',
    ];

    protected static function booted(): void
    {
        static::creating(function (Prescription $prescription) {
            if (empty($prescription->doctor_id)) {
                $user = Auth::user();
                if ($user) {
                    $prescription->doctor_id = $user->id;
                }
            }

            if (empty($prescription->folio)) {
                $nextVal = DB::select("select nextval('prescription_folio_seq')")[0]->nextval;
                $prescription->folio = 'REC-' . now()->year . '-' . str_pad($nextVal, 5, '0', STR_PAD_LEFT);
            }
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
        return "Receta {$eventName}";
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

    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
