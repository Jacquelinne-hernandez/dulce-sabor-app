<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Una reserva puede incluir varias mesas: se registran en
     * detalle_reservas (con la tarifa de cada una).
     */
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();

            // Las reservas requieren un cliente registrado.
            $table->foreignId('id_cliente')
                ->constrained('clientes')
                ->restrictOnDelete();

            $table->foreignId('id_usuario')
                ->constrained('usuarios')
                ->restrictOnDelete();

            $table->date('fecha');

            $table->time('hora_inicio');
            $table->time('hora_fin');

            $table->unsignedInteger('cantidad_personas');

            // Total por el uso de las mesas (suma de detalle_reservas).
            $table->decimal('total_mesas', 10, 2)->default(0);

            $table->text('observaciones')->nullable();

            $table->enum('estado', [
                'pendiente',
                'confirmada',
                'cancelada',
                'atendida',
            ])->default('pendiente');

            $table->timestamps();

            $table->index(['fecha', 'estado']);
            $table->index(['id_cliente', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
