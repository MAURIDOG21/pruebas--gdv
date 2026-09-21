<?php

namespace App\Enums;

enum ChronicDisease: string
{
    case DIABETES = 'DIABETES';
    case HIPERTENSION = 'HIPERTENSION';
    case OBESIDAD = 'OBESIDAD';
    case ASMA = 'ASMA';
    case CANCER = 'CANCER';
    case ENFERMEDAD_CARDIOVASCULAR = 'ENFERMEDAD_CARDIOVASCULAR';
    case INSUFICIENCIA_RENAL = 'INSUFICIENCIA_RENAL';
    case OTRA = 'OTRA';

    public function getLabel(): string
    {
        return match ($this) {
            self::DIABETES => 'Diabetes Mellitus',
            self::HIPERTENSION => 'Hipertensión Arterial',
            self::OBESIDAD => 'Obesidad',
            self::ASMA => 'Asma',
            self::CANCER => 'Cáncer',
            self::ENFERMEDAD_CARDIOVASCULAR => 'Enfermedad Cardiovascular',
            self::INSUFICIENCIA_RENAL => 'Insuficiencia Renal',
            self::OTRA => 'Otra',
        };
    }
}

