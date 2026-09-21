# Seguridad de Secretos y Configuración

## Resumen
**Objetivo:** proteger las claves, credenciales y parámetros sensibles del sistema (APP_KEY, credenciales de BD, tokens) y asegurar que la configuración refuerce las defensas del proyecto.

## Secretos en `.env`
- **APP_KEY**: clave base para cifrado (AES-256-CBC). Debe ser larga, aleatoria y única por entorno.
- **Credenciales de BD**: `DB_USERNAME`, `DB_PASSWORD` nunca deben estar en el repositorio.
- **Mail / Servicios externos**: `MAIL_PASSWORD`, API keys, etc. mantener únicamente en `.env` y sistemas de secretos.

Buenas prácticas:
- No commitear `.env` al repositorio.
- Rotación planificada de APP_KEY requiere re-cifrado de datos; evaluar impacto (ver `docs/Cifrado-de-Datos.md`).
- Usar gestores de secretos del entorno (Azure Key Vault, AWS Secrets Manager, etc.) si aplica.

## Configuración de Sesión y Cookies
Archivo: `config/session.php` (recomendaciones):
- `secure` → `true` en producción (HTTPS).
- `http_only` → `true` para impedir acceso desde JS.
- `same_site` → `lax` o `strict` según necesidades de SSO.
- `cookie` → nombre específico por entorno para evitar colisiones.

Aplicados en el panel vía middleware (ver `docs/Inicio-de-Sesion-Seguro.md`).

## Fortify y Autenticación
Archivo: `config/fortify.php`:
- `guard: web` asegurado.
- `limiters`: `login`, `two-factor` para anti-fuerza bruta.
- `features`: `twoFactorAuthentication`, `updatePasswords`, `updateProfileInformation` habilitan controles de seguridad.

## Cifrado de Datos
- Casts `encrypted` y `encrypted:json` activos en modelos clínicos (ver `docs/Cifrado-de-Datos.md`).
- 2FA: secretos y códigos de recuperación se encriptan con `Crypt::encryptString`.

## Auditoría y Registro
- Spatie Activitylog guarda eventos con metadatos `causer_role`, `ip`, `user_agent`, `route` (ver `docs/AUDITORIA_Y_SEGURIDAD.md`).
- Recomendación: conservar logs de seguridad con retención acorde a política (configurable en `config/activitylog.php`).

## Actualizaciones y Dependencias
- Mantener paquetes al día (Seguridad de dependencias: Fortify, Filament, DomPDF).
- Ejecutar `composer audit` (si disponible) y revisar CVEs.

## Despliegue y Transporte
- Forzar HTTPS en producción; configurar reverse proxy para cabeceras `X-Forwarded-Proto`.
- Content Security Policy (CSP) opcional para restringir fuentes (incluye inline SVG del QR ya que es generado internamente).

## Puntos de control
- Verificar que `.env` no esté en el repositorio.
- Confirmar `APP_KEY` presente y válido (`php artisan key:generate` si no existe).
- Revisar `config/session.php` y `config/fortify.php` acorde a entorno.
- Validar cifrado de campos en BD (ciphertext) y descifrado en aplicación (Eloquent).
- Probar flujo 2FA y límites de login.

## Referencias
- `.env` (no versionado)
- `config/fortify.php`, `config/session.php`
- `docs/Inicio-de-Sesion-Seguro.md`, `docs/Autenticacion-2FA.md`, `docs/Cifrado-de-Datos.md`, `docs/AUDITORIA_Y_SEGURIDAD.md`