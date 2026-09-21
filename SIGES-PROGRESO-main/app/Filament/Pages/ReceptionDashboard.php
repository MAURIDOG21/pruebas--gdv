<?php

namespace App\Filament\Pages;

use App\Enums\AppointmentStatus;
use App\Enums\Shift;
use App\Enums\VisitType;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Patients\Schemas\PatientForm;
use App\Models\Appointment;
use App\Models\ClinicSchedule;
use App\Models\MedicalRecord;
use App\Models\Patient;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class ReceptionDashboard extends Dashboard
{
    protected static ?string $title = 'Panel de Recepción';
    public static function shouldRegisterNavigation(): bool
    {
        $role = Auth::user()?->role?->value ?? null;
        return ! in_array($role, [\App\Enums\UserRole::MEDICO_GENERAL->value, \App\Enums\UserRole::ENFERMERO->value], true);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\RecepcionStats::class,
            \App\Filament\Widgets\QuickActionsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newVisit')
                ->label('Nueva visita')
                ->icon('heroicon-m-plus')
                ->color('success')
                ->form([
                    Select::make('medical_record_id')
                        ->label('Expediente Médico')
                        ->options(function () {
                            return MedicalRecord::with('patient')
                                ->get()
                                ->mapWithKeys(function ($record) {
                                    return [
                                        $record->id => $record->record_number . ' - ' . $record->patient->full_name,
                                    ];
                                });
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->createOptionForm(
                            PatientForm::getBasicPatientFormSchema()
                        )
                        ->createOptionUsing(function (array $data) {
                            $patientData = [
                                'full_name' => $data['full_name'],
                                'date_of_birth' => $data['date_of_birth'],
                                'sex' => $data['sex'],
                                'curp' => $data['curp'] ?? null,
                                'contact_phone' => $data['contact_phone'] ?? null,
                                'locality' => $data['locality'] ?? null,
                            ];
                            if (isset($data['tutor_id'])) {
                                $patientData['tutor_id'] = $data['tutor_id'];
                            }
                            $curpHash = isset($patientData['curp']) && $patientData['curp']
                                ? hash('sha256', strtoupper(trim($patientData['curp'])))
                                : null;
                            $patient = null;
                            if ($curpHash) {
                                $patient = Patient::where('curp_hash', $curpHash)->first();
                            }
                            if (! $patient) {
                                $patient = Patient::create($patientData);
                            }
                            $medicalRecord = MedicalRecord::where('patient_id', $patient->id)->first();
                            if (! $medicalRecord) {
                                try {
                                    $medicalRecord = MedicalRecord::firstOrCreate(['patient_id' => $patient->id], []);
                                } catch (\Illuminate\Database\QueryException $e) {
                                    $medicalRecord = MedicalRecord::where('patient_id', $patient->id)->first();
                                }
                            }
                            $medicalRecord->update([
                                'patient_type' => $data['medicalRecord']['patient_type'] ?? $data['patient_type'] ?? null,
                                'employee_status' => $data['medicalRecord']['employee_status'] ?? $data['employee_status'] ?? null,
                                'consent_form_path' => $data['medicalRecord']['consent_form_path'] ?? $data['consent_form_path'] ?? null,
                            ]);
                            return $medicalRecord->id;
                        })
                        ->afterStateUpdated(function ($state, $set) {
                            if ($state) {
                                $medicalRecord = MedicalRecord::with('patient')->find($state);
                                if ($medicalRecord && $medicalRecord->patient) {
                                    $patient = $medicalRecord->patient;
                                    $set('patient_name', $patient->full_name);
                                    $age = $patient->date_of_birth ? Carbon::parse($patient->date_of_birth)->age : null;
                                    $set('patient_age', $age ? ($age . ' años') : 'No disponible');
                                    $set('patient_sex', $patient->sex === 'M' ? 'Masculino' : 'Femenino');
                                }
                            } else {
                                $set('patient_name', null);
                                $set('patient_age', null);
                                $set('patient_sex', null);
                            }
                        }),

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
                        ->visible(fn ($get) => filled($get('medical_record_id'))),
                    DatePicker::make('date')
                        ->label('Fecha')
                        ->default(now()->toDateString())
                        ->required()
                        ->disabled()
                        ->helperText('La fecha corresponde al día actual'),
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
                        ->options(function ($get) {
                            $date = $get('date');
                            $shift = $get('shift');
                            return ClinicSchedule::query()
                                ->where('is_active', true)
                                ->where('is_shift_open', true)
                                ->when($date, fn ($q) => $q->whereDate('date', $date))
                                ->when($shift, fn ($q) => $q->where('shift', $shift))
                                ->orderBy('clinic_name')
                                ->with(['user', 'service'])
                                ->get()
                                ->mapWithKeys(fn ($schedule) => [
                                    $schedule->id => $schedule->clinic_name . ' — Médico: ' . ($schedule->user->name ?? 'N/A') . ' — Servicio: ' . ($schedule->service->name ?? 'N/A'),
                                ]);
                        })
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->required()
                        ->visible(fn ($get) => filled($get('shift')))
                        ->columnSpanFull()
                        ->hint('Seleccione un consultorio abierto para el turno elegido')
                        ->afterStateHydrated(function ($component, $state, $livewire) {
                            if ($state) {
                                $schedule = ClinicSchedule::with(['user', 'service'])->find($state);
                                if ($schedule) {
                                    // Sincronizar turno/fecha y autocompletar
                                    if (blank($livewire->data['shift'] ?? null)) {
                                        $livewire->data['shift'] = $schedule->shift->value;
                                    }
                                    if (blank($livewire->data['date'] ?? null)) {
                                        $livewire->data['date'] = optional($schedule->date)->toDateString() ?? $schedule->date;
                                    }
                                    $livewire->data['service_id'] = $schedule->service_id;
                                    $livewire->data['doctor_id'] = $schedule->user_id;
                                    // Panel informativo
                                    $livewire->data['consultorio_name'] = $schedule->clinic_name;
                                    $livewire->data['consultorio_doctor'] = optional($schedule->user)->name ?? 'N/A';
                                    $livewire->data['consultorio_servicio'] = optional($schedule->service)->name ?? 'N/A';
                                    $livewire->data['turno_estado'] = $schedule->is_shift_open ? 'Abierto' : 'Cerrado';
                                }
                            }
                        })
                        ->afterStateUpdated(function ($state, $set) {
                            if ($state) {
                                $schedule = ClinicSchedule::with(['user', 'service'])->find($state);
                                if ($schedule) {
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
                            TextInput::make('consultorio_name')->label('Consultorio')->disabled()->dehydrated(false),
                            TextInput::make('consultorio_doctor')->label('Médico')->disabled()->dehydrated(false),
                            TextInput::make('consultorio_servicio')->label('Servicio')->disabled()->dehydrated(false),
                            TextInput::make('turno_estado')->label('Estado del turno')->disabled()->dehydrated(false),
                        ])
                        ->columns(4)
                        ->columnSpanFull()
                        ->visible(fn ($get) => filled($get('clinic_schedule_id'))),

                    Hidden::make('service_id'),
                    Hidden::make('doctor_id'),
                    Select::make('visit_type')
                        ->label('Tipo de visita')
                        ->options(VisitType::class)
                        ->required(),
                    Select::make('status')
                        ->label('Estado')
                        ->options(AppointmentStatus::class)
                        ->default(AppointmentStatus::PENDING)
                        ->required()
                        ->disabled()
                        ->helperText('El estado inicial es "En revisión"'),
                    Textarea::make('reason_for_visit')
                        ->label('Motivo de la visita')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $schedule = $data['clinic_schedule_id'] ? ClinicSchedule::find($data['clinic_schedule_id']) : null;
                    $scheduleDate = ($schedule && $schedule->date) ? \Illuminate\Support\Carbon::parse($schedule->date)->toDateString() : null;
                    $formDateStr = isset($data['date']) ? \Illuminate\Support\Carbon::parse($data['date'])->toDateString() : ($scheduleDate ?? now()->toDateString());
                    $dateMatches = $scheduleDate && $formDateStr && $scheduleDate === $formDateStr;
                    $shiftValue = isset($data['shift']) ? ($data['shift'] instanceof \BackedEnum ? $data['shift']->value : $data['shift']) : ($schedule?->shift?->value ?? null);
                    $shiftMatches = $schedule && $schedule->shift && $schedule->shift->value === $shiftValue;
                    if (! $schedule || ! $schedule->is_active || ! $schedule->is_shift_open || ! $dateMatches || ! $shiftMatches) {
                        Notification::make()
                            ->title('Turno no disponible')
                            ->body('Para registrar una visita, seleccione un consultorio abierto que coincida con la fecha y turno.')
                            ->danger()
                            ->send();
                        return redirect(request()->header('Referer'));
                    }

                    $appointment = Appointment::create([
                        'medical_record_id' => $data['medical_record_id'],
                        'clinic_schedule_id' => $schedule->id,
                        'service_id' => $schedule->service_id,
                        'doctor_id' => $schedule->user_id,
                        'date' => $scheduleDate ?? $formDateStr,
                        'shift' => $schedule->shift->value,
                        'visit_type' => $data['visit_type'],
                        'status' => AppointmentStatus::PENDING,
                        'reason_for_visit' => $data['reason_for_visit'] ?? null,
                    ]);
                    Notification::make()->title('Visita creada')->success()->send();
                    return redirect(request()->header('Referer'));
                }),

            Action::make('openShift')
                ->label('Abrir Consultorio')
                ->icon('heroicon-m-play')
                ->color('primary')
                ->form([
                    Select::make('schedule_id')
                        ->label('Seleccionar Consultorio')
                        ->options(function () {
                            return ClinicSchedule::where('is_active', true)
                                ->where('is_shift_open', false)
                                ->orderByDesc('date')
                                ->with(['user', 'service'])
                                ->get()
                                ->mapWithKeys(function ($schedule) {
                                    return [
                                        $schedule->id => sprintf(
                                            '%s - %s - %s (%s)',
                                            $schedule->clinic_name,
                                            $schedule->shift->value,
                                            $schedule->service->name,
                                            $schedule->user->name
                                        ),
                                    ];
                                });
                        })
                        ->required()
                        ->searchable(),
                    Textarea::make('opening_notes')
                        ->label('Notas de Apertura')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $schedule = ClinicSchedule::find($data['schedule_id']);
                    if ($schedule && $schedule->openShift(Auth::user(), $data['opening_notes'] ?? null, true)) {
                        Notification::make()->title('Turno Abierto')->success()->send();
                        $this->redirect(url('/dashboard'));
                    } else {
                        Notification::make()->title('Error')->danger()->send();
                    }
                }),

            Action::make('closeShift')
                ->label('Cerrar Consultorio')
                ->icon('heroicon-m-stop')
                ->color('danger')
                ->visible(fn () => ClinicSchedule::query()->where('is_shift_open', true)->exists())
                ->form([
                    Select::make('schedule_id')
                        ->label('Seleccionar Consultorio a Cerrar')
                        ->options(function () {
                            return ClinicSchedule::where('is_shift_open', true)
                                ->orderByDesc('date')
                                ->with(['user', 'service'])
                                ->get()
                                ->mapWithKeys(function ($schedule) {
                                    return [
                                        $schedule->id => sprintf(
                                            '%s - %s - %s (%s)',
                                            $schedule->clinic_name,
                                            $schedule->shift->value,
                                            $schedule->service->name,
                                            $schedule->user->name
                                        ),
                                    ];
                                });
                        })
                        ->required()
                        ->searchable(),
                    Textarea::make('closing_notes')
                        ->label('Notas de Cierre')
                        ->rows(3),
                ])
                ->requiresConfirmation()
                ->modalHeading('Confirmar Cierre de Turno')
                ->modalDescription('¿Está seguro de que desea cerrar este turno?')
                ->action(function (array $data): void {
                    $schedule = ClinicSchedule::find($data['schedule_id']);
                    if ($schedule && $schedule->closeShift(Auth::user(), $data['closing_notes'] ?? null)) {
                        Notification::make()->title('Turno Cerrado')->success()->send();
                        $this->redirect(url('/dashboard'));
                    } else {
                        Notification::make()->title('Error')->danger()->send();
                    }
                }),
        ];
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\ColaRecepcionTable::class,
            \App\Filament\Widgets\UltimasVisitas::class,
            \App\Filament\Widgets\TurnosAbiertosWidget::class,
        ];
    }
}
