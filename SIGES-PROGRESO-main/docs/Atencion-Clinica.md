# Atención Clínica: Hoja Inicial de Enfermería, Hoja Inicial Médica, Evolución de Enfermería y Registro de Consulta

## Descripción General
Este documento describe el flujo completo de registro clínico desde recepción/consulta: hojas iniciales (enfermería y médica), notas de evolución de enfermería y el registro de consulta médica con receta y descarga de PDF.

- Página operativa principal: `app/Filament/Resources/Appointments/Pages/ViewAppointment.php`
- Recursos/relaciones dentro del expediente: `app/Filament/Resources/MedicalRecords/RelationManagers/*`
- Modelos y migraciones: `app/Models/*`, `database/migrations/*`
- Recetas: creación, visualización y descarga (`app/Models/Prescription.php`, `app/Http/Controllers/PdfGeneratorController.php`, `routes/web.php`, `resources/views/pdf/prescription.blade.php`)

## Acciones en la vista de la Visita
La página de detalle de visita integra todas las acciones clínicas.

- Archivo: `app/Filament/Resources/Appointments/Pages/ViewAppointment.php`
  - Registrar Consulta: `ViewAppointment.php:55-122`
    - Formulario con `diagnosis`, `treatment_plan`, y `items` para la receta.
    - Crea `Prescription` y puede marcar la visita como completada.
  - Imprimir receta (Paciente/Institución): `ViewAppointment.php:124-142`
    - Usa la última receta del expediente de la visita.
  - Registrar Hoja Inicial de Enfermería: `ViewAppointment.php:144-202`
    - Captura signos vitales y notas; genera `SomatometricReading` y vincula `NursingAssessmentInitial`.
  - Registrar Hoja Inicial Médica: `ViewAppointment.php:204-226`
    - Registra antecedentes, padecimiento actual, examen físico, diagnóstico y nota de tratamiento.
  - Registrar Evolución de Enfermería: `ViewAppointment.php:228-295`
    - Captura SOAP (P/S/O/A/P) y signos vitales; genera `SomatometricReading` y `NursingEvolution`.

## Hoja Inicial de Enfermería
Permite registrar signos vitales y notas al inicio de la atención de enfermería.

- Acción en visita: `ViewAppointment.php:144-165` (formulario) y `ViewAppointment.php:166-202` (acción)
  - Campos principales del formulario:
    - PA sistólica/diastólica, FC, FR, Temp (°C), Peso (kg), Talla (cm), Glucosa, SpO2, Observaciones.
    - Talla en cm: `ViewAppointment.php:159` (captura) → guardado como metros: `ViewAppointment.php:185`.
  - Persistencia:
    - Crea `SomatometricReading` con `medical_record_id`, `appointment_id` y `user_id` (`ViewAppointment.php:175-190`).
    - Crea `NursingAssessmentInitial` con vínculo a la lectura (`ViewAppointment.php:193-201`).

- Relación en expediente: `app/Filament/Resources/MedicalRecords/RelationManagers/NursingAssessmentInitialRelationManager.php`
  - Form y vista detallada con signos vitales y notas (`NursingAssessmentInitialRelationManager.php:23-43`, `45-91`).
  - Visualiza Talla en cm (convierte metros a cm): `NursingAssessmentInitialRelationManager.php:75-77`.

- Somatometría: `app/Filament/Resources/MedicalRecords/RelationManagers/SomatometricReadingsRelationManager.php`
  - Form captura `height_cm` y al guardar convierte a metros (`SomatometricReadingsRelationManager.php:68-119`).
  - Tabla e Infolist muestran talla en cm (`SomatometricReadingsRelationManager.php:86-88`, `150-152`).

- Modelo y migración relevantes:
  - `app/Models/SomatometricReading.php` (campos y relaciones; altura almacenada en metros).
  - `database/migrations/2025_09_24_010005_create_somatometric_readings_table.php:11-17` (estructura).
  - `app/Models/NursingAssessmentInitial.php` y `database/migrations/2025_11_15_000500_create_nursing_assessments_initial_table.php` (único por expediente).

## Hoja Inicial Médica
Registra la evaluación inicial del médico.

- Acción en visita: `ViewAppointment.php:204-227`
  - Formulario: alergias, antecedentes personales patológicos, gineco-obstétricos, padecimiento actual, exploración física, diagnóstico, tratamiento (`ViewAppointment.php:209-217`).
  - Persistencia: `MedicalInitialAssessment::create(...)` con `medical_record_id` y `user_id` (`ViewAppointment.php:218-223`).

- Relación en expediente: `app/Filament/Resources/MedicalRecords/RelationManagers/MedicalInitialAssessmentRelationManager.php`
  - Form: `MedicalInitialAssessmentRelationManager.php:22-33`.
  - Vista Infolist: `MedicalInitialAssessmentRelationManager.php:47-71`.

- Modelo y migración:
  - `app/Models/MedicalInitialAssessment.php` (campos encriptados y relaciones).
  - `database/migrations/2025_11_15_000510_create_medical_initial_assessments_table.php` (único por expediente).

## Evolución de Enfermería (Nota)
Registra una evolución con esquema SOAP y signos vitales.

- Acción en visita: `ViewAppointment.php:228-295`
  - Formulario: P, S, O, A, P y signos vitales (`ViewAppointment.php:234-252`).
  - Persistencia: crea `SomatometricReading` (`ViewAppointment.php:262-277`) y `NursingEvolution` (`ViewAppointment.php:281-291`).

- Relación en expediente: `app/Filament/Resources/Appointments/RelationManagers/NursingEvolutionsRelationManager.php`
  - Form y mutación para generar lectura somatométrica (convierte talla cm→m): `NursingEvolutionsRelationManager.php:25-48`, `76-117`.
  - Tabla resumen con signos: `NursingEvolutionsRelationManager.php:51-74`.
  - Vista Infolist: `NursingEvolutionsRelationManager.php:116-151`.

- Modelo y migración:
  - `app/Models/NursingEvolution.php` (campos encriptados y relaciones).
  - `database/migrations/2025_11_15_000520_create_nursing_evolutions_table.php`.

## Registro de Consulta Médica y Recetas
Permite registrar diagnóstico, plan de tratamiento y generar una receta.

- Acción en visita: `ViewAppointment.php:55-122`
  - Formulario de consulta:
    - Diagnóstico (`ViewAppointment.php:61-64`), Plan (`ViewAppointment.php:65-67`).
    - Receta como `Repeater` (Medicamento, Dosis, Frecuencia, Duración, Vía, Indicaciones) (`ViewAppointment.php:68-97`).
    - Opción para completar visita (`ViewAppointment.php:98-101`).
  - Persistencia: creación de `Prescription` con `items` encriptados y `issue_date` (`ViewAppointment.php:103-111`).
  - Marcado de visita como completada (`ViewAppointment.php:112-114`).
  - Notificación y redirección (`ViewAppointment.php:116-122`).

- Impresión/descarga de recetas:
  - Acciones rápidas en visita: `ViewAppointment.php:124-142` (Paciente/Institución).
  - Relación en expediente: `app/Filament/Resources/MedicalRecords/RelationManagers/PrescriptionsRelationManager.php:93-103` (imprimir en nueva pestaña).
  - Ruta: `routes/web.php:13-14` → nombre `prescription.download`.
  - Controlador: `app/Http/Controllers/PdfGeneratorController.php:36-52`.
  - Plantilla PDF: `resources/views/pdf/prescription.blade.php` (diagnóstico, tabla de medicamentos, notas, firma).

- Modelo de receta:
  - `app/Models/Prescription.php:18-33` (fillable/casts: `issue_date`, `diagnosis`, `notes`, `items` encriptados).
  - Generación automática de `doctor_id` y `folio`: `Prescription.php:35-50`.
  - Relaciones: `medicalRecord()` y `doctor()` (`Prescription.php:77-85`).

## Consideraciones de Datos y Seguridad
- Encriptación de información clínica sensible (`diagnosis`, `notes`, campos de evoluciones e iniciales) para proteger datos.
- Integridad referencial mediante FKs y unicidad por expediente en iniciales (médica y enfermería).
- Conversión de unidades estandarizada: Talla se ingresa en cm en formularios y se almacena en m.

## Flujo de Trabajo Sugerido
1. Registrar Hoja Inicial de Enfermería si es primera vez: signos y notas.
2. Registrar Hoja Inicial Médica: antecedentes, examen, diagnóstico preliminar.
3. Realizar evoluciones de enfermería subsecuentes según sea necesario.
4. Registrar consulta médica: diagnóstico final, plan y generar receta.
5. Descargar receta para paciente o institución desde la visita o desde el expediente.