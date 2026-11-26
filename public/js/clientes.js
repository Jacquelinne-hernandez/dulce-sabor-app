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
        eliminar: {
            titulo: 'Eliminar cliente',
            mensaje: function (c) {
                return '¿Desea eliminar al cliente "' + c.nombre + '"? ' +
                    'Su historial de pedidos y facturas se conservará.';
            }
        },
        fila: function (c) {
            return '<tr>' +
                '<td class="fw-semibold">' + App.esc(c.nombre) + '</td>' +
                '<td>' + App.esc(c.telefono || '—') + '</td>' +
                '<td>' + App.esc(c.correo || '—') + '</td>' +
                '<td><span class="badge text-bg-info">' + c.puntos + '</span></td>' +
                '<td>' + App.fechaCorta(c.fecha_registro) + '</td>' +
                '<td>' + App.badgeEstado(c.estado ? c.estado.estado : '') + '</td>' +
                '<td class="text-end">' + App.botonesCrud(c.id) + '</td>' +
                '</tr>';
        }
    });
});
