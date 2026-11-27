$(function () {
    const urlReactivar = $('#modulo-productos').data('urlReactivar');

    const crud = App.crud({
        raiz: '#modulo-productos',
        tabla: '#tabla-productos tbody',
        clave: 'productos',
        llave: 'producto',
        modal: '#modal-productos',
        form: '#form-productos',
        tituloNuevo: 'Nuevo producto',
        tituloEditar: 'Editar producto',
        antesDeEnviar: function (datos) {
            delete datos.imagen;

            return datos;
        },
        eliminar: {
            titulo: 'Inactivar producto',
            boton: 'Inactivar',
            mensaje: function (p) {
                return '¿Desea inactivar "' + p.nombre + '"? Dejará de estar disponible para la venta.';
            }
        },
        fila: function (p) {
            const inactivo = p.estado === 'inactivo';
            const bajo = !inactivo && p.stock <= p.stock_minimo;
            let acciones;

            if (inactivo) {
                acciones = '<button type="button" class="btn btn-sm btn-outline-success" ' +
                    'data-accion="reactivar" data-id="' + p.id + '" title="Reactivar">' +
                    '<i class="fa-solid fa-rotate-left"></i></button>';
            } else {
                acciones = App.botonesCrud(p.id, { tituloEliminar: 'Inactivar', iconoEliminar: 'fa-ban' });
            }

            const rutaImg = p.imagen_url || (p.imagen
                ? (p.imagen.startsWith('http') || p.imagen.startsWith('/') ? p.imagen : '/storage/' + p.imagen)
                : null);

            const thumbHtml = rutaImg
                ? '<div class="prod-thumb-wrapper"><img src="' + App.esc(rutaImg) + '" alt="' + App.esc(p.nombre) + '" ' +
                'onerror="this.outerHTML=\'<i class=\\\'fa-solid fa-cake-candles\\\'></i>\'"></div>'
                : '<div class="prod-thumb-wrapper"><i class="fa-solid fa-cake-candles"></i></div>';

            return '<tr>' +
                '<td class="fw-semibold"><div class="d-flex align-items-center gap-3">' + thumbHtml + '<span>' + App.esc(p.nombre) + '</span></div></td>' +
                '<td>' + App.esc(p.categoria ? p.categoria.nombre : '—') + '</td>' +
                '<td><span>' + App.dinero(p.precio) +
                (p.costo !== undefined && p.costo !== null
                    ? ' <small class="texto-pequeno fw-normal">costo ' + App.dinero(p.costo) + '</small>' : '') +
                '</span></td>' +
                '<td>' + p.stock +
                (bajo ? ' <span class="badge text-bg-warning" title="Stock mínimo: ' + p.stock_minimo + '">Bajo</span>' : '') +
                '</td>' +
                '<td>' + App.badgeEstado(p.estado) + '</td>' +
                '<td class="text-end">' + acciones + '</td>' +
                '</tr>';
        },
        acciones: {
            reactivar: function (id, p) {
                App.confirmar({
                    titulo: 'Reactivar producto',
                    mensaje: '¿Desea reactivar "' + p.nombre + '"?',
                    boton: 'Reactivar',
                    clase: 'btn-success'
                }).then(function (aceptado) {
                    if (!aceptado) {
                        return;
                    }

                    App.peticion('PATCH', App.url(urlReactivar, id))
                        .done(function (r) {
                            App.notificar('success', r.mensaje);
                            crud.cargar();
                        })
                        .fail(App.notificarError);
                });
            }
        }
    });
});
