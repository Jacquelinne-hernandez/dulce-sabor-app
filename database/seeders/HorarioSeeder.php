<?php

namespace Database\Seeders;

use App\Models\Horario;
use Illuminate\Database\Seeder;

class HorarioSeeder extends Seeder
{
    /**
     * Horario de atención por día de la semana (formato ISO:
     * 1 = lunes ... 7 = domingo). Puede modificarse desde el sistema.
     *
     * Las reservas dependen de estos registros: sin horarios no se
     * puede reservar ninguna mesa.
     */
    public function run(): void
    {
        $horarios = [
            1 => ['08:00', '18:00', true],
            2 => ['08:00', '18:00', true],
            3 => ['08:00', '18:00', true],
            4 => ['08:00', '18:00', true],
            5 => ['08:00', '18:00', true],
            6 => ['08:00', '18:00', true],
            7 => [null, null, false],
        ];

        foreach ($horarios as $dia => [$apertura, $cierre, $activo]) {
            Horario::updateOrCreate(
                ['dia_semana' => $dia],
                [
                    'hora_apertura' => $apertura,
                    'hora_cierre' => $cierre,
                    'activo' => $activo,
                ]
            );
        }
    }
}
