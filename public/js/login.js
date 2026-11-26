/*
 * login.js
 * Muestra u oculta la contraseña en el formulario de inicio de sesión.
 */
document.addEventListener('DOMContentLoaded', function () {
    const boton = document.getElementById('togglePassword');
    const campo = document.getElementById('password');
    const icono = document.getElementById('iconoPassword');

    if (!boton || !campo) {
        return;
    }

    boton.addEventListener('click', function () {
        const mostrar = campo.type === 'password';

        campo.type = mostrar ? 'text' : 'password';

        boton.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
        boton.setAttribute('aria-pressed', mostrar ? 'true' : 'false');

        if (icono) {
            icono.classList.toggle('fa-eye', !mostrar);
            icono.classList.toggle('fa-eye-slash', mostrar);
        }
    });
});
