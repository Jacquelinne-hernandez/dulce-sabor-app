
$(function () {
    const $raiz = $('#modulo-productos');
    const urlReactivar = $raiz.data('urlReactivar');
    const urlImagen = $raiz.data('urlImagen');

    // Fotografía del producto: se valida aquí y otra vez en el servidor.
    const MAX_BYTES = 2 * 1024 * 1024;
    const TIPOS = ['image/jpeg', 'image/png', 'image/webp'];

    const $archivo = $('#productos-imagen');
    const $contenedor = $('#productos-preview-contenedor');
    const $vista = $('#productos-preview');
    const $quitar = $('#productos-quitar-imagen');

    let idEditando = null;
    let imagenActual = null;   // dirección de la foto ya guardada (si hay)
    let urlTemporal = null;    // vista previa de la foto recién elegida

    function liberarTemporal() {
        if (urlTemporal) {
            URL.revokeObjectURL(urlTemporal);
            urlTemporal = null;
        }
    }

    // Muestra la foto guardada, o nada si no hay.
    function mostrarActual() {
        liberarTemporal();

        if (imagenActual) {
            $vista.attr('src', imagenActual);
            $contenedor.removeClass('d-none');
            $quitar.toggleClass('d-none', !idEditando);
        } else {
            $vista.attr('src', '');
            $contenedor.addClass('d-none');
            $quitar.addClass('d-none');
        }
    }

    function reiniciarImagen(url, id) {
        idEditando = id;
        imagenActual = url || null;
        $archivo.val('');
        mostrarActual();
    }

    $archivo.on('change', function () {
        const archivo = this.files && this.files[0];

        if (!archivo) {
            mostrarActual();

            return;
        }

        if (TIPOS.indexOf(archivo.type) === -1) {
            App.notificar('warning', 'La imagen debe ser JPG, PNG o WEBP.');
            $archivo.val('');
            mostrarActual();

            return;
        }

        if (archivo.size > MAX_BYTES) {
            App.notificar('warning', 'La imagen no puede pesar más de 2 MB.');
            $archivo.val('');
            mostrarActual();

            return;
        }

        liberarTemporal();
        urlTemporal = URL.createObjectURL(archivo);
        $vista.attr('src', urlTemporal);
        $contenedor.removeClass('d-none');
        $quitar.addClass('d-none');
    });

    // Quita la foto ya guardada del producto que se edita.
    $quitar.on('click', function () {
        if (!idEditando) {
            return;
        }

        App.confirmar({
            titulo: 'Quitar imagen',
            mensaje: '¿Desea quitar la imagen de este producto?',
            boton: 'Quitar'
        }).then(function (aceptado) {
            if (!aceptado) {
                return;
            }

            App.peticion('DELETE', App.url(urlImagen, idEditando))
                .done(function (r) {
                    App.notificar('success', r.mensaje);
                    imagenActual = null;
                    mostrarActual();
                    crud.cargar();
                })
                .fail(App.notificarError);
        });
    });

    // Sube la foto elegida (petición aparte, porque el formulario se envía en JSON).
    function subirImagen(id, archivo) {
        const datos = new FormData();

        datos.append('imagen', archivo);

        $.ajax({
            url: App.url(urlImagen, id),
            method: 'POST',
            data: datos,
            processData: false,
            contentType: false,
            dataType: 'json'
        })
            .done(function () {
                crud.cargar();
            })
            .fail(function (xhr) {
                App.notificar('warning', 'El producto se guardó, pero la imagen no pudo subirse.');
                App.notificarError(xhr);
            });
    }

    const crud = App.crud({
        raiz: '#modulo-productos',
        tabla: '#tabla-productos tbody',
        clave: 'productos',
        llave: 'producto',
        modal: '#modal-productos',
        form: '#form-productos',
        tituloNuevo: 'Nuevo producto',
        tituloEditar: 'Editar producto',
        alNuevo: function () {
            reiniciarImagen(null, null);
        },
        alEditar: function (p) {
            reiniciarImagen(p.imagen_url, p.id);
        },
        antesDeEnviar: function (datos) {
            // La foto no viaja en el JSON: se sube aparte al guardar.
            delete datos.imagen;
            delete datos.archivo_imagen;

            return datos;
        },
        despuesDeGuardar: function (r) {
            const archivo = $archivo[0].files && $archivo[0].files[0];

            if (archivo && r.producto) {
                subirImagen(r.producto.id, archivo);
            }
        },
        eliminar: {
            titulo: 'Inactivar producto',
            boton: 'Inactivar',
            mensaje: function (p) {
                return '¿Desea inactivar "' + p.nombre + '"? Dejará de estar disponible para la venta.';
            }
        },
        fila: function (p) {
            const inactivo = p.estado === 'inactivo';
            const bajo = !inactivo && p.stock <= p.stock_minimo;
            let acciones;

            if (inactivo) {
                acciones = '<button type="button" class="btn btn-sm btn-outline-success" ' +
                    'data-accion="reactivar" data-id="' + p.id + '" title="Reactivar">' +
                    '<i class="fa-solid fa-rotate-left"></i></button>';
            } else {
                acciones = App.botonesCrud(p.id, { tituloEliminar: 'Inactivar', iconoEliminar: 'fa-ban' });
            }

            // Si la foto se perdió, el servidor responde con una imagen
            // de reemplazo; si aun así falla, se muestra el ícono.
            const thumbHtml = p.imagen_url
                ? '<div class="prod-thumb-wrapper"><img src="' + App.esc(p.imagen_url) + '" alt="' + App.esc(p.nombre) + '" loading="lazy" ' +
                'onerror="this.outerHTML=\'<i class=\\\'fa-solid fa-cake-candles\\\'></i>\'"></div>'
                : '<div class="prod-thumb-wrapper"><i class="fa-solid fa-cake-candles"></i></div>';

            const origen = p.origen === 'compra'
                ? '<span class="badge text-bg-info">Comprado</span>'
                : '<span class="badge text-bg-secondary">Producción</span>';

            return '<tr>' +
                '<td class="fw-semibold"><div class="d-flex align-items-center gap-3">' + thumbHtml + '<span>' + App.esc(p.nombre) + '</span></div></td>' +
                '<td>' + App.esc(p.categoria ? p.categoria.nombre : '—') + '</td>' +
                '<td>' + origen + '</td>' +
                '<td><span>' + App.dinero(p.precio) +
                (p.costo !== undefined && p.costo !== null
                    ? ' <small class="texto-pequeno fw-normal">costo ' + App.dinero(p.costo) + '</small>' : '') +
                '</span></td>' +
                '<td>' + p.stock +
                (bajo ? ' <span class="badge text-bg-warning" title="Stock mínimo: ' + p.stock_minimo + '">Bajo</span>' : '') +
                '</td>' +
                '<td>' + App.badgeEstado(p.estado) + '</td>' +
                '<td class="text-end">' + acciones + '</td>' +
                '</tr>';
        },
        acciones: {
            reactivar: function (id, p) {
                App.confirmar({
                    titulo: 'Reactivar producto',
                    mensaje: '¿Desea reactivar "' + p.nombre + '"?',
                    boton: 'Reactivar',
                    clase: 'btn-success'
                }).then(function (aceptado) {
                    if (!aceptado) {
                        return;
                    }

                    App.peticion('PATCH', App.url(urlReactivar, id))
                        .done(function (r) {
                            App.notificar('success', r.mensaje);
                            crud.cargar();
                        })
                        .fail(App.notificarError);
                });
            }
        }
    });
});
