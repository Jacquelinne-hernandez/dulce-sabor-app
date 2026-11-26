<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * El pedido es la parte comercial (qué pidió el cliente y cuánto
     * cuesta). La producción se sigue en la tabla ordenes.
     */
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();

            // Cliente opcional: sin cliente es "consumidor final".
            $table->foreignId('id_cliente')
                ->nullable()
                ->constrained('clientes')
                ->restrictOnDelete();

            // Mesa ocupada por el pedido (consumo en el local sin reserva).
            $table->foreignId('id_mesa')
                ->nullable()
                ->constrained('mesas')
                ->restrictOnDelete();

            // Reserva de la que proviene el pedido (un pedido por reserva).
            $table->foreignId('id_reserva')
                ->nullable()
                ->unique()
                ->constrained('reservas')
                ->restrictOnDelete();

            $table->foreignId('id_usuario')
                ->constrained('usuarios')
                ->restrictOnDelete();

            $table->enum('tipo', [
                'local',
                'llevar',
                'reserva',
            ])->default('llevar');

            $table->dateTime('fecha')->useCurrent();

            // Fecha y hora programadas de entrega (null = para el momento).
            $table->dateTime('fecha_programada')->nullable();

            // Montos con IVA incluido.
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('cargo_mesas', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);

            $table->enum('estado', [
                'abierto',
                'completado',
                'cancelado',
            ])->default('abierto');

            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->index(['estado', 'fecha']);
            $table->index(['id_cliente', 'fecha']);
            $table->index(['id_mesa', 'fecha']);
            $table->index('fecha_programada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
