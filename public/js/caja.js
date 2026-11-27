/*
 * caja.js
 *  - En Compras, Gastos, Ingresos e Inicio: insignia "Dinero disponible" (se
 *    actualiza sola después de cada operación) y aviso si falta el capital inicial.
 *  - En la página Caja: resumen y estado de cuenta paginado desde el servidor.
 * Solo se carga para el administrador.
 */
// document.currentScript solo existe mientras se carga el archivo.
const SCRIPT_CAJA = document.currentScript;

$(function () {
    const urlEstado = SCRIPT_CAJA ? SCRIPT_CAJA.getAttribute('data-url-estado') : null;
    const urlCaja = SCRIPT_CAJA ? SCRIPT_CAJA.getAttribute('data-url-caja') : null;
    const urlIngresos = SCRIPT_CAJA ? SCRIPT_CAJA.getAttribute('data-url-ingresos') : null;

    const ETIQUETAS_INGRESO = {
        capital_inicial: 'Capital inicial', inversion: 'Inversión', aporte_socio: 'Aporte de socio',
        prestamo: 'Préstamo', donacion: 'Donación', otros: 'Otros'
    };
    const ETIQUETAS_GASTO = {
        arrendamiento: 'Arrendamiento', luz: 'Luz', agua: 'Agua', internet: 'Internet',
        salarios: 'Salarios', mantenimiento: 'Mantenimiento', otros: 'Otros'
    };

    function negativo(valor) {
        return Number(valor) < 0;
    }

    /* ------------------------------------------------------------------
     * Insignia en otras páginas
     * ------------------------------------------------------------------ */
    const $raizInsignia = $('#modulo-compras, #modulo-gastos, #modulo-ingresos, #modulo-dashboard').first();

    if ($raizInsignia.length && urlEstado) {
        const esIngresos = $raizInsignia.is('#modulo-ingresos');
        const $chip = $(
            '<div class="caja-chip" id="caja-chip" role="status">' +
                '<span class="caja-chip__icono"><i class="fa-solid fa-wallet"></i></span>' +
                '<span class="caja-chip__texto"><span class="caja-chip__titulo">Dinero disponible</span>' +
                '<strong class="caja-chip__valor">…</strong></span>' +
                (urlCaja ? '<a class="caja-chip__enlace" href="' + urlCaja + '">Ver caja</a>' : '') +
            '</div>'
        );
        const $aviso = $(
            '<div class="alert alert-warning caja-aviso-global d-none" role="alert">' +
                '<i class="fa-solid fa-triangle-exclamation me-2"></i>' +
                'La empresa aún no tiene <strong>capital inicial</strong>. ' +
                (esIngresos
                    ? '<button type="button" class="btn btn-sm btn-warning ms-1" data-registrar-capital>Registrar capital inicial</button>'
                    : 'Regístrelo en <a class="alert-link" href="' + (urlIngresos || '#') + '">Ingresos</a> (tipo «Capital inicial») para poder comprar, pagar a proveedores y registrar gastos.') +
            '</div>'
        );

        // Se coloca justo debajo del encabezado de la página.
        $raizInsignia.children().first().after($aviso).after($chip);

        const refrescar = function () {
            $.ajax({ url: urlEstado, method: 'GET', dataType: 'json' }).done(function (r) {
                const c = r.caja || {};

                $chip.find('.caja-chip__valor').text(App.dinero(c.disponible));
                $chip.toggleClass('caja-chip--negativa', negativo(c.disponible));
                $aviso.toggleClass('d-none', !!c.iniciada);
            });
        };

        refrescar();

        // Cualquier operación que guarda algo puede mover el dinero.
        $(document).ajaxSuccess(function (e, xhr, opciones) {
            const metodo = String(opciones.type || opciones.method || 'GET').toUpperCase();

            if (metodo !== 'GET' && opciones.url !== urlEstado) {
                refrescar();
            }
        });

        // En Ingresos, el aviso abre el formulario ya con "Capital inicial".
        $aviso.on('click', '[data-registrar-capital]', function () {
            $('#btn-nuevo-ingreso').trigger('click');
            setTimeout(function () {
                $('#ingresos-tipo').val('capital_inicial').trigger('change');
            }, 200);
        });
    }

    /* ------------------------------------------------------------------
     * Página Caja
     * ------------------------------------------------------------------ */
    const $raiz = $('#modulo-caja');

    if (!$raiz.length) {
        return;
    }

    const TAMANOS = [10, 25, 50];
    const estado = { pagina: 1, tamano: 25 };
    const $cuerpo = $('#tabla-caja tbody');
    let secuencia = 0;

    const $pie = $(
        '<div class="paginacion d-none" id="caja-paginacion">' +
            '<div class="paginacion__info" aria-live="polite"></div>' +
            '<div class="paginacion__controles">' +
                '<label class="paginacion__tamano">Filas ' +
                    '<select class="form-select form-select-sm" aria-label="Filas por página">' +
                    TAMANOS.map(function (n) { return '<option value="' + n + '">' + n + '</option>'; }).join('') +
                    '</select></label>' +
                '<nav aria-label="Paginación del estado de cuenta"><ul class="pagination pagination-sm mb-0"></ul></nav>' +
            '</div>' +
        '</div>'
    );

    $('#tabla-caja').closest('.table-responsive').after($pie);

    function concepto(m) {
        const marca = function (texto) {
            return '<span class="caja-origen caja-origen--' + m.origen + '"><span class="caja-origen__punto"></span>' + App.esc(texto) + '</span>';
        };

        if (m.origen === 'ingreso') {
            return marca('Ingreso: ' + (ETIQUETAS_INGRESO[m.tipo] || m.tipo));
        }

        if (m.origen === 'gasto') {
            return marca('Gasto: ' + (ETIQUETAS_GASTO[m.tipo] || m.tipo));
        }

        if (m.origen === 'cobro') {
            return marca('Cobro de venta (' + (m.tipo === 'tarjeta' ? 'tarjeta' : 'efectivo') + ')');
        }

        return marca('Pago a proveedor');
    }

    function detalle(m) {
        if (m.origen === 'cobro') {
            return 'Factura ' + (m.descripcion || '');
        }

        if (m.origen === 'pago_compra') {
            return (m.descripcion || '') + (m.tipo ? ' · ' + m.tipo : '');
        }

        return m.descripcion || '—';
    }

    function numerosDePagina(actual, total) {
        const lista = [];
        const incluir = {};

        if (total <= 7) {
            for (let n = 1; n <= total; n++) { lista.push(n); }

            return lista;
        }

        [1, total, actual - 1, actual, actual + 1].forEach(function (n) {
            if (n >= 1 && n <= total) { incluir[n] = true; }
        });

        let anterior = 0;

        Object.keys(incluir).map(Number).sort(function (a, b) { return a - b; }).forEach(function (n) {
            if (anterior && n - anterior > 1) { lista.push(null); }
            lista.push(n);
            anterior = n;
        });

        return lista;
    }

    function pintarPie(pag) {
        const hay = pag.total > TAMANOS[0];

        $pie.toggleClass('d-none', !hay);

        if (!hay) {
            return;
        }

        $pie.find('.paginacion__info').text('Mostrando ' + pag.desde + '–' + pag.hasta + ' de ' + pag.total + ' movimientos');
        $pie.find('select').val(String(estado.tamano));

        const actual = pag.pagina;
        let html = '<li class="page-item' + (actual === 1 ? ' disabled' : '') + '">' +
            '<button type="button" class="page-link" data-pagina="' + (actual - 1) + '" aria-label="Anterior"><i class="fa-solid fa-chevron-left"></i></button></li>';

        numerosDePagina(actual, pag.ultima).forEach(function (n) {
            html += n === null
                ? '<li class="page-item disabled"><span class="page-link">…</span></li>'
                : '<li class="page-item' + (n === actual ? ' active' : '') + '"><button type="button" class="page-link" data-pagina="' + n + '"' +
                  (n === actual ? ' aria-current="page"' : '') + '>' + n + '</button></li>';
        });

        html += '<li class="page-item' + (actual === pag.ultima ? ' disabled' : '') + '">' +
            '<button type="button" class="page-link" data-pagina="' + (actual + 1) + '" aria-label="Siguiente"><i class="fa-solid fa-chevron-right"></i></button></li>';

        $pie.find('.pagination').html(html);
    }

    function pintarResumen(c) {
        $('#caja-disponible').text(App.dinero(c.disponible)).closest('.dato-clave').toggleClass('caja-negativa', negativo(c.disponible));
        $('#caja-capital').text(c.iniciada ? App.dinero(c.capital_inicial) : 'Sin registrar');
        $('#caja-por-pagar').text(App.dinero(c.por_pagar));
        $('#caja-ingresos').text(App.dinero(c.ingresos));
        $('#caja-cobros').text(App.dinero(c.cobros));
        $('#caja-pagos').text(App.dinero(c.pagos_proveedores));
        $('#caja-gastos').text(App.dinero(c.gastos));
        $('#caja-aviso').toggleClass('d-none', !!c.iniciada);
    }

    function cargar() {
        const mia = ++secuencia;

        $.ajax({
            url: $raiz.data('urlMovimientos'),
            method: 'GET',
            dataType: 'json',
            data: { pagina: estado.pagina, por_pagina: estado.tamano }
        }).done(function (r) {
            if (mia !== secuencia) {
                return;
            }

            const pag = r.paginacion;

            if (pag.ultima > 0 && estado.pagina > pag.ultima) {
                estado.pagina = pag.ultima;
                cargar();

                return;
            }

            pintarResumen(r.caja || {});

            $cuerpo.html(r.movimientos.length ? r.movimientos.map(function (m) {
                return '<tr>' +
                    '<td class="text-nowrap">' + App.esc(App.fechaCorta(m.fecha)) + '</td>' +
                    '<td>' + concepto(m) + '</td>' +
                    '<td>' + App.esc(detalle(m)) + '</td>' +
                    '<td class="text-end texto-monto caja-entrada">' + (m.entrada ? App.dinero(m.entrada) : '') + '</td>' +
                    '<td class="text-end texto-monto caja-salida">' + (m.salida ? App.dinero(m.salida) : '') + '</td>' +
                    '<td class="text-end texto-monto caja-saldo">' + App.dinero(m.saldo) + '</td></tr>';
            }).join('') : '<tr><td colspan="6" class="text-center text-muted py-4">Aún no hay movimientos. Registre el capital inicial para comenzar.</td></tr>');

            pintarPie(pag);
        }).fail(function (xhr) {
            if (mia !== secuencia) {
                return;
            }

            $cuerpo.html('<tr><td colspan="6" class="text-center text-danger py-4">No se pudo cargar el estado de cuenta.</td></tr>');
            App.notificarError(xhr);
        });
    }

    $pie.on('click', '[data-pagina]', function (e) {
        e.preventDefault();

        const destino = parseInt($(this).attr('data-pagina'), 10);

        if (!isNaN(destino) && destino !== estado.pagina) {
            estado.pagina = destino;
            cargar();
        }
    });

    $pie.on('change', 'select', function () {
        estado.tamano = parseInt(this.value, 10) || 25;
        estado.pagina = 1;
        cargar();
    });

    cargar();
});
