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
        Schema::create('compras', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_proveedor')
                ->constrained('proveedores')
                ->restrictOnDelete();

            $table->foreignId('id_usuario')
                ->constrained('usuarios')
                ->restrictOnDelete();

            $table->dateTime('fecha_compra')->useCurrent();

            $table->decimal('total', 10, 2)->default(0);

            // Condición de pago: al contado o a crédito (genera deuda
            // con el proveedor, que se salda con abonos).
            $table->enum('condicion_pago', [
                'contado',
                'credito',
            ])->default('contado');

            $table->date('fecha_vencimiento')->nullable();

            $table->decimal('monto_pagado', 10, 2)->default(0);

            $table->enum('estado_pago', [
                'pendiente',
                'parcial',
                'pagada',
            ])->default('pagada');

            $table->enum('estado', [
                'completada',
            ])->default('completada');

            $table->timestamps();

            $table->index(['estado_pago', 'fecha_vencimiento']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
