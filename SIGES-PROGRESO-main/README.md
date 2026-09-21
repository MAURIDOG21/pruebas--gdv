# SIGES-PROGRESO — Sistema de Gestión y Digitalización de Expedientes Médicos

Sistema para la gestión clínica y digitalización de expedientes médicos con panel administrativo basado en Filament. Incluye módulos de pacientes, citas, expedientes, documentos, ausencias médicas y gestión de turnos de clínica.

## Requisitos

- `PHP >= 8.2`
- `Composer`
- `Node.js >= 18` y `npm`
- `PostgreSQL` (recomendado `>= 14`)

## Instalación

1. Clonar el repositorio.
2. Copiar el archivo de entorno:
   - `cp .env.example .env` (Linux/Mac) o duplicar manualmente en Windows.
3. Configurar credenciales en `.env` (DB, correo, etc.). Ejemplo de variables clave:
   - `DB_CONNECTION=pgsql`
   - `DB_HOST=127.0.0.1`
   - `DB_PORT=5432`
   - `DB_DATABASE=siges`
   - `DB_USERNAME=postgres`
   - `DB_PASSWORD=********`
4. Instalar dependencias de PHP:
   - `composer install`
5. Instalar dependencias Frontend:
   - `npm install`
6. Generar clave de aplicación:
   - `php artisan key:generate`

## Migraciones y datos

- Ejecutar migraciones:
  - `php artisan migrate`
- Poblar datos base (opcional):
  - `php artisan db:seed` (usa `DatabaseSeeder` y seeders relacionados)

### Nota sobre columnas de Turnos de Clínica

Para evitar errores de columnas indefinidas (por ejemplo, `is_shift_open`), se añadió una migración incremental:

- `database/migrations/2025_10_31_130500_add_shift_columns_to_clinic_schedules.php`

Esta migración agrega a `clinic_schedules` los campos:

- `is_shift_open` (boolean)
- `shift_opened_at` (timestamp)
- `shift_closed_at` (timestamp)
- `opened_by` (foreignId a `users`)
- `closed_by` (foreignId a `users`)
- `opening_notes` (text)
- `closing_notes` (text)
- `is_active` (boolean)

Si ves mensajes como “Undefined column…”, ejecuta nuevamente:

- `php artisan migrate`

## Servidor de desarrollo

- Iniciar servidor Laravel:
  - `php artisan serve`
  - Acceso: `http://127.0.0.1:8000/`
- Iniciar Vite (assets):
  - `npm run dev`

## Panel y rutas (Filament)

- Panel principal ID: `dashboard`
- Path base del panel: `/dashboard`
- Rutas principales:
  - Dashboard: `http://127.0.0.1:8000/dashboard`
  - Gestión de Turnos (consolidado): `http://127.0.0.1:8000/dashboard/clinic-schedules/day`
  - Horarios de Clínica: `http://127.0.0.1:8000/dashboard/clinic-schedules`

 Las redirecciones tras abrir/cerrar turno se realizan al path del panel (`/dashboard`) para evitar depender de nombres de rutas variables.

 ## Uso: Gestión de Turnos (consolidada en clinic-schedules)

 - Usa `Horario del Día` (`clinic-schedules/day`) para abrir y cerrar turnos del día.
 - “Turnos disponibles para abrir” se basan en registros del día con:
   - `date = hoy`, `is_active = true` y `is_shift_open = false`.
 - Crea/verifica asignaciones desde “Horarios de Clínica”. Los botones `Abrir Turno` y `Cerrar Turno` están en la tabla.
 - Tras abrir/cerrar un turno, se redirige al Dashboard del panel (`/dashboard`).

## Solución de problemas comunes

- Error `Illuminate\Database\QueryException` con columna indefinida (ej. `is_shift_open`):
  - Tu base de datos no tiene las columnas recientes; ejecuta `php artisan migrate`.

- Error `RouteNotFoundException` relacionado con `filament.admin.pages.*`:
  - El proyecto usa el panel `dashboard`. Asegúrate de usar rutas bajo `/dashboard`.
  - Las redirecciones internas se ajustaron para usar `url('/dashboard')`.

- No aparecen turnos disponibles:
  - Verifica que existan `clinic_schedules` para hoy, activos y sin turno abierto.

## Scripts útiles

- Compilar producción: `npm run build`
- Limpiar cachés: `php artisan optimize:clear`
- Ejecutar tests: `phpunit`

## Estructura relevante

- Migraciones de clínica:
  - `2025_10_24_174307_create_clinic_schedules_table.php`
  - `2025_10_31_130500_add_shift_columns_to_clinic_schedules.php`
- Páginas Filament:
  - `app/Filament/Pages/ShiftManagement.php`
  - `app/Providers/Filament/DashboardPanelProvider.php`
- Middleware:
  - `app/Http/Middleware/CheckShiftStatus.php`

---

Si necesitas que el Dashboard post-apertura cierre redirija a otra página específica (por ejemplo, una recepción distinta), indícalo y ajustamos la ruta de redirección correspondiente.

SIGES-PROGRESO es una aplicación web construida con Laravel y Filament para gestionar expedientes médicos, pacientes, servicios y citas, con paneles de indicadores interactivos basados en ApexCharts.

Este README explica las funcionalidades principales y cómo poner el proyecto a trabajar en tu entorno local o de producción.

## Características principales

- Gestión de pacientes y expedientes clínicos, incluyendo adultos y menores.
- Catálogo de servicios médicos sembrado (consulta general, pediatría, ginecología, laboratorio, etc.).
- Citas y visitas con estados (`AppointmentStatus`) y tipos de visita (`VisitType`: primera vez y subsecuente).
- Generación de documentos PDF (DOMPDF) y carga de archivos (plugin de Filament Upload).
- Panel administrativo con indicadores y gráficos:
  - Tipos de paciente (adulto vs. menor).
  - Nuevos vs. recurrentes por periodo (semana/mes/año) — gráfico de columnas apiladas.
  - Servicios más solicitados.
  - Visitas por médico.
  - Estado de visitas (agendadas, completadas, canceladas, etc.).
  - Visitas semanales.
- Páginas de Reportes dedicadas para organizar los widgets y evitar saturar el dashboard:
  - `Indicadores (Pacientes)`
  - `Indicadores (Servicios)`
  - `Indicadores (Visitas)`

## Tecnologías y versiones

- PHP `^8.2`
- Laravel `^11.31`
- Filament `4.0`
- Filament Apex Charts `4.0`
- Laravel Sanctum para API tokens
- Vite/Tailwind para assets
- Base de datos recomendada: PostgreSQL (ver notas de compatibilidad más abajo)

## Requisitos previos

- PHP 8.2+ con extensiones comunes habilitadas.
- Composer 2.x.
- Node.js 18+ y npm.
- PostgreSQL 14+ (recomendado). Puedes usar SQLite para desarrollo rápido, pero algunos widgets están optimizados para PostgreSQL.

## Instalación (local)

1. Clonar el repositorio:
   ```bash
   git clone <url-del-repo>
   cd SIGES-PROGRESO
   ```

2. Instalar dependencias:
   ```bash
   composer install
   npm install
   ```

3. Configurar entorno:
   - Copia el `.env.example` a `.env` si no se creó automáticamente:
     ```bash
     copy .env.example .env  # En Windows PowerShell: Copy-Item .env.example .env
     ```
   - Edita variables de conexión a la base de datos (PostgreSQL recomendado):
     ```env
     DB_CONNECTION=pgsql
     DB_HOST=127.0.0.1
     DB_PORT=5432
     DB_DATABASE=siges
     DB_USERNAME=postgres
     DB_PASSWORD=secret
     ```
   - Genera la clave de aplicación:
     ```bash
     php artisan key:generate
     ```

4. Migrar y sembrar datos:
   ```bash
   php artisan migrate --seed
   php artisan storage:link
   ```

   Durante el seeding se crea:
   - Usuario administrador: `admin@siges.com` con contraseña `password`.
   - Usuario de API: `api-user@siges.com` y se imprime su token en consola.
   - 75 pacientes de ejemplo (50 adultos, 25 menores).
   - 30 usuarios adicionales de prueba con contraseña `password`.
   - Catálogo de servicios médicos básicos.

5. Arrancar en desarrollo (elige una opción):
   - Procesos separados:
     ```bash
     php artisan serve
     php artisan queue:listen --tries=1
     npm run dev
     ```
   - Orquestado con Composer (servidor, cola, logs y Vite en paralelo):
     ```bash
     composer run dev
     ```

6. Acceder al panel:
   - URL por defecto del panel Filament: `http://localhost:8000/admin`
   - Inicia sesión con el administrador: correo `admin@siges.com`, contraseña `password`.

## Páginas de Reportes y widgets

- Navegación lateral: grupo `Reportes` con tres páginas dedicadas.
- Cada página usa `getHeaderWidgets()` para mostrar los gráficos correspondientes:
  - `Indicadores (Pacientes)`: Tipos de paciente y Nuevos vs recurrentes.
  - `Indicadores (Servicios)`: Servicios más solicitados y Visitas por médico.
  - `Indicadores (Visitas)`: Estado de visitas y Visitas semanales.
- Filtros disponibles en widgets: semana, mes, año. Las consultas de periodo usan `to_char(...)` para compatibilidad con PostgreSQL.

## API y tokens

- El seeder `CoreDataSeeder` crea un usuario API (`api-user@siges.com`) y muestra su token en la consola al finalizar `db:seed`.
- Si necesitas regenerar el token:
  ```bash
  php artisan tinker
  >>> $u = App\Models\User::where('email','api-user@siges.com')->first();
  >>> $u->tokens()->delete();
  >>> $u->createToken('sistema-de-turnos-token')->plainTextToken;
  ```

## Scripts útiles

- `composer run dev`: ejecuta servidor, cola, logs y Vite en paralelo.
- `php artisan optimize:clear`: limpia caches (config, rutas, vistas, eventos, cache de app).
- `php artisan migrate --graceful`: corre migraciones tolerantes.
- `npm run build`: construye assets para producción.

## Despliegue (producción)

- Configurar `.env`:
  ```env
  APP_ENV=production
  APP_DEBUG=false
  APP_URL=https://tuservidor
  DB_CONNECTION=pgsql
  # Configura cache, sesión y colas según infraestructura
  ```
- Construir assets y optimizar:
  ```bash
  npm run build
  php artisan optimize
  php artisan migrate --force
  ```
- Configurar un `queue:work` o `supervisor` para procesar colas.
- Servir con Nginx/Apache apuntando a `public/index.php`.

## Solución de problemas

- Error SQL `DATE_FORMAT` en PostgreSQL: las consultas de widgets usan `to_char(...)`. Si cambias de motor (MySQL/SQLite), adapta el SQL de agrupación por periodo.
- Widgets vacíos o sin datos: verifica que ejecutaste `php artisan migrate --seed` y prueba distintos filtros (semana/mes/año).
- Subidas de archivos: ejecuta `php artisan storage:link` y asegúrate de que el disco `public` esté configurado.
- Cachés incoherentes: `php artisan optimize:clear`.

## Estructura del proyecto (resumen)

- `app/Models`: modelos principales (`Patient`, `Appointment`, `Service`, etc.).
- `app/Enums`: tipos enumerados (`VisitType`, `AppointmentStatus`, `PatientType`, etc.).
- `app/Filament/Widgets`: widgets de ApexCharts.
- `app/Filament/Pages`: páginas de Reportes que agrupan los widgets.
- `database/seeders`: datos iniciales (usuarios, servicios, pacientes, tokens, etc.).

## Licencia

Este proyecto usa la licencia MIT.

## Contribuciones

Las contribuciones son bienvenidas. Abre un issue o PR con tu propuesta.

Este documento describe cómo está organizada la interfaz administrativa del proyecto usando Filament (Forms, Tables e Infolists), las interacciones entre los Resources, y las pautas para extender y mantener la solución.

## Índice
- Introducción y stack
- Arquitectura de Filament
- Resources y componentes
- Pacientes
- Visitas (Citas)
- Servicios
- Usuarios
- Estados y colores
- Navegación y páginas
- Reutilización de esquemas
- Interacciones clave entre Resources
- Notificaciones
- Extensión y buenas prácticas
- Comandos útiles

## Introducción y stack
- Framework: `Laravel`
- Admin UI: `Filament`
- Frontend tooling: `Vite`, `Tailwind`
- Base de datos: según tu `.env` local

Objetivo: digitalizar y administrar expedientes médicos, pacientes y visitas, con una UI consistente y reutilizable.

## Arquitectura de Filament
Cada Resource define tres piezas principales:
- `Form`: para crear/editar registros, con secciones y lógica reactiva.
- `Infolist`: para mostrar registros en modo lectura, alineado visualmente con el `Form`.
- `Table`: para listar registros con columnas, filtros y acciones.

Además, cada Resource expone sus `Pages` para listar, ver y editar.

## Resources y componentes
Estructura relevante del proyecto:
- `app/Filament/Resources/Patients/PatientResource.php`
  - Schemas: `app/Filament/Resources/Patients/Schemas/PatientForm.php`, `PatientInfolist.php`
  - Tables: `app/Filament/Resources/Patients/Tables/PatientsTable.php`
  - Pages: `ListPatients.php`, `ViewPatient.php`, `EditPatient.php`
- `app/Filament/Resources/Appointments/AppointmentResource.php`
  - Schemas: `app/Filament/Resources/Appointments/Schemas/AppointmentForm.php`
  - Tables: `app/Filament/Resources/Appointments/Tables/AppointmentsTable.php`
  - Pages: `ListAppointments.php`, `ViewAppointment.php`, `EditAppointment.php`
- `app/Filament/Resources/Services/ServiceResource.php`
  - Tables/Schemas/Páginas según la entidad Servicios
- `app/Filament/Resources/Users/UsersTable.php` (listado de usuarios)
- Enums para estados y etiquetas: `app/Enums/*` (`PatientType`, `AppointmentStatus`, `VisitType`, `Shift`, etc.)

## Pacientes
- Modelo: `App\Models\Patient`
- `Form`: define secciones (p. ej., información personal, contacto, expediente, tutor, discapacidad). Vive en `Patients/Schemas/PatientForm.php`.
- `Infolist`: replica el layout del `Form` para lectura en `Patients/Schemas/PatientInfolist.php`.
- `Table`: columnas, búsqueda y acciones en `Patients/Tables/PatientsTable.php`.
- `Pages`:
  - `ListPatients.php`: listado con filtros/acciones.
  - `ViewPatient.php`: vista detallada; título dinámico y acciones (p. ej. registrar somatometría).
  - `EditPatient.php`: edición del paciente.

Detalles prácticos:
- Columna `sex` en `PatientsTable.php` mapea `F/M` a `Femenino/Masculino` con `formatStateUsing` y colorea la badge con tokens compatibles de Filament.
- Para mantener consistencia visual, en `PatientResource::infolist` se delega a `PatientInfolist` para que el `View` sea idéntico al `Form` (orden de secciones, etiquetas y visibilidad condicional).

## Visitas (Citas)
- Modelo: `App\Models\Appointment`
- `Form`: `Appointments/Schemas/AppointmentForm.php` configura selección/creación de expediente, paciente y servicio, horarios, tipo de visita, etc.
- `Table`: `Appointments/Tables/AppointmentsTable.php` lista citas con filtros por fecha/estado y acciones.
- `Pages`: `ListAppointments.php`, `ViewAppointment.php`, `EditAppointment.php`.

Patrones útiles:
- Uso de `Enums` para estados (`AppointmentStatus`, `VisitType`, `Shift`) facilita colorear badges y estandarizar etiquetas.
- Formularios con dependencias (p. ej. al seleccionar `Patient`, se precargan datos relacionados del `MedicalRecord`).

## Servicios
- Modelo: `App\Models\Service`
- Resource: `ServiceResource.php` con sus páginas, tablas y formularios.
- Recomendado asignar colores y categorías usando `Enums` si aplica.

## Usuarios
- Modelo: `App\Models\User`
- Listado: `UsersTable.php` con columnas básicas y acciones administrativas.

## Estados y colores
- Filament usa tokens de color: `primary`, `success`, `warning`, `danger`, `info`, `gray`.
- Ejemplo de mapeo (sin afectar valores en BD):
  - `sex`: `F/Femenino` → `warning`, `M/Masculino` → `primary`.
  - Usa callbacks que normalicen el estado (`strtolower`, `trim`) para evitar caer en `gray` por mayúsculas/espacios.

## Navegación y páginas
- Cada Resource registra sus `Pages` estándar: `List`, `View`, `Edit`.
- En `ViewPatient.php` se personaliza el título con `getTitle()` y se agregan acciones (p. ej. `addSomatometricReading`) con formularios inline.
- Las acciones pueden notificar (`Filament\Notifications\Notification`) y crear registros relacionados (`somatometricReadings()` del modelo).

## Reutilización de esquemas
- `Form` y `Infolist` se definen en `Schemas/*` y se reutilizan en `PatientResource::form` y `PatientResource::infolist`.
- Buen patrón: delegar directamente a la clase de esquema, por ejemplo:

```php
// En PatientResource.php
public static function infolist(\Filament\Infolists\Infolist $schema): \Filament\Infolists\Infolist
{
    return \App\Filament\Resources\Patients\Schemas\PatientInfolist::configure($schema);
}
```

Así el `View` queda idéntico al `Form` y se centraliza el mantenimiento.

## Interacciones clave entre Resources
- Paciente ↔ Expediente médico ↔ Visitas: la cita puede vincularse a un expediente y a un servicio.
- Navegación cruzada: desde tablas y vistas se puede saltar a registros relacionados mediante acciones o enlaces.
- Estados y filtros consistentes: los `Enums` garantizan etiquetas y colores uniformes en formularios, infolists y tablas.

## Notificaciones
- `App\Notifications\NewVisitRegistered` envía avisos cuando se registra una nueva visita.
- Las acciones de página (`Action::make(...)`) pueden disparar `Notification::make()->success()->send()` para feedback inmediato.

## Extensión y buenas prácticas
- Reutiliza esquemas (`Schemas/*`) para evitar duplicación.
- Normaliza estados con `formatStateUsing` y `color()` en columnas.
- Usa `Enums` para etiquetas y mapeos; colorea con tokens de Filament.
- Mantén `Pages` enfocadas: `List` para filtros/acciones masivas, `View` para lectura fiel al `Form`, `Edit` para cambios.
- Al agregar nuevas entidades, replica el patrón: `Resource`, `Schemas/Form`, `Schemas/Infolist`, `Tables`, `Pages`.
- Limpia cachés si no ves cambios en UI: `php artisan optimize:clear`.

## Comandos útiles
- Instalar dependencias frontend: `npm install`
- Ejecutar build dev: `npm run dev`
- Enlazar almacenamiento: `php artisan storage:link`
- Migrar y seed: `php artisan migrate --seed`
- Limpiar cachés: `php artisan optimize:clear`
- Servidor de desarrollo: `php artisan serve`

---

Si necesitas que el `View Patient` sea idéntico al `Form`, asegúrate de que `PatientInfolist` refleje las mismas secciones y que `PatientResource::infolist` delegue a esa clase. Para el campo `sex`, mantén el mapeo `F/M` → `Femenino/Masculino` con colors de Filament y normalización del estado para una visualización robusta.