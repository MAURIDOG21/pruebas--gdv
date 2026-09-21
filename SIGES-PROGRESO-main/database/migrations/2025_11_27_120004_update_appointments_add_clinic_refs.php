<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('clinic_id')->nullable()->after('service_id')->constrained('clinics')->nullOnDelete();
            $table->foreignId('clinic_turn_id')->nullable()->after('clinic_id')->constrained('clinic_turns')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('clinic_turn_id');
            $table->dropConstrainedForeignId('clinic_id');
        });
    }
};
