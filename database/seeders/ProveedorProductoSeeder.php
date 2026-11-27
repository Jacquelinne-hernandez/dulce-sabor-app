<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Database\Seeder;

class ProveedorProductoSeeder extends Seeder
{
    /**
     * Productos COMPRADOS (origen "compra") y qué proveedor los ofrece, con
     * el precio al que los vende. Los demás productos del catálogo son de
     * producción propia.
     *
     * Se puede ejecutar varias veces: no duplica ni pisa datos existentes.
     *   php artisan db:seed --class=ProveedorProductoSeeder
     * Requiere ProveedorSeeder y CategoriaSeeder (y la migración de origen).
     */
    public function run(): void
    {
        // [proveedor, categoría, nombre, precio venta, precio compra,
        //  stock, stock mínimo, descripción]
        $comprados = [
            // Café Montaña Alta
            ['Café Montaña Alta', 'Bebidas', 'Café molido de la casa (bolsa 250 g)', 4.50, 2.80, 12, 4, 'Café molido, bolsa de 250 g'],
            ['Café Montaña Alta', 'Bebidas', 'Té en cajita (10 sobres)', 3.25, 1.90, 10, 4, 'Caja de 10 sobres de té surtido'],

            // Lácteos del Valle
            ['Lácteos del Valle', 'Bebidas', 'Leche saborizada (cajita 200 ml)', 1.25, 0.70, 24, 8, 'Cajita de leche saborizada'],

            // Frutas y Esencias Tropicales
            ['Frutas y Esencias Tropicales', 'Bebidas', 'Jugo natural de naranja (330 ml)', 1.75, 1.00, 18, 6, 'Botella de jugo natural de naranja'],
            ['Frutas y Esencias Tropicales', 'Bebidas', 'Agua embotellada 600 ml', 1.00, 0.45, 40, 12, 'Agua purificada embotellada'],

            // Cacao y Chocolates Cuscatlán
            ['Cacao y Chocolates Cuscatlán', 'Complementos', 'Barra de chocolate artesanal (50 g)', 1.50, 0.85, 30, 10, 'Barra de chocolate artesanal'],

            // Empaques Dulce Hogar
            ['Empaques Dulce Hogar', 'Complementos', 'Caja de regalo para pastel', 2.00, 1.10, 20, 6, 'Caja decorada para regalo'],
            ['Empaques Dulce Hogar', 'Complementos', 'Velas de cumpleaños (paquete)', 1.25, 0.60, 25, 8, 'Paquete de velas de cumpleaños'],

            // Distribuidora La Espiga
            ['Distribuidora La Espiga', 'Galletas', 'Galletas María (paquete)', 1.10, 0.65, 30, 10, 'Paquete de galletas María'],
        ];

        foreach ($comprados as [$nombreProveedor, $nombreCategoria, $nombre, $precio, $costo, $stock, $minimo, $descripcion]) {

            $proveedor = Proveedor::where('nombre', $nombreProveedor)->first();

            // Si el proveedor fue eliminado, se omite su producto.
            if (!$proveedor) {
                continue;
            }

            $categoria = Categoria::firstOrCreate(
                ['nombre' => $nombreCategoria],
                ['descripcion' => 'Artículos complementarios para pasteles y regalos']
            );

            // No se modifica un producto que ya exista (stock y costo reales).
            $producto = Producto::firstOrCreate(
                ['nombre' => $nombre],
                [
                    'id_categoria' => $categoria->id,
                    'precio' => $precio,
                    'costo' => $costo,
                    'stock' => $stock,
                    'stock_minimo' => $minimo,
                    'descripcion' => $descripcion,
                    'estado' => $stock > 0 ? 'disponible' : 'agotado',
                    'origen' => Producto::ORIGEN_COMPRA,
                ]
            );

            // Solo los productos de tipo "compra" se asocian a proveedores.
            if ($producto->origen !== Producto::ORIGEN_COMPRA) {
                continue;
            }

            if (!$proveedor->productos()->where('productos.id', $producto->id)->exists()) {
                $proveedor->productos()->attach($producto->id, ['precio_compra' => $costo]);
            }
        }
    }
}
