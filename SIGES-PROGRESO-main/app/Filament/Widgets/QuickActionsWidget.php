<?php

namespace App\Filament\Widgets;

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
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class QuickActionsWidget extends Widget implements HasForms
{
    use InteractsWithActions, InteractsWithForms;

    protected ?string $heading = 'Acciones rápidas';
    protected string $view = 'filament.widgets.quick-actions-widget';
    protected int | string | array $columnSpan = 'full';
    protected static bool $isLazy = false;

    public ?string $ticket_number = null;

    public function findTicket(): void
    {
        if (! filled($this->ticket_number)) {
            Notification::make()->title('Ingrese un ticket')->warning()->send();
            return;
        }

        $appointment = Appointment::query()->where('ticket_number', $this->ticket_number)->first();
        if ($appointment) {
            $this->redirect(AppointmentResource::getUrl('view', ['record' => $appointment]));
            return;
        }
        Notification::make()->title('No encontrado')->danger()->send();
    }

    public function newVisitAction(): Action
    {
        return Action::make('newVisit')
            ->label('Nueva visita (walk-in)')
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
                    }),
                DatePicker::make('date')
                    ->label('Fecha')
                    ->default(now()->toDateString())
                    ->required(),
                Radio::make('shift')
                    ->label('Turno')
                    ->options(Shift::class)
                    ->inline()
                    ->required(),
                Select::make('clinic_schedule_id')
                    ->label('Consultorio activo')
                    ->relationship('clinicSchedule', 'clinic_name', modifyQueryUsing: function (Builder $query, $get) {
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
                    ->required(),
                Select::make('visit_type')
                    ->label('Tipo de visita')
                    ->options(VisitType::class)
                    ->required(),
                Select::make('status')
                    ->label('Estado')
                    ->options(AppointmentStatus::class)
                    ->default(AppointmentStatus::PENDING)
                    ->required(),
                Textarea::make('reason_for_visit')
                    ->label('Motivo de la visita')
                    ->required()
                    ->rows(3),
            ])
            ->action(function (array $data) {
                $appointment = Appointment::create([
                    'medical_record_id' => $data['medical_record_id'],
                    'clinic_schedule_id' => $data['clinic_schedule_id'] ?? null,
                    'service_id' => $data['service_id'] ?? null,
                    'doctor_id' => $data['doctor_id'] ?? null,
                    'date' => $data['date'],
                    'shift' => $data['shift'],
                    'visit_type' => $data['visit_type'],
                    'status' => $data['status'] ?? AppointmentStatus::PENDING,
                    'reason_for_visit' => $data['reason_for_visit'] ?? null,
                ]);
                Notification::make()->title('Visita creada')->success()->send();
                $this->redirect(AppointmentResource::getUrl('view', ['record' => $appointment]));
            });
    }

    public function openShiftAction(): Action
    {
        return Action::make('openShift')
            ->label('Abrir Turnodd')
            ->icon('heroicon-m-play')
            ->color('success')
            ->form([
                Select::make('schedule_id')
                    ->label('Seleccionar Turno')
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
                if ($schedule && $schedule->openShift(Auth::user(), $data['opening_notes'] ?? null)) {
                    Notification::make()->title('Turno Abierto')->success()->send();
                    $this->redirect(url('/dashboard'));
                } else {
                    Notification::make()->title('Error')->danger()->send();
                }
            });
    }

    public function closeShiftAction(): Action
    {
        return Action::make('closeShift')
            ->label('Cerrar Turno')
            ->icon('heroicon-m-stop')
            ->color('danger')
            ->form([
                Select::make('schedule_id')
                    ->label('Seleccionar Turno a Cerrar')
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
            });
    }

    public function findByTicketAction(): Action
    {
        return Action::make('findByTicket')
            ->label('Buscar por ticket')
            ->icon('heroicon-m-magnifying-glass')
            ->form([
                TextInput::make('ticket_number')
                    ->label('Ticket')
                    ->required(),
            ])
            ->action(function (array $data) {
                $appointment = Appointment::query()->where('ticket_number', $data['ticket_number'])->first();
                if ($appointment) {
                    $this->redirect(AppointmentResource::getUrl('view', ['record' => $appointment]));
                }
                Notification::make()->title('No encontrado')->danger()->send();
            });
    }
}
