$(function () {
    const $raiz = $('#modulo-reservas');
    const urlFecha = $raiz.data('urlFecha');
    const urlEstado = $raiz.data('urlEstado');
    const urlActualizarEstado = $raiz.data('urlActualizarEstado');

    // Cambios de estado permitidos: [nuevo estado, texto, clase, ícono]
    const TRANSICIONES = {
        pendiente: [
            ['confirmada', 'Confirmar', 'btn-outline-primary', 'fa-check'],
            ['cancelada', 'Cancelar', 'btn-outline-danger', 'fa-xmark']
        ],
        confirmada: [
            ['atendida', 'Marcar como atendida', 'btn-outline-success', 'fa-user-check'],
            ['cancelada', 'Cancelar', 'btn-outline-danger', 'fa-xmark']
        ]
    };

    const crud = App.crud({
        raiz: '#modulo-reservas',
        tabla: '#tabla-reservas tbody',
        clave: 'reservas',
        llave: 'reserva',
        modal: '#modal-reservas',
        form: '#form-reservas',
        tituloNuevo: 'Nueva reserva',
        tituloEditar: 'Nueva reserva',
        urlLista: function () {
            const fecha = $('#filtro-fecha-reservas').val();
            const estado = $('#filtro-estado-reservas').val();

            if (fecha) {
                return urlFecha + '?fecha=' + encodeURIComponent(fecha);
            }

            if (estado) {
                return urlEstado + '?estado=' + encodeURIComponent(estado);
            }

            return $raiz.data('urlTodos');
        },
        // Si se filtra por fecha y estado a la vez, el estado se aplica aquí.
        filtrar: function (r) {
            const estado = $('#filtro-estado-reservas').val();

            return !estado || r.estado === estado;
        },
        alNuevo: function () {
            $('#reservas-fecha').attr('min', App.hoy());
        },
        fila: function (r) {
            const botones = (TRANSICIONES[r.estado] || []).map(function (t) {
                return '<button type="button" class="btn btn-sm ' + t[2] + ' me-1" data-accion="estado" ' +
                    'data-id="' + r.id + '" data-estado="' + t[0] + '" title="' + t[1] + '">' +
                    '<i class="fa-solid ' + t[3] + '"></i></button>';
            }).join('');

            return '<tr>' +
                '<td>' + App.fechaCorta(r.fecha) + '</td>' +
                '<td class="text-nowrap">' + App.hora(r.hora_inicio) + ' - ' + App.hora(r.hora_fin) + '</td>' +
                '<td class="fw-semibold">' + App.esc(r.cliente ? r.cliente.nombre : '—') + '</td>' +
                '<td>Mesa ' + (r.mesa ? r.mesa.numero : '—') + '</td>' +
                '<td>' + r.cantidad_personas + '</td>' +
                '<td>' + App.badgeEstado(r.estado) + '</td>' +
                '<td>' + App.esc(r.observaciones || '—') + '</td>' +
                '<td class="text-end text-nowrap">' + (botones || '<span class="text-muted">—</span>') + '</td>' +
                '</tr>';
        },
        acciones: {
            estado: function (id, reserva, $boton) {
                const nuevo = $boton.data('estado');

                function cambiar() {
                    App.peticion('PATCH', App.url(urlActualizarEstado, id), { estado: nuevo })
                        .done(function (r) {
                            App.notificar('success', r.mensaje);
                            crud.cargar();
                        })
                        .fail(App.notificarError);
                }

                if (nuevo !== 'cancelada') {
                    cambiar();

                    return;
                }

                App.confirmar({
                    titulo: 'Cancelar reserva',
                    mensaje: '¿Desea cancelar la reserva de ' + (reserva.cliente ? reserva.cliente.nombre : 'este cliente') + '?',
                    boton: 'Cancelar reserva'
                }).then(function (aceptado) {
                    if (aceptado) {
                        cambiar();
                    }
                });
            }
        }
    });

    $('#filtro-fecha-reservas, #filtro-estado-reservas').on('change', function () {
        crud.cargar();
    });

    $('#limpiar-filtros-reservas').on('click', function () {
        $('#filtro-fecha-reservas, #filtro-estado-reservas').val('');
        crud.cargar();
    });
});
