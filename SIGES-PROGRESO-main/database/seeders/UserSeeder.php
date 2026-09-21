<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Administrador', 'email' => 'admin@ejemplo.com', 'password' => '1234', 'role' => UserRole::ADMIN],
            ['name' => 'Director', 'email' => 'director@ejemplo.com', 'password' => '1234', 'role' => UserRole::DIRECTOR],
            ['name' => 'Médico General', 'email' => 'medico@ejemplo.com', 'password' => '1234', 'role' => UserRole::MEDICO_GENERAL],
            ['name' => 'Juan Medico', 'email' => 'juan.medico@ejemplo.com', 'password' => '1234', 'role' => UserRole::MEDICO_GENERAL],
            ['name' => 'Maria Medico', 'email' => 'maria.medico@ejemplo.com', 'password' => '1234', 'role' => UserRole::MEDICO_GENERAL],
            ['name' => 'Jose Medico', 'email' => 'jose.medico@ejemplo.com', 'password' => '1234', 'role' => UserRole::MEDICO_GENERAL],
            ['name' => 'Luis Medico', 'email' => 'luis.medico@ejemplo.com', 'password' => '1234', 'role' => UserRole::MEDICO_GENERAL],
            ['name' => 'Nutricionista', 'email' => 'nutricionista@ejemplo.com', 'password' => '1234', 'role' => UserRole::NUTRICIONISTA],
            ['name' => 'Andrea Nutricionista', 'email' => 'andrea.nutricionista@ejemplo.com', 'password' => '1234', 'role' => UserRole::NUTRICIONISTA],
            ['name' => 'Psicólogo', 'email' => 'psicologo@ejemplo.com', 'password' => '1234', 'role' => UserRole::PSICOLOGO],
            ['name' => 'Roberto Psicologo', 'email' => 'roberto.psicologo@ejemplo.com', 'password' => '1234', 'role' => UserRole::PSICOLOGO],
            ['name' => 'Farmacia', 'email' => 'farmacia@ejemplo.com', 'password' => '1234', 'role' => UserRole::FARMACIA],
            ['name' => 'Valeria Farmacia', 'email' => 'valeria.farmacia@ejemplo.com', 'password' => '1234', 'role' => UserRole::FARMACIA],
            ['name' => 'Enfermero', 'email' => 'enfermero@ejemplo.com', 'password' => '1234', 'role' => UserRole::ENFERMERO],
            ['name' => 'Carlos Enfermero', 'email' => 'carlos.enfermero@ejemplo.com', 'password' => '1234', 'role' => UserRole::ENFERMERO],
            ['name' => 'Ana Enfermera', 'email' => 'ana.enfermera@ejemplo.com', 'password' => '1234', 'role' => UserRole::ENFERMERO],
            ['name' => 'Pedro Enfermero', 'email' => 'pedro.enfermero@ejemplo.com', 'password' => '1234', 'role' => UserRole::ENFERMERO],
            ['name' => 'Laura Enfermera', 'email' => 'laura.enfermera@ejemplo.com', 'password' => '1234', 'role' => UserRole::ENFERMERO],
            ['name' => 'Recepcionista', 'email' => 'recepcionista@ejemplo.com', 'password' => '1234', 'role' => UserRole::RECEPCIONISTA],
            ['name' => 'Sofia Recepcionista', 'email' => 'sofia.recepcionista@ejemplo.com', 'password' => '1234', 'role' => UserRole::RECEPCIONISTA],
            ['name' => 'Diego Recepcionista', 'email' => 'diego.recepcionista@ejemplo.com', 'password' => '1234', 'role' => UserRole::RECEPCIONISTA],
            ['name' => 'Fernanda Recepcionista', 'email' => 'fernanda.recepcionista@ejemplo.com', 'password' => '1234', 'role' => UserRole::RECEPCIONISTA],
            ['name' => 'Miguel Recepcionista', 'email' => 'miguel.recepcionista@ejemplo.com', 'password' => '1234', 'role' => UserRole::RECEPCIONISTA],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(['email' => $u['email']], $u);
        }
    }
}
