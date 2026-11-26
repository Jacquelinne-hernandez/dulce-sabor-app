<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    /**
     * Crear usuarios predeterminados del sistema.
     */
    public function run(): void
    {


        $usuarios = [
            [
                'id_rol' => 1,
                'id_estado' => 1,
                'nombre' => 'Jacquelinne',
                'apellido' => 'Hernandez',
                'email' => 'admin@gmail.com',
                'password' => 'Admin123',
                'telefono' => null,
            ],
            [
                'id_rol' => 2,
                'id_estado' => 1,
                'nombre' => 'Nicolle',
                'apellido' => 'Clemente',
                'email' => 'Nicolle@gmail.com',
                'password' => '123456789',
                'telefono' => null,
            ],
        ];

        foreach ($usuarios as $usuario) {
            $existe = DB::table('usuarios')
                ->where('email', $usuario['email'])
                ->exists();

            // No duplicar usuarios si el seeder se ejecuta nuevamente.
            if ($existe) {
                continue;
            }

            DB::table('usuarios')->insert([
                'id_rol' => $usuario['id_rol'],
                'id_estado' => $usuario['id_estado'],
                'nombre' => $usuario['nombre'],
                'apellido' => $usuario['apellido'],
                'email' => $usuario['email'],
                'password' => Hash::make($usuario['password']),
                'telefono' => $usuario['telefono'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
