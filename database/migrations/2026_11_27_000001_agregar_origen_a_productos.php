<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Distingue los productos que la pastelería elabora (produccion) de los
     * que compra ya hechos a un proveedor (compra).
     */
    public function up(): void
    {
        if (!Schema::hasColumn('productos', 'origen')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->enum('origen', ['produccion', 'compra'])
                    ->default('produccion')
                    ->after('estado');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('productos', 'origen')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->dropColumn('origen');
            });
        }
    }
};
