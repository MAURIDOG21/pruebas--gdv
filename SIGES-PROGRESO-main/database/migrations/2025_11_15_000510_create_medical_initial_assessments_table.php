<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_initial_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_record_id')->constrained('medical_records')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->text('allergies')->nullable();
            $table->text('personal_pathological_history')->nullable();
            $table->text('gyneco_obstetric_history')->nullable();
            $table->text('current_illness')->nullable();
            $table->text('physical_exam')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('treatment_note')->nullable();
            $table->timestamps();
            $table->unique('medical_record_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_initial_assessments');
    }
};