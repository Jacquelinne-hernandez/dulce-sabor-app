/*
 * paginacion.js
 * Paginación de todas las tablas de listado (<table id="tabla-...">).
 * Funciona sobre las filas que ya pintó cada vista: respeta el buscador
 * (las filas ocultas por el filtro no cuentan) y se actualiza sola cuando
 * la tabla se vuelve a cargar. Para excluir una tabla: data-sin-paginar.
 * Para una tabla con otro id: App.paginar('#mi-tabla').
 */
(function (window, $) {
    'use strict';

    const App = window.App || (window.App = {});

    const TAMANOS = [10, 25, 50, 100];
    const VENTANA_REINICIO = 1200; // ms tras un cambio de filtro del usuario

    let reinicioHasta = 0;

    function esFilaDeDatos(tr) {
        // Las filas de "Cargando..." / "No hay registros" usan colspan.
        return !tr.querySelector('td[colspan]');
    }

    function Paginador(tabla) {
        const self = this;

        self.tabla = tabla;
        self.cuerpo = tabla.tBodies[0];
        self.pagina = 1;
        self.tamano = TAMANOS[0];
        self.programado = null;

        const $tabla = $(tabla);
        const $envoltura = $tabla.closest('.table-responsive');

        self.$pie = $(
            '<div class="paginacion d-none">' +
                '<div class="paginacion__info" aria-live="polite"></div>' +
                '<div class="paginacion__controles">' +
                    '<label class="paginacion__tamano">Filas ' +
                        '<select class="form-select form-select-sm" aria-label="Filas por página">' +
                        TAMANOS.map(function (n) { return '<option value="' + n + '">' + n + '</option>'; }).join('') +
                        '</select></label>' +
                    '<nav aria-label="Paginación de la tabla"><ul class="pagination pagination-sm mb-0"></ul></nav>' +
                '</div>' +
            '</div>'
        );

        ($envoltura.length ? $envoltura : $tabla).after(self.$pie);

        self.$pie.on('click', '[data-pagina]', function (e) {
            e.preventDefault();

            const destino = parseInt($(this).attr('data-pagina'), 10);

            if (!isNaN(destino) && destino !== self.pagina) {
                self.pagina = destino;
                self.pintar();
                self.verInicio();
            }
        });

        self.$pie.on('change', 'select', function () {
            self.tamano = parseInt(this.value, 10) || TAMANOS[0];
            self.pagina = 1;
            self.pintar();
        });

        // Cualquier cambio en el cuerpo (filas nuevas o filtradas) recalcula.
        new MutationObserver(function () { self.programar(); }).observe(self.cuerpo, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style']
        });

        self.pintar();
    }

    Paginador.prototype.programar = function () {
        const self = this;

        if (self.programado) {
            return;
        }

        self.programado = setTimeout(function () {
            self.programado = null;
            self.pintar();
        }, 0);
    };

    Paginador.prototype.verInicio = function () {
        const tarjeta = this.tabla.closest('.card') || this.tabla;
        const caja = tarjeta.getBoundingClientRect();

        if (caja.top < 0) {
            tarjeta.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    Paginador.prototype.pintar = function () {
        const filas = Array.prototype.filter.call(this.cuerpo.children, function (n) { return n.tagName === 'TR'; });
        const datos = filas.filter(esFilaDeDatos);
        const visibles = datos.filter(function (tr) { return tr.style.display !== 'none'; });
        const total = visibles.length;
        const paginas = Math.max(1, Math.ceil(total / this.tamano));

        if (Date.now() < reinicioHasta) {
            this.pagina = 1;
        }

        if (this.pagina > paginas) {
            this.pagina = paginas;
        }

        const inicio = (this.pagina - 1) * this.tamano;
        const fin = inicio + this.tamano;

        datos.forEach(function (tr) { tr.classList.remove('pag-oculta'); });
        visibles.forEach(function (tr, i) {
            if (i < inicio || i >= fin) {
                tr.classList.add('pag-oculta');
            }
        });

        this.actualizarPie(total, paginas, inicio, Math.min(fin, total));
    };

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

    Paginador.prototype.actualizarPie = function (total, paginas, inicio, fin) {
        const hay = total > TAMANOS[0];

        this.$pie.toggleClass('d-none', !hay);

        if (!hay) {
            return;
        }

        this.$pie.find('.paginacion__info').text(
            'Mostrando ' + (inicio + 1) + '–' + fin + ' de ' + total + ' registros'
        );
        this.$pie.find('select').val(String(this.tamano));

        const actual = this.pagina;
        let html = '<li class="page-item' + (actual === 1 ? ' disabled' : '') + '">' +
            '<button type="button" class="page-link" data-pagina="' + (actual - 1) + '" aria-label="Anterior">' +
            '<i class="fa-solid fa-chevron-left"></i></button></li>';

        numerosDePagina(actual, paginas).forEach(function (n) {
            if (n === null) {
                html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
            } else {
                html += '<li class="page-item' + (n === actual ? ' active' : '') + '">' +
                    '<button type="button" class="page-link" data-pagina="' + n + '"' +
                    (n === actual ? ' aria-current="page"' : '') + '>' + n + '</button></li>';
            }
        });

        html += '<li class="page-item' + (actual === paginas ? ' disabled' : '') + '">' +
            '<button type="button" class="page-link" data-pagina="' + (actual + 1) + '" aria-label="Siguiente">' +
            '<i class="fa-solid fa-chevron-right"></i></button></li>';

        this.$pie.find('.pagination').html(html);
    };

    /* ------------------------------------------------------------------
     * Inicio
     * ------------------------------------------------------------------ */
    function iniciar(tabla) {
        if (!tabla || !tabla.tBodies.length || tabla.getAttribute('data-paginada')) {
            return;
        }

        tabla.setAttribute('data-paginada', '1');
        $(tabla).data('paginador', new Paginador(tabla));
    }

    // Un cambio de búsqueda o de filtro hecho por el usuario vuelve a la página 1.
    function marcarReinicio(e) {
        const $o = $(e.target);

        if ($o.closest('.modal, table, .paginacion').length) {
            return;
        }

        reinicioHasta = Date.now() + VENTANA_REINICIO;
    }

    document.addEventListener('input', marcarReinicio, true);
    document.addEventListener('change', marcarReinicio, true);
    document.addEventListener('click', function (e) {
        if ($(e.target).closest('.filtro-rapido, [data-filtro-rapido], .btn-group, .nav-pills').length) {
            marcarReinicio(e);
        }
    }, true);

    App.paginar = function (selector) {
        $(selector).each(function () { iniciar(this); });
    };

    App.refrescarPaginacion = function () {
        $('table[data-paginada]').each(function () {
            const p = $(this).data('paginador');

            if (p) { p.pintar(); }
        });
    };

    $(function () {
        $('table[id^="tabla-"]:not([data-sin-paginar])').each(function () { iniciar(this); });
    });
})(window, jQuery);
