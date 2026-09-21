<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursing_evolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_record_id')->constrained('medical_records')->cascadeOnDelete();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->text('problem')->nullable();
            $table->text('subjective')->nullable();
            $table->text('objective')->nullable();
            $table->text('analysis')->nullable();
            $table->text('plan')->nullable();
            $table->foreignId('somatometric_reading_id')->nullable()->constrained('somatometric_readings');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_evolutions');
    }
};