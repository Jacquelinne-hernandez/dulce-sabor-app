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
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_cliente')
                ->constrained('clientes')
                ->restrictOnDelete();

            $table->foreignId('id_mesa')
                ->nullable()
                ->constrained('mesas')
                ->restrictOnDelete();

            $table->foreignId('id_usuario')
                ->constrained('usuarios')
                ->restrictOnDelete();

            $table->dateTime('fecha')->useCurrent();
            $table->decimal('total', 10, 2)->default(0);

            $table->enum('estado', [
                'pendiente',
                'en_preparacion',
                'listo',
                'entregado',
                'cancelado',
            ])->default('pendiente');

            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->index(['estado', 'fecha']);
            $table->index(['id_cliente', 'fecha']);
            $table->index(['id_mesa', 'fecha']);
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
