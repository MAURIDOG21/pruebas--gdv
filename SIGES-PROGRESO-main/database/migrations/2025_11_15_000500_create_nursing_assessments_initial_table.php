<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursing_assessments_initial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_record_id')->constrained('medical_records')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('somatometric_reading_id')->nullable()->constrained('somatometric_readings');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique('medical_record_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_assessments_initial');
    }
};