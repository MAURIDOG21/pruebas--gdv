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
        Schema::create('medical_leaves', function (Blueprint $table) {
            $table->id();

            // Folio único y no editable que se generará automáticamente
            $table->string('folio')->unique();

            // --- VÍNCULOS (RELACIONES) ---
            // Con el expediente. Si se borra el expediente, se borran sus incapacidades.
            $table->foreignId('medical_record_id')->constrained('medical_records')->cascadeOnDelete();            // Con el médico. Si se intenta borrar un médico, se restringirá si tiene incapacidades emitidas.
            $table->foreignId('doctor_id')->constrained('users')->restrictOnDelete();
            
            // --- LAS TRES FECHAS ---
            $table->date('issue_date'); // Fecha en que se expide el documento
            $table->date('start_date'); // Fecha en que inicia la incapacidad
            $table->date('end_date');   // Fecha en que termina la incapacidad

            // --- DETALLES DE LA INCAPACIDAD ---
            $table->text('reason');     // Justificación o diagnóstico médico (campo grande)
            $table->string('issuing_department')->nullable(); // Área que emite (ej. "Medicina General")
            
            // --- CAMPO PARA EL WORKFLOW ---
            // Guardará el estado actual: 'borrador', 'pendiente_aprobacion', etc.
            $table->string('status')->default('pendiente_aprobacion');
            
            $table->timestamps(); // Campos created_at y updated_at

            $table->softDeletes(); // soft delete (deleted_at)

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_leaves');
    }
};
