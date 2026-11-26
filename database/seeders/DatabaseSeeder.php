<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Poblar la base de datos.
     *
     * El orden importa: los usuarios dependen de roles y estados.
     */
    public function run(): void
    {
        $this->call([
            RolSeeder::class,
            EstadoSeeder::class,
            UsuarioSeeder::class,
            HorarioSeeder::class,
            CategoriaSeeder::class,
        ]);
    }
}
