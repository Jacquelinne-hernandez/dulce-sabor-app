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
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_pedido')
                ->unique()
                ->constrained('pedidos')
                ->restrictOnDelete();

            $table->foreignId('id_cliente')
                ->constrained('clientes')
                ->restrictOnDelete();

            $table->foreignId('id_usuario')
                ->constrained('usuarios')
                ->restrictOnDelete();

            $table->string('numero_factura', 30)->unique();

            $table->dateTime('fecha_emision')->useCurrent();

            $table->decimal('subtotal', 10, 2);
            $table->decimal('total', 10, 2);

            $table->decimal('efectivo_recibido', 10, 2);
            $table->decimal('cambio', 10, 2);

            $table->enum('estado', [
                'emitida',
                'anulada',
            ])->default('emitida');

            $table->timestamps();

            $table->index('fecha_emision');
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
