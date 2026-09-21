# Auditoría y Seguridad

## Resumen Ejecutivo
**Librería:** `spatie/laravel-activitylog`  
**Propósito:** asegurar trazabilidad completa de cambios (quién, cuándo, qué), cumplir con requerimientos normativos y habilitar investigación forense en caso de incidentes de seguridad.

El sistema registra automáticamente eventos relevantes sobre los modelos clínicos y administrativos, enriquecidos con metadatos de contexto (IP, User Agent, ruta, rol del usuario y el `medical_record_id` asociado).

---

## Estándar de Implementación (Blueprint)
**Patrón en modelos:**
- Incluir `use Spatie\Activitylog\Traits\LogsActivity;`
- Implementar `getActivitylogOptions()` con:
  - `useLogName('medical')`: canal lógico para los logs clínicos
  - `logFillable()`: registra cambios en atributos declarados como fillable
  - `logOnlyDirty()`: solo registra diferencias (no repite valores idénticos)
  - `dontSubmitEmptyLogs()`: evita entradas sin cambios
- Añadir una descripción legible por evento con `descriptionForEvent(string $eventName)`
- Inyectar metadatos de seguridad con `tapActivity(Spatie\Activitylog\Models\Activity $activity, string $eventName)`

**Eventos registrados por defecto:**
- `created`, `updated`, `deleted` (propios del ciclo de vida de Eloquent)

**Opciones clave:**
- `logOnlyDirty()` guarda únicamente atributos que cambiaron; esto reduce ruido y mejora la lectura y el almacenamiento.
- `dontSubmitEmptyLogs()` previene entradas vacías cuando un `save()` no modificó ningún atributo relevante.

---

## Estandarización de Metadatos (Contexto de Seguridad)
**Metadatos inyectados en cada actividad (propiedad JSON `properties`):**
- `medical_record_id`: Vinculación lógica al expediente médico
- `ip`: Dirección IP del cliente
- `user_agent`: Agente de usuario (recortado a 255 caracteres)
- `route`: Nombre de la ruta que originó la operación
- `causer_role`: Rol del usuario que causó el evento

**Razón de seguridad forense:**
- Permite reconstruir la escena del cambio (quién, desde dónde, bajo qué rol y en qué flujo).  
- Simplifica correlación de eventos con registros de red, SIEM y controles internos.

---

## Arquitectura de Datos
**Modelos bajo auditoría (con `LogsActivity`):**

| Modelo | Descripción de evento (`descriptionForEvent`) | Log name |
|---|---|---|
| `Appointment` | "Visita {created/updated/deleted}" | `medical` |
| `MedicalRecord` | "Expediente {created/updated/deleted}" | `medical` |
| `Patient` | "Paciente {created/updated/deleted}" | `medical` |
| `Tutor` | "Tutor {created/updated/deleted}" | `medical` |
| `SomatometricReading` | "Somatometría {created/updated/deleted}" | `medical` |
| `MedicalDocument` | "Documento {created/updated/deleted}" | `medical` |
| `NursingAssessment` | "Valoración {created/updated/deleted}" | `medical` |
| `NursingAssessmentInitial` | "Hoja Inicial de Enfermería {created/updated/deleted}" | `medical` |
| `MedicalInitialAssessment` | "Hoja Inicial Médica {created/updated/deleted}" | `medical` |
| `NursingEvolution` | "Evolución de Enfermería {created/updated/deleted}" | `medical` |
| `Prescription` | "Receta {created/updated/deleted}" | `medical` |

**Sujeto vs Causante:**
- **Sujeto** (`subject_type`, `subject_id`): el modelo afectado por el evento; determina dónde se ancla la actividad.
- **Causante** (`causer_type`, `causer_id`): el usuario (u otro agente) que originó el cambio.
- **Propiedades** (`properties` JSON): metadatos adicionales (seguridad y contexto), incluimos `medical_record_id` para correlación con expediente.

**Almacenamiento:**
- Tabla: `activity_log` (configurable en `config/activitylog.php`)
- Estructura: claves para sujeto/causante, `log_name`, `event`, `description`, `properties`, timestamps

---

## Visualización en el Panel (UI)
**Componente:** `app/Filament/Resources/MedicalRecords/RelationManagers/ActivityLogRelationManager.php`

- Muestra el historial dentro del expediente médico a través de la relación `activities` del modelo `MedicalRecord`.
- Columnas:
  - `Acción`: `description`
  - `Usuario`: nombre del causante, con tooltip que incluye `Rol` e `IP`
  - `Fecha`: `created_at` con orden descendente
  - `Cambios Realizados`: genera líneas tipo `campo: anterior -> nuevo`; con mascarado para campos sensibles (diagnóstico, notas, file_path, etc.)
  - `Agente`: `user_agent` (recortado)
- Filtrado por expediente: se lista únicamente el historial cuyo **sujeto** es el `MedicalRecord` actual; el metadato `medical_record_id` permite correlación con eventos de otros modelos en análisis externos.

---

## Guía para Desarrolladores
**Agregar auditoría a un modelo nuevo**

1) Añade el trait y opciones básicas:
```php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class ExampleModel extends Model
{
    use LogsActivity;

    protected $fillable = [ /* ... */ ];

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
        return match ($eventName) {
            'created' => 'Ejemplo creado',
            'updated' => 'Ejemplo actualizado',
            'deleted' => 'Ejemplo eliminado',
            default => "Ejemplo {$eventName}",
        };
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->properties = array_merge($activity->properties->toArray(), [
            'medical_record_id' => $this->medical_record_id ?? null,
            'ip' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 255),
            'route' => optional(request()->route())->getName(),
            'causer_role' => optional($activity->causer)->role?->value,
        ]);
    }
}
```

2) Declara `fillable` para los campos que deseas auditar (solo se registran cambios en estos).

3) Si el modelo está directamente asociado a un `MedicalRecord`, asegúrate de tener el campo `medical_record_id` para facilitar correlación.

**Buenas prácticas:**
- Usa `descriptionForEvent` con textos claros orientados al personal médico y auditoría.
- Mantén la lista de campos sensibles en el componente de UI para continuar el mascarado.
- Define `log_name` consistente (`medical`) para consultas agregadas y filtrado.

---

## Configuración y Mantenimiento
- Archivo: `config/activitylog.php`
  - `enabled`: activar/desactivar el sistema
  - `table_name`: nombre de tabla (por defecto `activity_log`)
  - `activity_model`: clase de actividad
  - `delete_records_older_than_days`: retención (p.ej. 365)
- Migración: crea tabla `activity_log` con índices y morphs.
- Monitoreo: considera exportar los logs hacia sistemas de SIEM o backup para cumplimiento.

---

## Referencias
- Spatie Activitylog: https://spatie.be/docs/laravel-activitylog
- Componentes del proyecto:
  - RelationManager de actividad: `app/Filament/Resources/MedicalRecords/RelationManagers/ActivityLogRelationManager.php`
  - Modelos con auditoría: ver lista anterior en "Arquitectura de Datos"