$(function () {
    const $raiz = $('#modulo-ordenes');
    const $cuerpo = $('#tabla-ordenes tbody');
    const url = {
        activas: $raiz.data('urlActivas'),
        todos: $raiz.data('urlTodos'),
        lista: $raiz.data('urlEstadoLista'),
        actualizar: $raiz.data('urlActualizar'),
        pedidoEstado: $raiz.data('urlPedidoEstado'),
        facturas: $raiz.data('urlFacturas')
    };
    const HORAS = Number($raiz.data('horasAnticipacion')) || 5;
    const SIGUIENTE = {
        pendiente: { estado: 'en_produccion', texto: 'Iniciar', completo: 'Iniciar producción', icono: 'fa-fire-burner', clase: 'btn-primary' },
        en_produccion: { estado: 'lista', texto: 'Lista', completo: 'Marcar como lista', icono: 'fa-check', clase: 'btn-primary' },
        lista: { estado: 'entregada', texto: 'Entregar', completo: 'Entregar', icono: 'fa-hand-holding-heart', clase: 'btn-success' }
    };
    const SUBFILTROS = [
        { estado: 'pendiente', texto: 'Pendientes' },
        { estado: 'en_produccion', texto: 'En producción' },
        { estado: 'lista', texto: 'Listas' }
    ];
    let filtro = 'activas';
    let subfiltro = '';
    let ordenes = [];
    let ordenCancelando = null;

    /* ------------------------------------------------------------------
     * Utilidades
     * ------------------------------------------------------------------ */
    function hace(valor) {
        const minutos = Math.max(0, Math.round((Date.now() - new Date(valor).getTime()) / 60000));

        if (minutos < 1) { return 'justo ahora'; }
        if (minutos < 60) { return 'hace ' + minutos + ' min'; }

        const horas = Math.floor(minutos / 60);

        return horas < 24 ? 'hace ' + horas + ' h' : 'hace ' + Math.floor(horas / 24) + ' d';
    }

    function lugar(p) {
        if (p.tipo === 'local') {
            return p.mesa ? 'Mesa ' + p.mesa.numero : 'En el local';
        }

        return p.tipo === 'reserva' ? 'Reserva' : 'Para llevar';
    }

    function esActiva(o) {
        return ['pendiente', 'en_produccion', 'lista'].indexOf(o.estado) !== -1;
    }

    // Un encargo programado solo se produce desde N horas antes de la entrega.
    function produccionDesde(o) {
        const p = o.pedido || {};

        if (!p.fecha_programada) {
            return null;
        }

        return new Date(new Date(p.fecha_programada).getTime() - HORAS * 3600000);
    }

    function puedeIniciar(o) {
        const desde = produccionDesde(o);

        return !desde || Date.now() >= desde.getTime();
    }

    function facturado(o) {
        const f = (o.pedido || {}).factura;

        return !!f && f.estado !== 'anulada';
    }

    // Antes de facturar se puede cancelar en cualquier etapa (se devuelve el stock).
    function cancelable(o) {
        const p = o.pedido || {};

        return p.estado === 'abierto' && !facturado(o) && o.estado !== 'cancelada';
    }

    function resumenProductos(p) {
        return (p.detalles || []).map(function (d) {
            return d.cantidad + '× ' + (d.producto ? d.producto.nombre : '—');
        }).join(', ');
    }

    function fechaCompleta(valor) {
        return valor ? App.fechaHora(valor) : '';
    }

    /* ------------------------------------------------------------------
     * Botones (los mismos en la fila y en el detalle)
     * ------------------------------------------------------------------ */
    function botonAvanzar(o, completo) {
        const sig = SIGUIENTE[o.estado];

        if (!sig) {
            return '';
        }

        const texto = completo ? sig.completo : sig.texto;

        if (o.estado === 'pendiente' && !puedeIniciar(o)) {
            const desde = produccionDesde(o);

            return '<span class="d-inline-block" title="Disponible desde el ' + App.esc(App.fechaHora(desde.toISOString())) +
                ' (' + HORAS + ' h antes de la entrega)"><button type="button" class="btn btn-sm btn-outline-secondary" disabled>' +
                '<i class="fa-solid fa-lock me-1"></i>' + texto + '</button></span>';
        }

        return '<button type="button" class="btn btn-sm ' + sig.clase + '" data-avanzar="' + o.id + '" data-estado="' + sig.estado + '">' +
            '<i class="fa-solid ' + sig.icono + ' me-1"></i>' + texto + '</button>';
    }

    function botonFacturar(o) {
        const p = o.pedido || {};

        if (o.estado === 'entregada' && p.estado === 'abierto' && !p.factura) {
            return '<a class="btn btn-sm btn-outline-success" href="' + url.facturas + '?pedido=' + p.id +
                '" title="Facturar"><i class="fa-solid fa-file-invoice-dollar me-1"></i>Facturar</a>';
        }

        return '';
    }

    function botonCancelar(o, completo) {
        if (!cancelable(o)) {
            return '';
        }

        return '<button type="button" class="btn btn-sm btn-outline-danger" data-cancelar="' + o.id +
            '" title="Cancelar orden y pedido"><i class="fa-solid fa-ban' + (completo ? ' me-1"></i>Cancelar orden' : '"></i>') + '</button>';
    }

    /* ------------------------------------------------------------------
     * Tabla
     * ------------------------------------------------------------------ */
    function celdaEntrega(o) {
        const p = o.pedido || {};

        if (!p.fecha_programada) {
            return '<span class="texto-pequeno">Inmediata</span>';
        }

        const atrasada = esActiva(o) && new Date(p.fecha_programada).getTime() < Date.now();
        let html = '<span class="fw-semibold">' + App.esc(App.fechaHora(p.fecha_programada)) + '</span>';

        if (atrasada) {
            html += ' <span class="badge text-bg-danger">Atrasada</span>';
        } else if (o.estado === 'pendiente' && !puedeIniciar(o)) {
            html += '<div class="texto-pequeno"><i class="fa-solid fa-lock me-1"></i>Producción desde ' +
                App.esc(App.fechaHora(produccionDesde(o).toISOString())) + '</div>';
        }

        return html;
    }

    function fila(o) {
        const p = o.pedido || {};
        const programadaAtrasada = p.fecha_programada && esActiva(o) && new Date(p.fecha_programada).getTime() < Date.now();
        const resumen = resumenProductos(p);

        return '<tr class="orden-fila orden-fila--' + o.estado + (programadaAtrasada ? ' orden-fila--atrasada' : '') + '">' +
            '<td class="text-nowrap"><div class="fw-bold">' + App.esc(o.numero_orden) + '</div>' +
                '<div class="texto-pequeno">' + hace(p.fecha) + '</div></td>' +
            '<td><span class="pedido-tipo pedido-tipo--' + p.tipo + '"><i class="fa-solid ' + App.iconoTipo(p.tipo) + '"></i>' +
                App.esc(lugar(p)) + '</span><div class="texto-pequeno mt-1">Pedido #' + p.id + '</div></td>' +
            '<td>' + App.esc(p.cliente ? p.cliente.nombre_completo : 'Consumidor final') + '</td>' +
            '<td><div class="orden-productos" title="' + App.esc(resumen) + '">' + App.esc(resumen || '—') + '</div>' +
                (o.notas ? '<div class="texto-pequeno"><i class="fa-regular fa-note-sticky me-1"></i>' + App.esc(o.notas) + '</div>' : '') + '</td>' +
            '<td>' + celdaEntrega(o) + '</td>' +
            '<td>' + App.badgeEstado(o.estado) + '</td>' +
            '<td class="text-end text-nowrap"><div class="orden-acciones">' +
                botonAvanzar(o, false) + botonFacturar(o) +
                '<button type="button" class="btn btn-sm btn-outline-secondary" data-ver="' + o.id + '" title="Ver detalle"><i class="fa-solid fa-eye"></i></button>' +
                botonCancelar(o, false) + '</div></td>' +
            '</tr>';
    }

    function pintarSubfiltros() {
        const $s = $('#ordenes-subfiltros');

        if (filtro !== 'activas') {
            subfiltro = '';
            $s.addClass('d-none').empty();

            return;
        }

        $s.removeClass('d-none').html(
            '<button type="button" class="btn' + (subfiltro === '' ? ' is-activo' : '') + '" data-subfiltro="">Todas (' + ordenes.length + ')</button>' +
            SUBFILTROS.map(function (f) {
                const n = ordenes.filter(function (o) { return o.estado === f.estado; }).length;

                return '<button type="button" class="btn' + (subfiltro === f.estado ? ' is-activo' : '') + '" data-subfiltro="' + f.estado + '">' +
                    f.texto + ' (' + n + ')</button>';
            }).join('')
        );
    }

    function pintar() {
        const lista = subfiltro ? ordenes.filter(function (o) { return o.estado === subfiltro; }) : ordenes;

        pintarSubfiltros();

        if (!lista.length) {
            $cuerpo.html('<tr><td colspan="7" class="text-center text-muted py-4"><i class="fa-regular fa-face-smile me-2"></i>No hay órdenes para mostrar.</td></tr>');
        } else {
            $cuerpo.html(lista.map(fila).join(''));
        }

        $('[data-filtro]').trigger('input');
    }

    function cargar(silencioso) {
        let peticion;

        if (!silencioso) {
            $cuerpo.html('<tr><td colspan="7" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Cargando...</td></tr>');
        }

        if (filtro === 'activas') {
            peticion = App.peticion('GET', url.activas);
        } else if (filtro === 'todas') {
            peticion = App.peticion('GET', url.todos);
        } else {
            peticion = App.peticion('GET', url.lista, { estado: filtro });
        }

        return peticion
            .done(function (r) {
                ordenes = r.ordenes || [];
                pintar();
            })
            .fail(function (xhr) {
                if (!silencioso) {
                    $cuerpo.html('<tr><td colspan="7" class="text-center text-danger py-4">No se pudieron cargar las órdenes.</td></tr>');
                    App.notificarError(xhr);
                }
            });
    }

    $('#ordenes-filtros').on('click', '[data-filtro-orden]', function () {
        filtro = $(this).data('filtroOrden');
        subfiltro = '';
        $('#ordenes-filtros .btn').removeClass('is-activo');
        $(this).addClass('is-activo');
        cargar();
    });

    $('#ordenes-subfiltros').on('click', '[data-subfiltro]', function () {
        subfiltro = String($(this).data('subfiltro') || '');
        pintar();
    });

    $('#ordenes-refrescar').on('click', function () { cargar(); });

    /* ------------------------------------------------------------------
     * Avanzar de estado
     * ------------------------------------------------------------------ */
    $raiz.on('click', '[data-avanzar]', function () {
        const $boton = $(this);
        const id = $boton.data('avanzar');

        App.cargando($boton, true);

        App.peticion('PATCH', App.url(url.actualizar, id), { estado: $boton.data('estado') })
            .done(function (r) {
                App.notificar('success', r.mensaje || 'Orden actualizada.');
                App.modal('#modal-ordenes-detalle').hide();
                cargar(true);
            })
            .fail(function (xhr) {
                App.cargando($boton, false);
                App.notificarError(xhr);
                cargar(true);
            });
    });

    /* ------------------------------------------------------------------
     * Detalle
     * ------------------------------------------------------------------ */
    function buscar(id) {
        return ordenes.filter(function (o) { return String(o.id) === String(id); })[0];
    }

    function dato(titulo, valor, extra) {
        return '<div class="col-6 col-md-3"><div class="dato-clave"><div class="dato-clave__titulo">' + titulo +
            '</div><div class="dato-clave__valor ' + (extra || '') + '">' + valor + '</div></div></div>';
    }

    function paso(titulo, fecha) {
        return fecha ? '<li class="linea-tiempo__item"><div class="linea-tiempo__titulo">' + titulo +
            '</div><div class="linea-tiempo__sub">' + App.esc(App.fechaHora(fecha)) + '</div></li>' : '';
    }

    function abrirDetalle(o) {
        const p = o.pedido || {};
        const desde = produccionDesde(o);
        const items = (p.detalles || []).map(function (d) {
            return '<tr><td class="fw-bold text-nowrap">' + d.cantidad + '×</td><td>' +
                App.esc(d.producto ? d.producto.nombre : '—') + '</td></tr>';
        }).join('');
        let aviso = '';

        if (facturado(o)) {
            aviso = '<div class="alert alert-info py-2 mt-3 mb-0"><i class="fa-solid fa-lock me-2"></i>El pedido ya fue facturado (' +
                App.esc(p.factura.numero_factura) + '): se considera consumido y no puede cancelarse. Si hubo un error, un administrador debe anular la factura.</div>';
        } else if (o.estado === 'pendiente' && desde && !puedeIniciar(o)) {
            aviso = '<div class="alert alert-warning py-2 mt-3 mb-0"><i class="fa-solid fa-lock me-2"></i>Encargo programado: podrá pasar a producción desde el ' +
                App.esc(App.fechaHora(desde.toISOString())) + ' (' + HORAS + ' horas antes de la entrega).</div>';
        }

        $('#ordenes-detalle-cuerpo').html(
            '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">' +
                '<div class="h4 mb-0">' + App.esc(o.numero_orden) + '</div>' + App.badgeEstado(o.estado) + '</div>' +
            '<div class="row g-3 mb-3">' +
                dato('Pedido', '#' + p.id) +
                dato('Tipo', App.esc(lugar(p))) +
                dato('Cliente', App.esc(p.cliente ? p.cliente.nombre_completo : 'Consumidor final')) +
                dato('Entrega', p.fecha_programada ? App.esc(App.fechaHora(p.fecha_programada)) : 'Inmediata') +
            '</div>' +
            '<div class="row g-3">' +
                '<div class="col-md-6"><h3 class="h6">Productos</h3><div class="table-responsive"><table class="table table-sm mb-0"><tbody>' +
                    (items || '<tr><td>—</td></tr>') + '</tbody></table></div>' +
                    (o.notas ? '<div class="texto-pequeno mt-2"><i class="fa-regular fa-note-sticky me-1"></i>' + App.esc(o.notas) + '</div>' : '') + '</div>' +
                '<div class="col-md-6"><h3 class="h6">Seguimiento</h3><ul class="linea-tiempo">' +
                    paso('Pedido registrado', p.fecha) + paso('Producción iniciada', o.fecha_inicio) +
                    paso('Lista para entregar', o.fecha_lista) + paso('Entregada', o.fecha_entrega) +
                    (o.estado === 'cancelada' ? paso('Cancelada', o.updated_at) : '') + '</ul></div>' +
            '</div>' + aviso
        );

        $('#ordenes-detalle-acciones').html(
            botonAvanzar(o, true) + ' ' + botonFacturar(o) + ' ' + botonCancelar(o, true)
        );

        App.modal('#modal-ordenes-detalle').show();
    }

    $raiz.on('click', '[data-ver]', function () {
        const o = buscar($(this).data('ver'));

        if (o) { abrirDetalle(o); }
    });

    /* ------------------------------------------------------------------
     * Cancelar (la orden y su pedido; se devuelve el stock)
     * ------------------------------------------------------------------ */
    $raiz.on('click', '[data-cancelar]', function () {
        const o = buscar($(this).data('cancelar'));

        if (!o) { return; }

        ordenCancelando = o;
        App.limpiarErrores($('#form-ordenes-cancelar'));
        $('#ordenes-motivo').val('');
        $('#ordenes-cancelar-texto').html('¿Cancelar la orden <strong>' + App.esc(o.numero_orden) + '</strong> (pedido #' +
            (o.pedido ? o.pedido.id : '') + ')?');
        App.modal('#modal-ordenes-detalle').hide();
        App.modal('#modal-ordenes-cancelar').show();
    });

    $('#form-ordenes-cancelar').on('submit', function (e) {
        e.preventDefault();

        const $f = $(this);
        const $enviar = $('button[type="submit"][form="form-ordenes-cancelar"]');

        if (!ordenCancelando) { return; }

        App.limpiarErrores($f);
        $enviar.prop('disabled', true);

        App.peticion('PATCH', App.url(url.pedidoEstado, ordenCancelando.pedido.id), {
            estado: 'cancelado',
            motivo: $('#ordenes-motivo').val() || null
        })
            .done(function (r) {
                App.notificar('success', r.mensaje || 'Orden cancelada y existencias devueltas.');
                App.modal('#modal-ordenes-cancelar').hide();
                cargar(true);
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

    // La tabla se mantiene al día sin recargar la página.
    setInterval(function () {
        if (!document.hidden && (filtro === 'activas' || filtro === 'todas')) {
            cargar(true);
        }
    }, 60000);

    cargar();
});
