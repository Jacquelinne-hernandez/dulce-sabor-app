/*
 * extras.js
 * Utilidades adicionales sobre el objeto "App" de global.js. Se carga después
 * de global.js en el layout y sirve a todas las vistas de operación.
 */
(function (window, $) {
    'use strict';

    const App = window.App;

    /* ------------------------------------------------------------------
     * Estados con color y texto legible (reemplaza App.badgeEstado)
     * ------------------------------------------------------------------ */
    const COLORES = {
        // pedidos
        abierto: 'info', completado: 'success', cancelado: 'danger',
        // órdenes de trabajo
        pendiente: 'warning', en_produccion: 'info', lista: 'primary',
        entregada: 'success', cancelada: 'danger',
        // facturas y pagos
        emitida: 'warning', pagada: 'success', anulada: 'danger', aplicado: 'success',
        // compras
        parcial: 'warning', credito: 'warning', contado: 'success', vencida: 'danger',
        // gastos
        registrado: 'success', anulado: 'danger',
        // reservas
        confirmada: 'primary', atendida: 'success',
        // mesas
        disponible: 'success', reservada: 'warning', ocupada: 'danger',
        mantenimiento: 'secondary',
        // productos
        agotado: 'danger', inactivo: 'secondary',
        // usuarios y clientes
        activo: 'success', bloqueado: 'warning', eliminado: 'secondary',
        // tipos de pedido
        local: 'primary', llevar: 'info', reserva: 'secondary'
    };

    const TEXTOS = {
        en_produccion: 'En producción',
        llevar: 'Para llevar',
        local: 'En el local',
        reserva: 'De reserva',
        emitida: 'Por cobrar',
        credito: 'A crédito'
    };

    App.textoEstado = function (estado) {
        const clave = String(estado || '').toLowerCase();

        if (TEXTOS[clave]) {
            return TEXTOS[clave];
        }

        const texto = clave.replace(/_/g, ' ');

        return texto.charAt(0).toUpperCase() + texto.slice(1);
    };

    App.badgeEstado = function (estado) {
        const clave = String(estado || '').toLowerCase();
        const color = COLORES[clave] || 'secondary';

        return '<span class="badge text-bg-' + color + '">' + App.esc(App.textoEstado(clave)) + '</span>';
    };

    /* ------------------------------------------------------------------
     * Utilidades varias
     * ------------------------------------------------------------------ */
    App.debounce = function (funcion, espera) {
        let temporizador = null;

        return function () {
            const contexto = this;
            const argumentos = arguments;

            clearTimeout(temporizador);
            temporizador = setTimeout(function () { funcion.apply(contexto, argumentos); }, espera);
        };
    };

    // Bloquea o libera un botón mientras se espera al servidor.
    App.cargando = function ($boton, activo) {
        $boton = $($boton);

        if (activo) {
            $boton.data('html-original', $boton.html())
                .prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm me-2"></span>Procesando...');
        } else {
            $boton.prop('disabled', false);

            if ($boton.data('html-original')) {
                $boton.html($boton.data('html-original'));
            }
        }
    };

    // Horas con decimales -> "1.5 h"
    App.horas = function (valor) {
        return Number(valor || 0).toString().replace(/\.0+$/, '') + ' h';
    };

    // Número de mesas de una reserva: "Mesa 2, Mesa 5"
    App.listaMesas = function (mesas) {
        return (mesas || []).map(function (m) { return 'Mesa ' + m.numero; }).join(', ') || '—';
    };

    // Ícono según el tipo de pedido.
    App.iconoTipo = function (tipo) {
        return { local: 'fa-utensils', llevar: 'fa-bag-shopping', reserva: 'fa-calendar-check' }[tipo] || 'fa-receipt';
    };

    /* ------------------------------------------------------------------
     * Máscaras de campos (tarjeta simulada)
     *   data-mascara="tarjeta" | "vencimiento" | "digitos"
     * ------------------------------------------------------------------ */
    $(document).on('input', '[data-mascara="tarjeta"]', function () {
        const digitos = this.value.replace(/\D/g, '').slice(0, 19);

        this.value = digitos.replace(/(.{4})/g, '$1 ').trim();
    });

    $(document).on('input', '[data-mascara="vencimiento"]', function () {
        const digitos = this.value.replace(/\D/g, '').slice(0, 4);

        this.value = digitos.length > 2 ? digitos.slice(0, 2) + '/' + digitos.slice(2) : digitos;
    });

    $(document).on('input', '[data-mascara="digitos"]', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 4);
    });

    /* ------------------------------------------------------------------
     * Buscador de clientes por código o nombre
     *
     *   const sel = App.selectorCliente('#contenedor', {
     *       urlFiltrar: '...',      // GET ?q=
     *       ayuda: 'texto',         // mensaje bajo el buscador
     *       alCambiar: function (cliente) {}   // cliente o null
     *   });
     *   sel.valor()  -> cliente elegido o null
     *   sel.fijar(c) / sel.limpiar() / sel.bloquear(true|false)
     * ------------------------------------------------------------------ */
    App.selectorCliente = function (selector, opciones) {
        const o = $.extend({
            urlFiltrar: '',
            ayuda: 'Sin cliente, la venta es a consumidor final (no acumula puntos).',
            alCambiar: null
        }, opciones);
        const $raiz = $(selector).addClass('sel-cliente');
        let cliente = null;
        let resultados = [];
        let bloqueado = false;
        let secuencia = 0;

        function nombre(c) {
            return c.nombre_completo || ((c.nombre || '') + ' ' + (c.apellido || '')).trim();
        }

        function notificar() {
            if (o.alCambiar) {
                o.alCambiar(cliente);
            }
        }

        function pintar() {
            if (cliente) {
                $raiz.html(
                    '<div class="sel-cliente__elegido">' +
                    '<span class="sel-cliente__avatar"><i class="fa-solid fa-user"></i></span>' +
                    '<div class="sel-cliente__datos"><strong>' + App.esc(nombre(cliente)) + '</strong>' +
                    '<small>' + App.esc(cliente.codigo) + '</small></div>' +
                    '<span class="badge text-bg-info">' + Number(cliente.puntos || 0) + ' pts</span>' +
                    (bloqueado ? '' :
                        '<button type="button" class="btn btn-sm btn-outline-secondary sel-cliente__quitar" ' +
                        'aria-label="Quitar cliente"><i class="fa-solid fa-xmark"></i></button>') +
                    '</div>'
                );
            } else {
                $raiz.html(
                    '<div class="sel-cliente__buscar">' +
                    '<input type="search" class="form-control" autocomplete="off" ' +
                    'placeholder="Código (CLI-00001) o nombre del cliente" aria-label="Buscar cliente"' +
                    (bloqueado ? ' disabled' : '') + '>' +
                    '<div class="sel-cliente__resultados d-none"></div></div>' +
                    '<small class="sel-cliente__ayuda">' + App.esc(o.ayuda) + '</small>'
                );
            }
        }

        function mostrarResultados(lista, mensaje) {
            const $caja = $raiz.find('.sel-cliente__resultados');

            resultados = lista || [];

            if (!resultados.length) {
                $caja.html('<div class="sel-cliente__vacio">' + App.esc(mensaje || 'Sin coincidencias.') + '</div>')
                    .removeClass('d-none');

                return;
            }

            $caja.html(resultados.map(function (c, i) {
                return '<button type="button" class="sel-cliente__item" data-i="' + i + '">' +
                    '<strong>' + App.esc(nombre(c)) + '</strong>' +
                    '<small>' + App.esc(c.codigo) + ' · ' + Number(c.puntos || 0) + ' puntos</small></button>';
            }).join('')).removeClass('d-none');
        }

        const buscar = App.debounce(function (texto) {
            const turno = ++secuencia;

            App.peticion('GET', o.urlFiltrar, { q: texto })
                .done(function (r) {
                    if (turno === secuencia) {
                        mostrarResultados(r.clientes);
                    }
                })
                .fail(function (xhr) {
                    if (turno === secuencia) {
                        mostrarResultados([], App.interpretarError(xhr).mensaje);
                    }
                });
        }, 300);

        $raiz.on('input', '.sel-cliente__buscar input', function () {
            const texto = $(this).val().trim();

            if (texto.length < 2) {
                secuencia++;
                $raiz.find('.sel-cliente__resultados').addClass('d-none').empty();

                return;
            }

            buscar(texto);
        });

        $raiz.on('keydown', '.sel-cliente__buscar input', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();

                if (resultados.length) {
                    cliente = resultados[0];
                    pintar();
                    notificar();
                }
            }
        });

        $raiz.on('click', '.sel-cliente__item', function () {
            cliente = resultados[Number($(this).data('i'))] || null;
            pintar();
            notificar();
        });

        $raiz.on('click', '.sel-cliente__quitar', function () {
            cliente = null;
            pintar();
            notificar();
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest($raiz).length) {
                $raiz.find('.sel-cliente__resultados').addClass('d-none');
            }
        });

        pintar();

        return {
            valor: function () { return cliente; },
            fijar: function (c) { cliente = c || null; pintar(); notificar(); },
            limpiar: function () { cliente = null; pintar(); notificar(); },
            bloquear: function (valor) { bloqueado = !!valor; pintar(); }
        };
    };

})(window, jQuery);
