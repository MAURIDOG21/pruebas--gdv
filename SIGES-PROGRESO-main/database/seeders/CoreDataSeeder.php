<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Enums\UserRole;
class CoreDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Crear el usuario Administrador principal si no existe
        User::firstOrCreate(
            ['email' => 'admin@siges.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'role' => UserRole::ADMIN,
            ]
        );

        // 2. Crear un usuario dedicado para la API
        $apiUser = User::firstOrCreate(
            ['email' => 'api-user@siges.com'],
            [
                'name' => 'Sistema de Turnos API',
                'password' => Hash::make(str()->random(20)), // Contraseña aleatoria, no la usaremos
                'role' => UserRole::ADMIN, // O un rol específico 'api' si lo creas
            ]
        );

        // 3. Crear el token y mostrarlo en la consola
        // Primero, borramos los tokens viejos para evitar acumularlos
        $apiUser->tokens()->delete();
        
        // Creamos el nuevo token
        $token = $apiUser->createToken('sistema-de-turnos-token')->plainTextToken;

        // Usamos un comando de Artisan para imprimir el token de forma destacada
        $this->command->info('API Token para el Sistema de Turnos:');
        $this->command->warn($token);
    }
}
