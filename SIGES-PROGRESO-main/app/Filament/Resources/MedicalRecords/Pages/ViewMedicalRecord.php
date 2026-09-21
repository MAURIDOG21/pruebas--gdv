<?php

namespace App\Filament\Resources\MedicalRecords\Pages;

use App\Filament\Resources\MedicalRecords\MedicalRecordResource;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Prescription;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ViewMedicalRecord extends ViewRecord
{
    protected static string $resource = MedicalRecordResource::class;
    public ?int $appointmentId = null;

    public function mount($record): void
    {
        parent::mount($record);
        $this->appointmentId = request()->query('appointment_id');
    }

    protected function getHeaderActions(): array
    {
        return [
            //EditAction::make(),

            Action::make('back_to_appointment')
                ->label('Volver a la Visita')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('gray')
                ->visible(fn () => filled($this->appointmentId))
                ->url(fn () => AppointmentResource::getUrl('view', ['record' => $this->appointmentId]))
                ->button(),

            Action::make('upload_medical_document')
                ->label('Subir nuevo documento')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->form([
                    TextInput::make('name')
                        ->label('Nombre del documento')
                        ->required()
                        ->maxLength(255),
                    FileUpload::make('file_path')
                        ->label('Archivo escaneado')
                        ->disk('public')
                        ->directory('medical_documents')
                        ->required(),
                ])
                ->action(function (array $data) {
                    \App\Models\MedicalDocument::create([
                        'medical_record_id' => $this->record->id,
                        'user_id' => Auth::id(),
                        'name' => $data['name'],
                        'file_path' => $data['file_path'],
                    ]);
                    Notification::make()->title('Documento subido')->success()->send();
                })
                ->button(),
/*
            Action::make('register_consultation_from_record')
                ->label('Registrar Consulta')
                ->icon('heroicon-o-clipboard-document')
                ->color('success')
                ->button()
                ->visible(function () {
                    $aid = request()->query('appointment_id');
                    if (! $aid) return false;
                    $role = Auth::user()?->role?->value;
                    if (! in_array($role, [UserRole::MEDICO_GENERAL->value, UserRole::ADMIN->value, UserRole::DIRECTOR->value], true)) return false;
                    $ap = Appointment::find($aid);
                    return $ap && $ap->medical_record_id === $this->record->id && $ap->status === AppointmentStatus::IN_PROGRESS && ($role !== UserRole::MEDICO_GENERAL->value || $ap->doctor_id === Auth::id());
                })
                ->url(fn () => AppointmentResource::getUrl('view', ['record' => request()->query('appointment_id')]))
                ->button(),
       
        */
                 ];
    }
}
