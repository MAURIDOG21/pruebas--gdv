# Inicio de Sesión Seguro

## Resumen
**Objetivo:** proteger el proceso de autenticación contra ataques comunes (fuerza bruta, robo de sesión, CSRF) y exigir controles adicionales como 2FA.  
**Stack:** Filament Panel (login), Laravel Fortify (autenticación, limitadores y 2FA), Middleware de seguridad (CSRF, sesión).

## Arquitectura
- **Punto de entrada:** `routes/web.php` redirige a `/dashboard/login` (Filament) para unificar el flujo de acceso.  
- **Panel:** `app/Providers/Filament/DashboardPanelProvider.php` habilita `->login()` y aplica middleware de seguridad.  
- **Fortify:** `config/fortify.php` define guard, limitadores y características (`twoFactorAuthentication`, cambio de contraseña, perfiles).  
- **2FA:** ver `docs/Autenticacion-2FA.md` para el reto posterior al login.

## Flujo de Autenticación
1. Usuario accede a `/dashboard/login` (Filament).  
2. Credenciales válidas → sesión creada bajo `guard: web`.  
3. Si el usuario tiene 2FA confirmada, se redirige a “Verificación 2FA” (página interna del panel).  
4. Tras la verificación (TOTP/código de recuperación), se concede acceso al panel.

## Configuración Fortify
Archivo: `config/fortify.php`
- **Guard:**
```php
'guard' => 'web',
```
- **Limitadores:**
```php
'limiters' => [
    'login' => 'login',
    'two-factor' => 'two-factor',
],
```
- **Sin vistas propias:**
```php
'views' => false,
```
- **Características:**
```php
'features' => [
    Features::registration(),
    Features::resetPasswords(),
    Features::updateProfileInformation(),
    Features::updatePasswords(),
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]),
],
```

## Middleware de Seguridad en el Panel
Archivo: `app/Providers/Filament/DashboardPanelProvider.php`
- **Aplicados:**
  - `EncryptCookies`, `AddQueuedCookiesToResponse`, `StartSession`, `AuthenticateSession`, `ShareErrorsFromSession`
  - `VerifyCsrfToken`, `SubstituteBindings`, `DisableBladeIconComponents`, `DispatchServingFilamentEvent`
  - `check.shift` (propio del dominio) y `twofactor.verify` (verificación 2FA)

| Middleware | Propósito |
|---|---|
| `AuthenticateSession` | Válida coherencia de sesión al cambiar credenciales |
| `VerifyCsrfToken` | Protege formularios (incl. login) contra CSRF |
| `StartSession` | Manejo de sesión servidor-side |
| `twofactor.verify` | Obliga a pasar reto 2FA tras login |

## Protección contra Fuerza Bruta
- Fortify aplica **rate limiting** a:
  - Intentos de login (`limiters['login']`)
  - Reto 2FA (`limiters['two-factor']`)
- Configurable vía `RateLimiter` si se requiere personalizar.

## Hashing de Contraseñas
Archivo: `app/Models/User.php`
- Cast automático:
```php
protected function casts(): array
{
    return [
        'password' => 'hashed',
    ];
}
```
- Asegura que cualquier asignación de `password` se guarde con el **hash** (configurado por Laravel), evitando almacenamiento en claro.

## Gestión de Sesión y Cookies
- **Cookies encriptadas**: `EncryptCookies` protege el contenido de cookies.
- **Sesión segura**: controlada por `StartSession` y validada por `AuthenticateSession`.
- Recomendación: revisar `config/session.php` para `secure`, `http_only`, `same_site` acorde al despliegue.

## Integración con 2FA
- Tras éxito de credenciales, se aplica **retención** hacia el reto 2FA si el usuario lo tiene activo y confirmado.  
- Archivo: `app/Http/Middleware/RequireTwoFactorVerified.php` redirige a `/dashboard/two-factor` cuando corresponde.

## Pruebas de Seguridad
- Credenciales incorrectas repetidas → observar limitación (intentos bloqueados temporalmente).
- Login correcto con 2FA activo → sistema debe solicitar el código 2FA.
- Cambios de contraseña → sesión debe invalidarse según política (middleware `AuthenticateSession`).

## Buenas Prácticas Complementarias
- **Recordar dispositivo**: implementar token de confianza para reducir fricción manteniendo seguridad (no habilitado por defecto).  
- **CAPTCHA**: añadir desafío en login si se detecta actividad anómala.  
- **Alertas**: notificar por correo/NIS eventos de login inusuales.

## Referencias de Archivos
- `routes/web.php` (redirección a login del panel)
- `app/Providers/Filament/DashboardPanelProvider.php` (login y middleware)
- `config/fortify.php` (guard, limitadores, características)
- `app/Http/Middleware/RequireTwoFactorVerified.php` (retención 2FA)
- `app/Models/User.php` (hashing de contraseña)
- `docs/Autenticacion-2FA.md` (detalle del reto 2FA)