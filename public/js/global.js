/*
 * global.js
 * Utilidades compartidas por todas las vistas del sistema (requiere jQuery
 * y Bootstrap). Cada vista tiene su propio archivo en public/js que usa
 * estas funciones a través del objeto global "App".
 */
(function (window, $) {
    'use strict';

    const App = {};
    window.App = App;

    /* ------------------------------------------------------------------
     * Configuración de AJAX: token CSRF y respuestas JSON
     * ------------------------------------------------------------------ */
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        }
    });

    /**
     * Envía una petición JSON al servidor.
     * Para GET, "datos" se envía como parámetros de la URL.
     */
    App.peticion = function (metodo, url, datos) {
        const opciones = {
            url: url,
            method: metodo,
            dataType: 'json'
        };

        if (datos !== undefined && datos !== null) {
            if (metodo === 'GET') {
                opciones.data = datos;
            } else {
                opciones.data = JSON.stringify(datos);
                opciones.contentType = 'application/json; charset=utf-8';
            }
        }

        return $.ajax(opciones);
    };

    /* ------------------------------------------------------------------
     * Formato de datos
     * ------------------------------------------------------------------ */
    App.esc = function (valor) {
        const mapa = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };

        return String(valor === null || valor === undefined ? '' : valor)
            .replace(/[&<>"']/g, function (c) { return mapa[c]; });
    };

    App.dinero = function (valor) {
        return '$' + Number(valor || 0).toFixed(2);
    };

    // "2026-11-13T00:00:00.000000Z" -> "2026-11-13"
    App.soloFecha = function (valor) {
        return valor ? String(valor).slice(0, 10) : '';
    };

    // "2026-11-13" -> "13/11/2026"
    App.fechaCorta = function (valor) {
        const f = App.soloFecha(valor);

        return f ? f.split('-').reverse().join('/') : '';
    };

    // "08:30:00" -> "08:30"
    App.hora = function (valor) {
        return valor ? String(valor).slice(0, 5) : '';
    };

    // Fecha y hora local del navegador: "13/11/2026 14:30"
    App.fechaHora = function (valor) {
        if (!valor) {
            return '';
        }

        const d = new Date(valor);

        if (isNaN(d.getTime())) {
            return '';
        }

        const dos = function (n) { return String(n).padStart(2, '0'); };

        return dos(d.getDate()) + '/' + dos(d.getMonth() + 1) + '/' + d.getFullYear() +
            ' ' + dos(d.getHours()) + ':' + dos(d.getMinutes());
    };

    // Fecha de hoy (local) en formato YYYY-MM-DD
    App.hoy = function () {
        const d = new Date();
        const dos = function (n) { return String(n).padStart(2, '0'); };

        return d.getFullYear() + '-' + dos(d.getMonth() + 1) + '-' + dos(d.getDate());
    };

    // Reemplaza el marcador __ID__ de una URL de plantilla.
    App.url = function (plantilla, id) {
        return String(plantilla).replace('__ID__', encodeURIComponent(id));
    };

    const COLORES_ESTADO = {
        // pedidos
        pendiente: 'warning', en_preparacion: 'info', listo: 'primary',
        entregado: 'success', cancelado: 'danger',
        // reservas
        confirmada: 'primary', cancelada: 'danger', atendida: 'success',
        // mesas
        disponible: 'success', reservada: 'warning', ocupada: 'danger',
        mantenimiento: 'secondary',
        // productos
        agotado: 'danger', inactivo: 'secondary',
        // facturas y compras
        emitida: 'success', anulada: 'danger', completada: 'success',
        // usuarios y clientes
        activo: 'success', bloqueado: 'warning', eliminado: 'secondary'
    };

    // Etiqueta de color para cualquier estado del sistema.
    App.badgeEstado = function (estado) {
        const clave = String(estado || '').toLowerCase();
        const color = COLORES_ESTADO[clave] || 'secondary';
        const texto = clave.replace(/_/g, ' ');

        return '<span class="badge text-bg-' + color + '">' +
            App.esc(texto.charAt(0).toUpperCase() + texto.slice(1)) + '</span>';
    };

    // Botones estándar de editar / eliminar para las filas de una tabla.
    App.botonesCrud = function (id, opciones) {
        const o = $.extend({
            editar: true,
            eliminar: true,
            tituloEliminar: 'Eliminar',
            iconoEliminar: 'fa-trash'
        }, opciones);
        let html = '';

        if (o.editar) {
            html += '<button type="button" class="btn btn-sm btn-outline-primary me-1" ' +
                'data-accion="editar" data-id="' + id + '" title="Editar">' +
                '<i class="fa-solid fa-pen"></i></button>';
        }

        if (o.eliminar) {
            html += '<button type="button" class="btn btn-sm btn-outline-danger" ' +
                'data-accion="eliminar" data-id="' + id + '" title="' + o.tituloEliminar + '">' +
                '<i class="fa-solid ' + o.iconoEliminar + '"></i></button>';
        }

        return html;
    };

    /* ------------------------------------------------------------------
     * Notificaciones (mismo estilo que components/toast.blade.php)
     * ------------------------------------------------------------------ */
    const TIPOS_TOAST = {
        success: { cabecera: 'bg-success text-white', icono: 'fa-circle-check', titulo: 'Éxito', cerrar: 'btn-close-white' },
        error: { cabecera: 'bg-danger text-white', icono: 'fa-circle-xmark', titulo: 'Error', cerrar: 'btn-close-white' },
        warning: { cabecera: 'bg-warning text-dark', icono: 'fa-triangle-exclamation', titulo: 'Advertencia', cerrar: '' },
        info: { cabecera: 'bg-info text-dark', icono: 'fa-circle-info', titulo: 'Información', cerrar: '' }
    };

    App.notificar = function (tipo, mensaje) {
        const t = TIPOS_TOAST[tipo] || TIPOS_TOAST.info;
        let $contenedor = $('.toast-container').first();

        if (!$contenedor.length) {
            $contenedor = $('<div class="toast-container position-fixed top-0 start-50 translate-middle-x p-4"></div>')
                .appendTo('body');
        }

        const $toast = $(
            '<div class="toast shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">' +
            '<div class="toast-header ' + t.cabecera + '">' +
            '<i class="fa-solid ' + t.icono + ' fs-5 me-2"></i>' +
            '<strong class="me-auto">' + t.titulo + '</strong>' +
            '<small>Justo ahora</small>' +
            '<button type="button" class="btn-close ' + t.cerrar + '" data-bs-dismiss="toast" aria-label="Cerrar"></button>' +
            '</div>' +
            '<div class="toast-body text-dark">' + App.esc(mensaje) + '</div>' +
            '</div>'
        ).appendTo($contenedor);

        $toast.on('hidden.bs.toast', function () { $toast.remove(); });

        new bootstrap.Toast($toast[0], { animation: true, autohide: true, delay: 4500 }).show();
    };

    /* ------------------------------------------------------------------
     * Manejo de errores del servidor
     * ------------------------------------------------------------------ */
    App.interpretarError = function (xhr) {
        const r = xhr.responseJSON || {};
        const resultado = { mensaje: '', errores: {} };

        if (xhr.status === 422) {
            resultado.errores = r.errors || {};

            const todos = [].concat.apply([], Object.keys(resultado.errores).map(function (k) {
                return resultado.errores[k];
            }));

            resultado.mensaje = r.mensaje || todos[0] || r.message || 'Los datos enviados no son válidos.';
        } else if (xhr.status === 419) {
            resultado.mensaje = 'La sesión expiró. Recargue la página e intente de nuevo.';
        } else if (xhr.status === 401) {
            resultado.mensaje = r.mensaje || 'Su sesión ha expirado. Inicie sesión nuevamente.';
        } else if (xhr.status === 403) {
            resultado.mensaje = r.mensaje || 'No tiene permisos para realizar esta acción.';
        } else if (xhr.status === 404) {
            resultado.mensaje = r.mensaje || 'El registro solicitado no existe.';
        } else if (xhr.status === 0) {
            resultado.mensaje = 'No se pudo conectar con el servidor.';
        } else if (xhr.status === 200) {
            // Respuesta no JSON (por ejemplo, la página de login): sesión vencida.
            resultado.mensaje = 'Su sesión ha expirado. Inicie sesión nuevamente.';
        } else {
            resultado.mensaje = r.mensaje || 'Ocurrió un error inesperado en el servidor.';
        }

        return resultado;
    };

    // Muestra un toast con el error de una petición fallida.
    App.notificarError = function (xhr) {
        const error = App.interpretarError(xhr);

        App.notificar('error', error.mensaje);

        if (xhr.status === 401 || xhr.status === 200) {
            setTimeout(function () { window.location.reload(); }, 1800);
        }
    };

    /* ------------------------------------------------------------------
     * Formularios
     * ------------------------------------------------------------------ */
    App.limpiarErrores = function ($form) {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.js-error').remove();
        $form.find('[data-errores]').addClass('d-none').empty();
    };

    App.mostrarErrores = function ($form, error) {
        const mensajes = [];

        $.each(error.errores, function (campo, lista) {
            const textos = [].concat(lista);
            const $campo = $form.find('[name="' + campo + '"]');

            textos.forEach(function (m) { mensajes.push(m); });

            if ($campo.length) {
                $campo.addClass('is-invalid');
                $('<div class="invalid-feedback js-error"></div>')
                    .text(textos[0])
                    .insertAfter($campo.last());
            }
        });

        if (!mensajes.length) {
            mensajes.push(error.mensaje);
        }

        const $caja = $form.find('[data-errores]');

        if ($caja.length) {
            const $lista = $('<ul class="mb-0 ps-3"></ul>');

            mensajes.forEach(function (m) { $('<li></li>').text(m).appendTo($lista); });
            $caja.empty().append($lista).removeClass('d-none');
        }
    };

    // Convierte los campos con atributo "name" de un formulario en un objeto.
    App.datosFormulario = function ($form) {
        const datos = {};

        $form.find('[name]').each(function () {
            const $c = $(this);
            const nombre = $c.attr('name');

            if ($c.is(':disabled')) {
                return;
            }

            if ($c.is(':checkbox')) {
                datos[nombre] = $c.is(':checked');
            } else if ($c.is(':radio')) {
                if ($c.is(':checked')) {
                    datos[nombre] = $c.val();
                }
            } else {
                datos[nombre] = $c.val();
            }
        });

        return datos;
    };

    // Rellena los campos de un formulario con los valores de un objeto.
    App.llenarFormulario = function ($form, item) {
        $form.find('[name]').each(function () {
            const $c = $(this);
            const nombre = $c.attr('name');

            if (!Object.prototype.hasOwnProperty.call(item, nombre)) {
                return;
            }

            const valor = item[nombre];

            if ($c.is(':checkbox')) {
                $c.prop('checked', !!valor);
            } else {
                $c.val(valor === null || valor === undefined ? '' : valor);
            }
        });
    };

    App.modal = function ($elemento) {
        return bootstrap.Modal.getOrCreateInstance($($elemento)[0]);
    };

    /* ------------------------------------------------------------------
     * Confirmación (usa #modalConfirmar del layout). Devuelve una Promise.
     * ------------------------------------------------------------------ */
    App.confirmar = function (opciones) {
        return new Promise(function (resolver) {
            const $m = $('#modalConfirmar');
            const modal = App.modal($m);
            let aceptado = false;

            $m.find('[data-confirmar-titulo]').text(opciones.titulo || 'Confirmar acción');
            $m.find('[data-confirmar-mensaje]').text(opciones.mensaje || '¿Desea continuar?');

            $m.find('[data-confirmar-aceptar]')
                .attr('class', 'btn ' + (opciones.clase || 'btn-danger'))
                .text(opciones.boton || 'Aceptar')
                .off('click')
                .on('click', function () {
                    aceptado = true;
                    modal.hide();
                });

            $m.one('hidden.bs.modal', function () { resolver(aceptado); });

            modal.show();
        });
    };

    /* ------------------------------------------------------------------
     * Buscador de tablas: <input data-filtro="#tabla">
     * ------------------------------------------------------------------ */
    $(document).on('input', '[data-filtro]', function () {
        const texto = $(this).val().toLowerCase();

        $($(this).data('filtro')).find('tbody tr').each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(texto) !== -1);
        });
    });

    // Quita la marca de error cuando el usuario corrige el campo.
    $(document).on('input change', '.is-invalid', function () {
        $(this).removeClass('is-invalid').siblings('.js-error').remove();
    });

    /* ------------------------------------------------------------------
     * CRUD genérico: tabla + modal con formulario
     *
     * Opciones:
     *   raiz, tabla, clave, llave, fila(item, ctx), modal, form,
     *   tituloNuevo, tituloEditar, eliminar{titulo,mensaje,boton,clase},
     *   urlLista(), filtrar(item), alNuevo($form), alEditar(item, $form),
     *   antesDeEnviar(datos, id), despuesDeGuardar(respuesta),
     *   acciones{ nombre: function (id, item, $boton) }
     *
     * Las URLs se leen de los atributos data-url-* del elemento raíz.
     * ------------------------------------------------------------------ */
    App.crud = function (cfg) {
        const $raiz = $(cfg.raiz);
        const $cuerpo = $(cfg.tabla);
        const $modal = cfg.modal ? $(cfg.modal) : $();
        const $form = cfg.form ? $(cfg.form) : $();
        const urls = {
            todos: $raiz.data('urlTodos'),
            insertar: $raiz.data('urlInsertar'),
            buscar: $raiz.data('urlBuscar'),
            actualizar: $raiz.data('urlActualizar'),
            eliminar: $raiz.data('urlEliminar')
        };
        const puedeEditar = $raiz.data('puedeEditar') !== false;
        const estado = { items: [], editandoId: null };

        function columnas() {
            return $cuerpo.closest('table').find('thead th').length || 1;
        }

        function buscarItem(id) {
            return estado.items.filter(function (i) { return String(i.id) === String(id); })[0];
        }

        function pintar() {
            const lista = cfg.filtrar ? estado.items.filter(cfg.filtrar) : estado.items;

            if (!lista.length) {
                $cuerpo.html('<tr><td colspan="' + columnas() + '" class="text-center text-muted py-4">' +
                    'No hay registros para mostrar.</td></tr>');
            } else {
                $cuerpo.html(lista.map(function (item) {
                    return cfg.fila(item, { puedeEditar: puedeEditar });
                }).join(''));
            }

            $('[data-filtro]').trigger('input');
        }

        function cargar() {
            $cuerpo.html('<tr><td colspan="' + columnas() + '" class="text-center text-muted py-4">' +
                '<span class="spinner-border spinner-border-sm me-2"></span>Cargando...</td></tr>');

            return App.peticion('GET', cfg.urlLista ? cfg.urlLista() : urls.todos)
                .done(function (r) {
                    estado.items = r[cfg.clave] || [];
                    pintar();
                })
                .fail(function (xhr) {
                    $cuerpo.html('<tr><td colspan="' + columnas() + '" class="text-center text-danger py-4">' +
                        'No se pudieron cargar los datos.</td></tr>');
                    App.notificarError(xhr);
                });
        }

        function abrirFormulario(titulo) {
            $modal.find('[data-titulo-modal]').text(titulo);
            App.modal($modal).show();
        }

        function abrirNuevo() {
            estado.editandoId = null;
            App.limpiarErrores($form);
            $form[0].reset();

            if (cfg.alNuevo) {
                cfg.alNuevo($form);
            }

            abrirFormulario(cfg.tituloNuevo);
        }

        function abrirEditar(id) {
            App.peticion('GET', App.url(urls.buscar, id))
                .done(function (r) {
                    const item = r[cfg.llave];

                    estado.editandoId = id;
                    App.limpiarErrores($form);
                    $form[0].reset();
                    App.llenarFormulario($form, item);

                    if (cfg.alEditar) {
                        cfg.alEditar(item, $form);
                    }

                    abrirFormulario(cfg.tituloEditar);
                })
                .fail(App.notificarError);
        }

        function eliminar(id) {
            const item = buscarItem(id);
            const c = cfg.eliminar || {};

            App.confirmar({
                titulo: c.titulo || 'Eliminar registro',
                mensaje: c.mensaje ? c.mensaje(item) : '¿Está seguro de eliminar este registro?',
                boton: c.boton || 'Eliminar',
                clase: c.clase || 'btn-danger'
            }).then(function (aceptado) {
                if (!aceptado) {
                    return;
                }

                App.peticion('DELETE', App.url(urls.eliminar, id))
                    .done(function (r) {
                        App.notificar('success', r.mensaje || 'Registro eliminado correctamente.');
                        cargar();
                    })
                    .fail(App.notificarError);
            });
        }

        // Clics en botones con data-accion (nuevo, editar, eliminar u otras).
        $raiz.on('click', '[data-accion]', function () {
            const $boton = $(this);
            const accion = $boton.data('accion');
            const id = $boton.data('id');

            if (accion === 'nuevo') {
                abrirNuevo();
            } else if (accion === 'editar') {
                abrirEditar(id);
            } else if (accion === 'eliminar') {
                eliminar(id);
            } else if (cfg.acciones && cfg.acciones[accion]) {
                cfg.acciones[accion](id, buscarItem(id), $boton);
            }
        });

        // Envío del formulario (alta o edición).
        $form.on('submit', function (e) {
            e.preventDefault();
            App.limpiarErrores($form);

            let datos = App.datosFormulario($form);

            if (cfg.antesDeEnviar) {
                datos = cfg.antesDeEnviar(datos, estado.editandoId);
            }

            const editando = estado.editandoId !== null;
            const metodo = editando ? 'PUT' : 'POST';
            const url = editando ? App.url(urls.actualizar, estado.editandoId) : urls.insertar;
            const $enviar = $modal.find('button[type="submit"]');

            $enviar.prop('disabled', true);

            App.peticion(metodo, url, datos)
                .done(function (r) {
                    App.notificar('success', r.mensaje || 'Operación realizada correctamente.');
                    App.modal($modal).hide();

                    if (cfg.despuesDeGuardar) {
                        cfg.despuesDeGuardar(r);
                    }

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

        cargar();

        return {
            cargar: cargar,
            pintar: pintar,
            items: function () { return estado.items; },
            abrirNuevo: abrirNuevo
        };
    };

})(window, jQuery);
