# XSS y Sanitización

## Resumen
**Objetivo:** prevenir inyección de scripts (XSS) y asegurar que el contenido mostrado/almacenado esté sanitizado y escapado correctamente.

## Renderizado en Blade
- `{{ $variable }}` escapa por defecto (HTML entities) — seguro para mostrar texto de usuario.
- `{!! $html !!}` NO escapa; usar solo para HTML generado y controlado por servidor.
- Plantillas del proyecto usan Blade y componentes de Filament, que escapan estados por defecto.

## Filament: Tablas, Infolists y Formularios
- Columnas `TextColumn` e `IconColumn` escapan valores; etiquetas y estados se formatean con funciones controladas.
- Infolists `TextEntry` igualmente escapan; cuando se construyen strings, se realizan con datos ya saneados.
- Formularios (`TextInput`, `Textarea`, `Select`) reciben input del usuario y luego pasan por validación (reglas de Laravel); el renderizado posterior se hace escapado.

## Caso Puntual: Código QR (SVG)
- Vista: `resources/views/filament/pages/security-settings.blade.php` renderiza `{!! $qrSvg !!}`.
- Origen seguro: el SVG se genera server‑side con **BaconQrCode** a partir de una URL `otpauth://` controlada → no incluye contenido proporcionado por el usuario ni scripts.
- Mitigación: mantener la generación en backend y no concatenar datos arbitrarios del usuario dentro del SVG.

## Validación y Sanitización de Entradas
- Reglas de formulario: emplear validación de Laravel (e.g., `email`, `string`, `current_password`) para asegurar formato y autenticidad.
- Información sensible (diagnóstico, notas, observaciones) se guarda **cifrada** (ver `docs/Cifrado-de-Datos.md`), evitando exposición directa.
- Evitar almacenar HTML proporcionado por el usuario; si alguna vez se requiere, usar un sanitizador (e.g., HTML Purifier) antes de renderizar.

## Recomendaciones
- Usar siempre `{{ }}` para imprimir variables en vistas.
- Reservar `{!! !!}` solo para HTML generado en backend y totalmente confiable.
- Validar y normalizar entradas (longitudes máximas, tipos, listas permitidas).
- No incluir `<script>` ni eventos inline en contenido dinámico.
- Establecer una **Content Security Policy (CSP)** en producción para restringir fuentes de scripts y estilos.
- Auditar campos que se muestran en listados para evitar mostrar HTML sin escapar.

## Pruebas
- Intentar ingresar scripts en campos texto (e.g., `<script>alert(1)</script>`) y verificar que se muestren como texto literal (escapados) en tablas e infolists.
- Confirmar que el QR se renderiza como SVG sin ejecutar scripts (observa el DOM).
- Verificar que ningún campo del sistema acepte HTML crudo salvo los controlados por backend.

## Referencias de Archivos
- Vistas: `resources/views/filament/pages/security-settings.blade.php`
- Páginas: `app/Filament/Pages/SecuritySettings.php`
- Componentes Filament: `app/Filament/Widgets/*`, `app/Filament/Resources/*`
- Cifrado y protección de datos: `docs/Cifrado-de-Datos.md`
- Seguridad de configuración: `docs/Seguridad-Secretos-y-Configuracion.md`