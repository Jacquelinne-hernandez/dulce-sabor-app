<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Orden de trabajo (producción) generada al registrar un pedido.
     */
    public function up(): void
    {
        Schema::create('ordenes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_pedido')
                ->unique()
                ->constrained('pedidos')
                ->restrictOnDelete();

            $table->foreignId('id_usuario')
                ->constrained('usuarios')
                ->restrictOnDelete();

            $table->string('numero_orden', 30)->unique();

            $table->enum('estado', [
                'pendiente',
                'en_produccion',
                'lista',
                'entregada',
                'cancelada',
            ])->default('pendiente');

            // Hitos de la producción.
            $table->dateTime('fecha_inicio')->nullable();
            $table->dateTime('fecha_lista')->nullable();
            $table->dateTime('fecha_entrega')->nullable();

            // Indicaciones para quien prepara la orden.
            $table->text('notas')->nullable();

            $table->timestamps();

            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordenes');
    }
};
