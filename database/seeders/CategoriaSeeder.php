<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    /**
     * Categorías iniciales de la pastelería.
     */
    public function run(): void
    {
        $categorias = [
            [
                'nombre' => 'Pasteles',
                'descripcion' => 'Pasteles y tortas para toda ocasión',
            ],
            [
                'nombre' => 'Cupcakes',
                'descripcion' => 'Cupcakes individuales y por docena',
            ],
            [
                'nombre' => 'Galletas',
                'descripcion' => 'Galletas artesanales',
            ],
            [
                'nombre' => 'Pan dulce',
                'descripcion' => 'Pan dulce y repostería diaria',
            ],
            [
                'nombre' => 'Postres',
                'descripcion' => 'Postres individuales y en porciones',
            ],
            [
                'nombre' => 'Bebidas',
                'descripcion' => 'Café, té y bebidas frías',
            ],
        ];

        foreach ($categorias as $categoria) {
            Categoria::updateOrCreate(
                ['nombre' => $categoria['nombre']],
                ['descripcion' => $categoria['descripcion']]
            );
        }
    }
}
