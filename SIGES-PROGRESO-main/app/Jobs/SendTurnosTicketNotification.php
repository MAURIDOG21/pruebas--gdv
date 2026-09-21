<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendTurnosTicketNotification implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $type, public array $data)
    {
    }

    public function handle(): void
    {
        if (! config('turnos.enabled')) {
            return;
        }
        // 1. Obtener URL base y validar
        $base = config('turnos.url') ?: config('services.turnos.api_url');


        if (! $base) {
            Log::error('Job fallido: No hay URL base configurada para el sistema de Turnos.');
            return;
        }

        // 2. Definir el endpoint dinámicamente según el tipo de evento
        $endpoint = match ($this->type) {
            'assign' => '/turnos/turno-siguiente',
            'confirm' => '/turnos/turno-confirmar-asistencia',
            'finish' => '/turnos/turno-finalizar',
            'cancel' => '/turnos/turno-cancelado',
            'new'    => '/turnos/nuevo-turno',
            default  => '/turnos/actualizar',
        };

        $url = rtrim($base, '/') . $endpoint;

        // 3. Configurar la petición
        // Usamos withoutVerifying() solo si estás en entorno local/dev. 
        $request = Http::withoutVerifying()->asJson();
        
        $token = config('turnos.token') ?: config('services.turnos.api_token');
        
        if ($token) {
            $request = $request->withToken($token);
        }

        // 4. Enviar la petición
        try {
            $response = $request->post($url, $this->data);
            
            if ($response->successful()) {
                Log::info("Turnos notificado ({$this->type})", ['url' => $url, 'status' => $response->status()]);
            } else {
                Log::warning("Turnos respondió con error ({$this->type})", ['url' => $url, 'status' => $response->status(), 'body' => $response->body()]);
            }
            
        } catch (\Throwable $e) {
            Log::warning("Fallo al notificar a Turnos ({$this->type})", ['url' => $url, 'error' => $e->getMessage()]);
        }
    }

    public function uniqueId(): string
    {
        $ticket = (string)($this->data['ticket_number'] ?? $this->data['ticket'] ?? '');
        return $this->type . ':' . $ticket;
    }
}
