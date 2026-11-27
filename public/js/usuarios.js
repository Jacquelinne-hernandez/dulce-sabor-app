$(function () {
    const $contrasenas = $('#form-usuarios [name="password"], #form-usuarios [name="password_confirmation"]');

    App.crud({
        raiz: '#modulo-usuarios',
        tabla: '#tabla-usuarios tbody',
        clave: 'usuarios',
        llave: 'usuario',
        modal: '#modal-usuarios',
        form: '#form-usuarios',
        tituloNuevo: 'Nuevo usuario',
        tituloEditar: 'Editar usuario',
        eliminar: {
            titulo: 'Eliminar usuario',
            mensaje: function (u) {
                return '¿Desea eliminar al usuario "' + u.nombre + ' ' + u.apellido + '"? ' +
                    'Su acceso será revocado y el registro se conservará para auditoría.';
            }
        },
        alNuevo: function () {
            $contrasenas.prop('required', true);
            $('#form-usuarios [data-ayuda-password]').addClass('d-none');
        },
        alEditar: function () {
            // La contraseña no se muestra; solo se cambia si se escribe una nueva.
            $contrasenas.prop('required', false).val('');
            $('#form-usuarios [data-ayuda-password]').removeClass('d-none');
        },
        fila: function (u) {
            return '<tr>' +
                '<td class="fw-semibold">' + App.esc(u.nombre + ' ' + u.apellido) +
                (u.debe_cambiar_password ? ' <span class="badge text-bg-warning" title="Debe cambiar su contraseña al ingresar">Contraseña pendiente</span>' : '') + '</td>' +
                '<td>' + App.esc(u.email) + '</td>' +
                '<td>' + App.esc(u.telefono || '—') + '</td>' +
                '<td>' + App.esc(u.rol ? u.rol.nombre : '—') + '</td>' +
                '<td>' + App.badgeEstado(u.estado ? u.estado.estado : '') + '</td>' +
                '<td class="text-end">' + App.botonesCrud(u.id) + '</td>' +
                '</tr>';
        }
    });
});
