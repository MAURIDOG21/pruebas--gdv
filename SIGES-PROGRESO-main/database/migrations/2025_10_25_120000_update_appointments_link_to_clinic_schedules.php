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
        Schema::table('appointments', function (Blueprint $table) {
            // Eliminar el campo suelto de consultorio
            if (Schema::hasColumn('appointments', 'clinic_room_number')) {
                $table->dropColumn('clinic_room_number');
            }

            // Agregar fecha de la visita (día de atención)
            if (! Schema::hasColumn('appointments', 'date')) {
                $table->date('date')->nullable()->index();
            }

            // Agregar vínculo fuerte al horario diario
            if (! Schema::hasColumn('appointments', 'clinic_schedule_id')) {
                $table->foreignId('clinic_schedule_id')
                    ->nullable()
                    ->constrained('clinic_schedules')
                    ->restrictOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Quitar relación y fecha
            if (Schema::hasColumn('appointments', 'clinic_schedule_id')) {
                $table->dropConstrainedForeignId('clinic_schedule_id');
            }
            if (Schema::hasColumn('appointments', 'date')) {
                $table->dropColumn('date');
            }

            // Restaurar el campo suelto de consultorio
            if (! Schema::hasColumn('appointments', 'clinic_room_number')) {
                $table->string('clinic_room_number')->nullable();
            }
        });
    }
};