<?php

namespace App\Filament\Resources\Appointments\Schemas;

use App\Enums\AppointmentStatus;
use App\Enums\VisitType;
use App\Enums\Shift;
use App\Enums\UserRole;
use App\Filament\Resources\Patients\Schemas\PatientForm;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Models\ClinicSchedule;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)           
            ->components([
                // Campo principal: Selección de Expediente Médico
                Select::make('medical_record_id')
                    ->label('Expediente Médico')
                    ->placeholder('Buscar por número de expediente...')
                    ->options(function () {
                        return MedicalRecord::with('patient')
                            ->get()
                            ->mapWithKeys(function ($record) {
                                return [
                                    $record->id => $record->record_number . ' - ' . $record->patient->full_name
                                ];
                            });
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required()
                    ->columnSpanFull()
                    ->afterStateUpdated(function ($state, $set) {
                        if ($state) {
                            $medicalRecord = MedicalRecord::with('patient')->find($state);
                            if ($medicalRecord && $medicalRecord->patient) {
                                $patient = $medicalRecord->patient;
                                $set('patient_name', $patient->full_name);
                                
                                // Calcular la edad a partir de date_of_birth
                                if ($patient->date_of_birth) {
                                    $age = Carbon::parse($patient->date_of_birth)->age;
                                    $set('patient_age', $age . ' años');
                                } else {
                                    $set('patient_age', 'No disponible');
                                }
                                
                                $set('patient_sex', $patient->sex === 'M' ? 'Masculino' : 'Femenino');
                            }
                        } else {
                            $set('patient_name', null);
                            $set('patient_age', null);
                            $set('patient_sex', null);
                        }
                    })
                    ->createOptionForm(
                        // Reutilizamos el esquema completo de paciente desde PatientForm
                        // El método getBasicPatientFormSchema() ahora maneja automáticamente el contexto
                        PatientForm::getBasicPatientFormSchema()
                    )
                    ->createOptionUsing(function (array $data,  $get) {
                        // Crear el paciente (esto automáticamente crea un MedicalRecord en el evento created)
                        $patientData = [
                            'full_name' => $data['full_name'],
                            'date_of_birth' => $data['date_of_birth'],
                            'sex' => $data['sex'],
                            'curp' => $data['curp'] ?? null,
                            'contact_phone' => $data['contact_phone'] ?? null,
                            'locality' => $data['locality'] ?? null,
                        ];
                        
                        // Añadir tutor_id si está presente en los datos
                        if (isset($data['tutor_id'])) {
                            $patientData['tutor_id'] = $data['tutor_id'];
                        }
                        
                        $patient = Patient::create($patientData);

                        // Actualizar el expediente médico que ya fue creado automáticamente
                        $medicalRecord = $patient->medicalRecord;
                        $medicalRecord->update([
                            'patient_type' => $data['patient_type'],
                        ]);

                        return $medicalRecord->id;
                    }),

                // Información del paciente (visible solo cuando se selecciona un expediente)
                Fieldset::make('Información del Paciente')
                    ->schema([
                        TextInput::make('patient_name')
                            ->label('Nombre del Paciente')
                            ->disabled()
                            ->dehydrated(false),
                        
                        TextInput::make('patient_age')
                            ->label('Edad')
                            ->disabled()
                            ->dehydrated(false),
                        
                        TextInput::make('patient_sex')
                            ->label('Sexo')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(3)
                    ->columnSpanFull()
                    ->visible(fn ($get, $livewire) => filled($get('medical_record_id')))
                    // Cargar datos del paciente cuando se está editando un registro existente
                    ->afterStateHydrated(function ($component, $state, $livewire) {
                        // Solo ejecutar si estamos en modo edición y hay un medical_record_id
                        if (isset($livewire->record) && $livewire->record->medical_record_id) {
                            $medicalRecord = MedicalRecord::with('patient')->find($livewire->record->medical_record_id);
                            if ($medicalRecord && $medicalRecord->patient) {
                                $patient = $medicalRecord->patient;
                                $livewire->data['patient_name'] = $patient->full_name;
                                
                                // Calcular la edad a partir de date_of_birth
                                if ($patient->date_of_birth) {
                                    $age = Carbon::parse($patient->date_of_birth)->age;
                                    $livewire->data['patient_age'] = $age . ' años';
                                } else {
                                    $livewire->data['patient_age'] = 'No disponible';
                                }
                                
                                $livewire->data['patient_sex'] = $patient->sex === 'M' ? 'Masculino' : 'Femenino';
                            }
                        }
                    }),

                // Campos del formulario de cita
                TextInput::make('ticket_number')
                    ->label('Número de Ticket')
                    ->placeholder('Se generará automáticamente si se deja vacío')
                    ->disabled()
                    ->helperText('Dejar vacío para generar ticket walk-in automáticamente'),

                DatePicker::make('date')
                    ->label('Fecha de la cita')
                    ->default(now()->toDateString())
                    ->required()
                    ->live()
                    ->disabled()
                    ->helperText('La fecha de la cita corresponde al día actual'),

                Radio::make('shift')
                    ->label('Turno')
                    ->options([
                        Shift::MATUTINO->value => 'Matutino',
                        Shift::VESPERTINO->value => 'Vespertino',
                    ])
                    ->inline()
                    ->required()
                    ->live()
                    ->columnSpanFull(),

                Select::make('clinic_schedule_id')
                    ->label('Consultorio activo')
                    ->relationship('clinicSchedule', 'clinic_name', modifyQueryUsing: function (Builder $query,  $get) {
                        $date = $get('date');
                        $shift = $get('shift');
                        return $query
                            ->where('is_active', true)
                            ->where('is_shift_open', true)
                            ->when($date, fn ($q) => $q->whereDate('date', $date))
                            ->when($shift, fn ($q) => $q->where('shift', $shift));
                    })
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->required()
                    ->visible(fn ($get) => filled($get('shift')))
                    ->columnSpanFull()
                    ->getOptionLabelFromRecordUsing(function (ClinicSchedule $record) {
                        $doctor = optional($record->user)->name;
                        $service = optional($record->service)->name;
                        return $record->clinic_name . ' — Médico: ' . ($doctor ?? 'N/A') . ' — Servicio: ' . ($service ?? 'N/A');
                    })
                    ->hint('Seleccione un consultorio abierto para el turno elegido')
                    ->afterStateHydrated(function ($component, $state, $livewire) {
                        if ($state) {
                            $schedule = ClinicSchedule::with(['user', 'service'])->find($state);
                            if ($schedule) {
                                if (blank($livewire->data['shift'] ?? null)) {
                                    // Usar el valor del enum para evitar desajustes
                                    $livewire->data['shift'] = $schedule->shift->value;
                                }
                                if (blank($livewire->data['date'] ?? null)) {
                                    $livewire->data['date'] = optional($schedule->date)->toDateString() ?? $schedule->date;
                                }
                                $livewire->data['service_id'] = $schedule->service_id;
                                $livewire->data['doctor_id'] = $schedule->user_id;
                                $livewire->data['consultorio_name'] = $schedule->clinic_name;
                                $livewire->data['consultorio_doctor'] = optional($schedule->user)->name ?? 'N/A';
                                $livewire->data['consultorio_servicio'] = optional($schedule->service)->name ?? 'N/A';
                                $livewire->data['turno_estado'] = $schedule->is_shift_open ? 'Abierto' : 'Cerrado';
                            }
                        }
                    })
                    ->afterStateUpdated(function ($state,  $set) {
                        if ($state) {
                            $schedule = ClinicSchedule::with(['user', 'service'])->find($state);
                            if ($schedule) {
                                // Sincronizar turno con el consultorio seleccionado (usar valor del enum)
                                $set('shift', $schedule->shift->value);
                                $set('date', optional($schedule->date)->toDateString() ?? $schedule->date);
                                $set('service_id', $schedule->service_id);
                                $set('doctor_id', $schedule->user_id);
                                $set('consultorio_name', $schedule->clinic_name);
                                $set('consultorio_doctor', optional($schedule->user)->name ?? 'N/A');
                                $set('consultorio_servicio', optional($schedule->service)->name ?? 'N/A');
                                $set('turno_estado', $schedule->is_shift_open ? 'Abierto' : 'Cerrado');
                            }
                        } else {
                            $set('shift', null);
                            $set('date', null);
                            $set('service_id', null);
                            $set('doctor_id', null);
                            $set('consultorio_name', null);
                            $set('consultorio_doctor', null);
                            $set('consultorio_servicio', null);
                            $set('turno_estado', null);
                        }
                    }),

                Fieldset::make('Detalles del Consultorio')
                    ->schema([
                        TextInput::make('consultorio_name')
                            ->label('Consultorio')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('consultorio_doctor')
                            ->label('Médico')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('consultorio_servicio')
                            ->label('Servicio')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('turno_estado')
                            ->label('Estado del turno')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(4)
                    ->columnSpanFull()
                    ->visible(fn ($get) => filled($get('clinic_schedule_id'))),

                Select::make('service_id')
                    ->label('Servicio')
                    ->relationship('service', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn ($get) => !($get('manual_edit_enabled') ?? false))
                    ->helperText('Se llena automáticamente al elegir el consultorio activo.')
                    ->visible(false),
                Select::make('visit_type')
                    ->label('Tipo de Visita')
                    ->options(VisitType::class)
                    ->default(VisitType::PRIMERA_VEZ)
                    ->required(),

                Select::make('doctor_id')
                    ->label('Doctor')
                    ->relationship('doctor', 'name')
                    ->searchable()
                    ->preload()
                    ->disabled(fn ($get) => !($get('manual_edit_enabled') ?? false))
                    ->helperText('Asignado automáticamente según el consultorio/turno.')
                    ->visible(false),

                Toggle::make('manual_edit_enabled')
                    ->label('Edición manual de asignaciones')
                    ->helperText('Permite editar los campos de médico y servicio')
                    ->reactive()
                    ->visible(function () {
                        $user = Auth::user();
                        return $user && in_array($user->role, [UserRole::ADMIN, UserRole::DIRECTOR]);
                    })
                    ->visible(false),

                Select::make('status')
                    ->label('Estado de la visita')
                    ->options(AppointmentStatus::class)
                    ->default(AppointmentStatus::PENDING)
                    ->required()
                    ->disabled()
                    ->helperText('El estado inicial es "En revisión"'),
                DateTimePicker::make('created_at')
                    ->label('Fecha de Creación')
                    ->displayFormat('d/m/Y H:i:s')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\ViewRecord || $livewire instanceof \Filament\Resources\Pages\EditRecord)
                    ->visible(false),
                Textarea::make('reason_for_visit')
                    ->label('Motivo de la Visita')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),

                Textarea::make('notes')
                    ->label('Notas Adicionales')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
