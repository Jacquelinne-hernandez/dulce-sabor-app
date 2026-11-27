<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RolMiddleware
{
    /**
     * Verifica que el usuario esté activo y tenga
     * uno de los roles requeridos para acceder a la ruta.
     */
    public function handle(
        Request $request,
        Closure $next,
                ...$roles
    ): Response {
        $usuario = auth()->user();

        /*
         * El middleware "auth" debe ejecutarse antes que este middleware.
         * Por seguridad, verificamos también que exista un usuario.
         */
        if (!$usuario) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'Debe iniciar sesión para continuar.',
                ], 401);
            }

            return redirect()->route('login');
        }

        /*
         * Verificamos el estado del usuario mediante
         * la relación con la tabla estados.
         */
        if (
            !$usuario->estado ||
            strtolower($usuario->estado->estado) !== 'activo'
        ) {
            auth()->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'Su cuenta se encuentra inactiva, contacte al administrador.',
                ], 401);
            }

            return redirect()
                ->route('login')
                ->with(
                    'warning',
                    'Su cuenta se encuentra inactiva, contacte al administrador.'
                );
        }

        /*
         * Cambio de contraseña obligatorio (usuario nuevo o con la contraseña
         * restablecida por el administrador): mientras no la cambie, solo
         * puede abrir la pantalla de cambio de contraseña.
         */
        if (
            $usuario->debe_cambiar_password &&
            !$request->routeIs('password.cambiar', 'password.actualizar')
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'Debe cambiar su contraseña para continuar.',
                    'redirigir' => route('password.cambiar'),
                ], 423);
            }

            return redirect()
                ->route('password.cambiar')
                ->with(
                    'warning',
                    'Por seguridad, debe cambiar su contraseña para continuar.'
                );
        }

        /*
         * Si la ruta no especifica roles, cualquier usuario
         * autenticado y activo puede continuar.
         */
        if (empty($roles)) {
            return $next($request);
        }

        /*
         * Verificamos si el rol del usuario coincide con
         * alguno de los roles permitidos por la ruta.
         */
        if (
            $usuario->rol &&
            in_array(
                strtolower($usuario->rol->nombre),
                array_map('strtolower', $roles),
                true
            )
        ) {
            return $next($request);
        }

        /*
         * El usuario está autenticado y activo, pero no tiene
         * permisos suficientes para acceder a la ruta.
         */
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No tiene permisos para realizar esta acción.',
            ], 403);
        }

        return redirect()
            ->route('home')
            ->with(
                'error',
                'No tiene permisos para acceder a esta sección.'
            );
    }
}
