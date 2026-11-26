$(function () {
    // Estados base: el login y la eliminación lógica dependen de ellos.
    const BASE_IDS = [1, 2, 3];

    App.crud({
        raiz: '#modulo-estados',
        tabla: '#tabla-estados tbody',
        clave: 'estados',
        llave: 'estado',
        modal: '#modal-estados',
        form: '#form-estados',
        tituloNuevo: 'Nuevo estado',
        tituloEditar: 'Editar estado',
        eliminar: {
            titulo: 'Eliminar estado',
            mensaje: function (e) { return '¿Desea eliminar el estado "' + e.estado + '"?'; }
        },
        fila: function (e) {
            const base = BASE_IDS.indexOf(e.id) !== -1;

            return '<tr>' +
                '<td>' + e.id + '</td>' +
                '<td class="fw-semibold">' + App.esc(e.estado) +
                (base ? ' <span class="badge text-bg-secondary">Base</span>' : '') + '</td>' +
                '<td>' + App.esc(e.descripcion || '—') + '</td>' +
                '<td class="text-end">' + App.botonesCrud(e.id, { eliminar: !base }) + '</td>' +
                '</tr>';
        }
    });
});
