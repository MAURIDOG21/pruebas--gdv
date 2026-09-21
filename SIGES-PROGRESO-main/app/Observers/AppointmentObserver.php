<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Enums\AppointmentStatus;
use App\Jobs\SendTurnosTicketNotification;

class AppointmentObserver
{
    /**
     * Escenario 1: Se crea una nueva visita (ej. Ticket Local o API)
     */
    public function created(Appointment $appointment): void
    {
        if (! config('turnos.enabled')) {
            return;
        }
        SendTurnosTicketNotification::dispatch(
            'new',
            [
                'ticket' => $appointment->ticket_number,
                'estado'        => 'creado',
                'created_at'    => $appointment->created_at->toIso8601String(),                
                'idServicio'    => $appointment->service_id, 
                'service_name'  => $appointment->service->name ?? null, 
            ]
        )->afterCommit();
    }

    public function updated(Appointment $appointment): void
    {
        // --- CHIVATO DE SEGURIDAD ---
        if (! config('turnos.enabled')) {
            return;
        }
            // --- CHIVATO 1 ---
        \Illuminate\Support\Facades\Log::info('Observer detectó actualización. Ticket: ' . $appointment->ticket_number);

        if ($appointment->wasChanged('clinic_schedule_id') && $appointment->clinic_schedule_id) {
            // --- CHIVATO 2 ---
            \Illuminate\Support\Facades\Log::info('Observer: Cambio de consultorio detectado. Despachando Job.');
            
            $appointment->loadMissing('clinicSchedule');
            // ... resto del código ...
        }

    if ($appointment->wasChanged('status') && $appointment->status === \App\Enums\AppointmentStatus::COMPLETED) {
        // --- CHIVATO 3 ---
        \Illuminate\Support\Facades\Log::info('Observer: Estado COMPLETED detectado. Despachando Job.');
        // ... resto del código ...
    }
        // Pre-cargamos la relación para tener los datos disponibles
        $appointment->loadMissing(['clinicSchedule', 'service']);
        
        $clinicName = optional($appointment->clinicSchedule)->clinic_name;

        // --- Escenario 2: Llamado a Consultorio (Revisión -> Consulta o Asignación) ---
        $assignedClinic = $appointment->wasChanged('clinic_schedule_id') && $appointment->clinic_schedule_id;
        $startedConsultation = $appointment->wasChanged('status') && $appointment->status === AppointmentStatus::IN_PROGRESS;

        if ($assignedClinic) {
            SendTurnosTicketNotification::dispatch(
                'assign',
                [
                    'ticket'      => $appointment->ticket_number,
                    'consultorio' => $clinicName,
                    'idServicio'  => $appointment->service_id,
                    'estado'      => 'en_revision',
                ]
            )->afterCommit();
        }
        if ($startedConsultation) {
            SendTurnosTicketNotification::dispatch(
                'confirm',
                [
                    'ticket' => $appointment->ticket_number,
                    'estado' => 'en_consulta',
                ]
            )->afterCommit();
        }

        // --- Escenario 3: Fin de Consulta (Consulta -> Completado) ---
        if ($appointment->wasChanged('status') && $appointment->status === AppointmentStatus::COMPLETED) {
            SendTurnosTicketNotification::dispatch(
                'finish',
                [
                    // --- ADAPTACIÓN A SU JSON (Solo pide ticket y estado) ---
                    'ticket' => $appointment->ticket_number,
                    'estado' => 'Finalizado',
                    'clinic_name'   => $clinicName,
                ]
            )->afterCommit();
        }

        // --- Escenario 4: Cancelación ---
        if ($appointment->wasChanged('status') && $appointment->status === AppointmentStatus::CANCELLED) {
            SendTurnosTicketNotification::dispatch(
                'cancel',
                [
                    // --- ADAPTACIÓN A SU JSON ---
                    'ticket' => $appointment->ticket_number,
                    'estado' => 'Cancelado',
                ]
            )->afterCommit();
        }
    }
}
