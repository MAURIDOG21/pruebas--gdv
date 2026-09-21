<?php

namespace App\Models;

use App\Enums\Shift; // <-- Importamos el Enum de Turno que ya existe
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'cost',
        'department',
        'is_active',
        'schedule',       // <-- Campo añadido
        'shift',          // <-- Campo añadido
        'responsible_id', // <-- Campo añadido
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'is_active' => 'boolean',
        'shift' => Shift::class, // <-- Le decimos a Laravel que use nuestro Enum
    ];

    /**
     * Relación: Un servicio tiene un responsable (que es un Usuario).
     */
    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    /**
     * Relación: Un servicio puede ser asignado a muchos pacientes.
     */
    public function patients()
    {
        return $this->belongsToMany(Patient::class)->withTimestamps();
    }
}