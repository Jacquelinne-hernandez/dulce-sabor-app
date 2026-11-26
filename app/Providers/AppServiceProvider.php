<?php

namespace App\Providers;

use App\Models\Orden;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // El layout muestra en la campana y en el menú cuántos pedidos
        // esperan atención: órdenes de trabajo pendientes, en producción o
        // listas para entregar.
        View::composer('layouts.app_layout', function ($vista) {

            $pendientes = 0;

            if (auth()->check()) {
                try {
                    $pendientes = Orden::whereIn(
                        'estado',
                        ['pendiente', 'en_produccion', 'lista']
                    )->count();
                } catch (\Throwable $e) {
                    // Si la tabla aún no existe, el layout sigue funcionando.
                    $pendientes = 0;
                }
            }

            $vista->with('pedidosPendientes', $pendientes);
        });
    }
}
