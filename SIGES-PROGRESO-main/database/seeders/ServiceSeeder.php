<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            ['id' => 1, 'name' => 'Medicina General', 'is_active' => true],
            ['id' => 2, 'name' => 'Nutricion', 'is_active' => true],
            ['id' => 3, 'name' => 'Dentista', 'is_active' => true],
            ['id' => 4, 'name' => 'Psicología', 'is_active' => true],
            ['id' => 5, 'name' => 'Enfermería', 'is_active' => true],
        ];

        DB::table('services')->whereNotIn('id', [1, 2, 3, 4, 5])->delete();
        DB::table('services')->upsert($records, ['id'], ['name', 'is_active']);

        $token = config('turnos.token') ?: config('services.turnos.api_token');
        $this->command?->info('TURNOS_API_TOKEN=' . $token);
    }
}
