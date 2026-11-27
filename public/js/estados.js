
/*
 * estados.js
 * Consulta de estados (fijos, creados por el seeder): solo lectura.
 */
$(function () {
    const $raiz = $('#modulo-estados');
    const $cuerpo = $('#tabla-estados tbody');

    $cuerpo.html('<tr><td colspan="3"><span class="spinner-border spinner-border-sm me-2"></span>Cargando...</td></tr>');

    App.peticion('GET', $raiz.data('urlTodos'))
        .done(function (r) {
            const estados = r.estados || [];

            if (!estados.length) {
                $cuerpo.html('<tr><td colspan="3">No hay estados registrados.</td></tr>');

                return;
            }

            $cuerpo.html(estados.map(function (e) {
                return '<tr>' +
                    '<td>' + e.id + '</td>' +
                    '<td class="fw-semibold">' + App.esc(e.estado) + '</td>' +
                    '<td>' + App.esc(e.descripcion || '—') + '</td>' +
                    '</tr>';
            }).join(''));
        })
        .fail(function (xhr) {
            $cuerpo.html('<tr><td colspan="3" class="text-danger">No se pudieron cargar los estados.</td></tr>');
            App.notificarError(xhr);
        });
});
