<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Fila única que sirve de "candado" de la caja: las operaciones que gastan
// dinero la bloquean para que dos de ellas, al mismo tiempo, no gasten lo
// mismo. El saldo en sí no se guarda: se calcula desde los movimientos.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('caja')) {
            return;
        }

        Schema::create('caja', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->timestamps();
        });

        DB::table('caja')->insert([
            'id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('caja');
    }
};
