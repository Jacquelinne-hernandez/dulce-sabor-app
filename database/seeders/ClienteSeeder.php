<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Correlativo;
use App\Models\Estado;
use Illuminate\Database\Seeder;

class ClienteSeeder extends Seeder
{
    /**
     * Clientes de ejemplo (datos ficticios).
     *
     * Algunos no dejaron teléfono ni correo: solo nombre y apellido,
     * como en la vida real. El código se genera con el correlativo,
     * y no se repite si el seeder se ejecuta otra vez.
     * Requiere que CorrelativoSeeder se haya ejecutado antes.
     */
    public function run(): void
    {
        // [nombre, apellido, teléfono, correo, puntos, días desde el registro]
        $clientes = [
            ['María Fernanda', 'Rivas Hernández', '7012-3456', 'maria.rivas@example.com', 45, 60],
            ['José Luis', 'Martínez', null, null, 12, 45],
            ['Ana Lucía', 'Portillo', null, 'ana.portillo@example.com', 80, 90],
            ['Carlos Alberto', 'Mejía', '7134-5678', null, 0, 5],
            ['Sofía Guadalupe', 'Chávez Rodríguez', '7256-7890', 'sofia.chavez@example.com', 130, 120],
            ['Diego Armando', 'Flores', null, null, 5, 20],
            ['Valeria Beatriz', 'Ortiz', '7378-9012', 'valeria.ortiz@example.com', 20, 30],
            ['Roberto Antonio', 'Cruz', '7490-1234', null, 60, 75],
        ];

        foreach ($clientes as [$nombre, $apellido, $telefono, $correo, $puntos, $dias]) {

            $existe = Cliente::where('nombre', $nombre)
                ->where('apellido', $apellido)
                ->exists();

            // No duplicar clientes si el seeder se ejecuta nuevamente.
            if ($existe) {
                continue;
            }

            Cliente::create([
                'id_estado' => Estado::ACTIVO,
                'codigo' => Correlativo::siguiente(Correlativo::CLIENTE),
                'nombre' => $nombre,
                'apellido' => $apellido,
                'telefono' => $telefono,
                'correo' => $correo,
                'fecha_registro' => now()->subDays($dias)->toDateString(),
                'puntos' => $puntos,
            ]);
        }
    }
}
