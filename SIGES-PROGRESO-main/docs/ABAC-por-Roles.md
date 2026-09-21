# ABAC por Roles (Control de Acceso Basado en Atributos y Roles)

## Resumen
**Objetivo:** asegurar que cada usuario acceda solo a los datos y acciones permitidas según su **rol** y **atributos** (propiedad de registro, turno, servicio asignado, estado, etc.).  
**Modelo híbrido:** RBAC (Roles) + ABAC (Atributos del sujeto, recurso y contexto).

## Modelo de Roles
- Enum de roles: `app/Enums/UserRole.php` define: Administrador, Director, Médico General, Nutricionista, Psicólogo, Farmacia, Enfermero, Recepcionista.  
- `User` castea `role` al enum para usar etiquetas/colores en UI (`app/Models/User.php:44-51`).

## Atributos Clave (ABAC)
- **Usuario**: `role`, `id`.  
- **Registros**: `medical_record_id`, `user_id`, `doctor_id`, `service_id`, `shift`, `date`.  
- **Contexto**: turno abierto/cerrado, activo/inactivo (`ClinicSchedule`), estado de visita (`AppointmentStatus`).

## Tácticas de Control
### 1) Visibilidad en la UI (Filament)
- Condiciones `->visible(fn () => ...)` en acciones y widgets, basadas en estado/rol/atributos.  
  Ejemplos:
  - `ReceptionDashboard`: acciones que dependen de `visit_type`, turno abierto, estatus de paciente.  
  - `ListClinicSchedules` y `ReceptionDashboard`: filtros por `shift`, `is_active`, `is_shift_open`.

### 2) Alcance de Datos (Query Scoping)
- Filtros en tablas y selects para restringir datos por atributos:  
  - `ListClinicSchedules.php:82-90`: selección de médicos limitada a roles clínicos (Médico, Nutricionista, Psicólogo, Enfermero).  
  - `ViewAppointment.php:90-105`: consultorios activos/abiertos, filtrados por `date` y `shift`.  
  - `SomatometricReadingsRelationManager`: convierte altura y limita edición a registros vinculados.

### 3) Políticas / Gates (Recomendado)
- Definir `Policies` para modelos sensibles (e.g., `MedicalRecord`, `Prescription`) y registrar en `AuthServiceProvider`:
```php
public function boot(): void
{
    Gate::define('delete-medical-record', function (User $user, MedicalRecord $record) {
        return in_array($user->role, [UserRole::ADMIN, UserRole::DIRECTOR]);
    });

    Gate::define('edit-user-role', function (User $user, User $target) {
        // Solo Admin/Director y nunca permitirse degradar su propio rol sin confirmación
        if (! in_array($user->role, [UserRole::ADMIN, UserRole::DIRECTOR])) return false;
        return $user->id !== $target->id || $user->role === UserRole::ADMIN;
    });
}
```
- Consumir gates en Filament:
```php
Action::make('change_role')
    ->visible(fn ($record) => Gate::allows('edit-user-role', $record))
```

### 4) Reglas de Formulario y Confirmación
- Confirmación de contraseña en acciones críticas (ver `docs/Confirmacion-Contrasena.md`).  
- `current_password` evita cambios maliciosos si otro obtiene la sesión.

## Ejemplos Integrados en el Proyecto
- **Recepción**: `QuickActionsWidget` y `ReceptionDashboard` filtran recursos disponibles por turno y estado.  
- **Horarios de Clínica**: selección de médicos por rol; acciones de abrir/cerrar turno condicionadas por atributos (`canBeOpened()`, `canBeClosed()`).  
- **Auditoría**: todas las acciones relevantes registradas con `causer_role` y `medical_record_id` (ver `docs/AUDITORIA_Y_SEGURIDAD.md`).

## Buenas Prácticas
- Centralizar reglas en `Policies` / `Gates` y usar condiciones UI como capa adicional, no única.
- ABAC debe considerar **propiedad** (e.g., médico solo ve sus propias citas) y **contexto** (turno, estado).  
- Evitar “super usuarios” amplios; usar roles específicos con permisos mínimos suficientes.
- Registrar cada acceso/acción sensible para auditoría.

## Roadmap Propuesto
- Implementar `Policies` para `MedicalRecord`, `Appointment`, `Prescription`, `ClinicSchedule` con decisiones ABAC:  
  - Médico puede ver/editar citas donde `doctor_id == user.id`.  
  - Recepcionista puede crear citas solo si hay turno abierto.  
  - Farmacia puede ver recetas, no eliminarlas.  
- Añadir `Scope` global opcional para filtrar por rol/propiedad en listados.
- Añadir middleware de autorización por ruta para páginas críticas.

## Referencias de Archivos
- Roles: `app/Enums/UserRole.php`  
- Usuario: `app/Models/User.php`  
- Recepción: `app/Filament/Pages/ReceptionDashboard.php`, `app/Filament/Widgets/*`  
- Horarios: `app/Filament/Resources/ClinicSchedules/*`  
- Confirmación de contraseña: `docs/Confirmacion-Contrasena.md`  
- Auditoría: `docs/AUDITORIA_Y_SEGURIDAD.md`