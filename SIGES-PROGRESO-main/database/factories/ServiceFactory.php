<?php

namespace Database\Factories;

use App\Enums\Shift;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Consulta General','Nutrición','Psicología','Farmacia','Control','Urgencias']).' '.$this->faker->unique()->numerify('###'),
            'description' => $this->faker->sentence(8),
            'is_active' => true,
        ];
    }
}
