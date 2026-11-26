<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Los precios incluyen IVA: base_imponible e iva se calculan a partir
     * del total. El cobro (efectivo, tarjeta, abonos) vive en la tabla pagos.
     */
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_pedido')
                ->unique()
                ->constrained('pedidos')
                ->restrictOnDelete();

            // Cliente opcional: sin cliente es "consumidor final".
            $table->foreignId('id_cliente')
                ->nullable()
                ->constrained('clientes')
                ->restrictOnDelete();

            $table->foreignId('id_usuario')
                ->constrained('usuarios')
                ->restrictOnDelete();

            $table->string('numero_factura', 30)->unique();

            $table->dateTime('fecha_emision')->useCurrent();

            // Subtotal de productos y cargo por mesas (IVA incluido).
            $table->decimal('subtotal', 10, 2);
            $table->decimal('cargo_mesas', 10, 2)->default(0);

            // Descuento por canje de puntos.
            $table->decimal('descuento', 10, 2)->default(0);
            $table->unsignedInteger('puntos_canjeados')->default(0);
            $table->unsignedInteger('puntos_ganados')->default(0);

            $table->decimal('total', 10, 2);

            // Desglose del IVA contenido en el total.
            $table->decimal('base_imponible', 10, 2);
            $table->decimal('iva', 10, 2);

            $table->enum('estado', [
                'emitida',
                'pagada',
                'anulada',
            ])->default('emitida');

            $table->timestamps();

            $table->index('fecha_emision');
            $table->index(['estado', 'fecha_emision']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
