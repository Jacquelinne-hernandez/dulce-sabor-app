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
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_cliente')
                ->constrained('clientes')
                ->restrictOnDelete();

            $table->foreignId('id_mesa')
                ->constrained('mesas')
                ->restrictOnDelete();

            $table->foreignId('id_usuario')
                ->constrained('usuarios')
                ->restrictOnDelete();

            $table->date('fecha');

            $table->time('hora_inicio');
            $table->time('hora_fin');

            $table->unsignedInteger('cantidad_personas');

            $table->text('observaciones')->nullable();

            $table->enum('estado', [
                'pendiente',
                'confirmada',
                'cancelada',
                'atendida',
            ])->default('pendiente');

            $table->timestamps();

            $table->index(['id_mesa', 'fecha', 'estado']);
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
