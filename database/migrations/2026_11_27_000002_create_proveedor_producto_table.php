<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Productos que ofrece cada proveedor, con el precio al que los vende.
     */
    public function up(): void
    {
        if (Schema::hasTable('proveedor_producto')) {
            return;
        }

        Schema::create('proveedor_producto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_proveedor')
                ->constrained('proveedores')
                ->cascadeOnDelete();
            $table->foreignId('id_producto')
                ->constrained('productos')
                ->cascadeOnDelete();
            $table->decimal('precio_compra', 10, 2);
            $table->timestamps();

            // Un proveedor ofrece cada producto una sola vez.
            $table->unique(['id_proveedor', 'id_producto']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedor_producto');
    }
};
