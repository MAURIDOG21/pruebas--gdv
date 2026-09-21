# Cifrado de Datos (AES-256)

## Resumen
**Objetivo:** proteger información sensible almacenada en la base de datos mediante cifrado simétrico a nivel de campo.  
**Stack:** Casts `encrypted` de Laravel (OpenSSL AES-256-CBC) y utilidades `Crypt`.

## Algoritmo y Claves
- **Algoritmo:** AES-256-CBC con IV aleatorio por valor (implementación de Laravel).  
- **Clave:** derivada de `APP_KEY` (`.env`), gestionada por Laravel.  
- **Cifrado a nivel de aplicación:** los campos se cifran/descifran automáticamente al leer/escribir desde Eloquent.

## Campos Cifrados Activos
- `app/Models/Appointment.php:38-40`  
  - `reason_for_visit` → `encrypted`  
  - `notes` → `encrypted`
- `app/Models/Prescription.php:28-33`  
  - `diagnosis` → `encrypted`  
  - `notes` → `encrypted`  
  - `items` → `encrypted:json`
- `app/Models/SomatometricReading.php:31-33`  
  - `observations` → `encrypted`
- `app/Models/MedicalInitialAssessment.php:28-35`  
  - `allergies`, `personal_pathological_history`, `gyneco_obstetric_history`, `current_illness`, `physical_exam`, `diagnosis`, `treatment_note` → `encrypted`
- `app/Models/NursingEvolution.php:28-34`  
  - `problem`, `subjective`, `objective`, `analysis`, `plan` → `encrypted`
- `app/Models/MedicalDocument.php:28-31`  
  - `name`, `file_path` → `encrypted`
- 2FA (manual): `app/Filament/Pages/SecuritySettings.php:46-50,77-79`  
  - `two_factor_secret`, `two_factor_recovery_codes` → `Crypt::encryptString(...)`

## Uso de Casts `encrypted`
- Declaración típica:
```php
protected $casts = [
    'diagnosis' => 'encrypted',
    'notes' => 'encrypted',
    'items' => 'encrypted:json',
];
```
- `encrypted` (string/text) cifra y descifra automáticamente.
- `encrypted:json` serializa a JSON antes de cifrar y devuelve array/objeto tras descifrar.

## Encriptación Manual (ejemplo 2FA)
- Generación y almacenamiento seguro:
```php
$secret = $provider->generateSecretKey();
$user->forceFill([
    'two_factor_secret' => Crypt::encryptString($secret),
    'two_factor_recovery_codes' => Crypt::encryptString(json_encode($codes)),
])->save();
```

## Migraciones y Tipos de Columnas
- Usar `text` para campos cifrados (el ciphertext puede superar longitudes pequeñas).  
- Ejemplo:
```php
$table->text('diagnosis')->nullable();
$table->text('notes')->nullable();
$table->text('items')->nullable();
```

## Limitaciones y Consideraciones
- **Búsquedas/ordenación:** no se puede filtrar/ordenar por contenido cifrado (el valor en DB es aleatorio).  
  - Si necesitas filtrado, considera almacenar una huella (hash) de baja sensibilidad en columna separada.  
- **Índices:** no útil sobre ciphertext; evitar índices en columnas cifradas.
- **Exportación/ETL:** descifrar desde la aplicación; no exponer APP_KEY en pipelines.

## Buenas Prácticas
- **Protege `APP_KEY`:** no compartir, rotar con plan de migración (re-cifrado).  
- **Backups cifrados:** cifrar copias y proteger accesos.  
- **MFA + Confirmación de contraseña:** exige controles adicionales para operar campos sensibles (ver 2FA y confirmación).  
- **Auditoría:** registrar operaciones sobre datos sensibles (ver `docs/AUDITORIA_Y_SEGURIDAD.md`).  
- **Menor exposición:** nunca loggear datos en claro; evitar dumps en clientes.

## Verificación
- Inserta y lee un registro con campos cifrados; verifica que en DB se almacena ciphertext (prefijos base64 y datos binarios encapsulados).  
- Comprueba que Eloquent devuelve en claro al usar el modelo.

## Referencias de Archivos
- Cifrado por casts:  
  - `app/Models/Appointment.php:38-40`  
  - `app/Models/Prescription.php:28-33`  
  - `app/Models/SomatometricReading.php:31-33`  
  - `app/Models/MedicalInitialAssessment.php:28-35`  
  - `app/Models/NursingEvolution.php:28-34`  
  - `app/Models/MedicalDocument.php:28-31`
- 2FA manual (`Crypt`): `app/Filament/Pages/SecuritySettings.php:46-50,77-79`
- Clave de app: `.env` (`APP_KEY`)