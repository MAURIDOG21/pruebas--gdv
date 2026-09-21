<?php

namespace App\Models;

use App\Enums\Shift; // Make sure you have this Enum
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinic_name',
        'user_id',
        'service_id',
        'shift',
        'date',
        'is_active',
        'is_shift_open',
        'shift_opened_at',
        'shift_closed_at',
        'opened_by',
        'closed_by',
        'opening_notes',
        'closing_notes',
    ];

    protected $casts = [
        'date' => 'date',
        'is_active' => 'boolean',
        'is_shift_open' => 'boolean',
        'shift_opened_at' => 'datetime',
        'shift_closed_at' => 'datetime',
        'shift' => Shift::class,
    ];

    protected static function booted(): void
    {
        static::updating(function (ClinicSchedule $model) {
            if ($model->isDirty('is_active') && $model->is_active) {
                $today = now()->toDateString();
                $exists = static::query()
                    ->where('clinic_name', $model->clinic_name)
                    ->where('shift', $model->shift)
                    ->whereDate('date', $today)
                    ->where('id', '!=', $model->id)
                    ->exists();
                if (! $exists) {
                    $model->date = $today;
                }
            }

            if ($model->isDirty('clinic_name') || $model->isDirty('shift') || $model->isDirty('date')) {
                $exists = static::query()
                    ->where('clinic_name', $model->clinic_name)
                    ->where('shift', $model->shift)
                    ->whereDate('date', $model->date instanceof \Carbon\Carbon ? $model->date->toDateString() : (string) $model->date)
                    ->where('id', '!=', $model->id)
                    ->exists();
                if ($exists) {
                    throw new \Illuminate\Validation\ValidationException(
                        validator([], [])->errors()->add('shift', 'Ya existe una asignación para ese consultorio, turno y fecha')
                    );
                }
            }
        });

        static::creating(function (ClinicSchedule $model) {
            $exists = static::query()
                ->where('clinic_name', $model->clinic_name)
                ->where('shift', $model->shift)
                ->whereDate('date', $model->date instanceof \Carbon\Carbon ? $model->date->toDateString() : (string) $model->date)
                ->exists();
            if ($exists) {
                throw new \Illuminate\Validation\ValidationException(
                    validator([], [])->errors()->add('shift', 'Ya existe una asignación para ese consultorio, turno y fecha')
                );
            }
        });
    }

    // A schedule belongs to one doctor (User)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // A schedule is for one specific Service
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    // Usuario que abrió el turno
    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    // Usuario que cerró el turno
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    // Métodos útiles para el control de turnos
    public function openShift(User $user, ?string $notes = null, bool $forceToday = false): bool
    {
        if ($this->is_shift_open) {
            return false; // Ya está abierto
        }

        $data = [
            'is_shift_open' => true,
            'shift_opened_at' => now(),
            'opened_by' => $user->id,
            'opening_notes' => $notes,
        ];

        $today = now()->toDateString();
        $currentDate = $this->date instanceof \Carbon\Carbon ? $this->date->toDateString() : (string) $this->date;
        if ($currentDate !== $today) {
            if ($forceToday) {
                $data['date'] = $today;
            } else {
                $exists = static::query()
                    ->where('clinic_name', $this->clinic_name)
                    ->where('shift', $this->shift)
                    ->whereDate('date', $today)
                    ->where('id', '!=', $this->id)
                    ->exists();
                if (! $exists) {
                    $data['date'] = $today;
                }
            }
        }

        $this->update($data);

        return true;
    }

    public function closeShift(User $user, ?string $notes = null): bool
    {
        if (!$this->is_shift_open) {
            return false; // No está abierto
        }

        $this->update([
            'is_shift_open' => false,
            'shift_closed_at' => now(),
            'closed_by' => $user->id,
            'closing_notes' => $notes,
        ]);

        return true;
    }

    public function canBeOpened(): bool
    {
        return !$this->is_shift_open && $this->is_active;
    }

    public function canBeClosed(): bool
    {
        return $this->is_shift_open;
    }

    // Scope para turnos abiertos
    public function scopeOpen($query)
    {
        return $query->where('is_shift_open', true);
    }

    // Scope para turnos cerrados
    public function scopeClosed($query)
    {
        return $query->where('is_shift_open', false);
    }
}
