<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Ingresos externos al giro del negocio: capital inicial, inversiones,
     * aportes de socios, préstamos, donaciones, etc. Las ventas NO van aquí.
     */
    public function up(): void
    {
        Schema::create('ingresos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_usuario')
                ->constrained('usuarios')
                ->restrictOnDelete();

            $table->enum('tipo', [
                'capital_inicial',
                'inversion',
                'aporte_socio',
                'prestamo',
                'donacion',
                'otros',
            ]);

            $table->string('descripcion', 255)->nullable();

            $table->decimal('monto', 10, 2);

            $table->date('fecha_ingreso');

            $table->enum('estado', [
                'registrado',
                'anulado',
            ])->default('registrado');

            $table->timestamps();

            $table->index(['fecha_ingreso', 'estado']);
            $table->index('tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingresos');
    }
};
