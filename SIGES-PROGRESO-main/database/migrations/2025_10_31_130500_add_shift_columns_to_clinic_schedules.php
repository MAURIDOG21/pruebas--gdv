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
        Schema::table('clinic_schedules', function (Blueprint $table) {
            // Columnas de control de apertura/cierre de turno
            if (! Schema::hasColumn('clinic_schedules', 'is_shift_open')) {
                $table->boolean('is_shift_open')->default(false)->comment('Indica si el turno está abierto');
            }

            if (! Schema::hasColumn('clinic_schedules', 'shift_opened_at')) {
                $table->timestamp('shift_opened_at')->nullable()->comment('Fecha y hora de apertura del turno');
            }

            if (! Schema::hasColumn('clinic_schedules', 'shift_closed_at')) {
                $table->timestamp('shift_closed_at')->nullable()->comment('Fecha y hora de cierre del turno');
            }

            if (! Schema::hasColumn('clinic_schedules', 'opened_by')) {
                $table->foreignId('opened_by')->nullable()->constrained('users')->comment('Usuario que abrió el turno');
            }

            if (! Schema::hasColumn('clinic_schedules', 'closed_by')) {
                $table->foreignId('closed_by')->nullable()->constrained('users')->comment('Usuario que cerró el turno');
            }

            if (! Schema::hasColumn('clinic_schedules', 'opening_notes')) {
                $table->text('opening_notes')->nullable()->comment('Notas al abrir el turno');
            }

            if (! Schema::hasColumn('clinic_schedules', 'closing_notes')) {
                $table->text('closing_notes')->nullable()->comment('Notas al cerrar el turno');
            }

            // En caso de instalaciones antiguas donde is_active no exista
            if (! Schema::hasColumn('clinic_schedules', 'is_active')) {
                $table->boolean('is_active')->default(false)->comment('Indica si el horario está activo');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clinic_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('clinic_schedules', 'closing_notes')) {
                $table->dropColumn('closing_notes');
            }
            if (Schema::hasColumn('clinic_schedules', 'opening_notes')) {
                $table->dropColumn('opening_notes');
            }
            if (Schema::hasColumn('clinic_schedules', 'closed_by')) {
                $table->dropConstrainedForeignId('closed_by');
            }
            if (Schema::hasColumn('clinic_schedules', 'opened_by')) {
                $table->dropConstrainedForeignId('opened_by');
            }
            if (Schema::hasColumn('clinic_schedules', 'shift_closed_at')) {
                $table->dropColumn('shift_closed_at');
            }
            if (Schema::hasColumn('clinic_schedules', 'shift_opened_at')) {
                $table->dropColumn('shift_opened_at');
            }
            if (Schema::hasColumn('clinic_schedules', 'is_shift_open')) {
                $table->dropColumn('is_shift_open');
            }
            // No se elimina is_active en reversión para evitar desactivar registros existentes
        });
    }
};