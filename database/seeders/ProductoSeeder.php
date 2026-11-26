<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    /**
     * Productos de ejemplo de la pastelería.
     *
     * Los precios incluyen IVA. Algunos productos quedan con stock bajo
     * o agotado a propósito, para que se vean las alertas del sistema.
     * Requiere que CategoriaSeeder se haya ejecutado antes.
     */
    public function run(): void
    {
        // [categoría, nombre, precio, costo, stock, stock mínimo, descripción]
        $productos = [
            // Pasteles
            ['Pasteles', 'Pastel tres leches (entero)', 18.00, 7.20, 6, 2, 'Pastel entero de tres leches, 12 porciones'],
            ['Pasteles', 'Pastel de chocolate (entero)', 20.00, 8.10, 5, 2, 'Pastel entero de chocolate con cobertura, 12 porciones'],
            ['Pasteles', 'Pastel de zanahoria (entero)', 19.50, 7.80, 4, 2, 'Pastel entero de zanahoria con queso crema'],
            ['Pasteles', 'Porción de pastel tres leches', 2.75, 1.10, 24, 8, 'Porción individual de pastel tres leches'],
            ['Pasteles', 'Porción de pastel de chocolate', 3.00, 1.20, 20, 8, 'Porción individual de pastel de chocolate'],

            // Cupcakes
            ['Cupcakes', 'Cupcake de vainilla', 1.75, 0.55, 40, 12, 'Cupcake de vainilla con betún de mantequilla'],
            ['Cupcakes', 'Cupcake de chocolate', 1.75, 0.60, 36, 12, 'Cupcake de chocolate con betún de chocolate'],
            ['Cupcakes', 'Cupcake red velvet', 2.25, 0.80, 3, 10, 'Cupcake red velvet con queso crema'],

            // Galletas
            ['Galletas', 'Galleta de chispas de chocolate', 1.00, 0.28, 60, 20, 'Galleta artesanal con chispas de chocolate'],
            ['Galletas', 'Galleta de avena y pasas', 0.90, 0.25, 45, 15, 'Galleta artesanal de avena con pasas'],
            ['Galletas', 'Alfajor de dulce de leche', 1.25, 0.40, 30, 10, 'Alfajor relleno de dulce de leche'],

            // Pan dulce
            ['Pan dulce', 'Concha', 0.75, 0.20, 50, 20, 'Pan dulce con cubierta de azúcar'],
            ['Pan dulce', 'Cuernito de mantequilla', 0.90, 0.30, 35, 12, 'Cuernito horneado con mantequilla'],
            ['Pan dulce', 'Empanada de leche', 1.00, 0.30, 28, 10, 'Empanada rellena de crema de leche'],
            ['Pan dulce', 'Quesadilla salvadoreña (porción)', 1.50, 0.50, 18, 6, 'Porción de quesadilla salvadoreña'],

            // Postres
            ['Postres', 'Flan de caramelo', 2.50, 0.90, 16, 6, 'Flan individual con caramelo'],
            ['Postres', 'Cheesecake de fresa (porción)', 3.25, 1.30, 14, 5, 'Porción de cheesecake con salsa de fresa'],
            ['Postres', 'Tiramisú (vasito)', 3.00, 1.20, 0, 5, 'Tiramisú individual en vasito'],

            // Bebidas
            ['Bebidas', 'Café americano', 1.25, 0.30, 100, 30, 'Café negro recién preparado'],
            ['Bebidas', 'Capuchino', 2.25, 0.65, 60, 20, 'Café con leche espumada'],
            ['Bebidas', 'Chocolate caliente', 2.00, 0.55, 50, 20, 'Chocolate caliente con leche'],
            ['Bebidas', 'Té frío de limón', 1.50, 0.35, 40, 15, 'Té frío con limón'],
        ];

        foreach ($productos as [$categoria, $nombre, $precio, $costo, $stock, $minimo, $descripcion]) {

            $idCategoria = Categoria::where('nombre', $categoria)->value('id');

            // Si la categoría no existe (fue eliminada), se omite el producto.
            if (!$idCategoria) {
                continue;
            }

            Producto::updateOrCreate(
                ['nombre' => $nombre],
                [
                    'id_categoria' => $idCategoria,
                    'precio' => $precio,
                    'costo' => $costo,
                    'stock' => $stock,
                    'stock_minimo' => $minimo,
                    'descripcion' => $descripcion,
                    'estado' => $stock > 0 ? 'disponible' : 'agotado',
                ]
            );
        }
    }
}
