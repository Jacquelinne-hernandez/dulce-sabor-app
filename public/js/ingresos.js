$(function () {
    const $raiz = $('#modulo-ingresos');
    const $cuerpo = $('#tabla-ingresos tbody');
    const tipos = $raiz.data('tipos') || [];
    const url = {
        todos: $raiz.data('urlTodos'),
        resumen: $raiz.data('urlResumen'),
        insertar: $raiz.data('urlInsertar'),
        actualizar: $raiz.data('urlActualizar'),
        anular: $raiz.data('urlAnular')
    };
    const ETIQUETAS = {
        capital_inicial: 'Capital inicial',
        inversion: 'Inversión',
        aporte_socio: 'Aporte de socio',
        prestamo: 'Préstamo',
        donacion: 'Donación',
        otros: 'Otros'
    };
    let ingresos = [];
    let editando = null;

    function etiqueta(tipo) {
        return ETIQUETAS[tipo] || App.textoEstado(tipo);
    }

    function filtros() {
        const f = {};

        if ($('#ingresos-desde').val()) { f.desde = $('#ingresos-desde').val(); }
        if ($('#ingresos-hasta').val()) { f.hasta = $('#ingresos-hasta').val(); }
        if ($('#ingresos-tipo-filtro').val()) { f.tipo = $('#ingresos-tipo-filtro').val(); }

        return f;
    }

    function pintar() {
        if (!ingresos.length) {
            $cuerpo.html('<tr><td colspan="7" class="text-center text-muted py-4">No hay ingresos en este rango.</td></tr>');

            return;
        }

        $cuerpo.html(ingresos.map(function (i) {
            const activo = i.estado === 'registrado';

            return '<tr' + (activo ? '' : ' class="ingreso-anulado"') + '>' +
                '<td class="text-nowrap">' + App.esc(App.fechaCorta(i.fecha_ingreso)) + '</td>' +
                '<td class="fw-semibold">' + App.esc(etiqueta(i.tipo)) + '</td>' +
                '<td>' + App.esc(i.descripcion || '—') + '</td>' +
                '<td class="texto-monto">' + App.dinero(i.monto) + '</td>' +
                '<td>' + App.esc(i.usuario ? i.usuario.nombre + ' ' + i.usuario.apellido : '—') + '</td>' +
                '<td>' + App.badgeEstado(i.estado) + '</td>' +
                '<td class="text-end text-nowrap">' + (activo
                    ? '<button type="button" class="btn btn-sm btn-outline-secondary" data-accion="editar" data-id="' + i.id + '" title="Editar"><i class="fa-solid fa-pen"></i></button> ' +
                      '<button type="button" class="btn btn-sm btn-outline-danger" data-accion="anular" data-id="' + i.id + '" title="Anular"><i class="fa-solid fa-ban"></i></button>'
                    : '') + '</td></tr>';
        }).join(''));

        $('[data-filtro]').trigger('input');
    }

    function cargarResumen() {
        const f = filtros();

        App.peticion('GET', url.resumen, { desde: f.desde, hasta: f.hasta }).done(function (r) {
            const s = r.resumen || {};
            const chips = Object.keys(s.por_tipo || {}).filter(function (k) {
                return parseFloat(s.por_tipo[k]) > 0;
            }).map(function (k) {
                return '<span class="ingreso-chip"><span>' + App.esc(etiqueta(k)) + '</span><strong>' + App.dinero(s.por_tipo[k]) + '</strong></span>';
            }).join('');

            $('#ingresos-total').text(App.dinero(s.total || 0));
            $('#ingresos-cantidad').text(s.cantidad || 0);
            $('#ingresos-por-tipo').html(chips || '<span class="texto-pequeno">Sin ingresos registrados.</span>');
        });
    }

    function cargar() {
        $cuerpo.html('<tr><td colspan="7" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Cargando...</td></tr>');

        App.peticion('GET', url.todos, filtros())
            .done(function (r) {
                ingresos = r.ingresos || [];
                pintar();
            })
            .fail(function (xhr) {
                $cuerpo.html('<tr><td colspan="7" class="text-center text-danger py-4">No se pudieron cargar los ingresos.</td></tr>');

                if (xhr.status === 422) {
                    App.notificar('warning', App.interpretarError(xhr).join(' '));
                } else {
                    App.notificarError(xhr);
                }
            });

        cargarResumen();
    }

    // Tipos en los dos selects.
    const opciones = tipos.map(function (t) {
        return '<option value="' + t + '">' + App.esc(etiqueta(t)) + '</option>';
    }).join('');

    $('#ingresos-tipo').html(opciones);
    $('#ingresos-tipo-filtro').html('<option value="">Todos</option>' + opciones);

    $('#ingresos-desde, #ingresos-hasta, #ingresos-tipo-filtro').on('change', cargar);
    $('#ingresos-limpiar').on('click', function () {
        $('#ingresos-desde, #ingresos-hasta').val('');
        $('#ingresos-tipo-filtro').val('');
        cargar();
    });

    /* ------------------------------------------------------------------
     * Crear / editar
     * ------------------------------------------------------------------ */
    function ajustarDescripcion() {
        $('#ingresos-descripcion-req').toggleClass('d-none', $('#ingresos-tipo').val() !== 'otros');
    }

    $('#ingresos-tipo').on('change', ajustarDescripcion);

    function abrir(ingreso) {
        const $f = $('#form-ingresos');

        editando = ingreso || null;
        App.limpiarErrores($f);
        $f[0].reset();
        $('#ingresos-fecha').attr('max', App.hoy()).val(App.hoy());
        $('#modal-ingresos .modal-title').text(ingreso ? 'Editar ingreso' : 'Registrar ingreso');

        if (ingreso) {
            $('#ingresos-tipo').val(ingreso.tipo);
            $('#ingresos-fecha').val(App.soloFecha(ingreso.fecha_ingreso));
            $('#ingresos-monto').val(parseFloat(ingreso.monto).toFixed(2));
            $('#ingresos-descripcion').val(ingreso.descripcion || '');
        }

        ajustarDescripcion();
        App.modal('#modal-ingresos').show();
    }

    $('#btn-nuevo-ingreso').on('click', function () { abrir(null); });

    $('#form-ingresos').on('submit', function (e) {
        e.preventDefault();

        const $f = $(this);
        const $enviar = $('button[type="submit"][form="form-ingresos"]');
        const datos = {
            tipo: $('#ingresos-tipo').val(),
            fecha_ingreso: $('#ingresos-fecha').val(),
            monto: $('#ingresos-monto').val(),
            descripcion: $('#ingresos-descripcion').val().trim() || null
        };

        App.limpiarErrores($f);
        $enviar.prop('disabled', true);

        App.peticion(editando ? 'PUT' : 'POST', editando ? App.url(url.actualizar, editando.id) : url.insertar, datos)
            .done(function (r) {
                App.notificar('success', r.mensaje || 'Ingreso guardado.');
                App.modal('#modal-ingresos').hide();
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

    /* ------------------------------------------------------------------
     * Acciones de la tabla
     * ------------------------------------------------------------------ */
    $(document).on('click', '#modulo-ingresos [data-accion]', function () {
        const id = $(this).data('id');
        const accion = $(this).data('accion');
        const ingreso = ingresos.filter(function (i) { return String(i.id) === String(id); })[0];

        if (!ingreso) {
            return;
        }

        if (accion === 'editar') {
            abrir(ingreso);
        } else if (accion === 'anular') {
            App.confirmar({
                titulo: 'Anular ingreso',
                mensaje: 'Se anulará el ingreso de ' + App.dinero(ingreso.monto) + ' (' + etiqueta(ingreso.tipo) + '). Dejará de contarse en el flujo de caja.',
                boton: 'Anular ingreso'
            }).then(function (si) {
                if (!si) {
                    return;
                }

                App.peticion('PATCH', App.url(url.anular, id))
                    .done(function (r) {
                        App.notificar('success', r.mensaje || 'Ingreso anulado.');
                        cargar();
                    })
                    .fail(App.notificarError);
            });
        }
    });

    cargar();
});
