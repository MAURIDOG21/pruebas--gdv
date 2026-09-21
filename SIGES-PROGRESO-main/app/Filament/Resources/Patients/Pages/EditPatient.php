<?php

namespace App\Filament\Resources\Patients\Pages;

use App\Filament\Resources\Patients\PatientResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification; // <-- Importar Notificaciones
use App\Filament\Resources\Appointments\AppointmentResource; // <-- Importar AppointmentResource

class EditPatient extends EditRecord
{
    protected static string $resource = PatientResource::class;
    // Propiedad para guardar el ID de la visita a la que redirigir
    public ?int $redirectToAppointmentId = null;
    protected array $mrData = [];

    protected static ?string $maxWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);

        // Si la URL tiene el parámetro redirect_to_appointment, lo guardamos
        if (request()->has('redirect_to_appointment')) {
            $this->redirectToAppointmentId = (int) request()->get('redirect_to_appointment');
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->mrData = [
            'patient_type' => data_get($data, 'medicalRecord.patient_type') ?? ($data['patient_type'] ?? null),
            'employee_status' => data_get($data, 'medicalRecord.employee_status') ?? ($data['employee_status'] ?? null),
        ];
        unset($data['patient_type'], $data['employee_status'], $data['medicalRecord']['patient_type'], $data['medicalRecord']['employee_status']);
        // 1. Verificamos el estado actual del paciente que estamos editando.
        //    La variable `$this->record` contiene el modelo del paciente.
        if ($this->record->status === 'pending_review') {
            // 2. Si está pendiente, forzamos el estado a 'active'.
            $data['status'] = 'active';

            // 3. (Opcional pero recomendado) Enviamos una notificación de éxito.
            Notification::make()
                ->title('Expediente Activado')
                ->body('El expediente del paciente ha sido completado y activado correctamente.')
                ->success()
                ->send();
        }

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $mr = \App\Models\MedicalRecord::where('patient_id', $this->record->id)->first();
        if ($mr) {
            $data['medicalRecord']['patient_type'] = $mr->patient_type?->value ?? null;
            $data['medicalRecord']['employee_status'] = $mr->employee_status?->value ?? null;
        }
        return $data;
    }

    protected function afterSave(): void
    {
        $mr = \App\Models\MedicalRecord::where('patient_id', $this->record->id)->first();
        if (! $mr) {
            $mr = \App\Models\MedicalRecord::firstOrCreate(['patient_id' => $this->record->id], []);
        }
        $payload = array_filter([
            'patient_type' => $this->mrData['patient_type'] ?? null,
            'employee_status' => $this->mrData['employee_status'] ?? null,
        ], fn ($v) => ! is_null($v));
        if (! empty($payload)) {
            $mr->update($payload);
        }
    }

        /**
     * ¡LA LÓGICA DE REDIRECCIÓN INTELIGENTE!
     * Este método decide a dónde ir después de guardar.
     */
    protected function getRedirectUrl(): string
    {
        // Si tenemos un ID de visita para redirección
        if ($this->redirectToAppointmentId) {
            // Redirigimos a la página de vista de la visita
            return AppointmentResource::getUrl('view', ['record' => $this->redirectToAppointmentId]);
        }

        // Si no, hacemos lo de siempre: volver a la lista de pacientes
        return $this->getResource()::getUrl('index');
    }

}
