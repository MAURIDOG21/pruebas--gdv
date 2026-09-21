# Autenticación de Dos Factores (2FA)

## Resumen
**Objetivo:** proteger el acceso con un segundo factor (TOTP) para todos los usuarios.  
**Stack:** Laravel Fortify para backend 2FA + Filament para UI del panel.

- Generación y confirmación de 2FA en una página de seguridad del panel.
- Desafío 2FA (TOTP o código de recuperación) antes de acceder al panel.
- Códigos de recuperación, regeneración y deshabilitado controlados desde la UI.

## Arquitectura General
- Backend 2FA: `laravel/fortify` (TOTP, recovery codes).  
- UI Filament: páginas `SecuritySettings` y `TwoFactorChallenge`.  
- Middleware de verificación: bloquea el acceso al panel si 2FA no fue validado tras login.

## Dependencias
- `laravel/fortify`
- `bacon/bacon-qr-code` (renderiza QR en SVG)

Ejecutar:
```bash
composer require laravel/fortify
composer require bacon/bacon-qr-code
php artisan vendor:publish --tag=fortify-config
php artisan migrate
```

## Migración
Archivo: `database/migrations/2025_11_20_000600_add_two_factor_columns_to_users_table.php`
- Columnas:
  - `two_factor_secret` (text, nullable)
  - `two_factor_recovery_codes` (text, nullable)
  - `two_factor_confirmed_at` (timestamp, nullable)

## Modelo User
Archivo: `app/Models/User.php`
- Implementa `Filament\Models\Contracts\FilamentUser`
- Usa el trait `Laravel\Fortify\TwoFactorAuthenticatable`
- Oculta secretos en `$hidden`
- Castea `two_factor_confirmed_at`
- Habilita acceso al panel con `canAccessPanel(Panel $panel): bool`

Ejemplo:
```php
use Laravel\Fortify\TwoFactorAuthenticatable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use TwoFactorAuthenticatable;

    protected $hidden = [
        'password','remember_token','two_factor_secret','two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => \App\Enums\UserRole::class,
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool { return true; }
}
```

## Configuración Fortify
Archivo: `config/fortify.php`
- `views => false` (usamos UI de Filament)
- `features` incluye 2FA con confirmación y confirmación de contraseña:
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

## Panel y Middleware
Archivo: `app/Providers/Filament/DashboardPanelProvider.php`
- Activa login y perfil:
```php
->login()
->profile()
```
- Registra middleware de verificación 2FA:
```php
->middleware([
    // ... otros
    'twofactor.verify',
])
```

Archivo: `app/Http/Middleware/RequireTwoFactorVerified.php`
- Redirige a `two-factor` si el usuario tiene 2FA habilitada y confirmada, pero no ha pasado el desafío en la sesión:
```php
$hasTwoFactor = filled($user->two_factor_secret) && !is_null($user->two_factor_confirmed_at);
$passed = session('two_factor_passed') === true;
if ($hasTwoFactor && !$passed) { return redirect('/dashboard/two-factor'); }
```

Archivo: `app/Providers/AppServiceProvider.php`
- Alias del middleware:
```php
$router = app('router');
$router->aliasMiddleware('twofactor.verify', \App\Http\Middleware\RequireTwoFactorVerified::class);
```

## Páginas del Panel
### Seguridad (gestión 2FA)
- Archivo: `app/Filament/Pages/SecuritySettings.php`
- Vista: `resources/views/filament/pages/security-settings.blade.php`
- Acciones:
  - Habilitar 2FA: genera secreto TOTP y códigos de recuperación, encripta y guarda.
  - Mostrar QR: convierte `otpauth://...` a SVG con BaconQrCode.
  - Confirmar 2FA: valida código TOTP, marca `two_factor_confirmed_at`.
  - Regenerar códigos de recuperación.
  - Deshabilitar 2FA: borra secreto, recuperación y confirmación.

QR (generación):
```php
$uri = app(TwoFactorAuthenticationProvider::class)->qrCodeUrl('SIGES-PROGRESO', $user->email, $secret);
$writer = new Writer(new ImageRenderer(new RendererStyle(240), new SvgImageBackEnd()));
$qrSvg = $writer->writeString($uri);
```

### Desafío 2FA
- Archivo: `app/Filament/Pages/TwoFactorChallenge.php`
- Vista: `resources/views/filament/pages/two-factor-challenge.blade.php`
- Verificación:
  - Código TOTP con Fortify.
  - Código de recuperación: si coincide, se consume (se elimina de la lista) y se permite el acceso.
  - En éxito, se setea `session(['two_factor_passed' => true])` y redirige a `/dashboard`.

## Flujo de Usuario
1. Inicia sesión con usuario y contraseña.
2. Si 2FA está habilitada y confirmada, se redirige a “Verificación 2FA”.
3. El usuario ingresa el código TOTP o uno de recuperación.
4. Se permite acceso al panel y la sesión marca verificación pasada.

## Verificación y Pruebas
- Habilitar 2FA y confirmar en “Seguridad”.
- Revisar en BD (`users`):
  - `two_factor_secret`, `two_factor_recovery_codes` poblados.
  - `two_factor_confirmed_at` con valor.
- Cerrar sesión y volver a iniciar: debe pedir código 2FA.
- Probar modo incógnito si hay sesión persistente.

## Solución de Problemas
- No aparece reto 2FA:
  - Verificar `two_factor_confirmed_at` no nulo.
  - Confirmar middleware `'twofactor.verify'` activo en el panel.
  - Limpiar cachés: `php artisan optimize:clear`.
- No aparece QR:
  - Asegurar dependencia `bacon/bacon-qr-code` instalada.
  - Verificar que el secreto existe y no está confirmado (antes de la confirmación se muestra QR).

## Seguridad y Privacidad
- Secretos y códigos de recuperación se almacenan encriptados (`Crypt::encryptString`).
- Se ocultan en serialización del modelo (`$hidden`).
- Códigos de recuperación se consumen al usarse.

## Referencias de Archivos
- Config: `config/fortify.php`
- Migración: `database/migrations/2025_11_20_000600_add_two_factor_columns_to_users_table.php`
- Modelo: `app/Models/User.php`
- Middleware: `app/Http/Middleware/RequireTwoFactorVerified.php`
- Panel Provider: `app/Providers/Filament/DashboardPanelProvider.php`
- Páginas: `app/Filament/Pages/SecuritySettings.php`, `app/Filament/Pages/TwoFactorChallenge.php`
- Vistas: `resources/views/filament/pages/security-settings.blade.php`, `resources/views/filament/pages/two-factor-challenge.blade.php`