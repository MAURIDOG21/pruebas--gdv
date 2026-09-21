# Panel de Recepción (Dashboard)

## Descripción General
El Panel de Recepción centraliza la operación diaria de recepción de pacientes: creación de visitas, apertura/cierre de turnos (consultorios), visualización de indicadores del día y tablas en vivo de flujo de pacientes.

- Página principal: `app/Filament/Pages/ReceptionDashboard.php:24-34,226-233`
- Registro del panel y página: `app/Providers/Filament/DashboardPanelProvider.php:37-61`
- Descubrimiento de widgets: `app/Providers/Filament/DashboardPanelProvider.php:57-58`

## Composición de la Página
- Encabezado
  - Widgets: `RecepcionStats`, `QuickActionsWidget` (`ReceptionDashboard.php:28-34`)
  - Acciones: Nueva visita, Abrir turno, Cerrar turno (`ReceptionDashboard.php:36-223`)
- Cuerpo
  - Widgets: `ColaRecepcionTable`, `UltimasVisitas`, `TurnosAbiertosWidget` (`ReceptionDashboard.php:226-233`)

## Encabezado: Acciones
- Nueva visita (`ReceptionDashboard.php:39-138`)
  - Campos: expediente médico, fecha, turno, consultorio activo, tipo, estado, motivo (`ReceptionDashboard.php:44-123`)
  - Crea `Appointment` y notifica éxito (`ReceptionDashboard.php:124-138`)
- Abrir turno (`ReceptionDashboard.php:140-179`)
  - Selección de `ClinicSchedule` activo y no abierto (`ReceptionDashboard.php:145-166`)
  - Acción: `openShift(Auth::user(), opening_notes)` y redirección (`ReceptionDashboard.php:171-179`)
- Cerrar turno (`ReceptionDashboard.php:181-222`)
  - Selección de `ClinicSchedule` abierto (`ReceptionDashboard.php:186-206`)
  - Confirmación modal y `closeShift(...)` (`ReceptionDashboard.php:211-222`)

## Encabezado: Widgets
### RecepcionStats
Indicadores del día y mini-gráfica.
- Título y disposición: `app/Filament/Widgets/RecepcionStats.php:16-20`
- KPIs del día: conteo por estado de visitas (`AppointmentStatus`) (`RecepcionStats.php:24-31`)
- Pacientes nuevos: `pending_review` (`RecepcionStats.php:32`)
- Consultorios activos hoy: `ClinicSchedule` (`RecepcionStats.php:33`)
- Mini-gráfica últimos 7 días: agrupación por fecha de creación (`RecepcionStats.php:36-51`)
- Bloques `Stat` y enlaces: `RecepcionStats.php:76-115`
- Actualización periódica: `RecepcionStats.php:118-119`

### QuickActionsWidget
Acciones rápidas y búsqueda por ticket.
- Metadatos: `app/Filament/Widgets/QuickActionsWidget.php:28-36`
- Búsqueda por ticket: campo y acción (`QuickActionsWidget.php:37-52`, vista Blade `resources/views/filament/widgets/quick-actions-widget.blade.php:1-12`)
- Nueva visita “walk-in”: `QuickActionsWidget.php:54-150`
- Abrir turno: `QuickActionsWidget.php:152-194`
- Cerrar turno: `QuickActionsWidget.php:196-240`
- Buscar por ticket (acción alternativa): `QuickActionsWidget.php:242-259`

## Cuerpo: Widgets
### ColaRecepcionTable
Lista de citas pendientes “hoy” con acciones.
- Metadatos: `app/Filament/Widgets/ColaRecepcionTable.php:14-18`
- Consulta: pendientes del día con relaciones (`ColaRecepcionTable.php:22-26`)
- Refresco y orden: `ColaRecepcionTable.php:27-28`
- Columnas principales: paciente, servicio, consultorio, tiempo transcurrido (`ColaRecepcionTable.php:29-47`)
- Acciones por registro: iniciar consulta, cancelar (`ColaRecepcionTable.php:49-63`)

### UltimasVisitas
Tabla en vivo de visitas del día con filtros.
- Metadatos: `app/Filament/Widgets/UltimasVisitas.php:15-19`
- Consulta y refresco: `UltimasVisitas.php:21-29`
- Columnas: paciente, servicio, consultorio, tipo, origen (íconos), estado, tiempos (`UltimasVisitas.php:31-111`)
- Filtros: hoy, últimos 30 minutos, pendientes, en consulta (`UltimasVisitas.php:113-129`)

### TurnosAbiertosWidget
Listado de turnos abiertos actuales.
- Metadatos: `app/Filament/Widgets/TurnosAbiertosWidget.php:12-16`
- Consulta: turnos abiertos con relaciones y orden (`TurnosAbiertosWidget.php:20-24`)
- Columnas: consultorio, servicio, médico, turno, fecha, abierto hace, usuario (`TurnosAbiertosWidget.php:27-49`)
- Refresco: `TurnosAbiertosWidget.php:25`

## Interacción con Turnos (ClinicSchedule)
- Apertura de turno desde Dashboard y Acciones Rápidas invoca `ClinicSchedule::openShift(...)` (`ReceptionDashboard.php:171-179`, `QuickActionsWidget.php:185-193`)
- Cierre de turno invoca `ClinicSchedule::closeShift(...)` (`ReceptionDashboard.php:214-222`, `QuickActionsWidget.php:231-239`)
- Lógica de modelo:
  - `app/Models/ClinicSchedule.php:82-111` apertura con intento de fijar `date = hoy` si no existe duplicado por `clinic_name`+`shift`+`date`
  - `app/Models/ClinicSchedule.php:114-127` cierre con auditoría y flags

## Modelos y Enums involucrados
- `Appointment`: creación y visualización en widgets (`ReceptionDashboard.php:124-135`, `ColaRecepcionTable.php:22-26`, `UltimasVisitas.php:21-26`)
- `ClinicSchedule`: selección de consultorio activo y turnos (`ReceptionDashboard.php:90-105,145-166,186-206`, `TurnosAbiertosWidget.php:20-24`)
- `Patient` y `MedicalRecord`: creación desde recepción y referencias (`ReceptionDashboard.php:62-79`, `QuickActionsWidget.php:78-96`)
- Enums:
  - `Shift`: selección de turno (`ReceptionDashboard.php:85-88`, `QuickActionsWidget.php:101-105`)
  - `VisitType`: tipo de visita (`ReceptionDashboard.php:110-113`, `QuickActionsWidget.php:121-124`)
  - `AppointmentStatus`: estado de visita (`ReceptionDashboard.php:114-118`, `QuickActionsWidget.php:125-129`, `RecepcionStats.php:24-31`)

## Notificaciones y Navegación
- Notificaciones de éxito/error al crear visitas y abrir/cerrar turnos (`ReceptionDashboard.php:136-138,174-178,216-221`; `QuickActionsWidget.php:147-149,188-192,234-239,257-258`)
- Redirecciones a dashboard o a la vista de la visita (`ReceptionDashboard.php:175-176`; `QuickActionsWidget.php:148-149,189-190`)

## Actualizaciones en vivo
- KPIs: cada 10s (`RecepcionStats.php:118-119`)
- Tablas: cola y últimas visitas cada 5s (`ColaRecepcionTable.php:27`, `UltimasVisitas.php:28`)
- Turnos abiertos: cada 10s (`TurnosAbiertosWidget.php:25`)

## Middleware y Registro del Panel
- Panel “dashboard” con middleware y autenticación (`DashboardPanelProvider.php:37-80`)
- Middleware personalizado: `'check.shift'` incluido (`DashboardPanelProvider.php:75-76`)

## Flujo típico en Recepción
1. Abrir turno de un consultorio activo desde encabezado o acciones rápidas (`ReceptionDashboard.php:140-179`, `QuickActionsWidget.php:152-194`).
2. Registrar visitas nuevas (expediente existente o creación rápida de paciente) (`ReceptionDashboard.php:39-138`, `QuickActionsWidget.php:54-150`).
3. Monitorear la cola de recepción y últimas visitas en vivo (`ColaRecepcionTable.php:14-66`, `UltimasVisitas.php:15-142`).
4. Cerrar turnos abiertos al finalizar (`ReceptionDashboard.php:181-222`, `QuickActionsWidget.php:196-240`).