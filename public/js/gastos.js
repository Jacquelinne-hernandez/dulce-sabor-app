$(function () {
    const $raiz = $('#modulo-gastos');
    const $cuerpo = $('#tabla-gastos tbody');
    const tipos = $raiz.data('tipos') || [];
    const url = {
        todos: $raiz.data('urlTodos'),
        resumen: $raiz.data('urlResumen'),
        insertar: $raiz.data('urlInsertar'),
        actualizar: $raiz.data('urlActualizar'),
        anular: $raiz.data('urlAnular')
    };
    let gastos = [];
    let editando = null;

    function etiqueta(tipo) {
        return App.textoEstado(tipo);
    }

    function filtros() {
        const f = {};

        if ($('#gastos-desde').val()) { f.desde = $('#gastos-desde').val(); }
        if ($('#gastos-hasta').val()) { f.hasta = $('#gastos-hasta').val(); }
        if ($('#gastos-tipo-filtro').val()) { f.tipo = $('#gastos-tipo-filtro').val(); }

        return f;
    }

    function pintar() {
        if (!gastos.length) {
            $cuerpo.html('<tr><td colspan="7">No hay gastos en este rango.</td></tr>');

            return;
        }

        $cuerpo.html(gastos.map(function (g) {
            const activo = g.estado === 'registrado';

            return '<tr' + (activo ? '' : ' class="gasto-anulado"') + '>' +
                '<td class="text-nowrap">' + App.esc(App.fechaCorta(g.fecha_gasto)) + '</td>' +
                '<td class="fw-semibold">' + App.esc(etiqueta(g.tipo)) + '</td>' +
                '<td>' + App.esc(g.descripcion || '—') + '</td>' +
                '<td class="texto-monto">' + App.dinero(g.monto) + '</td>' +
                '<td>' + App.esc(g.usuario ? g.usuario.nombre + ' ' + g.usuario.apellido : '—') + '</td>' +
                '<td>' + App.badgeEstado(g.estado) + '</td>' +
                '<td class="text-end text-nowrap">' + (activo
                    ? '<button type="button" class="btn btn-sm btn-outline-secondary" data-accion="editar" data-id="' + g.id + '" title="Editar"><i class="fa-solid fa-pen"></i></button>' +
                      '<button type="button" class="btn btn-sm btn-outline-danger" data-accion="anular" data-id="' + g.id + '" title="Anular"><i class="fa-solid fa-ban"></i></button>'
                    : '') + '</td></tr>';
        }).join(''));
    }

    function cargarResumen() {
        const f = filtros();

        App.peticion('GET', url.resumen, { desde: f.desde, hasta: f.hasta }).done(function (r) {
            const s = r.resumen || {};
            const chips = Object.keys(s.por_tipo || {}).filter(function (k) {
                return parseFloat(s.por_tipo[k]) > 0;
            }).map(function (k) {
                return '<span class="gasto-chip"><span>' + App.esc(etiqueta(k)) + '</span><strong>' + App.dinero(s.por_tipo[k]) + '</strong></span>';
            }).join('');

            $('#gastos-total').text(App.dinero(s.total || 0));
            $('#gastos-cantidad').text(s.cantidad || 0);
            $('#gastos-por-tipo').html(chips || '<span class="texto-pequeno">Sin gastos registrados.</span>');
        });
    }

    function cargar() {
        $cuerpo.html('<tr><td colspan="7"><span class="spinner-border spinner-border-sm me-2"></span>Cargando...</td></tr>');

        App.peticion('GET', url.todos, filtros())
            .done(function (r) {
                gastos = r.gastos || [];
                pintar();
            })
            .fail(function (xhr) {
                $cuerpo.html('<tr><td colspan="7" class="text-danger">No se pudieron cargar los gastos.</td></tr>');

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

    $('#gastos-tipo').html(opciones);
    $('#gastos-tipo-filtro').html('<option value="">Todos</option>' + opciones);

    $('#gastos-desde, #gastos-hasta, #gastos-tipo-filtro').on('change', cargar);
    $('#gastos-limpiar').on('click', function () {
        $('#gastos-desde, #gastos-hasta').val('');
        $('#gastos-tipo-filtro').val('');
        cargar();
    });

    /* ------------------------------------------------------------------
     * Crear / editar
     * ------------------------------------------------------------------ */
    function ajustarDescripcion() {
        $('#gastos-descripcion-req').toggleClass('d-none', $('#gastos-tipo').val() !== 'otros');
    }

    $('#gastos-tipo').on('change', ajustarDescripcion);

    function abrir(gasto) {
        const $f = $('#form-gastos');

        editando = gasto || null;
        App.limpiarErrores($f);
        $f[0].reset();
        $('#gastos-fecha').attr('max', App.hoy()).val(App.hoy());
        $('#modal-gastos .modal-title').text(gasto ? 'Editar gasto' : 'Registrar gasto');

        if (gasto) {
            $('#gastos-tipo').val(gasto.tipo);
            $('#gastos-fecha').val(App.soloFecha ? App.soloFecha(gasto.fecha_gasto) : String(gasto.fecha_gasto).slice(0, 10));
            $('#gastos-monto').val(parseFloat(gasto.monto).toFixed(2));
            $('#gastos-descripcion').val(gasto.descripcion || '');
        }

        ajustarDescripcion();
        App.modal('#modal-gastos').show();
    }

    $('#btn-nuevo-gasto').on('click', function () { abrir(null); });

    $('#form-gastos').on('submit', function (e) {
        e.preventDefault();

        const $f = $(this);
        const $enviar = $('button[type="submit"][form="form-gastos"]');
        const datos = {
            tipo: $('#gastos-tipo').val(),
            fecha_gasto: $('#gastos-fecha').val(),
            monto: $('#gastos-monto').val(),
            descripcion: $('#gastos-descripcion').val().trim() || null
        };

        App.limpiarErrores($f);
        $enviar.prop('disabled', true);

        App.peticion(editando ? 'PUT' : 'POST', editando ? App.url(url.actualizar, editando.id) : url.insertar, datos)
            .done(function (r) {
                App.notificar('success', r.mensaje || 'Gasto guardado.');
                App.modal('#modal-gastos').hide();
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
    $(document).on('click', '#modulo-gastos [data-accion]', function () {
        const id = $(this).data('id');
        const accion = $(this).data('accion');
        const gasto = gastos.filter(function (g) { return String(g.id) === String(id); })[0];

        if (!gasto) {
            return;
        }

        if (accion === 'editar') {
            abrir(gasto);
        } else if (accion === 'anular') {
            App.confirmar({
                titulo: 'Anular gasto',
                mensaje: 'Se anulará el gasto de ' + App.dinero(gasto.monto) + ' (' + etiqueta(gasto.tipo) + '). Dejará de contarse en los resultados.',
                boton: 'Anular gasto'
            }).then(function (si) {
                if (!si) {
                    return;
                }

                App.peticion('PATCH', App.url(url.anular, id))
                    .done(function (r) {
                        App.notificar('success', r.mensaje || 'Gasto anulado.');
                        cargar();
                    })
                    .fail(App.notificarError);
            });
        }
    });

    cargar();
});
