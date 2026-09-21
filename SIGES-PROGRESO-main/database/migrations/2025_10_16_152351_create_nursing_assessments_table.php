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
    Schema::create('nursing_assessments', function (Blueprint $table) {
        $table->id();
        // Relación uno a uno con el expediente
        $table->foreignId('medical_record_id')->unique()->constrained('medical_records')->cascadeOnDelete();
        $table->foreignId('user_id')->constrained('users'); // Enfermero/a que la llenó

        // Aquí van los campos de la hoja inicial
        $table->text('allergies')->nullable(); // ALERGIAS
        $table->text('personal_pathological_history')->nullable(); // ANTECEDENTES PERSONALES PATOLÓGICOS
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nursing_assessments');
    }
};
