<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Pagos y abonos a proveedores por compras al contado o a crédito.
     */
    public function up(): void
    {
        Schema::create('pagos_compras', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_compra')
                ->constrained('compras')
                ->restrictOnDelete();

            $table->foreignId('id_usuario')
                ->constrained('usuarios')
                ->restrictOnDelete();

            $table->decimal('monto', 10, 2);

            $table->dateTime('fecha_pago')->useCurrent();

            $table->string('observaciones', 255)->nullable();

            $table->timestamps();

            $table->index(['id_compra', 'fecha_pago']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos_compras');
    }
};
