<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_categoria')
                ->constrained('categorias');

            $table->string('nombre', 100)->unique();

            // Precio de venta (IVA incluido) y costo (promedio ponderado
            // de las compras), para poder calcular la ganancia.
            $table->decimal('precio', 10, 2);
            $table->decimal('costo', 10, 2)->default(0);

            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('stock_minimo')->default(0);

            $table->text('descripcion')->nullable();
            $table->string('imagen', 255)->nullable();

            $table->enum('estado', [
                'disponible',
                'agotado',
                'inactivo'
            ])->default('disponible');

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
