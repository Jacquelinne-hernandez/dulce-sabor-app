$(function () {
    const $raiz = $('#modulo-facturas');
    const urlBuscar = $raiz.data('urlBuscar');
    const urlAnular = $raiz.data('urlAnular');
    const $pedido = $('#facturas-id_pedido');
    const $efectivo = $('#facturas-efectivo_recibido');

    function totalSeleccionado() {
        return parseFloat($pedido.find('option:selected').data('total')) || 0;
    }

    // Muestra el total del pedido y el cambio que se debe entregar.
    function recalcular() {
        const total = totalSeleccionado();
        const efectivo = parseFloat($efectivo.val()) || 0;
        const cambio = efectivo - total;

        $('#factura-total').text(App.dinero(total));
        $('#factura-cambio').text(App.dinero(cambio > 0 ? cambio : 0));
    }

    $pedido.on('change', function () {
        $efectivo.val(totalSeleccionado() > 0 ? totalSeleccionado().toFixed(2) : '');
        recalcular();
    });

    $efectivo.on('input', function () {
        recalcular();
    });

    const crud = App.crud({
        raiz: '#modulo-facturas',
        tabla: '#tabla-facturas tbody',
        clave: 'facturas',
        llave: 'factura',
        modal: '#modal-facturas',
        form: '#form-facturas',
        tituloNuevo: 'Emitir factura',
        tituloEditar: 'Emitir factura',
        alNuevo: function () {
            recalcular();
        },
        despuesDeGuardar: function (r) {
            // El pedido facturado ya no se puede volver a facturar.
            $pedido.find('option[value="' + r.factura.id_pedido + '"]').remove();
        },
        fila: function (f, ctx) {
            const anular = ctx.puedeEditar && f.estado === 'emitida'
                ? '<button type="button" class="btn btn-sm btn-outline-danger" data-accion="anular" ' +
                  'data-id="' + f.id + '" title="Anular factura"><i class="fa-solid fa-ban"></i></button>'
                : '';

            return '<tr>' +
                '<td class="fw-semibold text-nowrap">' + App.esc(f.numero_factura) + '</td>' +
                '<td class="text-nowrap">' + App.fechaHora(f.fecha_emision) + '</td>' +
                '<td>' + App.esc(f.cliente ? f.cliente.nombre : '—') + '</td>' +
                '<td>#' + f.id_pedido + '</td>' +
                '<td>' + App.dinero(f.total) + '</td>' +
                '<td>' + App.dinero(f.efectivo_recibido) + '</td>' +
                '<td>' + App.dinero(f.cambio) + '</td>' +
                '<td>' + App.badgeEstado(f.estado) + '</td>' +
                '<td class="text-end text-nowrap">' +
                '<button type="button" class="btn btn-sm btn-outline-secondary me-1" data-accion="detalle" ' +
                'data-id="' + f.id + '" title="Ver detalle"><i class="fa-solid fa-eye"></i></button>' +
                anular + '</td>' +
                '</tr>';
        },
        acciones: {
            detalle: function (id) {
                App.peticion('GET', App.url(urlBuscar, id))
                    .done(function (r) {
                        const f = r.factura;
                        const filas = f.detalles.map(function (d) {
                            return '<tr>' +
                                '<td>' + App.esc(d.nombre_producto) + '</td>' +
                                '<td class="text-end">' + d.cantidad + '</td>' +
                                '<td class="text-end">' + App.dinero(d.precio_unitario) + '</td>' +
                                '<td class="text-end">' + App.dinero(d.subtotal) + '</td>' +
                                '</tr>';
                        }).join('');

                        $('#detalle-facturas').html(
                            '<dl class="row mb-3">' +
                            '<dt class="col-sm-3">Factura</dt><dd class="col-sm-9">' + App.esc(f.numero_factura) + ' ' + App.badgeEstado(f.estado) + '</dd>' +
                            '<dt class="col-sm-3">Fecha</dt><dd class="col-sm-9">' + App.fechaHora(f.fecha_emision) + '</dd>' +
                            '<dt class="col-sm-3">Cliente</dt><dd class="col-sm-9">' + App.esc(f.cliente ? f.cliente.nombre : '—') + '</dd>' +
                            '<dt class="col-sm-3">Pedido</dt><dd class="col-sm-9">#' + f.id_pedido + '</dd>' +
                            '<dt class="col-sm-3">Emitida por</dt><dd class="col-sm-9 mb-0">' +
                            App.esc(f.usuario ? f.usuario.nombre + ' ' + f.usuario.apellido : '—') + '</dd>' +
                            '</dl>' +
                            '<div class="table-responsive"><table class="table table-sm align-middle mb-0">' +
                            '<thead class="table-light"><tr><th>Producto</th><th class="text-end">Cantidad</th>' +
                            '<th class="text-end">Precio</th><th class="text-end">Subtotal</th></tr></thead>' +
                            '<tbody>' + filas + '</tbody>' +
                            '<tfoot>' +
                            '<tr><th colspan="3" class="text-end">Total</th><th class="text-end">' + App.dinero(f.total) + '</th></tr>' +
                            '<tr><td colspan="3" class="text-end">Efectivo recibido</td><td class="text-end">' + App.dinero(f.efectivo_recibido) + '</td></tr>' +
                            '<tr><td colspan="3" class="text-end">Cambio</td><td class="text-end">' + App.dinero(f.cambio) + '</td></tr>' +
                            '</tfoot>' +
                            '</table></div>'
                        );

                        App.modal('#modal-facturas-detalle').show();
                    })
                    .fail(App.notificarError);
            },

            anular: function (id, factura) {
                App.confirmar({
                    titulo: 'Anular factura',
                    mensaje: '¿Desea anular la factura ' + factura.numero_factura + '? Esta acción no se puede deshacer y se retirarán los puntos otorgados al cliente.',
                    boton: 'Anular factura'
                }).then(function (aceptado) {
                    if (!aceptado) {
                        return;
                    }

                    App.peticion('PATCH', App.url(urlAnular, id))
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
