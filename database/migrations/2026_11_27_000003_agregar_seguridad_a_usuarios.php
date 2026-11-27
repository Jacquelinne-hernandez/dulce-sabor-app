<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * - remember_token: permite la opción "Recordarme" del inicio de sesión.
     * - debe_cambiar_password: el usuario nuevo (o con la contraseña
     *   restablecida por el administrador) debe cambiarla antes de usar el
     *   sistema.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('usuarios', 'remember_token')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->rememberToken()->after('password');
            });
        }

        if (!Schema::hasColumn('usuarios', 'debe_cambiar_password')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->boolean('debe_cambiar_password')
                    ->default(false)
                    ->after('telefono');
            });
        }
    }

    public function down(): void
    {
        foreach (['remember_token', 'debe_cambiar_password'] as $columna) {
            if (Schema::hasColumn('usuarios', $columna)) {
                Schema::table('usuarios', function (Blueprint $table) use ($columna) {
                    $table->dropColumn($columna);
                });
            }
        }
    }
};
