<?php

namespace App\Support;

use App\Enums\Locality;

class ColoniaCatalog
{
    public static function getColonias(?string $locality): array
    {
        if (! $locality) {
            return [];
        }

        // Mapeo de Localidad => Lista de Colonias
        // Puedes agregar o modificar colonias aquí según necesites.
        $map = [
            Locality::PROGRESO_DE_CASTRO->value => [
                'Centro',
                'Benito Juárez',
                'Francisco I. Madero',
                'Héctor Victoria',
                'Ismael García',
                'Juan Montalvo',
                'Nueva Yucalpetén',
                'Revolución',
                'Vicente Guerrero',
                'Canul Reyes',
                'Ciénaga 2000',
                'Costa Azul',
                'Fovissste',
                'Brisas del Sol',
            ],
            Locality::CHELEM->value => [
                'Centro',
                'Zona Playa',
                'Entrada Chelem',
                'Zona Ría',
                'Puerto de Abrigo',
            ],
            Locality::CHICXULUB_PUERTO->value => [
                'Centro',
                'Zona Veraniega',
                'Benito Juárez',
                'Revolución',
            ],
            Locality::CHUBURNA_PUERTO->value => [
                'Centro',
                'Dunas',
                'Zona Playa',
                'Gilberto',
            ],
            Locality::CAMPESTRE_FLAMBOYANES->value => [
                'Campestre',
                'Paraíso',
                'Zona Industrial',
                'IVEY',
            ],
            Locality::SAN_IGNACIO->value => [
                'Centro',
                'Hacienda San Ignacio',
            ],
            Locality::ELENA->value => [
                'Centro',
            ],
            Locality::XTUL->value => [
                'Centro',
            ],
        ];

        $colonias = $map[$locality] ?? [];

        // Devolvemos un array formato ['Centro' => 'Centro'] para el Select de Filament
        return array_combine($colonias, $colonias);
    }
}