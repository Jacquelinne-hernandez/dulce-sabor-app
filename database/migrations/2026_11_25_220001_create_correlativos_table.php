<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Contadores para la numeración secuencial de documentos
     * (clientes, facturas y órdenes).
     */
    public function up(): void
    {
        Schema::create('correlativos', function (Blueprint $table) {
            $table->id();

            $table->string('tipo', 20)->unique();
            $table->string('prefijo', 10);
            $table->unsignedTinyInteger('longitud')->default(6);
            $table->unsignedBigInteger('ultimo_numero')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('correlativos');
    }
};
