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

        Schema::create('mesas', function (Blueprint $table) {
            $table->id();

            $table->integer('numero')->unique();
            $table->integer('capacidad');

            // Tarifa por hora que se cobra cuando la mesa se reserva
            // para un evento (la define el administrador).
            $table->decimal('tarifa_hora', 8, 2)->default(0);

            $table->enum('estado', [
                'disponible',
                'reservada',
                'ocupada',
                'mantenimiento'
            ])->default('disponible');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mesas');
    }
};
