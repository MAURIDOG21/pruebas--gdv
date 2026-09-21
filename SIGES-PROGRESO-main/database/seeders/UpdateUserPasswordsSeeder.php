<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UpdateUserPasswordsSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->get()->each(function (User $user) {
            $user->update(['password' => '1234']);
        });
    }
}

