<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Trazabilidad: cada cambio de estado de pedidos, órdenes, facturas
     * y reservas queda registrado con quién lo hizo y cuándo.
     */
    public function up(): void
    {
        Schema::create('historial_estados', function (Blueprint $table) {
            $table->id();

            // PEDIDO, ORDEN, FACTURA o RESERVA.
            $table->string('entidad', 30);
            $table->unsignedBigInteger('id_entidad');

            $table->string('estado_anterior', 30)->nullable();
            $table->string('estado_nuevo', 30);

            $table->foreignId('id_usuario')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();

            $table->string('comentario', 255)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['entidad', 'id_entidad']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_estados');
    }
};
