<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            // Encrypted values are longer; use text and remove unique on curp
            $table->text('full_name')->nullable()->change();
            $table->text('curp')->nullable()->change();
            $table->text('contact_phone')->nullable()->change();
            // Auxiliar para búsqueda/validación: hash determinístico del CURP
            if (! Schema::hasColumn('patients', 'curp_hash')) {
                $table->string('curp_hash', 64)->nullable();
                $table->unique('curp_hash');
            }
        });

        // Drop unique index on patients.curp (ciphertext is randomized, uniqueness cannot be enforced)
        Schema::table('patients', function (Blueprint $table) {
            $table->dropUnique('patients_curp_unique');
        });

        Schema::table('medical_documents', function (Blueprint $table) {
            $table->text('name')->change();
            $table->text('file_path')->change();
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            // encrypted:json stores ciphertext; use text instead of json
            $table->text('items')->nullable()->change();
        });

        Schema::table('tutors', function (Blueprint $table) {
            $table->text('full_name')->change();
            $table->text('relationship')->change();
            $table->text('phone_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('full_name')->nullable()->change();
            $table->string('curp', 18)->nullable()->change();
            $table->string('contact_phone')->nullable()->change();
            $table->unique('curp');
            if (Schema::hasColumn('patients', 'curp_hash')) {
                $table->dropUnique('patients_curp_hash_unique');
                $table->dropColumn('curp_hash');
            }
        });

        Schema::table('medical_documents', function (Blueprint $table) {
            $table->string('name')->change();
            $table->string('file_path')->change();
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->json('items')->nullable()->change();
        });

        Schema::table('tutors', function (Blueprint $table) {
            $table->string('full_name', 255)->change();
            $table->string('relationship', 100)->change();
            $table->string('phone_number', 20)->nullable()->change();
        });
    }
};
