$(function () {
    const $raiz = $('#modulo-facturas');
    const $cuerpo = $('#tabla-facturas tbody');
    const esAdmin = String($raiz.data('esAdmin')) === 'true';
    const url = {
        todos: $raiz.data('urlTodos'),
        buscar: $raiz.data('urlBuscar'),
        porFacturar: $raiz.data('urlPorFacturar'),
        cotizar: $raiz.data('urlCotizar'),
        insertar: $raiz.data('urlInsertar'),
        pago: $raiz.data('urlPago'),
        anular: $raiz.data('urlAnular'),
        pdf: $raiz.data('urlPdf'),
        ticket: $raiz.data('urlTicket')
    };
    let facturas = [];
    let pedidosPorFacturar = [];
    let cotizacion = null;
    let turnoCotizacion = 0;
    let facturaPago = null;
    let idAnulando = null;
    let pedidoInicial = new URLSearchParams(window.location.search).get('pedido');

    function dinero(c) {
        return App.dinero(c);
    }

    function saldoDe(f) {
        return Number(f.saldo || 0);
    }

    function clienteTexto(f) {
        return f.cliente ? f.cliente.nombre_completo : 'Consumidor final';
    }

    /* ------------------------------------------------------------------
     * Listado
     * ------------------------------------------------------------------ */
    function filtradas() {
        const texto = $('#facturas-buscar').val().trim().toLowerCase();
        const estado = $('#facturas-filtro-estado').val();
        const fecha = $('#facturas-filtro-fecha').val();

        return facturas.filter(function (f) {
            const busqueda = [f.numero_factura, clienteTexto(f), f.cliente ? f.cliente.codigo : '', f.id_pedido].join(' ').toLowerCase();
            const d = new Date(f.fecha_emision);
            const dia = isNaN(d.getTime()) ? '' : d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');

            return (!texto || busqueda.indexOf(texto) !== -1) &&
                (!estado || f.estado === estado) && (!fecha || dia === fecha);
        });
    }

    function enlacesImpresion(f) {
        return '<a class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener" title="Factura en PDF" href="' +
            App.url(url.pdf, f.id) + '"><i class="fa-solid fa-file-pdf"></i></a>' +
            '<a class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener" title="Ticket" href="' +
            App.url(url.ticket, f.id) + '"><i class="fa-solid fa-receipt"></i></a>';
    }

    function botones(f) {
        let html = '<button type="button" class="btn btn-sm btn-outline-secondary" data-accion="ver" data-id="' + f.id +
            '" title="Ver detalle"><i class="fa-solid fa-eye"></i></button>';

        if (f.estado === 'emitida') {
            html += '<button type="button" class="btn btn-sm btn-outline-success" data-accion="cobrar" data-id="' + f.id +
                '" title="Registrar pago"><i class="fa-solid fa-cash-register"></i></button>';
        }

        if (f.estado !== 'anulada') {
            html += enlacesImpresion(f);
        }

        if (esAdmin && f.estado !== 'anulada') {
            html += '<button type="button" class="btn btn-sm btn-outline-danger" data-accion="anular" data-id="' + f.id +
                '" title="Anular factura"><i class="fa-solid fa-ban"></i></button>';
        }

        return html;
    }

    function pintar() {
        const lista = filtradas();

        if (!lista.length) {
            $cuerpo.html('<tr><td colspan="8">No hay facturas para mostrar.</td></tr>');

            return;
        }

        $cuerpo.html(lista.map(function (f) {
            return '<tr>' +
                '<td>' + App.esc(f.numero_factura) + '</td>' +
                '<td class="text-nowrap">' + App.esc(App.fechaHora(f.fecha_emision)) + '</td>' +
                '<td>' + App.esc(clienteTexto(f)) + (f.cliente ? '<div class="texto-pequeno">' + App.esc(f.cliente.codigo) + '</div>' : '') + '</td>' +
                '<td>#' + f.id_pedido + '</td>' +
                '<td class="texto-monto">' + dinero(f.total) + '</td>' +
                '<td class="texto-monto">' + (f.estado === 'anulada' ? '—' : dinero(f.saldo)) + '</td>' +
                '<td>' + App.badgeEstado(f.estado) + '</td>' +
                '<td class="text-end text-nowrap">' + botones(f) + '</td>' +
                '</tr>';
        }).join(''));
    }

    function cargar() {
        $cuerpo.html('<tr><td colspan="8"><span class="spinner-border spinner-border-sm me-2"></span>Cargando...</td></tr>');

        return App.peticion('GET', url.todos)
            .done(function (r) {
                facturas = r.facturas || [];
                pintar();
            })
            .fail(function (xhr) {
                $cuerpo.html('<tr><td colspan="8" class="text-danger">No se pudieron cargar las facturas.</td></tr>');
                App.notificarError(xhr);
            });
    }

    $('#facturas-buscar').on('input', pintar);
    $('#facturas-filtro-estado, #facturas-filtro-fecha').on('change', pintar);
    $('#facturas-limpiar').on('click', function () {
        $('#facturas-buscar, #facturas-filtro-fecha').val('');
        $('#facturas-filtro-estado').val('');
        pintar();
    });

    /* ------------------------------------------------------------------
     * Nueva factura
     * ------------------------------------------------------------------ */
    function pintarCotizacion() {
        const $caja = $('#facturas-cotizacion');
        const $emitir = $('#facturas-emitir');

        if (!cotizacion) {
            $caja.addClass('d-none').empty();
            $('#facturas-bloque-puntos').addClass('d-none');
            $emitir.prop('disabled', true);

            return;
        }

        const c = cotizacion;
        const tienePuntos = Number(c.puntos_disponibles) > 0;

        $('#facturas-bloque-puntos').toggleClass('d-none', !tienePuntos);
        $('#facturas-puntos-ayuda').text('Disponibles: ' + c.puntos_disponibles + ' · máximo en esta factura: ' +
            c.puntos_maximos + ' (cada punto vale ' + dinero(c.valor_punto) + ').');
        $('#facturas-puntos').attr('max', c.puntos_maximos);

        let html = '<div class="caja-totales">' +
            '<div class="caja-totales__fila"><span>Productos</span><span class="texto-monto">' + dinero(c.subtotal) + '</span></div>';

        if (Number(c.cargo_mesas) > 0) {
            html += '<div class="caja-totales__fila"><span>Uso de mesas</span><span class="texto-monto">' + dinero(c.cargo_mesas) + '</span></div>';
        }

        if (Number(c.descuento) > 0) {
            html += '<div class="caja-totales__fila caja-totales__fila--descuento"><span>Descuento por ' + c.puntos_canjeados +
                ' puntos</span><span class="texto-monto">− ' + dinero(c.descuento) + '</span></div>';
        }

        html += '<div class="caja-totales__fila caja-totales__fila--total"><span>Total a pagar</span><span class="texto-monto">' +
            dinero(c.total) + '</span></div>' +
            '<div class="caja-totales__fila"><span>Base imponible</span><span class="texto-monto">' + dinero(c.base_imponible) + '</span></div>' +
            '<div class="caja-totales__fila"><span>IVA incluido (' + c.iva_porcentaje + ' %)</span><span class="texto-monto">' + dinero(c.iva) + '</span></div>' +
            '<div class="texto-pequeno mt-1">' + (c.puntos_ganados > 0
                ? '<i class="fa-solid fa-star me-1"></i>El cliente ganará ' + c.puntos_ganados + ' puntos con esta factura.'
                : 'Consumidor final: no acumula puntos.') + '</div></div>';

        $caja.html(html).removeClass('d-none');
        $emitir.prop('disabled', false);
    }

    function cotizar() {
        const id = $('#facturas-pedido').val();
        const turno = ++turnoCotizacion;

        App.limpiarErrores($('#form-facturas'));

        if (!id) {
            cotizacion = null;
            pintarCotizacion();

            return;
        }

        App.peticion('GET', url.cotizar, {
            id_pedido: id,
            puntos_canjeados: Number($('#facturas-puntos').val()) || 0
        })
            .done(function (r) {
                if (turno === turnoCotizacion) {
                    cotizacion = r.cotizacion;
                    pintarCotizacion();
                }
            })
            .fail(function (xhr) {
                if (turno !== turnoCotizacion) {
                    return;
                }

                cotizacion = null;
                pintarCotizacion();

                if (xhr.status === 422) {
                    App.mostrarErrores($('#form-facturas'), App.interpretarError(xhr));
                } else {
                    App.notificarError(xhr);
                }
            });
    }

    function cargarPedidos() {
        return App.peticion('GET', url.porFacturar).done(function (r) {
            pedidosPorFacturar = r.pedidos || [];

            $('#facturas-pedido').html('<option value="">Seleccione...</option>' + pedidosPorFacturar.map(function (p) {
                const lugar = p.mesa ? 'Mesa ' + p.mesa.numero : App.textoEstado(p.tipo);

                return '<option value="' + p.id + '">#' + p.id + ' · ' + App.esc(lugar) + ' · ' +
                    App.esc(p.cliente ? p.cliente.nombre_completo : 'Consumidor final') + ' · ' + dinero(p.total) +
                    (p.orden ? ' · ' + App.esc(p.orden.numero_orden) : '') + '</option>';
            }).join(''));

            $('#facturas-pedido-ayuda').text(pedidosPorFacturar.length
                ? 'Solo aparecen pedidos abiertos con la orden entregada y sin factura.'
                : 'No hay pedidos listos para facturar. Entregue primero la orden en la pantalla de Órdenes.');
        }).fail(App.notificarError);
    }

    function abrirNueva(idPedido) {
        const $f = $('#form-facturas');

        App.limpiarErrores($f);
        $f[0].reset();
        cotizacion = null;
        pintarCotizacion();

        cargarPedidos().done(function () {
            if (idPedido && pedidosPorFacturar.some(function (p) { return String(p.id) === String(idPedido); })) {
                $('#facturas-pedido').val(idPedido);
                cotizar();
            }
        });

        App.modal('#modal-facturas').show();
    }

    $('#btn-nueva-factura').on('click', function () { abrirNueva(null); });
    $('#facturas-pedido').on('change', function () {
        $('#facturas-puntos').val(0);
        cotizar();
    });
    $('#facturas-puntos').on('input', App.debounce(cotizar, 350));
    $('#facturas-puntos-max').on('click', function () {
        if (cotizacion) {
            $('#facturas-puntos').val(cotizacion.puntos_maximos);
            cotizar();
        }
    });

    $('#form-facturas').on('submit', function (e) {
        e.preventDefault();

        const $f = $(this);
        const $enviar = $('#facturas-emitir');

        App.limpiarErrores($f);
        $enviar.prop('disabled', true);

        App.peticion('POST', url.insertar, {
            id_pedido: Number($('#facturas-pedido').val()),
            puntos_canjeados: Number($('#facturas-puntos').val()) || 0
        })
            .done(function (r) {
                App.notificar('success', r.mensaje || 'Factura emitida.');
                App.modal('#modal-facturas').hide();
                cargar();

                // Siguiente paso natural: cobrar la factura recién emitida.
                if (r.factura) {
                    setTimeout(function () { abrirPago(r.factura); }, 450);
                }
            })
            .fail(function (xhr) {
                $enviar.prop('disabled', false);

                if (xhr.status === 422) {
                    App.mostrarErrores($f, App.interpretarError(xhr));
                } else {
                    App.notificarError(xhr);
                }
            });
    });

    /* ------------------------------------------------------------------
     * Cobro
     * ------------------------------------------------------------------ */
    function metodoActual() {
        return $('#form-pago input[name="metodo"]:checked').val();
    }

    function calcularCambio() {
        const monto = facturaPago ? saldoDe(facturaPago) : 0;
        const recibido = Number($('#pago-recibido').val()) || 0;

        const diferencia = Math.round((recibido - monto) * 100) / 100;

        $('#pago-cambio').closest('.pago-cambio').toggleClass('text-danger', diferencia < 0);
        $('#pago-cambio').text(diferencia < 0 ? 'Faltan ' + dinero(-diferencia) : dinero(diferencia));
    }

    function ajustarMetodo() {
        const tarjeta = metodoActual() === 'tarjeta';

        $('#pago-efectivo').toggleClass('d-none', tarjeta);
        $('#pago-tarjeta').toggleClass('d-none', !tarjeta);
    }

    function abrirPago(f) {
        const $f = $('#form-pago');

        facturaPago = f;
        App.limpiarErrores($f);
        $f[0].reset();
        ajustarMetodo();

        $('#pago-resumen').html(
            '<div class="caja-totales__fila"><span>Factura</span><span>' + App.esc(f.numero_factura) + '</span></div>' +
            '<div class="caja-totales__fila"><span>Total</span><span class="texto-monto">' + dinero(f.total) + '</span></div>' +
            '<div class="caja-totales__fila caja-totales__fila--total"><span>Saldo pendiente</span><span class="texto-monto">' +
            dinero(f.saldo) + '</span></div>'
        );

        $('#pago-recibido').val(saldoDe(f).toFixed(2));
        calcularCambio();
        App.modal('#modal-facturas-pago').show();
    }

    $('#form-pago').on('change', 'input[name="metodo"]', ajustarMetodo);
    // El pago siempre cubre el saldo completo: no hay campo de monto.
    $('#pago-recibido').on('input', calcularCambio);

    $('#form-pago').on('submit', function (e) {
        e.preventDefault();

        const $f = $(this);
        const $enviar = $('button[type="submit"][form="form-pago"]');
        const datos = {
            id_factura: facturaPago.id,
            metodo: metodoActual()
        };

        if (datos.metodo === 'efectivo') {
            datos.efectivo_recibido = $('#pago-recibido').val() || null;
        } else {
            datos.tarjeta_titular = $('#pago-titular').val();
            datos.tarjeta_numero = $('#pago-numero').val().replace(/\s/g, '');
            datos.tarjeta_vencimiento = $('#pago-vence').val();
            datos.tarjeta_cvv = $('#pago-cvv').val();
        }

        App.limpiarErrores($f);
        $enviar.prop('disabled', true);

        App.peticion('POST', url.pago, datos)
            .done(function (r) {
                App.notificar('success', r.mensaje || 'Pago registrado.');
                App.modal('#modal-facturas-pago').hide();
                cargar();
            })
            .fail(function (xhr) {
                if (xhr.status === 422) {
                    App.mostrarErrores($f, App.interpretarError(xhr));
                } else {
                    App.notificarError(xhr);
                }
            })
            .always(function () {
                $enviar.prop('disabled', false);
                $('#pago-cvv').val('');
            });
    });

    /* ------------------------------------------------------------------
     * Detalle
     * ------------------------------------------------------------------ */
    function lineaHistorial(h) {
        const quien = h.usuario ? h.usuario.nombre + ' ' + h.usuario.apellido : 'Sistema';

        return '<li class="linea-tiempo__item"><div class="linea-tiempo__titulo">' +
            (h.estado_anterior ? App.esc(App.textoEstado(h.estado_anterior)) + ' → ' : '') +
            App.esc(App.textoEstado(h.estado_nuevo)) + '</div>' +
            '<div class="linea-tiempo__sub">' + App.esc(App.fechaHora(h.created_at)) + ' · ' + App.esc(quien) +
            (h.comentario ? ' · ' + App.esc(h.comentario) : '') + '</div></li>';
    }

    function filaPago(p) {
        const medio = p.metodo === 'tarjeta'
            ? 'Tarjeta ' + App.esc(p.tarjeta_marca || '') + ' ···· ' + App.esc(p.tarjeta_ultimos4 || '')
            : 'Efectivo' + (Number(p.cambio) > 0 ? ' <span class="texto-pequeno">(cambio ' + dinero(p.cambio) + ')</span>' : '');

        return '<tr><td class="text-nowrap">' + App.esc(App.fechaHora(p.fecha_pago)) + '</td><td>' + medio + '</td>' +
            '<td>' + App.badgeEstado(p.estado) + '</td><td class="text-end texto-monto">' + dinero(p.monto) + '</td></tr>';
    }

    function verDetalle(id) {
        App.peticion('GET', App.url(url.buscar, id))
            .done(function (res) {
                const f = res.factura;
                const lineas = (f.detalles || []).map(function (d) {
                    return '<tr><td>' + App.esc(d.nombre_producto) + '</td><td>' + d.cantidad + '</td>' +
                        '<td class="texto-monto">' + dinero(d.precio_unitario) + '</td>' +
                        '<td class="text-end texto-monto">' + dinero(d.subtotal) + '</td></tr>';
                }).join('');
                const pagos = (f.pagos || []).map(filaPago).join('');
                let pie = '<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>';

                if (f.estado !== 'anulada') {
                    pie = '<a class="btn btn-outline-dark" target="_blank" rel="noopener" href="' + App.url(url.pdf, f.id) +
                        '"><i class="fa-solid fa-file-pdf me-1"></i>PDF</a>' +
                        '<a class="btn btn-outline-dark" target="_blank" rel="noopener" href="' + App.url(url.ticket, f.id) +
                        '"><i class="fa-solid fa-receipt me-1"></i>Ticket</a>' + pie;
                }

                if (f.estado === 'emitida') {
                    pie += '<button type="button" class="btn btn-success" data-accion="cobrar-detalle" data-id="' + f.id +
                        '"><i class="fa-solid fa-cash-register me-1"></i>Registrar pago</button>';
                }

                $('#facturas-detalle-pie').html(pie);

                $('#facturas-detalle-cuerpo').html(
                    '<div class="row g-3 mb-3">' +
                    '<div class="col-6 col-md-3"><div class="dato-clave"><div class="dato-clave__titulo">Factura</div><div class="dato-clave__valor">' + App.esc(f.numero_factura) + '</div></div></div>' +
                    '<div class="col-6 col-md-3"><div class="dato-clave"><div class="dato-clave__titulo">Estado</div><div class="dato-clave__valor">' + App.badgeEstado(f.estado) + '</div></div></div>' +
                    '<div class="col-6 col-md-3"><div class="dato-clave"><div class="dato-clave__titulo">Total</div><div class="dato-clave__valor">' + dinero(f.total) + '</div></div></div>' +
                    '<div class="col-6 col-md-3"><div class="dato-clave"><div class="dato-clave__titulo">Saldo</div><div class="dato-clave__valor">' + dinero(f.saldo) + '</div></div></div></div>' +
                    '<p class="mb-3"><strong>Cliente:</strong> ' + App.esc(clienteTexto(f)) + (f.cliente ? ' <span class="texto-pequeno">' + App.esc(f.cliente.codigo) + '</span>' : '') + '<br>' +
                    '<strong>Emitida:</strong> ' + App.esc(App.fechaHora(f.fecha_emision)) + (f.usuario ? ' por ' + App.esc(f.usuario.nombre + ' ' + f.usuario.apellido) : '') + '<br>' +
                    '<strong>Pedido:</strong> #' + f.id_pedido +
                    (Number(f.puntos_canjeados) > 0 ? '<br><strong>Puntos canjeados:</strong> ' + f.puntos_canjeados : '') +
                    (Number(f.puntos_ganados) > 0 ? '<br><strong>Puntos ganados:</strong> ' + f.puntos_ganados : '') + '</p>' +
                    '<div class="table-responsive mb-3"><table class="table table-sm mb-0"><thead><tr><th>Producto</th><th>Cant.</th><th>Precio</th><th class="text-end">Subtotal</th></tr></thead><tbody>' + lineas + '</tbody>' +
                    '<tfoot>' +
                    '<tr><td colspan="3" class="text-end">Productos</td><td class="text-end texto-monto">' + dinero(f.subtotal) + '</td></tr>' +
                    (Number(f.cargo_mesas) > 0 ? '<tr><td colspan="3" class="text-end">Uso de mesas</td><td class="text-end texto-monto">' + dinero(f.cargo_mesas) + '</td></tr>' : '') +
                    (Number(f.descuento) > 0 ? '<tr><td colspan="3" class="text-end">Descuento por puntos</td><td class="text-end texto-monto">− ' + dinero(f.descuento) + '</td></tr>' : '') +
                    '<tr><td colspan="3" class="text-end fw-bold">Total</td><td class="text-end fw-bold texto-monto">' + dinero(f.total) + '</td></tr>' +
                    '<tr><td colspan="3" class="text-end texto-pequeno">Base imponible</td><td class="text-end texto-pequeno texto-monto">' + dinero(f.base_imponible) + '</td></tr>' +
                    '<tr><td colspan="3" class="text-end texto-pequeno">IVA incluido (13 %)</td><td class="text-end texto-pequeno texto-monto">' + dinero(f.iva) + '</td></tr>' +
                    '</tfoot></table></div>' +
                    '<h3 class="h6">Pagos</h3>' +
                    (pagos ? '<div class="table-responsive mb-3"><table class="table table-sm mb-0"><thead><tr><th>Fecha</th><th>Medio</th><th>Estado</th><th class="text-end">Monto</th></tr></thead><tbody>' + pagos + '</tbody></table></div>'
                        : '<p class="texto-pequeno">Todavía no hay pagos registrados.</p>') +
                    '<h3 class="h6">Trazabilidad</h3>' +
                    '<ul class="linea-tiempo">' + ((f.historial || []).map(lineaHistorial).join('') || '<li class="texto-pequeno">Sin movimientos.</li>') + '</ul>'
                );

                App.modal('#modal-facturas-detalle').show();
            })
            .fail(App.notificarError);
    }

    /* ------------------------------------------------------------------
     * Anulación (administrador)
     * ------------------------------------------------------------------ */
    $('#form-anular').on('submit', function (e) {
        e.preventDefault();

        const $f = $(this);
        const $enviar = $('button[type="submit"][form="form-anular"]');

        App.limpiarErrores($f);
        $enviar.prop('disabled', true);

        App.peticion('PATCH', App.url(url.anular, idAnulando), { motivo: $('#anular-motivo').val() })
            .done(function (r) {
                App.notificar('success', r.mensaje || 'Factura anulada.');
                App.modal('#modal-facturas-anular').hide();
                cargar();
            })
            .fail(function (xhr) {
                if (xhr.status === 422) {
                    App.mostrarErrores($f, App.interpretarError(xhr));
                } else {
                    App.notificarError(xhr);
                }
            })
            .always(function () { $enviar.prop('disabled', false); });
    });

    function buscarEnLista(id) {
        return facturas.filter(function (f) { return String(f.id) === String(id); })[0];
    }

    $(document).on('click', '#modulo-facturas [data-accion]', function () {
        const id = $(this).data('id');
        const accion = $(this).data('accion');

        if (accion === 'ver') {
            verDetalle(id);
        } else if (accion === 'cobrar') {
            abrirPago(buscarEnLista(id));
        } else if (accion === 'cobrar-detalle') {
            App.modal('#modal-facturas-detalle').hide();
            setTimeout(function () { abrirPago(buscarEnLista(id)); }, 400);
        } else if (accion === 'anular') {
            idAnulando = id;
            App.limpiarErrores($('#form-anular'));
            $('#anular-motivo').val('');
            App.modal('#modal-facturas-anular').show();
        }
    });

    cargar().always(function () {
        // Llegó desde "Facturar" en Pedidos u Órdenes.
        if (pedidoInicial) {
            abrirNueva(pedidoInicial);
            pedidoInicial = null;
        }
    });
});
