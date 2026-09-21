<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Patients\PatientResource;
use App\Filament\Resources\Patients\Schemas\PatientForm;
use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Enums\MedicalLeaveStatus;
use App\Models\Prescription;
use App\Models\MedicalLeave;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Section;
use App\Models\NursingEvolution;
use App\Models\SomatometricReading;
use App\Models\VitalSign;
use App\Enums\VisitType;
use App\Models\MedicalInitialAssessment;
use App\Models\NursingAssessmentInitial;
class ViewAppointment extends ViewRecord
{
    protected static string $resource = AppointmentResource::class;

    public function mount($record): void
    {
        parent::mount($record);
        if (Auth::user()?->role?->value === UserRole::RECEPCIONISTA->value && $this->record->status === AppointmentStatus::COMPLETED) {
            $this->redirect(AppointmentResource::getUrl('index'));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar Visita')
                ->color('primary')
                ->icon('heroicon-o-pencil')
                ->visible(fn () => $this->record->medicalRecord->patient->status === 'active' && ! in_array(Auth::user()?->role?->value, [UserRole::MEDICO_GENERAL->value, UserRole::ENFERMERO->value], true)),
                
            Action::make('complete_patient_record')
                ->label('Completar Expediente')
                ->icon('heroicon-o-identification')
                ->color('primary')
                ->visible(fn () => $this->record->medicalRecord->patient->status === 'pending_review')
                ->url(fn () => PatientResource::getUrl('edit', [
                    'record' => $this->record->medicalRecord->patient->id,
                    'redirect_to_appointment' => $this->record->id,
                ])),

            Action::make('register_consultation')
                ->label('Registrar Consulta')
                ->icon('heroicon-o-clipboard-document')
                ->color('success')
                ->visible(fn () => $this->record->status === AppointmentStatus::IN_PROGRESS && $this->record->doctor_id === Auth::id())
                ->form([
                    Textarea::make('diagnosis')
                        ->label('Diagnóstico')
                        ->required()
                        ->rows(4),
                    Textarea::make('treatment_plan')
                        ->label('Plan de Tratamiento')
                        ->rows(4),
                    Repeater::make('items')
                        ->label('Receta')
                        ->minItems(1)
                        ->addActionLabel('Añadir Medicamento')
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('drug')
                                ->label('Medicamento')
                                ->required(),
                            TextInput::make('dose')
                                ->label('Dosis')
                                ->placeholder('500 mg'),
                            TextInput::make('frequency')
                                ->label('Frecuencia')
                                ->placeholder('Cada 8 horas'),
                            TextInput::make('duration')
                                ->label('Duración')
                                ->placeholder('5 días'),
                            Select::make('route')
                                ->label('Vía')
                                ->options([
                                    'Oral' => 'Oral',
                                    'IM' => 'IM',
                                    'IV' => 'IV',
                                    'Topical' => 'Tópica',
                                ]),
                            Textarea::make('instructions')
                                ->label('Indicaciones')
                                ->rows(2),
                        ]),
                    Toggle::make('complete_visit')
                        ->label('Marcar visita como completada')
                        ->default(true),
                ])
                ->action(function (array $data): void {
                    $prescription = Prescription::create([
                        'medical_record_id' => $this->record->medical_record_id,
                        'appointment_id' => $this->record->id,
                        'doctor_id' => Auth::id(),
                        'issue_date' => now()->toDateString(),
                        'diagnosis' => $data['diagnosis'],
                        'notes' => $data['treatment_plan'] ?? null,
                        'items' => $data['items'] ?? [],
                    ]);

                    if (($data['complete_visit'] ?? true) === true) {
                        $this->record->update(['status' => AppointmentStatus::COMPLETED]);
                    }

                    Notification::make()
                        ->title('Consulta registrada')
                        ->success()
                        ->actions([
                            Action::make('ver_receta')
                                ->label('Ver receta')
                                ->url(route('prescription.download', ['prescriptionId' => $prescription->id, 'copyType' => 'patient']))
                                ->button(),
                        ])
                        ->send();

                    $this->redirect(AppointmentResource::getUrl('view', ['record' => $this->record]));
                }),

            Action::make('view_medical_record')
                ->label('Ver Expediente')
                ->icon('heroicon-o-identification')
                ->color('info')
                ->visible(fn () => in_array(Auth::user()?->role?->value, [UserRole::MEDICO_GENERAL->value, UserRole::ADMIN->value, UserRole::DIRECTOR->value], true))
                ->action(function (): void {
                    $url = \App\Filament\Resources\MedicalRecords\MedicalRecordResource::getUrl('view', [
                        'record' => $this->record->medical_record_id,
                    ]);
                    $url .= '?appointment_id=' . $this->record->id;
                    $this->redirect($url);
                }),

            Action::make('create_medical_leave')
                ->label('Crear Incapacidad')
                ->icon('heroicon-o-document-text')
                ->color('warning')
                ->visible(fn () => Prescription::where('medical_record_id', $this->record->medical_record_id)->exists() && in_array(Auth::user()?->role?->value, [UserRole::MEDICO_GENERAL->value, UserRole::ADMIN->value, UserRole::DIRECTOR->value], true))
                ->form([
                    Section::make('Detalles de la Incapacidad')
                        ->columns(2)
                        ->schema([
                            \Filament\Forms\Components\DatePicker::make('issue_date')->label('Fecha de Emisión')->default(now())->required()->disabled()->dehydrated(false),
                            \Filament\Forms\Components\DatePicker::make('start_date')->label('Fecha de Inicio')->native(false)->required()->live(),
                            \Filament\Forms\Components\TextInput::make('duration_in_days')->label('Duración (días)')->numeric()->required()->live(onBlur: true)->afterStateUpdated(function ($get,$set) { $startDate = $get('start_date'); $duration = $get('duration_in_days'); if ($startDate && $duration) { $set('end_date', \Carbon\Carbon::parse($startDate)->addDays($duration)->format('Y-m-d')); } }),
                            \Filament\Forms\Components\DatePicker::make('end_date')->label('Fecha de Fin')->native(false)->disabled()->dehydrated(false),
                            \Filament\Forms\Components\Textarea::make('reason')->label('Justificación / Diagnóstico Médico')->columnSpanFull()->required(),
                        ]),
                ])
                ->action(function (array $data): void {
                    $endDate = isset($data['end_date']) ? $data['end_date'] : \Carbon\Carbon::parse($data['start_date'])->addDays(($data['duration_in_days'] ?? 0))->format('Y-m-d');
                    $issueDate = isset($data['issue_date']) ? $data['issue_date'] : now()->toDateString();
                    $leave = MedicalLeave::create([
                        'medical_record_id' => $this->record->medical_record_id,
                        'doctor_id' => Auth::id(),
                        'issue_date' => $issueDate,
                        'start_date' => $data['start_date'],
                        'end_date' => $endDate,
                        'duration_in_days' => $data['duration_in_days'],
                        'reason' => $data['reason'],
                        'status' => MedicalLeaveStatus::PENDING_APPROVAL->value,
                    ]);
                    Notification::make()
                        ->title('Incapacidad creada')
                        ->success()
                        ->actions([
                            Action::make('ver_incapacidad')
                                ->label('Ver documento')
                                ->url(route('medical-leave.download', ['medicalLeaveId' => $leave->id, 'copyType' => 'patient']))
                                ->button(),
                        ])
                        ->send();
                    $this->redirect(AppointmentResource::getUrl('view', ['record' => $this->record]));
                }),

            Action::make('print_prescription_patient')
                ->label('Imprimir receta (Paciente)')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->visible(fn () => $this->record->status === AppointmentStatus::COMPLETED && Prescription::where('appointment_id', $this->record->id)->exists())
                ->url(fn () => route('prescription.download', [
                    'prescriptionId' => Prescription::where('appointment_id', $this->record->id)->orderByDesc('id')->value('id'),
                    'copyType' => 'patient',
                ])),

            Action::make('print_prescription_institution')
                ->label('Imprimir receta (Institución)')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->visible(fn () => $this->record->status === AppointmentStatus::COMPLETED && Prescription::where('appointment_id', $this->record->id)->exists())
                ->url(fn () => route('prescription.download', [
                    'prescriptionId' => Prescription::where('appointment_id', $this->record->id)->orderByDesc('id')->value('id'),
                    'copyType' => 'institution',
                ])),

            Action::make('register_nursing_initial')
                ->label('Registrar Hoja Inicial de Enfermería')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('warning')
                ->visible(fn () => $this->record->visit_type === VisitType::PRIMERA_VEZ->value && $this->record->medicalRecord->nursingAssessmentInitial === null && ! in_array(Auth::user()?->role?->value, [UserRole::MEDICO_GENERAL->value, UserRole::RECEPCIONISTA->value], true))
                ->form([
                    Section::make('Signos vitales')
                        ->columns(3)
                        ->schema([
                            TextInput::make('blood_pressure_systolic')->label('PA sistólica')->numeric()->minValue(50)->maxValue(250)->step('1')->rules(['integer']),
                            TextInput::make('blood_pressure_diastolic')->label('PA diastólica')->numeric()->minValue(30)->maxValue(150)->step('1')->rules(['integer']),
                            TextInput::make('heart_rate')->label('FC')->numeric()->minValue(30)->maxValue(220)->step('1')->rules(['integer']),
                            TextInput::make('respiratory_rate')->label('FR')->numeric()->minValue(6)->maxValue(40)->step('1')->rules(['integer']),
                            TextInput::make('temperature')->label('Temp (°C)')->numeric()->minValue(30)->maxValue(45)->step('0.1'),
                            TextInput::make('weight')->label('Peso (kg)')->numeric()->minValue(2)->maxValue(400)->step('0.1'),
                            TextInput::make('height_cm')->label('Talla (cm)')->numeric()->minValue(50)->maxValue(250)->step('1')->rules(['integer']),
                            TextInput::make('blood_glucose')->label('Glucosa')->numeric()->minValue(20)->maxValue(999)->step('1')->rules(['integer']),
                            TextInput::make('oxygen_saturation')->label('SpO2 (%)')->numeric()->minValue(0)->maxValue(100)->step('1')->rules(['integer']),
                        ]),
                    Textarea::make('notes')->label('Notas de enfermería')->rows(4),
                ])
                ->action(function (array $data): void {
                    $owner = $this->record;
                    $hasVitals = collect([
                        'blood_pressure_systolic','blood_pressure_diastolic','heart_rate','respiratory_rate',
                        'temperature','weight','height_cm','blood_glucose','oxygen_saturation','observations',
                    ])->some(fn ($k) => isset($data[$k]) && $data[$k] !== null && $data[$k] !== '');

                    $readingId = null;
                    if ($hasVitals) {
                        $int = fn ($v) => is_numeric($v) ? (int) round($v) : null;
                        $float1 = fn ($v) => is_numeric($v) ? round((float) $v, 1) : null;
                        $reading = SomatometricReading::create([
                            'medical_record_id' => $owner->medical_record_id,
                            'appointment_id' => $owner->id,
                            'user_id' => Auth::id(),
                            'blood_pressure_systolic' => $int($data['blood_pressure_systolic'] ?? null),
                            'blood_pressure_diastolic' => $int($data['blood_pressure_diastolic'] ?? null),
                            'heart_rate' => $int($data['heart_rate'] ?? null),
                            'respiratory_rate' => $int($data['respiratory_rate'] ?? null),
                            'temperature' => $float1($data['temperature'] ?? null),
                            'weight' => $float1($data['weight'] ?? null),
                            'height' => isset($data['height_cm']) ? round(($data['height_cm'] / 100), 3) : null,
                            'blood_glucose' => $int($data['blood_glucose'] ?? null),
                            'oxygen_saturation' => $int($data['oxygen_saturation'] ?? null),
                            'observations' => $data['observations'] ?? null,
                        ]);
                        $readingId = $reading->id;
                    }

                    NursingAssessmentInitial::create([
                        'medical_record_id' => $owner->medical_record_id,
                        'user_id' => Auth::id(),
                        'somatometric_reading_id' => $readingId,
                        'notes' => $data['notes'] ?? null,
                    ]);

                    Notification::make()->title('Hoja inicial de enfermería registrada')->success()->send();
                    $this->redirect(AppointmentResource::getUrl('index'));
                }),

            Action::make('register_medical_initial')
                ->label('Registrar Hoja Inicial Médica')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('warning')
                ->visible(fn () => $this->record->visit_type === VisitType::PRIMERA_VEZ->value && $this->record->medicalRecord->medicalInitialAssessment === null && in_array(Auth::user()?->role?->value, [UserRole::MEDICO_GENERAL->value, UserRole::ADMIN->value, UserRole::DIRECTOR->value], true))
                ->form([
                    Textarea::make('allergies')->label('Alergias')->rows(3),
                    Textarea::make('personal_pathological_history')->label('Antecedentes personales patológicos')->rows(4),
                    Textarea::make('gyneco_obstetric_history')->label('Antecedentes gineco-obstétricos')->rows(4),
                    Textarea::make('current_illness')->label('Padecimiento actual')->rows(5),
                    Textarea::make('physical_exam')->label('Exploración física')->rows(5),
                    Textarea::make('diagnosis')->label('Diagnóstico')->rows(3),
                    Textarea::make('treatment_note')->label('Nota de tratamiento')->rows(3),
                ])
                ->action(function (array $data): void {
                    $owner = $this->record;
                    MedicalInitialAssessment::create(array_merge($data, [
                        'medical_record_id' => $owner->medical_record_id,
                        'user_id' => Auth::id(),
                    ]));
                    Notification::make()->title('Hoja inicial médica registrada')->success()->send();
                    $this->redirect(AppointmentResource::getUrl('view', ['record' => $owner]));
                }),

            Action::make('register_nursing_evolution')
                ->label('Registrar Evolución de Enfermería')
                ->icon('heroicon-o-document-plus')
                ->color('info')
                ->visible(fn () => $this->record->visit_type === VisitType::SUBSECUENTE->value && ! in_array(Auth::user()?->role?->value, [UserRole::MEDICO_GENERAL->value, UserRole::RECEPCIONISTA->value], true))
                ->form([
                    Textarea::make('problem')->label('P'),
                    Textarea::make('subjective')->label('S'),
                    Textarea::make('objective')->label('O'),
                    Textarea::make('analysis')->label('A'),
                    Textarea::make('plan')->label('P'),
                    Section::make('Signos vitales')
                        ->columns(3)
                        ->schema([
                            TextInput::make('blood_pressure_systolic')->label('PA sistólica')->numeric()->minValue(50)->maxValue(250)->step('1')->rules(['integer']),
                            TextInput::make('blood_pressure_diastolic')->label('PA diastólica')->numeric()->minValue(30)->maxValue(150)->step('1')->rules(['integer']),
                            TextInput::make('heart_rate')->label('FC')->numeric()->minValue(30)->maxValue(220)->step('1')->rules(['integer']),
                            TextInput::make('respiratory_rate')->label('FR')->numeric()->minValue(6)->maxValue(40)->step('1')->rules(['integer']),
                            TextInput::make('temperature')->label('Temp (°C)')->numeric()->minValue(30)->maxValue(45)->step('0.1'),
                            TextInput::make('weight')->label('Peso (kg)')->numeric()->minValue(2)->maxValue(400)->step('0.1'),
                            TextInput::make('height_cm')->label('Talla (cm)')->numeric()->minValue(50)->maxValue(250)->step('1')->rules(['integer']),
                            TextInput::make('blood_glucose')->label('Glucosa')->numeric()->minValue(20)->maxValue(999)->step('1')->rules(['integer']),
                            TextInput::make('oxygen_saturation')->label('SpO2 (%)')->numeric()->minValue(0)->maxValue(100)->step('1')->rules(['integer']),
                        ]),
                ])
                ->action(function (array $data): void {
                    $owner = $this->record;
                    $hasVitals = collect([
                        'blood_pressure_systolic','blood_pressure_diastolic','heart_rate','respiratory_rate',
                        'temperature','weight','height_cm','blood_glucose','oxygen_saturation','observations',
                    ])->some(fn ($k) => isset($data[$k]) && $data[$k] !== null && $data[$k] !== '');

                    $readingId = null;
                    if ($hasVitals) {
                        $int = fn ($v) => is_numeric($v) ? (int) round($v) : null;
                        $float1 = fn ($v) => is_numeric($v) ? round((float) $v, 1) : null;
                        $reading = SomatometricReading::create([
                            'medical_record_id' => $owner->medical_record_id,
                            'appointment_id' => $owner->id,
                            'user_id' => Auth::id(),
                            'blood_pressure_systolic' => $int($data['blood_pressure_systolic'] ?? null),
                            'blood_pressure_diastolic' => $int($data['blood_pressure_diastolic'] ?? null),
                            'heart_rate' => $int($data['heart_rate'] ?? null),
                            'respiratory_rate' => $int($data['respiratory_rate'] ?? null),
                            'temperature' => $float1($data['temperature'] ?? null),
                            'weight' => $float1($data['weight'] ?? null),
                            'height' => isset($data['height_cm']) ? round(($data['height_cm'] / 100), 3) : null,
                            'blood_glucose' => $int($data['blood_glucose'] ?? null),
                            'oxygen_saturation' => $int($data['oxygen_saturation'] ?? null),
                            'observations' => $data['observations'] ?? null,
                        ]);
                        $readingId = $reading->id;
                    }

                    NursingEvolution::create([
                        'medical_record_id' => $owner->medical_record_id,
                        'appointment_id' => $owner->id,
                        'user_id' => Auth::id(),
                        'problem' => $data['problem'] ?? null,
                        'subjective' => $data['subjective'] ?? null,
                        'objective' => $data['objective'] ?? null,
                        'analysis' => $data['analysis'] ?? null,
                        'plan' => $data['plan'] ?? null,
                        'somatometric_reading_id' => $readingId,
                    ]);

                    Notification::make()->title('Evolución de enfermería registrada')->success()->send();
                    $this->redirect(AppointmentResource::getUrl('index'));
                }),
        ];
    }

        public function getTitle(): string
    {
        // La variable $this->record contiene la visita que se está viendo.
        // Construimos un título más descriptivo.
        return "Detalles de la Visita #{$this->getRecord()->ticket_number}";
    }
}
