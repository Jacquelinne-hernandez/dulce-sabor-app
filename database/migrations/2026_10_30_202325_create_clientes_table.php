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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_estado')
                ->default(1)
                ->constrained('estados');

            // Código único del cliente (el que se imprime en su tarjeta).
            $table->string('codigo', 20)->unique();

            $table->string('nombre', 50);
            $table->string('apellido', 50);

            // Datos de contacto opcionales.
            $table->string('telefono', 20)->nullable();
            $table->string('correo', 100)->nullable()->unique();

            $table->date('fecha_registro')->nullable();
            $table->unsignedInteger('puntos')->default(0);

            $table->timestamps();

            $table->index(['nombre', 'apellido']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
