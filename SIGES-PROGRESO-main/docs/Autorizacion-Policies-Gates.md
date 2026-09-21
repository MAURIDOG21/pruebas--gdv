# Autorización con Policies y Gates

## Resumen
**Objetivo:** controlar quién puede ver, crear, editar o eliminar recursos, combinando reglas de rol (RBAC) y atributos/contexto (ABAC).  
**Herramientas:** Policies (por modelo) y Gates (reglas sueltas) de Laravel, consumidas en componentes Filament.

## Cuándo usar Policies vs Gates
- **Policies**: reglas agrupadas por modelo (e.g., `MedicalRecordPolicy`, `PrescriptionPolicy`). Ideales para operaciones CRUD y consistencia en todo el proyecto.  
- **Gates**: reglas puntuales (e.g., “cambiar rol de usuario”), útiles cuando no encajan claramente en un modelo o son de alto nivel administrativo.

## Blueprint de Policies
1) Crear una Policy para cada modelo sensible: `php artisan make:policy MedicalRecordPolicy --model=MedicalRecord`.  
2) Implementar métodos estándar:
```php
class MedicalRecordPolicy
{
    public function view(User $user, MedicalRecord $record): bool
    {
        // Médicos pueden ver expedientes vinculados a sus citas
        if ($user->role === UserRole::MEDICO_GENERAL) {
            return $record->appointments()->where('doctor_id', $user->id)->exists();
        }
        // Admin/Director acceso total
        return in_array($user->role, [UserRole::ADMIN, UserRole::DIRECTOR], true);
    }

    public function update(User $user, MedicalRecord $record): bool
    {
        // Editar solo si el médico tiene relación activa y turno abierto
        if ($user->role === UserRole::MEDICO_GENERAL) {
            return $record->appointments()
                ->where('doctor_id', $user->id)
                ->whereHas('clinicSchedule', fn ($q) => $q->where('is_shift_open', true))
                ->exists();
        }
        return in_array($user->role, [UserRole::ADMIN, UserRole::DIRECTOR], true);
    }

    public function delete(User $user, MedicalRecord $record): bool
    {
        // Solo Admin/Director pueden eliminar (recomendado soft delete)
        return in_array($user->role, [UserRole::ADMIN, UserRole::DIRECTOR], true);
    }
}
```
3) Registrar Policies en `AuthServiceProvider`:
```php
protected $policies = [
    MedicalRecord::class => MedicalRecordPolicy::class,
    Appointment::class => AppointmentPolicy::class,
    Prescription::class => PrescriptionPolicy::class,
];
```

## Blueprint de Gates
- Definir reglas globales en `AuthServiceProvider::boot()`:
```php
Gate::define('edit-user-role', function (User $user, User $target) {
    // Admin/Director pueden cambiar rol; evitar auto-degradación insegura
    if (! in_array($user->role, [UserRole::ADMIN, UserRole::DIRECTOR], true)) return false;
    return $user->id !== $target->id || $user->role === UserRole::ADMIN;
});

Gate::define('close-open-shifts', function (User $user) {
    // Solo Admin/Director pueden cerrar turnos masivos
    return in_array($user->role, [UserRole::ADMIN, UserRole::DIRECTOR], true);
});
```

## Consumo en Filament
- **Acciones**: controlar visibilidad/ejecución con Policies/Gates.
```php
Action::make('change_role')
    ->visible(fn ($record) => Gate::allows('edit-user-role', $record))
```
- **Record actions estándar (Edit/Delete/View)**: Filament puede respetar Policies si el Resource está configurado; refuerza con `->visible()` cuando sea necesario.
- **Selects/Queries**: limitar opciones por rol/atributos (ABAC), p.ej. lista de médicos por roles clínicos, consultorios con turno abierto (ver `ReceptionDashboard`, `ListClinicSchedules`).

## Patrón Híbrido RBAC + ABAC
- **RBAC** (quién): roles de `UserRole` determinan base de permisos.  
- **ABAC** (cuándo/dónde): atributos de registros (`doctor_id`, `service_id`, `is_shift_open`, `visit_type`) y contexto (`date`, `shift`).  
- Ejemplo: Médicos solo editan expedientes con citas propias y turno abierto.

## Recomendaciones
- Centraliza reglas en Policies; usa Gates para casos administrativos globales.
- No depender solo de visibilidad UI; siempre validar en backend (Policies/Gates).  
- Auditar acciones autorizadas (ver `docs/AUDITORIA_Y_SEGURIDAD.md`).
- Probar rutas/acciones con cuentas de cada rol.

## Pruebas
- Usuario Médico intenta ver/editar expediente sin relación: denegado.  
- Recepcionista intenta cerrar turnos masivos: denegado.  
- Admin cambia rol de usuario: permitido; otro rol: denegado.  
- Policies respetadas en acciones de Filament (editar/eliminar).

## Referencias de Archivos
- Roles: `app/Enums/UserRole.php`  
- Panel y acciones: `app/Filament/Pages/ReceptionDashboard.php`, `app/Filament/Resources/*`  
- Ejemplos ABAC: `app/Filament/Resources/ClinicSchedules/Pages/ListClinicSchedules.php` (filtros/selecciones), `app/Filament/Pages/ReceptionDashboard.php` (consultorios por turno/fecha)
- Confirmación de contraseña: `docs/Confirmacion-Contrasena.md`  
- ABAC por roles: `docs/ABAC-por-Roles.md`