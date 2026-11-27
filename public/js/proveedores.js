
$(function () {
    const $raiz = $('#modulo-proveedores');
    const $lineas = $('#proveedores-lineas');
    const $vacio = $('#proveedores-sin-productos');
    const NUEVO = '__nuevo__';

    // Productos de tipo "comprado" (se amplía al crear uno nuevo desde aquí).
    const productos = ($raiz.data('productos') || []).slice();
    const categorias = $raiz.data('categorias') || [];

    function opcionesProductos() {
        return '<option value="">Seleccione...</option>' + productos.map(function (p) {
            return '<option value="' + p.id + '">' + App.esc(p.nombre) + '</option>';
        }).join('') + '<option value="' + NUEVO + '">➕ Producto nuevo…</option>';
    }

    function opcionesCategorias() {
        return '<option value="">Categoría...</option>' + categorias.map(function (c) {
            return '<option value="' + c.id + '">' + App.esc(c.nombre) + '</option>';
        }).join('');
    }

    // Un producto no puede repetirse en la lista del mismo proveedor.
    function bloquearRepetidos() {
        const elegidos = $lineas.find('[data-campo="id_producto"]').map(function () {
            return $(this).val();
        }).get();

        $lineas.find('[data-campo="id_producto"]').each(function () {
            const propio = $(this).val();

            $(this).find('option').each(function () {
                const v = $(this).attr('value');

                $(this).prop('disabled', !!v && v !== NUEVO && v !== propio && elegidos.indexOf(v) !== -1);
            });
        });
    }

    function actualizarVacio() {
        $vacio.toggleClass('d-none', $lineas.children().length > 0);
    }

    function agregarLinea(fila) {
        const $l = $(
            '<tr data-linea>' +
            '<td>' +
            '<select class="form-select form-select-sm" data-campo="id_producto" aria-label="Producto">' + opcionesProductos() + '</select>' +
            '<div class="row g-2 mt-1 d-none" data-bloque-nuevo>' +
            '<div class="col-12"><input type="text" class="form-control form-control-sm" data-campo="nombre" maxlength="100" placeholder="Nombre del producto nuevo" aria-label="Nombre del producto nuevo"></div>' +
            '<div class="col-7"><select class="form-select form-select-sm" data-campo="id_categoria" aria-label="Categoría">' + opcionesCategorias() + '</select></div>' +
            '<div class="col-5"><input type="number" class="form-control form-control-sm" data-campo="precio_venta" min="0.01" step="0.01" placeholder="Venta ($)" aria-label="Precio de venta"></div>' +
            '</div>' +
            '</td>' +
            '<td><input type="number" class="form-control form-control-sm" data-campo="precio_compra" min="0.01" step="0.01" placeholder="0.00" aria-label="Precio del proveedor"></td>' +
            '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-quitar-linea title="Quitar"><i class="fa-solid fa-trash"></i></button></td>' +
            '</tr>'
        );

        if (fila) {
            $l.find('[data-campo="id_producto"]').val(String(fila.id));
            $l.find('[data-campo="precio_compra"]').val(Number(fila.precio).toFixed(2));
        }

        $lineas.append($l);
        bloquearRepetidos();
        actualizarVacio();
    }

    $('#agregar-producto-proveedor').on('click', function () {
        agregarLinea();
    });

    $lineas.on('change', '[data-campo="id_producto"]', function () {
        const $l = $(this).closest('[data-linea]');

        $l.find('[data-bloque-nuevo]').toggleClass('d-none', $(this).val() !== NUEVO);
        bloquearRepetidos();
    });

    $lineas.on('click', '[data-quitar-linea]', function () {
        $(this).closest('[data-linea]').remove();
        bloquearRepetidos();
        actualizarVacio();
    });

    // Arma la lista de productos del proveedor a partir de las filas.
    function leerProductos() {
        const filas = [];

        $lineas.find('[data-linea]').each(function () {
            const $l = $(this);
            const id = $l.find('[data-campo="id_producto"]').val();
            const precio = $l.find('[data-campo="precio_compra"]').val();

            // Fila en blanco: se ignora.
            if (!id && precio === '') {
                return;
            }

            const fila = { precio_compra: precio };

            if (id === NUEVO) {
                fila.nombre = $l.find('[data-campo="nombre"]').val();
                fila.id_categoria = $l.find('[data-campo="id_categoria"]').val();
                fila.precio_venta = $l.find('[data-campo="precio_venta"]').val();
            } else {
                fila.id_producto = id;
            }

            filas.push(fila);
        });

        return filas;
    }

    App.crud({
        raiz: '#modulo-proveedores',
        tabla: '#tabla-proveedores tbody',
        clave: 'proveedores',
        llave: 'proveedor',
        modal: '#modal-proveedores',
        form: '#form-proveedores',
        tituloNuevo: 'Nuevo proveedor',
        tituloEditar: 'Editar proveedor',
        alNuevo: function () {
            $lineas.empty();
            actualizarVacio();
        },
        alEditar: function (p) {
            $lineas.empty();

            (p.productos || []).forEach(function (producto) {
                agregarLinea({
                    id: producto.id,
                    precio: producto.pivot ? producto.pivot.precio_compra : 0
                });
            });

            actualizarVacio();
        },
        antesDeEnviar: function (datos) {
            datos.productos = leerProductos();

            return datos;
        },
        despuesDeGuardar: function (r) {
            // Los productos nuevos quedan disponibles para los siguientes proveedores.
            ((r.proveedor && r.proveedor.productos) || []).forEach(function (producto) {
                const existe = productos.some(function (p) { return p.id === producto.id; });

                if (!existe) {
                    productos.push({ id: producto.id, nombre: producto.nombre });
                }
            });
        },
        eliminar: {
            titulo: 'Eliminar proveedor',
            mensaje: function (p) { return '¿Desea eliminar al proveedor "' + p.nombre + '"?'; }
        },
        fila: function (p) {
            const cantidad = p.productos_count || 0;

            return '<tr>' +
                '<td class="fw-semibold">' + App.esc(p.nombre) + '</td>' +
                '<td>' + App.esc(p.nombre_contacto || '—') + '</td>' +
                '<td>' + App.esc(p.telefono || '—') + '</td>' +
                '<td>' + App.esc(p.email || '—') + '</td>' +
                '<td>' + (cantidad
                    ? '<span class="badge text-bg-info">' + cantidad + (cantidad === 1 ? ' producto' : ' productos') + '</span>'
                    : '<span class="text-muted">Sin productos</span>') + '</td>' +
                '<td>' + App.esc(p.direccion || '—') + '</td>' +
                '<td class="text-end">' + App.botonesCrud(p.id) + '</td>' +
                '</tr>';
        }
    });
});
