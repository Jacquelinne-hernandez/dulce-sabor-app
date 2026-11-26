$(function () {
    const $raiz = $('#modulo-reservas');
    const $cuerpo = $('#tabla-reservas tbody');
    const $form = $('#form-reservas');
    const url = {
        todos: $raiz.data('urlTodos'),
        buscar: $raiz.data('urlBuscar'),
        insertar: $raiz.data('urlInsertar'),
        disponibles: $raiz.data('urlMesasDisponibles'),
        cotizar: $raiz.data('urlCotizar'),
        estado: $raiz.data('urlEstado'),
        pedidos: $raiz.data('urlPedidos')
    };
    let reservas = [];
    let mesasLibres = [];
    let turnoMesas = 0;
    let turnoCotizacion = 0;

    const selector = App.selectorCliente('#reservas-cliente', {
        urlFiltrar: $raiz.data('urlClientes'),
        ayuda: 'Busque por código (CLI-00001) o por nombre.'
    });

    /* ------------------------------------------------------------------
     * Listado
     * ------------------------------------------------------------------ */
    function textoBusqueda(r) {
        return [
            r.cliente ? r.cliente.nombre_completo + ' ' + r.cliente.codigo : '',
            App.listaMesas(r.mesas)
        ].join(' ').toLowerCase();
    }

    function filtradas() {
        const texto = $('#reservas-buscar').val().trim().toLowerCase();
        const estado = $('#reservas-filtro-estado').val();
        const fecha = $('#reservas-filtro-fecha').val();

        return reservas.filter(function (r) {
            return (!texto || textoBusqueda(r).indexOf(texto) !== -1) &&
                (!estado || r.estado === estado) &&
                (!fecha || App.soloFecha(r.fecha) === fecha);
        });
    }

    function botones(r) {
        let html = '<button type="button" class="btn btn-sm btn-outline-secondary" data-accion="ver" ' +
            'data-id="' + r.id + '" title="Ver detalle"><i class="fa-solid fa-eye"></i></button>';

        if (r.estado === 'pendiente') {
            html += '<button type="button" class="btn btn-sm btn-outline-success" data-accion="confirmada" ' +
                'data-id="' + r.id + '" title="Confirmar"><i class="fa-solid fa-check"></i></button>';
        }

        if (r.estado === 'confirmada' && App.soloFecha(r.fecha) === App.hoy()) {
            html += '<a class="btn btn-sm btn-outline-primary" title="Registrar el pedido de esta reserva" ' +
                'href="' + url.pedidos + '?reserva=' + r.id + '"><i class="fa-solid fa-utensils"></i></a>';
        }

        if (r.estado === 'pendiente' || r.estado === 'confirmada') {
            html += '<button type="button" class="btn btn-sm btn-outline-danger" data-accion="cancelada" ' +
                'data-id="' + r.id + '" title="Cancelar"><i class="fa-solid fa-ban"></i></button>';
        }

        return html;
    }

    function pintar() {
        const lista = filtradas();

        if (!lista.length) {
            $cuerpo.html('<tr><td colspan="8">No hay reservas para mostrar.</td></tr>');

            return;
        }

        $cuerpo.html(lista.map(function (r) {
            return '<tr>' +
                '<td>' + App.fechaCorta(r.fecha) + '</td>' +
                '<td class="texto-monto">' + App.hora(r.hora_inicio) + ' – ' + App.hora(r.hora_fin) + '</td>' +
                '<td><div class="fw-semibold">' + App.esc(r.cliente ? r.cliente.nombre_completo : '—') + '</div>' +
                '<div class="texto-pequeno">' + App.esc(r.cliente ? r.cliente.codigo : '') + '</div></td>' +
                '<td>' + App.esc(App.listaMesas(r.mesas)) + '</td>' +
                '<td>' + r.cantidad_personas + '</td>' +
                '<td class="texto-monto">' + App.dinero(r.total_mesas) + '</td>' +
                '<td>' + App.badgeEstado(r.estado) + '</td>' +
                '<td class="text-end text-nowrap">' + botones(r) + '</td>' +
                '</tr>';
        }).join(''));
    }

    function cargar() {
        $cuerpo.html('<tr><td colspan="8"><span class="spinner-border spinner-border-sm me-2"></span>Cargando...</td></tr>');

        return App.peticion('GET', url.todos)
            .done(function (r) {
                reservas = r.reservas || [];
                pintar();
            })
            .fail(function (xhr) {
                $cuerpo.html('<tr><td colspan="8" class="text-danger">No se pudieron cargar las reservas.</td></tr>');
                App.notificarError(xhr);
            });
    }

    $('#reservas-buscar').on('input', pintar);
    $('#reservas-filtro-estado, #reservas-filtro-fecha').on('change', pintar);
    $('#reservas-limpiar').on('click', function () {
        $('#reservas-buscar, #reservas-filtro-fecha').val('');
        $('#reservas-filtro-estado').val('');
        pintar();
    });

    /* ------------------------------------------------------------------
     * Nueva reserva: mesas libres y cotización
     * ------------------------------------------------------------------ */
    function horarioCompleto() {
        const fecha = $('#reservas-fecha').val();
        const inicio = $('#reservas-hora-inicio').val();
        const fin = $('#reservas-hora-fin').val();

        return fecha && inicio && fin && fecha >= App.hoy() && fin > inicio
            ? { fecha: fecha, hora_inicio: inicio, hora_fin: fin }
            : null;
    }

    function mesasElegidas() {
        return $('#reservas-mesas input:checked').map(function () { return Number(this.value); }).get();
    }

    function pintarCotizacion(c) {
        const $caja = $('#reservas-cotizacion');

        if (!c) {
            $caja.addClass('d-none').empty();

            return;
        }

        const filas = c.mesas.map(function (m) {
            return '<div class="caja-totales__fila"><span>Mesa ' + m.numero + ' · ' +
                App.dinero(m.tarifa_hora) + '/h × ' + App.horas(m.horas) + '</span>' +
                '<span class="texto-monto">' + App.dinero(m.subtotal) + '</span></div>';
        }).join('');

        $caja.html('<div class="caja-totales">' + filas +
            '<div class="caja-totales__fila caja-totales__fila--total"><span>Cobro por las mesas</span>' +
            '<span class="texto-monto">' + App.dinero(c.total) + '</span></div>' +
            '<div class="texto-pequeno mt-1">El tiempo se cobra por media hora completa.</div></div>').removeClass('d-none');
    }

    function cotizar() {
        const horario = horarioCompleto();
        const ids = mesasElegidas();
        const turno = ++turnoCotizacion;

        if (!horario || !ids.length) {
            pintarCotizacion(null);

            return;
        }

        App.peticion('GET', url.cotizar, {
            id_mesas: ids,
            hora_inicio: horario.hora_inicio,
            hora_fin: horario.hora_fin
        }).done(function (r) {
            if (turno === turnoCotizacion) {
                pintarCotizacion(r.cotizacion);
            }
        }).fail(function () {
            if (turno === turnoCotizacion) {
                pintarCotizacion(null);
            }
        });
    }

    function pintarMesas() {
        const $caja = $('#reservas-mesas');

        if (!mesasLibres.length) {
            $caja.html('<div class="reserva-mesas__aviso">No hay mesas libres en ese horario.</div>');

            return;
        }

        $caja.html(mesasLibres.map(function (m) {
            return '<div class="opcion-tarjeta reserva-mesa">' +
                '<input type="checkbox" id="rm-' + m.id + '" value="' + m.id + '">' +
                '<label for="rm-' + m.id + '"><i class="fa-solid fa-chair"></i>' +
                '<span class="reserva-mesa__datos"><strong>Mesa ' + m.numero + '</strong>' +
                '<small>' + m.capacidad + ' personas · ' + App.dinero(m.tarifa_hora) + '/h</small></span></label></div>';
        }).join(''));
    }

    function buscarMesas() {
        const horario = horarioCompleto();
        const turno = ++turnoMesas;

        mesasLibres = [];
        pintarCotizacion(null);

        if (!horario) {
            $('#reservas-mesas').html('<div class="reserva-mesas__aviso">Indique la fecha y el horario para ver las mesas libres.</div>');

            return;
        }

        $('#reservas-mesas').html('<div class="reserva-mesas__aviso"><span class="spinner-border spinner-border-sm me-2"></span>Consultando...</div>');

        App.peticion('GET', url.disponibles, horario)
            .done(function (r) {
                if (turno === turnoMesas) {
                    mesasLibres = r.mesas || [];
                    pintarMesas();
                }
            })
            .fail(function (xhr) {
                if (turno !== turnoMesas) {
                    return;
                }

                $('#reservas-mesas').html('<div class="reserva-mesas__aviso text-danger">' +
                    App.esc(App.interpretarError(xhr).mensaje) + '</div>');
            });
    }

    $('#reservas-fecha, #reservas-hora-inicio, #reservas-hora-fin').on('change', buscarMesas);
    $('#reservas-mesas').on('change', 'input[type="checkbox"]', cotizar);

    $('#btn-nueva-reserva').on('click', function () {
        App.limpiarErrores($form);
        $form[0].reset();
        selector.limpiar();
        $('#reservas-fecha').attr('min', App.hoy());
        mesasLibres = [];
        buscarMesas();
        App.modal('#modal-reservas').show();
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        App.limpiarErrores($form);

        const cliente = selector.valor();
        const datos = {
            id_cliente: cliente ? cliente.id : null,
            id_mesas: mesasElegidas(),
            fecha: $('#reservas-fecha').val(),
            hora_inicio: $('#reservas-hora-inicio').val(),
            hora_fin: $('#reservas-hora-fin').val(),
            cantidad_personas: $('#reservas-personas').val(),
            observaciones: $('#reservas-observaciones').val()
        };
        const $enviar = $('button[type="submit"][form="form-reservas"]');

        if (!cliente) {
            App.mostrarErrores($form, { mensaje: 'Debe seleccionar un cliente registrado.', errores: {} });

            return;
        }

        $enviar.prop('disabled', true);

        App.peticion('POST', url.insertar, datos)
            .done(function (r) {
                App.notificar('success', r.mensaje || 'Reserva registrada correctamente.');
                App.modal('#modal-reservas').hide();
                cargar();
            })
            .fail(function (xhr) {
                const error = App.interpretarError(xhr);

                if (xhr.status === 422) {
                    App.mostrarErrores($form, error);
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
                const r = res.reserva;
                const mesas = (r.mesas || []).map(function (m) {
                    const p = m.pivot || {};

                    return '<tr><td>Mesa ' + m.numero + '</td><td>' + m.capacidad + '</td>' +
                        '<td class="texto-monto">' + App.dinero(p.tarifa_hora) + '</td>' +
                        '<td>' + App.horas(p.horas) + '</td>' +
                        '<td class="text-end texto-monto">' + App.dinero(p.subtotal) + '</td></tr>';
                }).join('');

                const historial = (r.historial || []).map(lineaHistorial).join('');

                $('#reservas-detalle-cuerpo').html(
                    '<div class="row g-3 mb-3">' +
                    '<div class="col-6 col-md-3"><div class="dato-clave"><div class="dato-clave__titulo">Fecha</div>' +
                    '<div class="dato-clave__valor">' + App.fechaCorta(r.fecha) + '</div></div></div>' +
                    '<div class="col-6 col-md-3"><div class="dato-clave"><div class="dato-clave__titulo">Horario</div>' +
                    '<div class="dato-clave__valor">' + App.hora(r.hora_inicio) + ' – ' + App.hora(r.hora_fin) + '</div></div></div>' +
                    '<div class="col-6 col-md-3"><div class="dato-clave"><div class="dato-clave__titulo">Personas</div>' +
                    '<div class="dato-clave__valor">' + r.cantidad_personas + '</div></div></div>' +
                    '<div class="col-6 col-md-3"><div class="dato-clave"><div class="dato-clave__titulo">Estado</div>' +
                    '<div class="dato-clave__valor">' + App.badgeEstado(r.estado) + '</div></div></div>' +
                    '</div>' +
                    '<p class="mb-3"><strong>Cliente:</strong> ' + App.esc(r.cliente ? r.cliente.nombre_completo : '—') +
                    ' <span class="texto-pequeno">' + App.esc(r.cliente ? r.cliente.codigo : '') + '</span>' +
                    (r.pedido ? '<br><strong>Pedido asociado:</strong> #' + r.pedido.id + ' (' + App.esc(App.textoEstado(r.pedido.estado)) + ')' : '') +
                    (r.observaciones ? '<br><strong>Observaciones:</strong> ' + App.esc(r.observaciones) : '') + '</p>' +
                    '<div class="table-responsive mb-3"><table class="table table-sm mb-0"><thead><tr>' +
                    '<th>Mesa</th><th>Capacidad</th><th>Tarifa/h</th><th>Horas</th><th class="text-end">Subtotal</th>' +
                    '</tr></thead><tbody>' + mesas + '</tbody>' +
                    '<tfoot><tr><td colspan="4" class="text-end fw-bold">Total mesas</td>' +
                    '<td class="text-end fw-bold texto-monto">' + App.dinero(r.total_mesas) + '</td></tr></tfoot></table></div>' +
                    '<h3 class="h6">Trazabilidad</h3>' +
                    '<ul class="linea-tiempo">' + (historial || '<li class="texto-pequeno">Sin movimientos.</li>') + '</ul>'
                );

                App.modal('#modal-reservas-detalle').show();
            })
            .fail(App.notificarError);
    }

    /* ------------------------------------------------------------------
     * Cambios de estado
     * ------------------------------------------------------------------ */
    function cambiarEstado(id, estado) {
        return App.peticion('PATCH', App.url(url.estado, id), { estado: estado })
            .done(function (r) {
                App.notificar('success', r.mensaje || 'Estado actualizado correctamente.');
                cargar();
            })
            .fail(App.notificarError);
    }

    $raiz.on('click', '[data-accion]', function () {
        const id = $(this).data('id');
        const accion = $(this).data('accion');

        if (accion === 'ver') {
            verDetalle(id);
        } else if (accion === 'confirmada') {
            cambiarEstado(id, 'confirmada');
        } else if (accion === 'cancelada') {
            App.confirmar({
                titulo: 'Cancelar reserva',
                mensaje: '¿Desea cancelar esta reserva? Las mesas quedarán libres para ese horario.',
                boton: 'Cancelar reserva'
            }).then(function (aceptado) {
                if (aceptado) {
                    cambiarEstado(id, 'cancelada');
                }
            });
        }
    });

    cargar();
});
