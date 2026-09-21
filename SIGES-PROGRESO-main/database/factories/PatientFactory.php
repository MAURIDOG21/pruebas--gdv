<?php

namespace Database\Factories;

use App\Enums\Locality;
use App\Enums\ChronicDisease;
use App\Support\ColoniaCatalog;
use App\Models\Patient;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\PatientType;
class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        $sex = $this->faker->randomElement(['M','F']);
        $curp = $this->faker->boolean(80)
            ? strtoupper($this->faker->unique()->regexify('[A-Z]{4}[0-9]{6}[A-Z]{6}[0-9]{2}'))
            : null;
        $locality = $this->faker->randomElement(Locality::cases());
        $colonias = ColoniaCatalog::getColonias($locality->value);
        $colonias = is_array($colonias) ? array_keys($colonias) : [];
        $colonia = $colonias ? $this->faker->randomElement($colonias) : null;
        $diseaseCases = ChronicDisease::cases();
        $diseasesCount = $this->faker->numberBetween(0, 3);
        $diseases = $diseasesCount > 0
            ? Arr::random(array_map(fn($c) => $c->value, $diseaseCases), $diseasesCount)
            : [];
        return [
            'tutor_id' => null,
            'full_name' => $this->faker->name(),
            'date_of_birth' => $this->faker->dateTimeBetween('-90 years', '-1 year')->format('Y-m-d'),
            'sex' => $sex,
            'curp' => $curp,
            'locality' => $locality,
            'colonia' => $colonia,
            'contact_phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'has_disability' => $this->faker->boolean(10),
            'disability_details' => $this->faker->optional()->sentence(6),
            'status' => $this->faker->randomElement(['active','pending_review']),
            'chronic_diseases' => $diseases,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Patient $patient) {
            $type = Arr::random(\App\Enums\PatientType::cases());
            $patient->medicalRecord()->update(['patient_type' => $type->value]);
        });
    }
}
