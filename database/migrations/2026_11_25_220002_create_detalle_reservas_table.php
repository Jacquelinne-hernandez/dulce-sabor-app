<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Mesas incluidas en una reserva. La tarifa y las horas se guardan
     * al momento de reservar, para que el cobro no cambie después.
     */
    public function up(): void
    {
        Schema::create('detalle_reservas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_reserva')
                ->constrained('reservas')
                ->cascadeOnDelete();

            $table->foreignId('id_mesa')
                ->constrained('mesas')
                ->restrictOnDelete();

            $table->decimal('tarifa_hora', 8, 2);

            // Horas cobradas, redondeadas hacia arriba a media hora.
            $table->decimal('horas', 4, 1);

            $table->decimal('subtotal', 10, 2);

            $table->timestamps();

            $table->unique(['id_reserva', 'id_mesa']);
            $table->index('id_mesa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_reservas');
    }
};
