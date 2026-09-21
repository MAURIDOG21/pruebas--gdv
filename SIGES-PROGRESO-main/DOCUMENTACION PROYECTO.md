# SIGES-PROGRESO — Informe Técnico Actualizado

## Notas de actualización (2025-11-19)

- Este documento integra tu redacción original y añade una revisión técnica alineada al estado actual del código.
- Incluye correcciones y precisiones con referencias `archivo:línea` para facilitar verificación.

## Estado técnico actual del proyecto

- Stack y paquetes principales desde `composer.json`:
  - `php ^8.2`, `laravel/framework ^11.31`, `filament/filament 4.0`, `laravel/sanctum ^4.2`, `barryvdh/laravel-dompdf ^3.1`, `maatwebsite/excel ^3.1`, `spatie/laravel-activitylog ^4.10`, `leandrocfe/filament-apex-charts 4.0`.
- Entidades (Modelos): `Patient`, `MedicalRecord`, `Appointment`, `SomatometricReading`, `MedicalLeave`, `Prescription`, `Service`, `ClinicSchedule`, `NursingAssessment*`, `NursingEvolution`, `Tutor`, `User`.
- Recursos de Filament: Pacientes, Expedientes, Citas, Horarios, Servicios, Usuarios; RelationManagers para historial clínico y documentos.
- API y seguridad: Endpoints bajo `auth:sanctum` (`routes/api.php:6,13,16`), middleware contextual de turno (`app/Http/Middleware/CheckShiftStatus.php:25-33,48-69`).
- Reporterías: Widgets Apex Charts y exportaciones Excel (`app/Filament/Widgets/*`, `app/Exports/*`).

## Diferencias clave respecto al documento original

- Citas y consultorios:
  - Se eliminó `clinic_room_number` y se incorporó `date` y `clinic_schedule_id` para vincular la cita a un horario diario.
  - Confirmación: `database/migrations/2025_10_25_120000_update_appointments_link_to_clinic_schedules.php:20-31`, `app/Models/Appointment.php:26-33`.

- Control de acceso:
  - No hay middleware `role:` ni Policies registradas en rutas; los roles existen vía Enum (`UserRole`).
  - Control contextual (ABAC) por turno mediante `CheckShiftStatus`.
  - Confirmación: `routes/web.php:9`, `app/Models/User.php:49`, `app/Http/Middleware/CheckShiftStatus.php:25-33,48-69`.

- Trazabilidad (sellos de usuario):
  - No se usan `created_by` / `updated_by` en tablas; la trazabilidad se realiza con `spatie/laravel-activitylog` y relaciones explícitas (`doctor_id`, `user_id`).
  - Confirmación: `app/Models/MedicalRecord.php:19`, `app/Models/Patient.php:20`, `app/Models/Appointment.php:17`.

- Somatometrías ligadas a citas:
  - `appointment_id` es opcional y se añadió por migración posterior.
  - Confirmación: `database/migrations/2025_10_16_000001_add_appointment_id_to_somatometric_readings_table.php:10-15`.

- Folios y secuencias PostgreSQL:
  - Expedientes con secuencia `medical_record_number_seq`: `app/Models/MedicalRecord.php:39-42`.
  - Incapacidades con secuencia `medical_leave_folio_seq`: `app/Models/MedicalLeave.php:49-52`.

- PDFs de incapacidades y recetas:
  - Rutas de descarga: `routes/web.php:10-14`.
  - Generación con DomPDF: `app/Http/Controllers/PdfGeneratorController.php:13-34,36-52`.

## Enums activos

- `PatientType`, `EmployeeStatus`, `AppointmentStatus`, `MedicalLeaveStatus`, `VisitType`, `Shift`, `Locality` (`app/Enums/*.php`).

## Campos cifrados

- Pacientes: `full_name`, `curp`, `contact_phone`, `address`, `disability_details` (`app/Models/Patient.php:39-45`).
- Expedientes: `consent_form_path` (`app/Models/MedicalRecord.php:32-33`).
- Citas: `reason_for_visit`, `notes` (`app/Models/Appointment.php:37-40`).

## Rutas clave actuales

- Redirección inicial: `routes/web.php:5-7`.
- Descarga de PDFs: `routes/web.php:10-14`.
- API protegida: `routes/api.php:6,13,16`.

## Módulos del panel (Filament)

- Pacientes, Expedientes, Citas, Horarios, Servicios, Usuarios.
- Documentos médicos con borrado masivo: `app/Filament/Resources/MedicalRecords/RelationManagers/MedicalDocumentsRelationManager.php:77-79`.

---

# Contenido original (convertido a Markdown)

## Portada

INSTITUTO TECNOLÓGICO SUPERIOR PROGRESO  
ORGANISMO PÚBLICO DESCENTRALIZADO DEL GOBIERNO DEL ESTADO  
CLAVE: 31ETI0004Q  

SIGES-PROGRESO SISTEMA ELECTRÓNICO DE EXPEDIENTES CLÍNICOS  
INFORME TÉCNICO DE RESIDENCIA PROFESIONAL  

CARRERA  
LICENCIATURA EN SISTEMAS COMPUTACIONALES  

RESIDENTE  
CESAR URIEL CRUZ CANUL  

ASESOR(A) EXTERNO  
MTI. JOSÉ EMILIANO TUYIN ANGUAS  

ASESOR(A) INTERNO  
MTRA. LIGIA BREATIZ CHUC-US  

Progreso, Yucatán  
2025  

## Contenido

- Carta terminación  
- Contenido  
- Índice de tablas y figuras  
- Resumen  
- CAPÍTULO I. INTRODUCCIÓN  
  - Justificación  
  - Objetivos  
  - Objetivo General  
  - Objetivos Específicos  
  - Fundamento teórico  
- CAPÍTULO II. IDENTIFICACIÓN DE LA PROBLEMÁTICA  
  - Características del área  
  - Identificación de la problemática  
  - Alcances y limitaciones  
- CAPÍTULO III. METODOLOGÍA  
  - Procedimiento y descripción de las actividades realizadas  
  - Sprint 1: Digitalización de expedientes  
  - Sprint 2: Reportes y BI  

## Resumen

En este momento, aún no he redactado los resultados, el análisis ni las conclusiones finales del proyecto.

## Capítulo I. Introducción

### Justificación

La justificación para el desarrollo del Sistema Electrónico de Expedientes Clínicos para los Servicios de Salud del H. Ayuntamiento de Progreso se fundamenta en la magnitud del problema operativo de gestión manual de más de 1,200 registros clínicos mensuales, con riesgos de confidencialidad, trazabilidad y calidad de datos.

### Objetivos

- Desarrollar un sistema web que optimice la gestión de la información clínica, asegure la privacidad de datos sensibles y provea inteligencia de negocio.
- Optimizar la gestión de historiales clínicos mediante digitalización y flujos operativos ordenados.
- Asegurar confidencialidad, integridad y trazabilidad con autenticación robusta, control de acceso y auditoría.
- Proveer inteligencia operativa mediante análisis clínico-demográfico y paneles de mando.

### Fundamento teórico (Síntesis)

- Metodologías ágiles y MVC en Laravel (Eloquent, Blade/Livewire/Filament).
- Componentes reactivos con Livewire y Filament; HTML-over-the-wire.
- Observador (eventos/listeners) y eventos de modelo para automatizaciones.
- Diseño relacional y normalización; integridad referencial (cascade vs restrict).
- Secuencias transaccionales en PostgreSQL para folios.
- ORM Eloquent, mutators/accessors, Enums tipados.
- Trazabilidad y auditoría: SoftDeletes y Activity Log.
- BI: OLTP vs OLAP, agregaciones SQL, visualización con ApexCharts y exportación con Excel.
- Seguridad: Autenticación (bcrypt), RBAC conceptual, ABAC contextual (turno), OWASP (SQLi/XSS/CSRF).

## Capítulo II. Identificación de la problemática

- Dependencia de expedientes físicos, ineficiencia operativa, vulnerabilidad de confidencialidad y falta de indicadores confiables.
- Plan de acción por sprints: digitalización, seguridad/control de accesos, reportes inteligentes.
- Alcances y limitaciones: entorno LAN, roles, servicios específicos, datos simulados, sin retro-digitalización.

## Capítulo III. Metodología

### Sprint 1: Digitalización de expedientes

- Modelado de datos de `patients`, `medical_records`, `somatometric_readings` con integridad y trazabilidad.
- Creación automática de expediente al registrar paciente (`app/Models/Patient.php:71-74`).
- Folio del expediente por secuencia (`app/Models/MedicalRecord.php:39-42`).
- Formularios reactivos en Filament (Tabs, validaciones CURP, autocompletado, visibilidad condicional, alta rápida de tutor).
- Listado con columnas enriquecidas y filtros por Tabs (tipos de paciente).

### Sprint 2: Reportes y BI

- Dashboard operativo (polling 5s) en recepción.
- Indicadores con ApexCharts: estado de visitas, tendencia semanal, servicios más solicitados, productividad por médico, composición de pacientes, nuevos vs recurrentes.
- Exportación de datos con `maatwebsite/excel` y modales de parámetros.

## Anexos

### Rutas y descargas PDF

- Incapacidades y recetas: `routes/web.php:10-14`, controlador: `app/Http/Controllers/PdfGeneratorController.php:13-34,36-52`.

### Documentos médicos (UI)

- Borrado masivo en gestor de documentos: `app/Filament/Resources/MedicalRecords/RelationManagers/MedicalDocumentsRelationManager.php:77-79`.

---

> Nota: Este Markdown conserva la estructura esencial del documento original y agrega secciones técnicas actualizadas con referencias al código vigente para facilitar su mantenimiento.