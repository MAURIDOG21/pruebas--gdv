<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            // --- RELACIONES ---
            // Cada visita pertenece a un expediente médico.
            $table->foreignId('medical_record_id')->constrained('medical_records')->cascadeOnDelete()->nullable();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            // El médico que atiende EN ESTA VISITA (opcional, se asigna después)
            $table->foreignId('doctor_id')->nullable()->constrained('users')->nullOnDelete();

            // --- DATOS DE LA VISITA ---
            $table->string('ticket_number')->unique(); // El ticket del sistema de turnos
            $table->string('shift', 50)->nullable(); // El turno de ESTA VISITA
            $table->string('visit_type', 50)->nullable(); // Si ESTA VISITA es de 1ra vez o subsecuente
            $table->string('clinic_room_number')->nullable();
            $table->text('reason_for_visit');
            $table->text('notes')->nullable();
            $table->string('status')->default('pending'); // Estado de la visita: 'pending', 'completed', etc.
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};