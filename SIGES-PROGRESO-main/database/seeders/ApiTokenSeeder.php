<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;

class ApiTokenSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', UserRole::ADMIN->value)->first();
        if ($admin) {
            $token = $admin->createToken('Seed Admin API')->plainTextToken;
            $this->command->info('API Token (Admin '.$admin->email.'): '.$token);
        }

        $recep = User::where('role', UserRole::RECEPCIONISTA->value)->first();
        if ($recep) {
            $token = $recep->createToken('Seed Recepcion API')->plainTextToken;
            $this->command->info('API Token (Recepción '.$recep->email.'): '.$token);
        }
    }
}
