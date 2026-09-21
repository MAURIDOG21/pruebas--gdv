<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ServiceSeeder::class,
            ClinicSeeder::class,
            ClinicScheduleSeeder::class,
            PatientSeeder::class,
            PatientsBulkSeeder::class,
            AppointmentSeeder::class,
            AppointmentsBulkSeeder::class,
            PrescriptionSeeder::class,
            PrescriptionsBulkSeeder::class,
            MedicalLeavesBulkSeeder::class,
            SomatometricReadingSeeder::class,
            NursingAssessmentSeeder::class,
            MedicalDocumentSeeder::class,
            ApiTokenSeeder::class,
        ]);
    }
}
