<?php

namespace Database\Seeders;

use App\Models\Proveedor;
use Illuminate\Database\Seeder;

class ProveedorSeeder extends Seeder
{
    /**
     * Proveedores de ejemplo para la pastelería (datos ficticios).
     */
    public function run(): void
    {
        $proveedores = [
            [
                'nombre' => 'Distribuidora La Espiga',
                'nombre_contacto' => 'Carlos Menjívar',
                'telefono' => '2234-5601',
                'email' => 'ventas@laespiga.example.com',
                'direccion' => 'Calle Principal #12, San Salvador',
                'descripcion' => 'Harinas, azúcar, levadura y polvo de hornear',
            ],
            [
                'nombre' => 'Lácteos del Valle',
                'nombre_contacto' => 'Rosa Amaya',
                'telefono' => '2245-7712',
                'email' => 'pedidos@lacteosvalle.example.com',
                'direccion' => 'Km 14 carretera a Santa Ana',
                'descripcion' => 'Leche, crema, mantequilla, queso crema y huevos',
            ],
            [
                'nombre' => 'Cacao y Chocolates Cuscatlán',
                'nombre_contacto' => 'Mario Portillo',
                'telefono' => '2256-3390',
                'email' => 'contacto@cacaocuscatlan.example.com',
                'direccion' => 'Zona Industrial, Soyapango',
                'descripcion' => 'Chocolate para repostería, cocoa y coberturas',
            ],
            [
                'nombre' => 'Café Montaña Alta',
                'nombre_contacto' => 'Lucía Hernández',
                'telefono' => '2267-4408',
                'email' => 'ventas@cafemontana.example.com',
                'direccion' => 'Ruta de las Flores, Ahuachapán',
                'descripcion' => 'Café en grano y molido, té e infusiones',
            ],
            [
                'nombre' => 'Empaques Dulce Hogar',
                'nombre_contacto' => 'Jorge Rivas',
                'telefono' => '2278-5523',
                'email' => 'info@empaquesdulcehogar.example.com',
                'direccion' => 'Col. Layco, San Salvador',
                'descripcion' => 'Cajas para pastel, bolsas, vasos y desechables',
            ],
            [
                'nombre' => 'Frutas y Esencias Tropicales',
                'nombre_contacto' => 'Elena Cruz',
                'telefono' => '2289-6634',
                'email' => 'ventas@frutasesencias.example.com',
                'direccion' => 'Mercado Central, local 45, San Salvador',
                'descripcion' => 'Fruta fresca, esencias, colorantes y decoraciones',
            ],
        ];

        foreach ($proveedores as $proveedor) {
            Proveedor::updateOrCreate(
                ['nombre' => $proveedor['nombre']],
                [
                    'nombre_contacto' => $proveedor['nombre_contacto'],
                    'telefono' => $proveedor['telefono'],
                    'email' => $proveedor['email'],
                    'direccion' => $proveedor['direccion'],
                    'descripcion' => $proveedor['descripcion'],
                ]
            );
        }
    }
}
