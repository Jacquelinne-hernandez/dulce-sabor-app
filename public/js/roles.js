$(function () {
    // Roles base: de ellos dependen los permisos de las rutas.
    const BASE = ['Administrador', 'Operador'];

    App.crud({
        raiz: '#modulo-roles',
        tabla: '#tabla-roles tbody',
        clave: 'roles',
        llave: 'rol',
        modal: '#modal-roles',
        form: '#form-roles',
        tituloNuevo: 'Nuevo rol',
        tituloEditar: 'Editar rol',
        eliminar: {
            titulo: 'Eliminar rol',
            mensaje: function (r) { return '¿Desea eliminar el rol "' + r.nombre + '"?'; }
        },
        fila: function (r) {
            const base = BASE.indexOf(r.nombre) !== -1;

            return '<tr>' +
                '<td>' + r.id + '</td>' +
                '<td class="fw-semibold">' + App.esc(r.nombre) +
                (base ? ' <span class="badge text-bg-secondary">Base</span>' : '') + '</td>' +
                '<td>' + App.esc(r.descripcion || '—') + '</td>' +
                '<td class="text-end">' + App.botonesCrud(r.id, { eliminar: !base }) + '</td>' +
                '</tr>';
        }
    });
});
