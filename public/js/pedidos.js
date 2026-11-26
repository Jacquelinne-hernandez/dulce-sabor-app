$(function () {
    const $raiz = $('#modulo-pedidos');
    const $cuerpo = $('#tabla-pedidos tbody');
    const $form = $('#form-pedidos');
    const url = {
        todos: $raiz.data('urlTodos'),
        buscar: $raiz.data('urlBuscar'),
        insertar: $raiz.data('urlInsertar'),
        estado: $raiz.data('urlEstado'),
        reservas: $raiz.data('urlReservas'),
        catalogo: $raiz.data('urlCatalogo'),
        mesas: $raiz.data('urlMesas'),
        facturas: $raiz.data('urlFacturas'),
        ordenes: $raiz.data('urlOrdenes')
    };
    const PUNTOS_POR_UNIDAD = Number($raiz.data('puntosUnidad')) || 1;

    function dos(n) {
        return String(n).padStart(2, '0');
    }

    // Fecha local (YYYY-MM-DD) de un valor de fecha y hora del servidor.
    function fechaLocalISO(valor) {
        const d = new Date(valor);

        return isNaN(d.getTime()) ? '' : d.getFullYear() + '-' + dos(d.getMonth() + 1) + '-' + dos(d.getDate());
    }

    // Valor para <input type="datetime-local">: YYYY-MM-DDTHH:mm
    function fechaHoraInput(d) {
        return fechaLocalISO(d) + 'T' + dos(d.getHours()) + ':' + dos(d.getMinutes());
    }
    const ANTICIPACION = Number($raiz.data('minutosAnticipacion')) || 30;

    let pedidos = [];
    let catalogo = [];
    let reservasHoy = [];
    let carrito = {};          // id_producto -> { producto, cantidad }
    let idCancelando = null;
    let reservaInicial = new URLSearchParams(window.location.search).get('reserva');

    const selector = App.selectorCliente('#pedidos-cliente', {
        urlFiltrar: $raiz.data('urlClientes'),
        alCambiar: function () { totales(); }
    });

    $('#pedidos-anticipacion').text(ANTICIPACION);

    /* ------------------------------------------------------------------
     * Listado
     * ------------------------------------------------------------------ */
    function clienteTexto(p) {
        return p.cliente ? p.cliente.nombre_completo + ' ' + p.cliente.codigo : 'Consumidor final';
    }

    function entregaTexto(p) {
        if (p.tipo === 'local') {
            return p.mesa ? 'Mesa ' + p.mesa.numero : '—';
        }

        if (p.tipo === 'reserva') {
            return p.reserva ? App.listaMesas(p.reserva.mesas) + ' · ' + App.hora(p.reserva.hora_inicio) : 'Reserva';
        }

        return p.fecha_programada ? 'Programado: ' + App.fechaHora(p.fecha_programada) : 'Para llevar (sin programar)';
    }

    function filtrados() {
        const texto = $('#pedidos-buscar').val().trim().toLowerCase();
        const estado = $('#pedidos-filtro-estado').val();
        const tipo = $('#pedidos-filtro-tipo').val();
        const fecha = $('#pedidos-filtro-fecha').val();

        return pedidos.filter(function (p) {
            const busqueda = [p.id, clienteTexto(p), p.orden ? p.orden.numero_orden : '',
                p.factura ? p.factura.numero_factura : ''].join(' ').toLowerCase();

            return (!texto || busqueda.indexOf(texto) !== -1) &&
                (!estado || p.estado === estado) &&
                (!tipo || p.tipo === tipo) &&
                (!fecha || fechaLocalISO(p.fecha) === fecha);
        });
    }

    function botones(p) {
        let html = '<button type="button" class="btn btn-sm btn-outline-secondary" data-accion="ver" ' +
            'data-id="' + p.id + '" title="Ver detalle"><i class="fa-solid fa-eye"></i></button>';

        if (p.estado === 'abierto' && p.orden && p.orden.estado === 'entregada' && !p.factura) {
            html += '<a class="btn btn-sm btn-outline-success" href="' + url.facturas + '?pedido=' + p.id +
                '" title="Facturar"><i class="fa-solid fa-file-invoice-dollar"></i></a>';
        }

        if (p.estado === 'abierto' && !p.factura) {
            html += '<button type="button" class="btn btn-sm btn-outline-danger" data-accion="cancelar" ' +
                'data-id="' + p.id + '" title="Cancelar pedido"><i class="fa-solid fa-ban"></i></button>';
        }

        return html;
    }

    function pintar() {
        const lista = filtrados();

        if (!lista.length) {
            $cuerpo.html('<tr><td colspan="9">No hay pedidos para mostrar.</td></tr>');

            return;
        }

        $cuerpo.html(lista.map(function (p) {
            return '<tr>' +
                '<td>#' + p.id + '</td>' +
                '<td class="text-nowrap">' + App.esc(App.fechaHora(p.fecha)) + '</td>' +
                '<td><span class="pedido-tipo pedido-tipo--' + p.tipo + '"><i class="fa-solid ' + App.iconoTipo(p.tipo) + '"></i>' +
                App.esc(App.textoEstado(p.tipo)) + '</span></td>' +
                '<td>' + App.esc(clienteTexto(p)) + '</td>' +
                '<td>' + App.esc(entregaTexto(p)) + '</td>' +
                '<td>' + (p.orden ? '<div class="fw-semibold">' + App.esc(p.orden.numero_orden) + '</div>' +
                    App.badgeEstado(p.orden.estado) : '—') + '</td>' +
                '<td class="texto-monto">' + App.dinero(p.total) + '</td>' +
                '<td>' + App.badgeEstado(p.estado) + '</td>' +
                '<td class="text-end text-nowrap">' + botones(p) + '</td>' +
                '</tr>';
        }).join(''));
    }

    function cargar() {
        $cuerpo.html('<tr><td colspan="9"><span class="spinner-border spinner-border-sm me-2"></span>Cargando...</td></tr>');

        return App.peticion('GET', url.todos)
            .done(function (r) {
                pedidos = r.pedidos || [];
                pintar();
            })
            .fail(function (xhr) {
                $cuerpo.html('<tr><td colspan="9" class="text-danger">No se pudieron cargar los pedidos.</td></tr>');
                App.notificarError(xhr);
            });
    }

    $('#pedidos-buscar').on('input', pintar);
    $('#pedidos-filtro-estado, #pedidos-filtro-tipo, #pedidos-filtro-fecha').on('change', pintar);
    $('#pedidos-limpiar').on('click', function () {
        $('#pedidos-buscar, #pedidos-filtro-fecha').val('');
        $('#pedidos-filtro-estado, #pedidos-filtro-tipo').val('');
        pintar();
    });

    /* ------------------------------------------------------------------
     * Nuevo pedido: tipo, mesas, reservas y productos
     * ------------------------------------------------------------------ */
    function tipoActual() {
        return $form.find('input[name="tipo"]:checked').val();
    }

    function reservaElegida() {
        const id = Number($('#pedidos-reserva').val());

        return reservasHoy.filter(function (r) { return r.id === id; })[0] || null;
    }

    function ajustarTipo() {
        const tipo = tipoActual();

        $form.find('[data-tipo]').each(function () {
            $(this).toggleClass('d-none', $(this).data('tipo').split(' ').indexOf(tipo) === -1);
        });

        if (tipo === 'llevar') {
            actualizarProgramado();
        } else {
            $('#pedidos-inmediata').prop('disabled', false);
        }

        if (tipo === 'reserva') {
            cargarReservas();
        }

        totales();
    }

    function actualizarProgramado() {
        const programar = $('#pedidos-programar').is(':checked');

        $('#pedidos-bloque-programado').toggleClass('d-none', !programar);

        if (programar) {
            $('#pedidos-inmediata').prop('checked', false).prop('disabled', true);
        } else {
            $('#pedidos-inmediata').prop('disabled', false);
            $('#pedidos-fecha-programada').val('');
        }
    }

    function cargarMesas() {
        return App.peticion('GET', url.mesas).done(function (r) {
            const opciones = (r.mesas || []).map(function (m) {
                const bloqueada = m.estado === 'mantenimiento' || m.estado === 'reservada';

                return '<option value="' + m.id + '"' + (bloqueada ? ' disabled' : '') + '>Mesa ' + m.numero +
                    ' · ' + m.capacidad + ' personas · ' + App.textoEstado(m.estado) + '</option>';
            }).join('');

            $('#pedidos-mesa').html('<option value="">Seleccione...</option>' + opciones);
        });
    }

    function cargarReservas() {
        return App.peticion('GET', url.reservas).done(function (r) {
            const seleccion = $('#pedidos-reserva').val();

            reservasHoy = r.reservas || [];
            $('#pedidos-reserva').html('<option value="">Seleccione...</option>' + reservasHoy.map(function (x) {
                return '<option value="' + x.id + '">' + App.hora(x.hora_inicio) + '–' + App.hora(x.hora_fin) + ' · ' +
                    App.esc(App.listaMesas(x.mesas)) + ' · ' + App.esc(x.cliente ? x.cliente.nombre_completo : '') + '</option>';
            }).join(''));

            if (reservaInicial && reservasHoy.some(function (x) { return String(x.id) === String(reservaInicial); })) {
                $('#pedidos-reserva').val(reservaInicial);
                reservaInicial = null;
            } else if (seleccion) {
                $('#pedidos-reserva').val(seleccion);
            }

            $('#pedidos-reserva-ayuda').text(reservasHoy.length
                ? 'Se cobra además el uso de las mesas reservadas.'
                : 'No hay reservas confirmadas de hoy pendientes de atender.');
            totales();
        }).fail(App.notificarError);
    }

    function cargarCatalogo() {
        return App.peticion('GET', url.catalogo).done(function (r) {
            catalogo = (r.productos || []).filter(function (p) {
                return p.estado === 'disponible' && p.stock > 0;
            });
            pintarCatalogo();
        }).fail(App.notificarError);
    }

    function pintarCatalogo() {
        const texto = $('#pedidos-buscar-producto').val().trim().toLowerCase();
        const lista = catalogo.filter(function (p) {
            return !texto || p.nombre.toLowerCase().indexOf(texto) !== -1 ||
                (p.categoria && p.categoria.nombre.toLowerCase().indexOf(texto) !== -1);
        });

        $('#pedidos-catalogo').html(lista.length ? lista.map(function (p) {
            const enCarrito = carrito[p.id] ? carrito[p.id].cantidad : 0;

            return '<button type="button" class="pedido-prod" data-agregar="' + p.id + '"' +
                (enCarrito >= p.stock ? ' disabled' : '') + '>' +
                '<span class="pedido-prod__nombre">' + App.esc(p.nombre) + '</span>' +
                '<span class="pedido-prod__meta">' + App.dinero(p.precio) + ' · quedan ' + (p.stock - enCarrito) + '</span></button>';
        }).join('') : '<div class="texto-pequeno p-2">No hay productos disponibles.</div>');
    }

    function pintarCarrito() {
        const ids = Object.keys(carrito);

        if (!ids.length) {
            $('#pedidos-carrito').html('<div class="texto-pequeno">Aún no agregó productos.</div>');

            return;
        }

        $('#pedidos-carrito').html(ids.map(function (id) {
            const l = carrito[id];
            const subtotal = Math.round(Number(l.producto.precio) * 100) * l.cantidad / 100;

            return '<div class="pedido-linea" data-id="' + id + '">' +
                '<div class="pedido-linea__nombre">' + App.esc(l.producto.nombre) +
                '<small>' + App.dinero(l.producto.precio) + ' c/u</small></div>' +
                '<div class="pedido-linea__cant">' +
                '<button type="button" class="btn btn-sm btn-outline-secondary" data-menos="' + id + '" aria-label="Quitar uno">−</button>' +
                '<span>' + l.cantidad + '</span>' +
                '<button type="button" class="btn btn-sm btn-outline-secondary" data-mas="' + id + '" aria-label="Agregar uno"' +
                (l.cantidad >= l.producto.stock ? ' disabled' : '') + '>+</button></div>' +
                '<div class="pedido-linea__sub texto-monto">' + App.dinero(subtotal) + '</div>' +
                '<button type="button" class="btn btn-sm btn-outline-danger" data-quitar="' + id + '" aria-label="Quitar producto">' +
                '<i class="fa-solid fa-xmark"></i></button></div>';
        }).join(''));
    }

    function totales() {
        let subtotalC = 0;
        let unidades = 0;

        Object.keys(carrito).forEach(function (id) {
            subtotalC += Math.round(Number(carrito[id].producto.precio) * 100) * carrito[id].cantidad;
            unidades += carrito[id].cantidad;
        });

        const reserva = tipoActual() === 'reserva' ? reservaElegida() : null;
        const cargoC = reserva ? Math.round(Number(reserva.total_mesas) * 100) : 0;
        const cliente = tipoActual() === 'reserva' ? (reserva ? reserva.cliente : null) : selector.valor();
        let html = '<div class="caja-totales__fila"><span>Productos (' + unidades + ')</span>' +
            '<span class="texto-monto">' + App.dinero(subtotalC / 100) + '</span></div>';

        if (tipoActual() === 'reserva') {
            html += '<div class="caja-totales__fila"><span>Uso de mesas' +
                (reserva ? ' (' + App.esc(App.listaMesas(reserva.mesas)) + ')' : '') + '</span>' +
                '<span class="texto-monto">' + App.dinero(cargoC / 100) + '</span></div>';
        }

        html += '<div class="caja-totales__fila caja-totales__fila--total"><span>Total</span>' +
            '<span class="texto-monto">' + App.dinero((subtotalC + cargoC) / 100) + '</span></div>';

        if (cliente) {
            html += '<div class="texto-pequeno mt-1"><i class="fa-solid fa-star me-1"></i>' +
                App.esc(cliente.nombre_completo || cliente.nombre) + ' ganaría ' + (unidades * PUNTOS_POR_UNIDAD) +
                ' puntos al facturar.</div>';
        } else {
            html += '<div class="texto-pequeno mt-1">Consumidor final: no acumula puntos.</div>';
        }

        $('#pedidos-totales').html(html);
    }

    function refrescarCarrito() {
        pintarCarrito();
        pintarCatalogo();
        totales();
    }

    $('#pedidos-catalogo').on('click', '[data-agregar]', function () {
        const id = Number($(this).data('agregar'));
        const producto = catalogo.filter(function (p) { return p.id === id; })[0];

        if (!producto) {
            return;
        }

        carrito[id] = carrito[id] || { producto: producto, cantidad: 0 };

        if (carrito[id].cantidad < producto.stock) {
            carrito[id].cantidad++;
        }

        refrescarCarrito();
    });

    $('#pedidos-carrito').on('click', '[data-mas]', function () {
        const l = carrito[$(this).data('mas')];

        if (l && l.cantidad < l.producto.stock) {
            l.cantidad++;
        }

        refrescarCarrito();
    });

    $('#pedidos-carrito').on('click', '[data-menos]', function () {
        const id = $(this).data('menos');

        if (carrito[id] && --carrito[id].cantidad <= 0) {
            delete carrito[id];
        }

        refrescarCarrito();
    });

    $('#pedidos-carrito').on('click', '[data-quitar]', function () {
        delete carrito[$(this).data('quitar')];
        refrescarCarrito();
    });

    $('#pedidos-buscar-producto').on('input', pintarCatalogo);
    $form.on('change', 'input[name="tipo"]', ajustarTipo);
    $('#pedidos-programar').on('change', actualizarProgramado);
    $('#pedidos-reserva').on('change', totales);

    function abrirNuevo() {
        App.limpiarErrores($form);
        $form[0].reset();
        selector.limpiar();
        carrito = {};
        $('#pedidos-buscar-producto').val('');
        $('#pedidos-fecha-programada').attr('min', fechaHoraInput(new Date(Date.now() + ANTICIPACION * 60000)));
        ajustarTipo();
        refrescarCarrito();
        $.when(cargarMesas(), cargarCatalogo());
        App.modal('#modal-pedidos').show();
    }

    $('#btn-nuevo-pedido').on('click', abrirNuevo);

    $form.on('submit', function (e) {
        e.preventDefault();
        App.limpiarErrores($form);

        const tipo = tipoActual();
        const datos = {
            tipo: tipo,
            observaciones: $('#pedidos-observaciones').val(),
            detalles: Object.keys(carrito).map(function (id) {
                return { id_producto: Number(id), cantidad: carrito[id].cantidad };
            })
        };

        if (tipo === 'local') {
            datos.id_mesa = $('#pedidos-mesa').val() || null;
        }

        if (tipo === 'reserva') {
            datos.id_reserva = $('#pedidos-reserva').val() || null;
        }

        if (tipo !== 'reserva' && selector.valor()) {
            datos.id_cliente = selector.valor().id;
        }

        if (tipo === 'llevar' && $('#pedidos-programar').is(':checked')) {
            datos.fecha_programada = ($('#pedidos-fecha-programada').val() || '').replace('T', ' ') || null;
        }

        if (tipo !== 'reserva' && $('#pedidos-inmediata').is(':checked')) {
            datos.entrega_inmediata = true;
        }

        if (!datos.detalles.length) {
            App.mostrarErrores($form, { mensaje: 'Agregue al menos un producto al pedido.', errores: {} });

            return;
        }

        const $enviar = $('button[type="submit"][form="form-pedidos"]');

        $enviar.prop('disabled', true);

        App.peticion('POST', url.insertar, datos)
            .done(function (r) {
                const orden = r.pedido && r.pedido.orden ? ' Orden ' + r.pedido.orden.numero_orden + '.' : '';

                App.notificar('success', (r.mensaje || 'Pedido registrado.') + orden);
                App.modal('#modal-pedidos').hide();
                cargar();
            })
            .fail(function (xhr) {
                if (xhr.status === 422) {
                    App.mostrarErrores($form, App.interpretarError(xhr));
                } else {
                    App.notificarError(xhr);
                }
            })
            .always(function () { $enviar.prop('disabled', false); });
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

    function verDetalle(id) {
        App.peticion('GET', App.url(url.buscar, id))
            .done(function (res) {
                const p = res.pedido;
                const lineas = (p.detalles || []).map(function (d) {
                    return '<tr><td>' + App.esc(d.producto ? d.producto.nombre : '—') + '</td><td>' + d.cantidad + '</td>' +
                        '<td class="texto-monto">' + App.dinero(d.precio_unitario) + '</td>' +
                        '<td class="text-end texto-monto">' + App.dinero(d.subtotal) + '</td></tr>';
                }).join('');

                $('#pedidos-detalle-cuerpo').html(
                    '<div class="row g-3 mb-3">' +
                    '<div class="col-6 col-md-3"><div class="dato-clave"><div class="dato-clave__titulo">Pedido</div>' +
                    '<div class="dato-clave__valor">#' + p.id + '</div></div></div>' +
                    '<div class="col-6 col-md-3"><div class="dato-clave"><div class="dato-clave__titulo">Tipo</div>' +
                    '<div class="dato-clave__valor">' + App.esc(App.textoEstado(p.tipo)) + '</div></div></div>' +
                    '<div class="col-6 col-md-3"><div class="dato-clave"><div class="dato-clave__titulo">Estado</div>' +
                    '<div class="dato-clave__valor">' + App.badgeEstado(p.estado) + '</div></div></div>' +
                    '<div class="col-6 col-md-3"><div class="dato-clave"><div class="dato-clave__titulo">Total</div>' +
                    '<div class="dato-clave__valor">' + App.dinero(p.total) + '</div></div></div></div>' +
                    '<p class="mb-3"><strong>Cliente:</strong> ' + App.esc(clienteTexto(p)) + '<br>' +
                    '<strong>Registrado:</strong> ' + App.esc(App.fechaHora(p.fecha)) +
                    (p.usuario ? ' por ' + App.esc(p.usuario.nombre + ' ' + p.usuario.apellido) : '') + '<br>' +
                    '<strong>Mesa / entrega:</strong> ' + App.esc(entregaTexto(p)) +
                    (p.orden ? '<br><strong>Orden:</strong> ' + App.esc(p.orden.numero_orden) + ' ' + App.badgeEstado(p.orden.estado) : '') +
                    (p.factura ? '<br><strong>Factura:</strong> ' + App.esc(p.factura.numero_factura) + ' ' + App.badgeEstado(p.factura.estado) : '') +
                    (p.observaciones ? '<br><strong>Observaciones:</strong> ' + App.esc(p.observaciones) : '') + '</p>' +
                    '<div class="table-responsive mb-3"><table class="table table-sm mb-0"><thead><tr>' +
                    '<th>Producto</th><th>Cant.</th><th>Precio</th><th class="text-end">Subtotal</th></tr></thead><tbody>' + lineas + '</tbody>' +
                    '<tfoot><tr><td colspan="3" class="text-end">Productos</td><td class="text-end texto-monto">' + App.dinero(p.subtotal) + '</td></tr>' +
                    (Number(p.cargo_mesas) > 0 ? '<tr><td colspan="3" class="text-end">Uso de mesas</td><td class="text-end texto-monto">' + App.dinero(p.cargo_mesas) + '</td></tr>' : '') +
                    '<tr><td colspan="3" class="text-end fw-bold">Total</td><td class="text-end fw-bold texto-monto">' + App.dinero(p.total) + '</td></tr></tfoot></table></div>' +
                    '<h3 class="h6">Trazabilidad del pedido</h3>' +
                    '<ul class="linea-tiempo">' + ((p.historial || []).map(lineaHistorial).join('') || '<li class="texto-pequeno">Sin movimientos.</li>') + '</ul>'
                );

                App.modal('#modal-pedidos-detalle').show();
            })
            .fail(App.notificarError);
    }

    /* ------------------------------------------------------------------
     * Cancelación
     * ------------------------------------------------------------------ */
    $('#form-pedidos-cancelar').on('submit', function (e) {
        e.preventDefault();

        const $f = $(this);
        const $enviar = $('button[type="submit"][form="form-pedidos-cancelar"]');

        App.limpiarErrores($f);
        $enviar.prop('disabled', true);

        App.peticion('PATCH', App.url(url.estado, idCancelando), {
            estado: 'cancelado',
            motivo: $('#pedidos-motivo').val() || null
        })
            .done(function (r) {
                App.notificar('success', r.mensaje || 'Pedido cancelado.');
                App.modal('#modal-pedidos-cancelar').hide();
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

    $raiz.on('click', '#tabla-pedidos [data-accion]', function () {
        const id = $(this).data('id');

        if ($(this).data('accion') === 'ver') {
            verDetalle(id);
        } else {
            idCancelando = id;
            App.limpiarErrores($('#form-pedidos-cancelar'));
            $('#pedidos-motivo').val('');
            App.modal('#modal-pedidos-cancelar').show();
        }
    });

    cargar().always(function () {
        // Llegó desde "Atender" en Reservas: abre el pedido con la reserva elegida.
        if (reservaInicial) {
            abrirNuevo();
            $('#tipo-reserva').prop('checked', true).trigger('change');
        }
    });
});
