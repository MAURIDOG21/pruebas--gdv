<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClinicSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            ['name' => 'Consultorio Médico 1', 'location' => null, 'is_active' => true],
            ['name' => 'Consultorio Médico 2', 'location' => null, 'is_active' => true],
            ['name' => 'Consultorio Médico 3', 'location' => null, 'is_active' => true],
            ['name' => 'Consultorio Médico 4', 'location' => null, 'is_active' => true],
            ['name' => 'Psicología', 'location' => null, 'is_active' => true],
            ['name' => 'Odontología', 'location' => null, 'is_active' => true],
            ['name' => 'Nutrición', 'location' => null, 'is_active' => true],
        ];

        DB::table('clinics')->upsert($records, ['name'], ['location', 'is_active']);

        $this->command?->info('Clinics seeded: '.count($records));
    }
}

