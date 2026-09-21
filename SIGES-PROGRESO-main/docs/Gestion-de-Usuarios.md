# Gestión de Usuarios (Enfoque de Ciberseguridad)

## Resumen
- Objetivo: asegurar el ciclo de vida de cuentas (alta, edición, cambio de rol, eliminación, autenticación) con controles de seguridad consistentes.
- Controles aplicados: 2FA obligatorio opcional por usuario, confirmación de contraseña en acciones críticas, mínimo privilegio por roles, hashing de contraseñas, limitadores de login, auditoría operativa.

## Roles y Privilegios
- Enum de roles: `app/Enums/UserRole.php` define Administrador, Director, Médico General, Nutricionista, Psicólogo, Farmacia, Enfermero, Recepcionista.
- Selección de rol en creación/edición: `app/Filament/Resources/Users/Schemas/UserForm.php:29-33` usa `UserRole::class` para opciones tipadas.
- Sugerencia: aplicar políticas/gates para restringir cambios de rol a Admin/Director (ver `docs/ABAC-por-Roles.md`).

## Autenticación y 2FA
- Trait: `Laravel\Fortify\TwoFactorAuthenticatable` en `app/Models/User.php:11,18`.
- Secretos 2FA y códigos de recuperación se encriptan y se ocultan en serialización (`$hidden`): `app/Models/User.php:37-42`.
- Gestión de 2FA en el panel:
  - Página de seguridad: `app/Filament/Pages/SecuritySettings.php` (habilitar, confirmar, regenerar, deshabilitar 2FA).
  - Reto 2FA tras login: middleware `twofactor.verify` (`app/Http/Middleware/RequireTwoFactorVerified.php`) redirige a `app/Filament/Pages/TwoFactorChallenge.php`.
- Documentación complementaria: `docs/Autenticacion-2FA.md`.

## Hashing de Contraseñas
- Cast automático: `app/Models/User.php:49-56` — `password` se guarda hasheado.
- Fortify habilita `updatePasswords` para cambios seguros (ver `config/fortify.php:71-86`).

## Confirmación de Contraseña en Acciones Críticas
- Protección de eliminación y cambio de rol:
  - Tabla de usuarios: `app/Filament/Resources/Users/Tables/UsersTable.php:75-83` incluye `DeleteAction` con formulario `password` (`current_password`) y acción `change_role` con confirmación y contraseña.
  - Edición de usuario: `app/Filament/Resources/Users/Pages/EditUser.php:16-18` con `DeleteAction` protegido.
- Expedientes (referencia): `app/Filament/Resources/MedicalRecords/Tables/MedicalRecordsTable.php:65-73` y `Pages/EditMedicalRecord.php:16-19` protegen `DeleteAction` con contraseña.
- Deshabilitar 2FA: `app/Filament/Pages/SecuritySettings.php:81-94` exige contraseña (`current_password`) antes de borrar secretos.
- Detalle en `docs/Confirmacion-Contrasena.md`.

## Seguridad de Sesión y Login
- Panel activa `->login()` y aplica middleware de seguridad: `app/Providers/Filament/DashboardPanelProvider.php`.
- Middleware relevantes: `StartSession`, `AuthenticateSession`, `VerifyCsrfToken`, `twofactor.verify`.
- Rate limiting (Fortify): `config/fortify.php:117-120` — limitadores `login` y `two-factor`.
- Más en `docs/Inicio-de-Sesion-Seguro.md`.

## Eliminación Segura de Usuarios
- El modelo `User` usa `SoftDeletes`: `app/Models/User.php:15` — las eliminaciones marcan `deleted_at` y permiten recuperación.
- Recomendación: restringir eliminación a Admin/Director y auditar las operaciones.

## Auditoría Operativa
- Las acciones clínicas registran `causer_role`, IP, agente, ruta y `medical_record_id` (ver `docs/AUDITORIA_Y_SEGURIDAD.md`).
- Sugerencia: extender auditoría a `User` (cambios de rol y estado) si se requiere rastreo completo de administración.

## Buenas Prácticas
- Principio de mínimo privilegio: asignar el rol más limitado necesario.
- Requerir 2FA para roles sensibles (Admin/Director/Medicina).
- Confirmación de contraseña para cualquier acción de alto impacto (cambios de rol, eliminación, deshabilitar 2FA).
- Revisar periódicamente cuentas inactivas y aplicar `soft delete` o desactivación.
- Notificar por correo cambios administrativos relevantes (cambio de rol, deshabilitar 2FA, eliminación).

## Referencias de Archivos
- Modelo: `app/Models/User.php`
- Formularios y tablas: `app/Filament/Resources/Users/Schemas/UserForm.php`, `app/Filament/Resources/Users/Tables/UsersTable.php`, `app/Filament/Resources/Users/Pages/EditUser.php`
- 2FA: `app/Filament/Pages/SecuritySettings.php`, `app/Filament/Pages/TwoFactorChallenge.php`, `app/Http/Middleware/RequireTwoFactorVerified.php`, `config/fortify.php`
- Documentos: `docs/Autenticacion-2FA.md`, `docs/Confirmacion-Contrasena.md`, `docs/Inicio-de-Sesion-Seguro.md`, `docs/ABAC-por-Roles.md`