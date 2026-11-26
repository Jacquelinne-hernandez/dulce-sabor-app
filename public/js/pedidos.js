$(function () {
    const $raiz = $('#modulo-pedidos');
    const $lineas = $('#lineas-pedidos');
    const catalogo = $raiz.data('productos') || [];
    const urlBuscar = $raiz.data('urlBuscar');
    const urlFecha = $raiz.data('urlFecha');
    const urlEstado = $raiz.data('urlEstado');
    const urlActualizarEstado = $raiz.data('urlActualizarEstado');

    // Cambios de estado permitidos: [nuevo estado, texto, clase, ícono]
    const TRANSICIONES = {
        pendiente: [
            ['en_preparacion', 'Pasar a preparación', 'btn-outline-info', 'fa-fire-burner'],
            ['cancelado', 'Cancelar pedido', 'btn-outline-danger', 'fa-xmark']
        ],
        en_preparacion: [
            ['listo', 'Marcar como listo', 'btn-outline-primary', 'fa-check'],
            ['cancelado', 'Cancelar pedido', 'btn-outline-danger', 'fa-xmark']
        ],
        listo: [
            ['entregado', 'Entregar', 'btn-outline-success', 'fa-bag-shopping'],
            ['cancelado', 'Cancelar pedido', 'btn-outline-danger', 'fa-xmark']
        ]
    };

    /* ---------------------------- Catálogo y líneas ---------------------------- */

    function buscarProducto(id) {
        return catalogo.filter(function (p) { return String(p.id) === String(id); })[0];
    }

    function opcionesProductos() {
        return '<option value="">Seleccione...</option>' + catalogo
            .filter(function (p) { return p.stock > 0; })
            .map(function (p) {
                return '<option value="' + p.id + '">' + App.esc(p.nombre) + ' (stock ' + p.stock + ')</option>';
            }).join('');
    }

    function agregarLinea() {
        $lineas.append(
            '<tr data-linea>' +
            '<td><select class="form-select form-select-sm" data-campo="id_producto" aria-label="Producto">' +
            opcionesProductos() + '</select></td>' +
            '<td><input type="number" class="form-control form-control-sm" data-campo="cantidad" ' +
            'min="1" step="1" value="1" aria-label="Cantidad"></td>' +
            '<td class="text-end" data-precio>$0.00</td>' +
            '<td class="text-end" data-subtotal>$0.00</td>' +
            '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" ' +
            'data-quitar-linea title="Quitar"><i class="fa-solid fa-trash"></i></button></td>' +
            '</tr>'
        );
    }

    function recalcular() {
        let total = 0;

        $lineas.find('[data-linea]').each(function () {
            const $l = $(this);
            const producto = buscarProducto($l.find('[data-campo="id_producto"]').val());
            const $cantidad = $l.find('[data-campo="cantidad"]');
            const cantidad = parseInt($cantidad.val(), 10) || 0;
            const precio = producto ? parseFloat(producto.precio) : 0;

            if (producto) {
                $cantidad.attr('max', producto.stock);
            } else {
                $cantidad.removeAttr('max');
            }

            $l.find('[data-precio]').text(App.dinero(precio));
            $l.find('[data-subtotal]').text(App.dinero(precio * cantidad));
            total += precio * cantidad;
        });

        $('#total-pedidos').text(App.dinero(total));
    }

    function leerLineas() {
        return $lineas.find('[data-linea]').map(function () {
            const $l = $(this);

            return {
                id_producto: $l.find('[data-campo="id_producto"]').val(),
                cantidad: $l.find('[data-campo="cantidad"]').val()
            };
        }).get();
    }

    // Mantiene el catálogo local al día sin recargar la página.
    function descontarCatalogo(pedido) {
        (pedido.detalles || []).forEach(function (d) {
            const p = buscarProducto(d.id_producto);

            if (p) {
                p.stock -= d.cantidad;
            }
        });
    }

    function restituirCatalogo(pedido) {
        (pedido.detalles || []).forEach(function (d) {
            const p = buscarProducto(d.id_producto);

            if (p) {
                p.stock += d.cantidad;
            } else if (d.producto) {
                catalogo.push({
                    id: d.id_producto,
                    nombre: d.producto.nombre,
                    precio: d.producto.precio,
                    stock: d.cantidad
                });
            }
        });
    }

    $('#agregar-linea-pedidos').on('click', function () {
        agregarLinea();
    });

    $lineas.on('input change', '[data-campo]', function () {
        recalcular();
    });

    $lineas.on('click', '[data-quitar-linea]', function () {
        $(this).closest('[data-linea]').remove();

        if (!$lineas.children().length) {
            agregarLinea();
        }

        recalcular();
    });

    /* --------------------------------- Listado --------------------------------- */

    const crud = App.crud({
        raiz: '#modulo-pedidos',
        tabla: '#tabla-pedidos tbody',
        clave: 'pedidos',
        llave: 'pedido',
        modal: '#modal-pedidos',
        form: '#form-pedidos',
        tituloNuevo: 'Nuevo pedido',
        tituloEditar: 'Nuevo pedido',
        urlLista: function () {
            const fecha = $('#filtro-fecha-pedidos').val();
            const estado = $('#filtro-estado-pedidos').val();

            if (fecha) {
                return urlFecha + '?fecha=' + encodeURIComponent(fecha);
            }

            if (estado) {
                return urlEstado + '?estado=' + encodeURIComponent(estado);
            }

            return $raiz.data('urlTodos');
        },
        // Si se filtra por fecha y estado a la vez, el estado se aplica aquí.
        filtrar: function (p) {
            const estado = $('#filtro-estado-pedidos').val();

            return !estado || p.estado === estado;
        },
        alNuevo: function () {
            $lineas.empty();
            agregarLinea();
            recalcular();
        },
        antesDeEnviar: function (datos) {
            datos.detalles = leerLineas();

            return datos;
        },
        despuesDeGuardar: function (r) {
            descontarCatalogo(r.pedido);
        },
        fila: function (p) {
            const botones = (TRANSICIONES[p.estado] || []).map(function (t) {
                return '<button type="button" class="btn btn-sm ' + t[2] + ' me-1" data-accion="estado" ' +
                    'data-id="' + p.id + '" data-estado="' + t[0] + '" title="' + t[1] + '">' +
                    '<i class="fa-solid ' + t[3] + '"></i></button>';
            }).join('');

            return '<tr>' +
                '<td>' + p.id + '</td>' +
                '<td class="text-nowrap">' + App.fechaHora(p.fecha) + '</td>' +
                '<td class="fw-semibold">' + App.esc(p.cliente ? p.cliente.nombre : '—') + '</td>' +
                '<td>' + (p.mesa ? 'Mesa ' + p.mesa.numero : '<span class="text-muted">Para llevar</span>') + '</td>' +
                '<td>' + App.dinero(p.total) + '</td>' +
                '<td>' + App.badgeEstado(p.estado) + '</td>' +
                '<td class="text-end text-nowrap">' +
                '<button type="button" class="btn btn-sm btn-outline-secondary me-1" data-accion="detalle" ' +
                'data-id="' + p.id + '" title="Ver detalle"><i class="fa-solid fa-eye"></i></button>' +
                botones + '</td>' +
                '</tr>';
        },
        acciones: {
            detalle: function (id) {
                App.peticion('GET', App.url(urlBuscar, id))
                    .done(function (r) {
                        const p = r.pedido;
                        const filas = p.detalles.map(function (d) {
                            return '<tr>' +
                                '<td>' + App.esc(d.producto ? d.producto.nombre : '—') + '</td>' +
                                '<td class="text-end">' + d.cantidad + '</td>' +
                                '<td class="text-end">' + App.dinero(d.precio_unitario) + '</td>' +
                                '<td class="text-end">' + App.dinero(d.subtotal) + '</td>' +
                                '</tr>';
                        }).join('');

                        $('#detalle-pedidos').html(
                            '<dl class="row mb-3">' +
                            '<dt class="col-sm-3">Pedido</dt><dd class="col-sm-9">#' + p.id + ' ' + App.badgeEstado(p.estado) + '</dd>' +
                            '<dt class="col-sm-3">Fecha</dt><dd class="col-sm-9">' + App.fechaHora(p.fecha) + '</dd>' +
                            '<dt class="col-sm-3">Cliente</dt><dd class="col-sm-9">' + App.esc(p.cliente ? p.cliente.nombre : '—') + '</dd>' +
                            '<dt class="col-sm-3">Mesa</dt><dd class="col-sm-9">' + (p.mesa ? 'Mesa ' + p.mesa.numero : 'Para llevar') + '</dd>' +
                            '<dt class="col-sm-3">Atendido por</dt><dd class="col-sm-9">' +
                            App.esc(p.usuario ? p.usuario.nombre + ' ' + p.usuario.apellido : '—') + '</dd>' +
                            '<dt class="col-sm-3">Observaciones</dt><dd class="col-sm-9 mb-0">' + App.esc(p.observaciones || '—') + '</dd>' +
                            '</dl>' +
                            '<div class="table-responsive"><table class="table table-sm align-middle mb-0">' +
                            '<thead class="table-light"><tr><th>Producto</th><th class="text-end">Cantidad</th>' +
                            '<th class="text-end">Precio</th><th class="text-end">Subtotal</th></tr></thead>' +
                            '<tbody>' + filas + '</tbody>' +
                            '<tfoot><tr><th colspan="3" class="text-end">Total</th>' +
                            '<th class="text-end">' + App.dinero(p.total) + '</th></tr></tfoot>' +
                            '</table></div>'
                        );

                        App.modal('#modal-pedidos-detalle').show();
                    })
                    .fail(App.notificarError);
            },

            estado: function (id, pedido, $boton) {
                const nuevo = $boton.data('estado');

                function cambiar() {
                    App.peticion('PATCH', App.url(urlActualizarEstado, id), { estado: nuevo })
                        .done(function (r) {
                            if (nuevo === 'cancelado') {
                                restituirCatalogo(r.pedido);
                            }

                            App.notificar('success', r.mensaje);
                            crud.cargar();
                        })
                        .fail(App.notificarError);
                }

                if (nuevo !== 'cancelado') {
                    cambiar();

                    return;
                }

                App.confirmar({
                    titulo: 'Cancelar pedido',
                    mensaje: '¿Desea cancelar el pedido #' + id + '? Las existencias de sus productos serán restituidas.',
                    boton: 'Cancelar pedido'
                }).then(function (aceptado) {
                    if (aceptado) {
                        cambiar();
                    }
                });
            }
        }
    });

    $('#filtro-fecha-pedidos, #filtro-estado-pedidos').on('change', function () {
        crud.cargar();
    });

    $('#limpiar-filtros-pedidos').on('click', function () {
        $('#filtro-fecha-pedidos, #filtro-estado-pedidos').val('');
        crud.cargar();
    });
});
