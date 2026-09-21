# Rate Limiting y Anti Fuerza Bruta

## Resumen
**Objetivo:** limitar intentos de autenticación para prevenir ataques de fuerza bruta y abuso de endpoints sensibles.  
**Stack:** Laravel Fortify (limiters de login y 2FA), RateLimiter de Laravel, middleware del panel.

## Arquitectura
- **Limiters Fortify**: controlan el número de intentos por ventana de tiempo.
- **Guard `web`**: autenticación principal (ver `config/fortify.php`).
- **Middleware del panel**: sesión, CSRF y verificación 2FA (ver `docs/Inicio-de-Sesion-Seguro.md`).

## Configuración Actual
Archivo: `config/fortify.php`
```php
'limiters' => [
    'login' => 'login',
    'two-factor' => 'two-factor',
],
```
Fortify aplica limitación a:
- Intentos de **login**
- Intentos de **reto 2FA**

## Personalización de Limiters
Puedes definir reglas específicas con el RateLimiter de Laravel en el `boot()` de un Service Provider.

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

public function boot(): void
{
    RateLimiter::for('login', function (Request $request) {
        $email = (string) $request->input('email');
        $ip = (string) $request->ip();
        // Límite: 5 intentos por minuto por combinación email+IP
        return [
            Limit::perMinute(5)->by($email.'|'.$ip),
        ];
    });

    RateLimiter::for('two-factor', function (Request $request) {
        $userId = optional($request->user())->id ?? 'guest';
        $ip = (string) $request->ip();
        // Límite: 10 intentos por minuto por usuario+IP
        return [
            Limit::perMinute(10)->by($userId.'|'.$ip),
        ];
    });
}
```

### Opciones Avanzadas
- **Ventanas de tiempo** diferentes por ruta/acción.
- **Claves de rate limit** que incluyan más contexto (User-Agent, país, etc.).
- **Back-off progresivo**: incrementar la ventana temporal si se exceden límites reiteradamente.

## Integración con 2FA
- El límite para el reto 2FA (`two-factor`) evita automatización de intentos de códigos TOTP.
- Recomendación: mantener límites más altos que login, pero suficientes para bloquear ataques (p.ej., 10/min).

## Observabilidad y Respuesta
- **Logs**: Fortify y Laravel registran eventos; complementa con auditoría (ver `docs/AUDITORIA_Y_SEGURIDAD.md`).
- **Mensajes de error**: genéricos para evitar filtraciones (no indicar cuál dato falló).
- **Alertas**: opcional, dispara notificaciones si se detectan múltiples bloqueos desde una misma IP.

## Pruebas
1. Intenta múltiples logins erróneos con el mismo email+IP; verifica que, tras 5 intentos, el sistema bloquea temporalmente.
2. Realiza varios intentos de reto 2FA; confirma que el límite se aplica.
3. Observa los tiempos de desbloqueo (un minuto por la regla anterior) y confirma que tras el periodo los intentos vuelven a contar.

## Buenas Prácticas Complementarias
- **CAPTCHA**: añadir en login si se detecta abuso.
- **Lista de bloqueo**: negar IPs o rangos tras detecciones reiteradas.
- **Alertas de seguridad**: enviar correo al usuario ante actividad de login inusual.
- **Política de contraseñas**: robustas y rotación si corresponde.

## Referencias de Archivos
- `config/fortify.php` (limiters de login y 2FA)
- `app/Providers/*ServiceProvider.php` (personalización RateLimiter)
- `docs/Inicio-de-Sesion-Seguro.md` (middleware y flujo de login)
- `docs/Autenticacion-2FA.md` (reto 2FA)
- `docs/AUDITORIA_Y_SEGURIDAD.md` (auditoría y metadatos)