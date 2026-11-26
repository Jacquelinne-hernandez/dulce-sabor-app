<?php

namespace Database\Seeders;

use App\Models\Correlativo;
use Illuminate\Database\Seeder;

class CorrelativoSeeder extends Seeder
{
    /**
     * Contadores de numeración secuencial.
     *
     * Si el contador ya existe no se reinicia, así que es seguro
     * ejecutar el seeder más de una vez.
     */
    public function run(): void
    {
        $correlativos = [
            [
                'tipo' => Correlativo::CLIENTE,
                'prefijo' => 'CLI-',
                'longitud' => 5,
            ],
            [
                'tipo' => Correlativo::FACTURA,
                'prefijo' => 'FAC-',
                'longitud' => 6,
            ],
            [
                'tipo' => Correlativo::ORDEN,
                'prefijo' => 'ORD-',
                'longitud' => 6,
            ],
        ];

        foreach ($correlativos as $correlativo) {
            Correlativo::firstOrCreate(
                ['tipo' => $correlativo['tipo']],
                [
                    'prefijo' => $correlativo['prefijo'],
                    'longitud' => $correlativo['longitud'],
                    'ultimo_numero' => 0,
                ]
            );
        }
    }
}
