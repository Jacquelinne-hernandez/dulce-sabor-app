<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Pagos de una factura (puede haber varios abonos). Del pago con
     * tarjeta solo se guardan la marca y los últimos 4 dígitos.
     */
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_factura')
                ->constrained('facturas')
                ->restrictOnDelete();

            $table->foreignId('id_usuario')
                ->constrained('usuarios')
                ->restrictOnDelete();

            $table->enum('metodo', [
                'efectivo',
                'tarjeta',
            ]);

            $table->decimal('monto', 10, 2);

            // Solo para pagos en efectivo.
            $table->decimal('efectivo_recibido', 10, 2)->nullable();
            $table->decimal('cambio', 10, 2)->nullable();

            // Solo para pagos con tarjeta (simulados).
            $table->string('tarjeta_marca', 20)->nullable();
            $table->string('tarjeta_ultimos4', 4)->nullable();
            $table->string('referencia', 30)->nullable();

            $table->dateTime('fecha_pago')->useCurrent();

            $table->enum('estado', [
                'aplicado',
                'anulado',
            ])->default('aplicado');

            $table->timestamps();

            $table->index(['id_factura', 'estado']);
            $table->index('fecha_pago');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
