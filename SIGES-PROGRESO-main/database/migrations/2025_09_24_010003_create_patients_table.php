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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            // --- RELACIONES ---
            // Un paciente puede tener un tutor asignado.
            $table->foreignId('tutor_id')->nullable()->constrained('tutors')->onDelete('set null');
            // --- DATOS BIOGRÁFICOS ---
            // Estos son nullable para permitir la creación de "pre-expedientes" desde la API.
            $table->string('full_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('sex', 50)->nullable();
            $table->string('curp', 18)->nullable()->unique();
            // --- DATOS DE CONTACTO ---
            $table->string('locality')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('address')->nullable();
            // --- DETALLES ADICIONALES ---
            $table->boolean('has_disability')->default(false);
            $table->text('disability_details')->nullable();   
            // --- ESTADO DEL REGISTRO ---
            // Para el flujo de la API (ej. 'pending_review', 'active', 'inactive')
            $table->string('status')->default('active');  
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};