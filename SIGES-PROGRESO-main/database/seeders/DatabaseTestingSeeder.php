<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseTestingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 50 expedientes para pacientes adultos
        \App\Models\Patient::factory()->count(50)->create();

        // 25 expedientes para pacientes menores (usa el estado forMinor de la factory)
        \App\Models\Patient::factory()->count(25)->isMinor()->create();

    }
}


