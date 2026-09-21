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
        Schema::create('clinic_schedules', function (Blueprint $table) {
            $table->id();

            // The name/number of the clinic for this specific schedule
            $table->string('clinic_name');

            // Foreign keys to link the schedule to other resources
            $table->foreignId('user_id')->constrained('users')->comment('The doctor assigned');
            $table->foreignId('service_id')->constrained('services');

           
            $table->string('shift'); // We'll cast this to the Shift Enum in the model
            $table->date('date'); // The specific day this schedule is for
            $table->boolean('is_active')->default(false); // Off by default

            // Campos para control de apertura/cierre de turno
            $table->boolean('is_shift_open')->default(false)->comment('Indica si el turno está abierto');
            $table->timestamp('shift_opened_at')->nullable()->comment('Fecha y hora de apertura del turno');
            $table->timestamp('shift_closed_at')->nullable()->comment('Fecha y hora de cierre del turno');
            $table->foreignId('opened_by')->nullable()->constrained('users')->comment('Usuario que abrió el turno');
            $table->foreignId('closed_by')->nullable()->constrained('users')->comment('Usuario que cerró el turno');
            $table->text('opening_notes')->nullable()->comment('Notas al abrir el turno');
            $table->text('closing_notes')->nullable()->comment('Notas al cerrar el turno');

            // Ensure one unique assignment per clinic + date + shift
            $table->unique(['clinic_name', 'date', 'shift'], 'clinic_day_shift_unique');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinic_schedules');
    }
};
