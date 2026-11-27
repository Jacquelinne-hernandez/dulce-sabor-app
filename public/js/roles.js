
/*
 * roles.js
 * Consulta de roles (fijos, creados por el seeder): solo lectura.
 */
$(function () {
    const $raiz = $('#modulo-roles');
    const $cuerpo = $('#tabla-roles tbody');

    $cuerpo.html('<tr><td colspan="3"><span class="spinner-border spinner-border-sm me-2"></span>Cargando...</td></tr>');

    App.peticion('GET', $raiz.data('urlTodos'))
        .done(function (r) {
            const roles = r.roles || [];

            if (!roles.length) {
                $cuerpo.html('<tr><td colspan="3">No hay roles registrados.</td></tr>');

                return;
            }

            $cuerpo.html(roles.map(function (rol) {
                return '<tr>' +
                    '<td>' + rol.id + '</td>' +
                    '<td class="fw-semibold">' + App.esc(rol.nombre) + '</td>' +
                    '<td>' + App.esc(rol.descripcion || '—') + '</td>' +
                    '</tr>';
            }).join(''));
        })
        .fail(function (xhr) {
            $cuerpo.html('<tr><td colspan="3" class="text-danger">No se pudieron cargar los roles.</td></tr>');
            App.notificarError(xhr);
        });
});
