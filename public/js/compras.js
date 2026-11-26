$(function () {
    const $raiz = $('#modulo-compras');
    const $lineas = $('#lineas-compras');
    const productos = $raiz.data('productos') || [];
    const urlBuscar = $raiz.data('urlBuscar');

    function opcionesProductos() {
        return '<option value="">Seleccione...</option>' + productos.map(function (p) {
            return '<option value="' + p.id + '">' + App.esc(p.nombre) + '</option>';
        }).join('');
    }

    function agregarLinea() {
        $lineas.append(
            '<tr data-linea>' +
            '<td><select class="form-select form-select-sm" data-campo="id_producto" aria-label="Producto">' +
            opcionesProductos() + '</select></td>' +
            '<td><input type="number" class="form-control form-control-sm" data-campo="cantidad" ' +
            'min="1" step="1" value="1" aria-label="Cantidad"></td>' +
            '<td><input type="number" class="form-control form-control-sm" data-campo="precio_unitario" ' +
            'min="0.01" step="0.01" aria-label="Precio unitario"></td>' +
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
            const cantidad = parseFloat($l.find('[data-campo="cantidad"]').val()) || 0;
            const precio = parseFloat($l.find('[data-campo="precio_unitario"]').val()) || 0;
            const subtotal = cantidad * precio;

            $l.find('[data-subtotal]').text(App.dinero(subtotal));
            total += subtotal;
        });

        $('#total-compras').text(App.dinero(total));
    }

    function leerLineas() {
        return $lineas.find('[data-linea]').map(function () {
            const $l = $(this);

            return {
                id_producto: $l.find('[data-campo="id_producto"]').val(),
                cantidad: $l.find('[data-campo="cantidad"]').val(),
                precio_unitario: $l.find('[data-campo="precio_unitario"]').val()
            };
        }).get();
    }

    $('#agregar-linea-compras').on('click', function () {
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

    App.crud({
        raiz: '#modulo-compras',
        tabla: '#tabla-compras tbody',
        clave: 'compras',
        llave: 'compra',
        modal: '#modal-compras',
        form: '#form-compras',
        tituloNuevo: 'Registrar compra',
        tituloEditar: 'Registrar compra',
        alNuevo: function () {
            $lineas.empty();
            agregarLinea();
            recalcular();
        },
        antesDeEnviar: function (datos) {
            datos.detalles = leerLineas();

            return datos;
        },
        fila: function (c) {
            return '<tr>' +
                '<td>' + c.id + '</td>' +
                '<td>' + App.fechaHora(c.fecha_compra) + '</td>' +
                '<td class="fw-semibold">' + App.esc(c.proveedor ? c.proveedor.nombre : '—') + '</td>' +
                '<td>' + App.esc(c.usuario ? c.usuario.nombre + ' ' + c.usuario.apellido : '—') + '</td>' +
                '<td>' + (c.detalles ? c.detalles.length : 0) + '</td>' +
                '<td>' + App.dinero(c.total) + '</td>' +
                '<td>' + App.badgeEstado(c.estado) + '</td>' +
                '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-secondary" ' +
                'data-accion="detalle" data-id="' + c.id + '" title="Ver detalle">' +
                '<i class="fa-solid fa-eye"></i></button></td>' +
                '</tr>';
        },
        acciones: {
            detalle: function (id) {
                App.peticion('GET', App.url(urlBuscar, id))
                    .done(function (r) {
                        const c = r.compra;
                        const filas = c.detalles.map(function (d) {
                            return '<tr>' +
                                '<td>' + App.esc(d.producto ? d.producto.nombre : '—') + '</td>' +
                                '<td class="text-end">' + d.cantidad + '</td>' +
                                '<td class="text-end">' + App.dinero(d.precio_unitario) + '</td>' +
                                '<td class="text-end">' + App.dinero(d.subtotal) + '</td>' +
                                '</tr>';
                        }).join('');

                        $('#detalle-compras').html(
                            '<dl class="row mb-3">' +
                            '<dt class="col-sm-3">Compra</dt><dd class="col-sm-9">#' + c.id + '</dd>' +
                            '<dt class="col-sm-3">Fecha</dt><dd class="col-sm-9">' + App.fechaHora(c.fecha_compra) + '</dd>' +
                            '<dt class="col-sm-3">Proveedor</dt><dd class="col-sm-9">' + App.esc(c.proveedor ? c.proveedor.nombre : '—') + '</dd>' +
                            '<dt class="col-sm-3">Registrada por</dt><dd class="col-sm-9 mb-0">' +
                            App.esc(c.usuario ? c.usuario.nombre + ' ' + c.usuario.apellido : '—') + '</dd>' +
                            '</dl>' +
                            '<div class="table-responsive"><table class="table table-sm align-middle mb-0">' +
                            '<thead class="table-light"><tr><th>Producto</th><th class="text-end">Cantidad</th>' +
                            '<th class="text-end">Precio unitario</th><th class="text-end">Subtotal</th></tr></thead>' +
                            '<tbody>' + filas + '</tbody>' +
                            '<tfoot><tr><th colspan="3" class="text-end">Total</th>' +
                            '<th class="text-end">' + App.dinero(c.total) + '</th></tr></tfoot>' +
                            '</table></div>'
                        );

                        App.modal('#modal-compras-detalle').show();
                    })
                    .fail(App.notificarError);
            }
        }
    });
});
