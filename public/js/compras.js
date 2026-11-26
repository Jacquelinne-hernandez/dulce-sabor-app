$(function () {
    const $raiz = $('#modulo-compras');
    const $cuerpo = $('#tabla-compras tbody');
    const $lineas = $('#lineas-compras');
    const productos = $raiz.data('productos') || [];
    const proveedores = $raiz.data('proveedores') || [];
    const url = {
        todos: $raiz.data('urlTodos'),
        insertar: $raiz.data('urlInsertar'),
        buscar: $raiz.data('urlBuscar'),
        abonos: $raiz.data('urlAbonos'),
        deudas: $raiz.data('urlDeudas')
    };
    let compras = [];
    let filtroPago = '';
    let compraAbierta = null;

    function dinero(c) {
        return App.dinero(c);
    }

    function nombreProveedor(c) {
        return c.proveedor ? c.proveedor.nombre : '—';
    }

    function numero(v) {
        return parseFloat(v) || 0;
    }

    /* ------------------------------------------------------------------
     * Deudas con proveedores
     * ------------------------------------------------------------------ */
    function cargarDeudas() {
        App.peticion('GET', url.deudas).done(function (r) {
            const s = r.resumen || {};

            $('#deuda-pendientes').text(s.compras_pendientes || 0);
            $('#deuda-vencidas').text(s.compras_vencidas || 0);
            $('#deuda-total').text(dinero(s.total_adeudado || 0));
            $('#deuda-total-vencido').text(dinero(s.total_vencido || 0));

            const filas = (s.por_proveedor || []).map(function (p) {
                return '<tr><td>' + App.esc(p.proveedor) + '</td><td class="text-end">' + p.compras + '</td>' +
                    '<td class="text-end texto-monto">' + dinero(p.saldo) + '</td>' +
                    '<td class="text-end texto-monto' + (numero(p.vencido) > 0 ? ' text-danger' : '') + '">' + dinero(p.vencido) + '</td></tr>';
            }).join('');

            $('#deuda-proveedores').html(filas);
            $('#deuda-proveedores-caja').toggleClass('d-none', !filas);
        });
    }

    /* ------------------------------------------------------------------
     * Listado
     * ------------------------------------------------------------------ */
    function filtradas() {
        const texto = $('#compras-buscar').val().trim().toLowerCase();

        return compras.filter(function (c) {
            const busqueda = (nombreProveedor(c) + ' ' + c.id).toLowerCase();

            if (texto && busqueda.indexOf(texto) === -1) {
                return false;
            }

            if (filtroPago === 'saldo') {
                return c.estado_pago !== 'pagada';
            }

            if (filtroPago === 'vencida') {
                return !!c.vencida;
            }

            if (filtroPago === 'pagada') {
                return c.estado_pago === 'pagada';
            }

            return true;
        });
    }

    function condicionTexto(c) {
        if (c.condicion_pago !== 'credito') {
            return App.badgeEstado('contado');
        }

        return App.badgeEstado('credito') + '<div class="texto-pequeno">Vence ' + App.esc(App.fechaCorta(c.fecha_vencimiento)) + '</div>';
    }

    function pagoTexto(c) {
        return App.badgeEstado(c.estado_pago) + (c.vencida ? ' ' + App.badgeEstado('vencida') : '');
    }

    function pintar() {
        const lista = filtradas();

        if (!lista.length) {
            $cuerpo.html('<tr><td colspan="8">No hay compras para mostrar.</td></tr>');

            return;
        }

        $cuerpo.html(lista.map(function (c) {
            const conSaldo = c.estado_pago !== 'pagada';

            return '<tr' + (c.vencida ? ' class="compra-vencida"' : '') + '>' +
                '<td>' + c.id + '</td>' +
                '<td class="text-nowrap">' + App.esc(App.fechaHora(c.fecha_compra)) + '</td>' +
                '<td class="fw-semibold">' + App.esc(nombreProveedor(c)) + '</td>' +
                '<td>' + condicionTexto(c) + '</td>' +
                '<td class="texto-monto">' + dinero(c.total) + '</td>' +
                '<td class="texto-monto">' + dinero(c.saldo) + '</td>' +
                '<td>' + pagoTexto(c) + '</td>' +
                '<td class="text-end text-nowrap">' +
                '<button type="button" class="btn btn-sm btn-outline-secondary" data-accion="detalle" data-id="' + c.id + '" title="Ver detalle"><i class="fa-solid fa-eye"></i></button>' +
                (conSaldo ? '<button type="button" class="btn btn-sm btn-outline-success" data-accion="abonar" data-id="' + c.id + '" title="Registrar abono"><i class="fa-solid fa-hand-holding-dollar"></i></button>' : '') +
                '</td></tr>';
        }).join(''));
    }

    function cargar() {
        $cuerpo.html('<tr><td colspan="8"><span class="spinner-border spinner-border-sm me-2"></span>Cargando...</td></tr>');

        return App.peticion('GET', url.todos)
            .done(function (r) {
                compras = r.compras || [];
                pintar();
            })
            .fail(function (xhr) {
                $cuerpo.html('<tr><td colspan="8" class="text-danger">No se pudieron cargar las compras.</td></tr>');
                App.notificarError(xhr);
            });
    }

    function recargarTodo() {
        cargar();
        cargarDeudas();
    }

    $('#compras-buscar').on('input', pintar);
    $('#compras-filtros').on('click', '[data-filtro-pago]', function () {
        filtroPago = String($(this).data('filtroPago'));
        $('#compras-filtros .btn').removeClass('is-activo');
        $(this).addClass('is-activo');
        pintar();
    });

    /* ------------------------------------------------------------------
     * Registrar compra
     * ------------------------------------------------------------------ */
    function opcionesProductos() {
        return '<option value="">Seleccione...</option>' + productos.map(function (p) {
            return '<option value="' + p.id + '" data-costo="' + (p.costo || 0) + '">' + App.esc(p.nombre) +
                ' (stock ' + p.stock + ', costo ' + dinero(p.costo || 0) + ')</option>';
        }).join('');
    }

    function agregarLinea() {
        $lineas.append(
            '<tr data-linea>' +
            '<td><select class="form-select form-select-sm" data-campo="id_producto" aria-label="Producto">' + opcionesProductos() + '</select></td>' +
            '<td><input type="number" class="form-control form-control-sm" data-campo="cantidad" min="1" step="1" value="1" aria-label="Cantidad"></td>' +
            '<td><input type="number" class="form-control form-control-sm" data-campo="precio_unitario" min="0.01" step="0.01" aria-label="Costo unitario"></td>' +
            '<td class="text-end texto-monto" data-subtotal>$0.00</td>' +
            '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-quitar-linea title="Quitar"><i class="fa-solid fa-trash"></i></button></td>' +
            '</tr>'
        );
    }

    function totalCompra() {
        let total = 0;

        $lineas.find('[data-linea]').each(function () {
            const $l = $(this);
            const subtotal = numero($l.find('[data-campo="cantidad"]').val()) * numero($l.find('[data-campo="precio_unitario"]').val());

            $l.find('[data-subtotal]').text(dinero(subtotal));
            total += subtotal;
        });

        return total;
    }

    function esCredito() {
        return $('#form-compras input[name="condicion_pago"]:checked').val() === 'credito';
    }

    function recalcular() {
        const total = totalCompra();
        const abono = Math.min(numero($('#compras-abono').val()), total);

        $('#total-compras').text(dinero(total));
        $('#resumen-abono').text(dinero(abono));
        $('#resumen-saldo').text(dinero(Math.max(0, total - abono)));
    }

    function ajustarCondicion() {
        $('#form-compras [data-solo-credito]').toggleClass('d-none', !esCredito());
        recalcular();
    }

    function diasDespues(n) {
        const d = new Date();

        d.setDate(d.getDate() + n);

        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    function abrirNueva() {
        const $f = $('#form-compras');

        App.limpiarErrores($f);
        $f[0].reset();
        $('#compras-id_proveedor').html('<option value="">Seleccione...</option>' + proveedores.map(function (p) {
            return '<option value="' + p.id + '">' + App.esc(p.nombre) + '</option>';
        }).join(''));
        $('#compras-vencimiento').attr('min', App.hoy()).val(diasDespues(30));
        $lineas.empty();
        agregarLinea();
        ajustarCondicion();
        App.modal('#modal-compras').show();
    }

    $('#btn-nueva-compra').on('click', abrirNueva);
    $('#agregar-linea-compras').on('click', agregarLinea);
    $('#form-compras').on('change', 'input[name="condicion_pago"]', ajustarCondicion);
    $('#compras-abono').on('input', recalcular);

    $lineas.on('change', '[data-campo="id_producto"]', function () {
        const $l = $(this).closest('[data-linea]');
        const costo = $(this).find(':selected').data('costo');
        const $precio = $l.find('[data-campo="precio_unitario"]');

        // Sugiere el costo actual para que el usuario solo lo ajuste.
        if (costo && !$precio.val()) {
            $precio.val(Number(costo).toFixed(2));
        }

        recalcular();
    });
    $lineas.on('input', '[data-campo]', recalcular);
    $lineas.on('click', '[data-quitar-linea]', function () {
        $(this).closest('[data-linea]').remove();

        if (!$lineas.children().length) {
            agregarLinea();
        }

        recalcular();
    });

    $('#form-compras').on('submit', function (e) {
        e.preventDefault();

        const $f = $(this);
        const $enviar = $('button[type="submit"][form="form-compras"]');
        const datos = {
            id_proveedor: $('#compras-id_proveedor').val(),
            condicion_pago: esCredito() ? 'credito' : 'contado',
            detalles: $lineas.find('[data-linea]').map(function () {
                const $l = $(this);

                return {
                    id_producto: $l.find('[data-campo="id_producto"]').val(),
                    cantidad: $l.find('[data-campo="cantidad"]').val(),
                    precio_unitario: $l.find('[data-campo="precio_unitario"]').val()
                };
            }).get()
        };

        if (esCredito()) {
            datos.fecha_vencimiento = $('#compras-vencimiento').val();

            if ($('#compras-abono').val() !== '') {
                datos.abono_inicial = $('#compras-abono').val();
            }
        }

        App.limpiarErrores($f);
        $enviar.prop('disabled', true);

        App.peticion('POST', url.insertar, datos)
            .done(function (r) {
                App.notificar('success', r.mensaje || 'Compra registrada.');
                App.modal('#modal-compras').hide();
                recargarTodo();
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
     * Detalle y abonos
     * ------------------------------------------------------------------ */
    function filaPago(p) {
        return '<tr><td class="text-nowrap">' + App.esc(App.fechaHora(p.fecha_pago)) + '</td>' +
            '<td>' + App.esc(p.usuario ? p.usuario.nombre + ' ' + p.usuario.apellido : '—') + '</td>' +
            '<td>' + App.esc(p.observaciones || '') + '</td>' +
            '<td class="text-end texto-monto">' + dinero(p.monto) + '</td></tr>';
    }

    function verDetalle(id, enfocarAbono) {
        return App.peticion('GET', App.url(url.buscar, id))
            .done(function (r) {
                const c = r.compra;
                const saldo = numero(c.saldo);
                const filas = (c.detalles || []).map(function (d) {
                    return '<tr><td>' + App.esc(d.producto ? d.producto.nombre : '—') + '</td>' +
                        '<td class="text-end">' + d.cantidad + '</td>' +
                        '<td class="text-end texto-monto">' + dinero(d.precio_unitario) + '</td>' +
                        '<td class="text-end texto-monto">' + dinero(d.subtotal) + '</td></tr>';
                }).join('');
                const pagos = (c.pagos || []).map(filaPago).join('');

                compraAbierta = c;

                $('#detalle-compras').html(
                    '<div class="row g-3 mb-3">' +
                    '<div class="col-6 col-md-3"><div class="dato-clave"><div class="dato-clave__titulo">Compra</div><div class="dato-clave__valor">#' + c.id + '</div></div></div>' +
                    '<div class="col-6 col-md-3"><div class="dato-clave"><div class="dato-clave__titulo">Total</div><div class="dato-clave__valor">' + dinero(c.total) + '</div></div></div>' +
                    '<div class="col-6 col-md-3"><div class="dato-clave"><div class="dato-clave__titulo">Pagado</div><div class="dato-clave__valor">' + dinero(c.monto_pagado) + '</div></div></div>' +
                    '<div class="col-6 col-md-3"><div class="dato-clave"><div class="dato-clave__titulo">Saldo</div><div class="dato-clave__valor' + (c.vencida ? ' text-danger' : '') + '">' + dinero(c.saldo) + '</div></div></div></div>' +
                    '<p class="mb-3"><strong>Proveedor:</strong> ' + App.esc(nombreProveedor(c)) + '<br>' +
                    '<strong>Fecha:</strong> ' + App.esc(App.fechaHora(c.fecha_compra)) + '<br>' +
                    '<strong>Registrada por:</strong> ' + App.esc(c.usuario ? c.usuario.nombre + ' ' + c.usuario.apellido : '—') + '<br>' +
                    '<strong>Condición:</strong> ' + condicionTexto(c) + ' ' + pagoTexto(c) + '</p>' +
                    '<div class="table-responsive mb-3"><table class="table table-sm align-middle mb-0">' +
                    '<thead class="table-light"><tr><th>Producto</th><th class="text-end">Cantidad</th><th class="text-end">Costo unitario</th><th class="text-end">Subtotal</th></tr></thead>' +
                    '<tbody>' + filas + '</tbody></table></div>' +
                    '<h3 class="h6">Pagos al proveedor</h3>' +
                    (pagos ? '<div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Fecha</th><th>Registró</th><th>Observaciones</th><th class="text-end">Monto</th></tr></thead><tbody>' + pagos + '</tbody></table></div>'
                        : '<p class="texto-pequeno mb-0">Todavía no hay pagos registrados.</p>')
                );

                const $f = $('#form-abono');

                App.limpiarErrores($f);
                $f[0].reset();
                $f.toggleClass('d-none', saldo <= 0);
                $('#abono-monto').attr('max', saldo.toFixed(2)).val(saldo > 0 ? saldo.toFixed(2) : '');

                if (!$('#modal-compras-detalle').hasClass('show')) {
                    App.modal('#modal-compras-detalle').show();
                }

                if (enfocarAbono) {
                    $('#modal-compras-detalle').one('shown.bs.modal', function () { $('#abono-monto').trigger('focus').select(); });
                }
            })
            .fail(App.notificarError);
    }

    $('#form-abono').on('submit', function (e) {
        e.preventDefault();

        const $f = $(this);
        const $enviar = $f.find('button[type="submit"]');

        App.limpiarErrores($f);
        $enviar.prop('disabled', true);

        App.peticion('POST', App.url(url.abonos, compraAbierta.id), {
            monto: $('#abono-monto').val(),
            observaciones: $('#abono-obs').val() || null
        })
            .done(function (r) {
                App.notificar('success', r.mensaje || 'Abono registrado.');
                verDetalle(compraAbierta.id);
                recargarTodo();
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

    $(document).on('click', '#modulo-compras [data-accion]', function () {
        const id = $(this).data('id');
        const accion = $(this).data('accion');

        if (accion === 'detalle') {
            verDetalle(id);
        } else if (accion === 'abonar') {
            verDetalle(id, true);
        }
    });

    recargarTodo();
});
