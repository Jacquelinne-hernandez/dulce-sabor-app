<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Gastos adicionales del negocio (arrendamiento, servicios, etc.).
     */
    public function up(): void
    {
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_usuario')
                ->constrained('usuarios')
                ->restrictOnDelete();

            $table->enum('tipo', [
                'arrendamiento',
                'luz',
                'agua',
                'internet',
                'salarios',
                'mantenimiento',
                'otros',
            ]);

            $table->string('descripcion', 255)->nullable();

            $table->decimal('monto', 10, 2);

            $table->date('fecha_gasto');

            $table->enum('estado', [
                'registrado',
                'anulado',
            ])->default('registrado');

            $table->timestamps();

            $table->index(['fecha_gasto', 'estado']);
            $table->index('tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
