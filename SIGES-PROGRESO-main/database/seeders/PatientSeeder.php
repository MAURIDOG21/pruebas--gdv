<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\MedicalRecord;
use App\Enums\Locality;
use App\Enums\PatientType;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $patients = [
            ['full_name' => 'María Fernanda López Castillo', 'curp' => 'LOCA000315MYNPSR03', 'date_of_birth' => '2000-03-15', 'sex' => 'F', 'locality' => Locality::PROGRESO_DE_CASTRO, 'contact_phone' => '999-234-8871', 'address' => 'Calle 27 #112 x 30 y 32, Progreso, Yucatán'],
            ['full_name' => 'José Manuel Chan Uc', 'curp' => 'CAUJ950728HYNNCN08', 'date_of_birth' => '1995-07-28', 'sex' => 'M', 'locality' => Locality::CHELEM, 'contact_phone' => '999-145-6620', 'address' => 'Calle 19 #201 x 48, Chelem, Yucatán'],
            ['full_name' => 'Ana Patricia Méndez Ceballos', 'curp' => 'MECA840912MYNNDN02', 'date_of_birth' => '1984-09-12', 'sex' => 'F', 'locality' => Locality::CHICXULUB_PUERTO, 'contact_phone' => '999-563-0144', 'address' => 'Calle 5 #45 x 10 y 12, Chicxulub Puerto'],
            ['full_name' => 'Ricardo Alejandro Poot Dzib', 'curp' => 'PODR020421HYNZBC06', 'date_of_birth' => '2002-04-21', 'sex' => 'M', 'locality' => Locality::CAMPESTRE_FLAMBOYANES, 'contact_phone' => '999-771-2299', 'address' => 'Calle 60 #320, Flamboyanes, Yucatán'],
            ['full_name' => 'Carolina Beatriz Catzín Peña', 'curp' => 'CAPC990105MYNTNR04', 'date_of_birth' => '1999-01-05', 'sex' => 'F', 'locality' => Locality::SAN_IGNACIO, 'contact_phone' => '999-879-6621', 'address' => 'Calle 14 #98 x 21, San Ignacio'],
            ['full_name' => 'Luis Alberto Ucab May', 'curp' => 'UAML910923HYNMYL01', 'date_of_birth' => '1991-09-23', 'sex' => 'M', 'locality' => Locality::CHUBURNA_PUERTO, 'contact_phone' => '999-773-5010', 'address' => 'Calle 3 #20 x 10, Chuburná Puerto'],
            ['full_name' => 'Daniela Sofía Ramírez Poot', 'curp' => 'RAPS050712MYNMNN07', 'date_of_birth' => '2005-07-12', 'sex' => 'F', 'locality' => Locality::XTUL, 'contact_phone' => '999-201-4453', 'address' => 'Calle 2 #6, Xtul, Yucatán'],
            ['full_name' => 'Héctor Armando Canto Cetz', 'curp' => 'CAOH870503HYNNTC03', 'date_of_birth' => '1987-05-03', 'sex' => 'M', 'locality' => Locality::ELENA, 'contact_phone' => '999-445-9981', 'address' => 'Calle 16 #150, Elená'],
            ['full_name' => 'Julieta Marcela Dzib Pech', 'curp' => 'DIPJ940812MYNBCL05', 'date_of_birth' => '1994-08-12', 'sex' => 'F', 'locality' => Locality::PROGRESO_DE_CASTRO, 'contact_phone' => '999-552-0194', 'address' => 'Calle 80 #402 x 33 y 35, Centro, Progreso'],
            ['full_name' => 'Salvador Enrique Pech Chan', 'curp' => 'PECS780227HYNHNL01', 'date_of_birth' => '1978-02-27', 'sex' => 'M', 'locality' => Locality::CHICXULUB_PUERTO, 'contact_phone' => '999-664-1229', 'address' => 'Calle 13 #90 x 18 y 20, Chicxulub Puerto'],
        ];

        foreach ($patients as $p) {
            $curpHash = hash('sha256', strtoupper(trim($p['curp'])));
            $patient = Patient::updateOrCreate(['curp_hash' => $curpHash], $p);
            $record = MedicalRecord::firstOrCreate(['patient_id' => $patient->id]);
            $record->update(['patient_type' => PatientType::EXTERNAL->value]);
        }
    }
}
