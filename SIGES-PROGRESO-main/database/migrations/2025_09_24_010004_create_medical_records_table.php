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
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();

            // --- RELACIÓN UNO-A-UNO ---
            // Cada expediente pertenece a un único paciente.
            $table->foreignId('patient_id')->unique()->constrained('patients')->cascadeOnDelete();

            // --- DATOS DEL EXPEDIENTE ---
            $table->string('record_number')->unique(); // El N° de Expediente oficial
            
            // La clasificación del paciente es una propiedad del expediente, no de la persona.
            $table->string('patient_type', 100)->nullable(); 
            $table->string('employee_status', 100)->nullable();

            // --- CAMPO PARA EL CONSENTIMIENTO ---
            // Guardará la ruta del archivo del consentimiento informado escaneado.
            $table->string('consent_form_path')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};