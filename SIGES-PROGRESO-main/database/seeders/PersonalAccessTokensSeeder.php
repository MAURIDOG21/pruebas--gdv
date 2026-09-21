<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Storage;

class PersonalAccessTokensSeeder extends Seeder
{
    public function run(): void
    {
        // Elimina tokens existentes para evitar duplicados
        PersonalAccessToken::query()->delete();

        $users = User::all();

        if ($users->isEmpty()) {
            if (property_exists($this, 'command') && $this->command) {
                $this->command->warn('No hay usuarios en la base de datos. No se generaron tokens.');
            }
            return;
        }

        $lines = [];

        foreach ($users as $user) {
            $token = $user->createToken('seeded-api-access', ['*']);
            $lines[] = ($user->email ?? ('user#'.$user->id)) . ': ' . $token->plainTextToken;
        }

        // Asegura el directorio y guarda los tokens generados
        Storage::disk('local')->makeDirectory('private');
        Storage::disk('local')->put('private/seeded_tokens.txt', implode(PHP_EOL, $lines));

        if (property_exists($this, 'command') && $this->command) {
            $this->command->info('Tokens generados para ' . count($lines) . ' usuarios.');
            $this->command->info('Archivo con tokens: storage/app/private/seeded_tokens.txt');
        }
    }
}