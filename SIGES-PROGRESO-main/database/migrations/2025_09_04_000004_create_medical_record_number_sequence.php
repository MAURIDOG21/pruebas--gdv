<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateMedicalRecordNumberSequence extends Migration
{
    public function up()
    {
        DB::statement('CREATE SEQUENCE IF NOT EXISTS medical_record_number_seq START 1;');
    }

    public function down()
    {
        DB::statement('DROP SEQUENCE IF EXISTS medical_record_number_seq;');
    }
}
