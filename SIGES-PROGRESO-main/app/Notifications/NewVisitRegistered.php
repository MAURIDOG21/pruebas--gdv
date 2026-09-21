<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use App\Filament\Resources\Appointments\AppointmentResource;

class NewVisitRegistered extends Notification
{
    public function __construct(
        protected Appointment $appointment
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Nueva Visita Registrada',
            'body' => "Se ha registrado una nueva visita con ticket #{$this->appointment->ticket_number}",
            'icon' => 'heroicon-o-ticket',
            'url' => AppointmentResource::getUrl('view', ['record' => $this->appointment]),
        ];
    }
}