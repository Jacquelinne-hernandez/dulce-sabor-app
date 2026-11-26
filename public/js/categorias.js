$(function () {
    App.crud({
        raiz: '#modulo-categorias',
        tabla: '#tabla-categorias tbody',
        clave: 'categorias',
        llave: 'categoria',
        modal: '#modal-categorias',
        form: '#form-categorias',
        tituloNuevo: 'Nueva categoría',
        tituloEditar: 'Editar categoría',
        eliminar: {
            titulo: 'Eliminar categoría',
            mensaje: function (c) { return '¿Desea eliminar la categoría "' + c.nombre + '"?'; }
        },
        fila: function (c) {
            return '<tr>' +
                '<td class="fw-semibold">' + App.esc(c.nombre) + '</td>' +
                '<td>' + App.esc(c.descripcion || '—') + '</td>' +
                '<td><span class="badge text-bg-secondary">' + c.productos_count + '</span></td>' +
                // Una categoría con productos no puede eliminarse.
                '<td class="text-end">' + App.botonesCrud(c.id, { eliminar: c.productos_count === 0 }) + '</td>' +
                '</tr>';
        }
    });
});
