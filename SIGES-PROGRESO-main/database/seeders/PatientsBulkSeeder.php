<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Patient;
use App\Models\MedicalRecord;
use App\Enums\PatientType;
use App\Enums\EmployeeStatus;

class PatientsBulkSeeder extends Seeder
{
    public function run(): void
    {
        $total = 1000;
        $types = [
            PatientType::EXTERNAL->value,
            PatientType::EMPLOYEE->value,
            PatientType::EMPLOYEE_DEPENDENT->value,
            PatientType::PEDIATRIC->value,
        ];
        $weights = [
            PatientType::EXTERNAL->value => 45,
            PatientType::EMPLOYEE->value => 25,
            PatientType::EMPLOYEE_DEPENDENT->value => 15,
            PatientType::PEDIATRIC->value => 15,
        ];
        $employeeWeights = [
            EmployeeStatus::SINDICALIZADO->value => 60,
            EmployeeStatus::CONFIANZA->value => 40,
        ];

        $pickWeighted = function (array $weights): string {
            $sum = array_sum($weights);
            $r = random_int(1, $sum);
            $acc = 0;
            foreach ($weights as $key => $w) {
                $acc += $w;
                if ($r <= $acc) {
                    return $key;
                }
            }
            return array_key_first($weights);
        };

        for ($i = 0; $i < $total; $i++) {
            $patient = Patient::factory()->create();
            $type = $pickWeighted($weights);
            $record = MedicalRecord::firstOrCreate(['patient_id' => $patient->id], []);
            $payload = ['patient_type' => $type];

            if ($type === PatientType::PEDIATRIC->value) {
                $dob = now()->subYears(random_int(1, 17))->subDays(random_int(0, 364))->toDateString();
                $patient->update(['date_of_birth' => $dob]);
                $tutor = \App\Models\Tutor::create([
                    'full_name' => fake()->name(),
                    'relationship' => fake()->randomElement(['Madre', 'Padre', 'Tutor legal', 'Abuela', 'Abuelo']),
                    'phone_number' => fake()->phoneNumber(),
                    'address' => fake()->address(),
                ]);
                $patient->update(['tutor_id' => $tutor->id]);
            } elseif ($type === PatientType::EMPLOYEE->value) {
                $payload['employee_status'] = $pickWeighted($employeeWeights);
                $dob = now()->subYears(random_int(18, 65))->subDays(random_int(0, 364))->toDateString();
                $patient->update(['date_of_birth' => $dob]);
            } else {
                // Para otros tipos, permitir cualquier edad, pero favorecer adultos
                $adult = (random_int(1, 100) <= 70);
                $years = $adult ? random_int(18, 80) : random_int(1, 17);
                $dob = now()->subYears($years)->subDays(random_int(0, 364))->toDateString();
                $patient->update(['date_of_birth' => $dob]);
            }

            $record->update($payload);
        }
    }
}
