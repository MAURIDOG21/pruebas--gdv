<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_turns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->restrictOnDelete();
            $table->date('date');
            $table->string('shift');
            $table->boolean('is_open')->default(false);
            $table->timestamp('opened_at')->nullable();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['clinic_id','date','shift']);
            $table->index(['clinic_id']);
            $table->index(['date','shift']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_turns');
    }
};

