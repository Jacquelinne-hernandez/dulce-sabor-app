<?php

namespace App\Http\Controllers;

use App\Http\Requests\CambiarPasswordRequest;
use App\Models\Bitacora;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PasswordController extends Controller
{
    // Pantalla para cambiar la propia contraseña. Es la única que puede
    // abrir un usuario con cambio obligatorio pendiente (usuario nuevo o
    // con la contraseña restablecida por el administrador).
    public function formulario(): View
    {
        return view('auth.cambiar_password', [
            'obligatorio' => (bool) Auth::user()->debe_cambiar_password,
        ]);
    }

    public function actualizar(CambiarPasswordRequest $request): RedirectResponse
    {
        $usuario = Auth::user();

        $usuario->update([
            'password' => $request->validated()['password'],
            'debe_cambiar_password' => false,
        ]);

        // Nueva sesión tras cambiar la credencial.
        $request->session()->regenerate();

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'USUARIOS',
            $usuario->id,
            'El usuario cambió su propia contraseña.',
            $usuario->nombre
        );

        return redirect()
            ->route('home')
            ->with('success', 'Su contraseña se actualizó correctamente.');
    }
}
