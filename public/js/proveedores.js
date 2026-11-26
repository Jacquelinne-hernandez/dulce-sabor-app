$(function () {
    App.crud({
        raiz: '#modulo-proveedores',
        tabla: '#tabla-proveedores tbody',
        clave: 'proveedores',
        llave: 'proveedor',
        modal: '#modal-proveedores',
        form: '#form-proveedores',
        tituloNuevo: 'Nuevo proveedor',
        tituloEditar: 'Editar proveedor',
        eliminar: {
            titulo: 'Eliminar proveedor',
            mensaje: function (p) { return '¿Desea eliminar al proveedor "' + p.nombre + '"?'; }
        },
        fila: function (p) {
            return '<tr>' +
                '<td class="fw-semibold">' + App.esc(p.nombre) + '</td>' +
                '<td>' + App.esc(p.nombre_contacto || '—') + '</td>' +
                '<td>' + App.esc(p.telefono || '—') + '</td>' +
                '<td>' + App.esc(p.email || '—') + '</td>' +
                '<td>' + App.esc(p.direccion || '—') + '</td>' +
                '<td class="text-end">' + App.botonesCrud(p.id) + '</td>' +
                '</tr>';
        }
    });
});
