<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSomatometricReadingsTable extends Migration
{
    public function up()
    {
        Schema::create('somatometric_readings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('medical_record_id')->constrained('medical_records')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->smallInteger('blood_pressure_systolic')->nullable();
            $table->smallInteger('blood_pressure_diastolic')->nullable();
            $table->smallInteger('heart_rate')->nullable();
            $table->smallInteger('respiratory_rate')->nullable(); // FR (Nuevo)
            $table->decimal('temperature', 4, 1)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('height', 5, 2)->nullable();
            $table->decimal('blood_glucose', 5, 2)->nullable(); // DESTROSTIX MG/DL (Nuevo)
            $table->decimal('oxygen_saturation', 5, 2)->nullable(); // SPO2 (Nuevo)
            $table->text('observations')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('somatometric_readings');
    }
}
