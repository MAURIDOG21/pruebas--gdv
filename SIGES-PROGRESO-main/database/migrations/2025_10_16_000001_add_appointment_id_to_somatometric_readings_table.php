<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('somatometric_readings', function (Blueprint $table) {
            $table->foreignId('appointment_id')
                ->nullable()
                ->constrained('appointments')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('somatometric_readings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('appointment_id');
        });
    }
};