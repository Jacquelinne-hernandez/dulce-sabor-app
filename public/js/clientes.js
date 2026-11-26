$(function () {
    App.crud({
        raiz: '#modulo-clientes',
        tabla: '#tabla-clientes tbody',
        clave: 'clientes',
        llave: 'cliente',
        modal: '#modal-clientes',
        form: '#form-clientes',
        tituloNuevo: 'Nuevo cliente',
        tituloEditar: 'Editar cliente',
        alNuevo: function () {
            $('#clientes-bloque-codigo').addClass('d-none');
            $('#clientes-nota-codigo').removeClass('d-none');
        },
        alEditar: function (c) {
            $('#clientes-codigo-texto').text(c.codigo || '—');
            $('#clientes-bloque-codigo').removeClass('d-none');
            $('#clientes-nota-codigo').addClass('d-none');
        },
        eliminar: {
            titulo: 'Eliminar cliente',
            mensaje: function (c) {
                return '¿Desea eliminar al cliente ' + c.codigo + ' (' + c.nombre_completo + ')? ' +
                    'Su historial de pedidos y facturas se conservará.';
            }
        },
        fila: function (c) {
            return '<tr>' +
                '<td><span class="cliente-codigo-chip">' + App.esc(c.codigo) + '</span></td>' +
                '<td class="fw-semibold">' + App.esc(c.nombre_completo) + '</td>' +
                '<td>' + App.esc(c.telefono || '—') + '</td>' +
                '<td>' + App.esc(c.correo || '—') + '</td>' +
                '<td><span class="badge text-bg-info">' + Number(c.puntos || 0) + ' pts</span></td>' +
                '<td>' + App.fechaCorta(c.fecha_registro) + '</td>' +
                '<td>' + App.badgeEstado(c.estado ? c.estado.estado : '') + '</td>' +
                '<td class="text-end">' + App.botonesCrud(c.id) + '</td>' +
                '</tr>';
        }
    });
});
