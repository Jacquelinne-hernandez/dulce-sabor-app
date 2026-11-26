/* ==========================================
   DULCE SABOR SYS - DASHBOARD (administrador)
   Gráfica diaria en SVG: ventas, compras y gastos.
   Sin librerías externas.
   ========================================== */
$(function () {
    const $g = $('#dash-grafica');

    if (!$g.length) {
        return;
    }

    const serie = ($g.data('serie') || []).map(function (d) {
        return { fecha: d.fecha, ventas: parseFloat(d.ventas) || 0, compras: parseFloat(d.compras) || 0, gastos: parseFloat(d.gastos) || 0 };
    });
    const SERIES = [
        { clave: 'ventas', nombre: 'Ventas', color: 'var(--serie-1)' },
        { clave: 'compras', nombre: 'Compras', color: 'var(--serie-2)' },
        { clave: 'gastos', nombre: 'Gastos', color: 'var(--serie-3)' }
    ];
    const NS = 'http://www.w3.org/2000/svg';

    // Tabla equivalente (también sirve como alternativa accesible).
    $('#dash-tabla-datos tbody').html(serie.map(function (d) {
        return '<tr><td>' + App.esc(App.fechaCorta(d.fecha)) + '</td>' + SERIES.map(function (s) {
            return '<td class="text-end">' + App.dinero(d[s.clave]) + '</td>';
        }).join('') + '</tr>';
    }).join(''));

    if (!serie.length || serie.every(function (d) { return !d.ventas && !d.compras && !d.gastos; })) {
        $g.html('<div class="dash-vacio">No hubo ventas, compras ni gastos en este período.</div>');

        return;
    }

    function pasoBonito(maximo) {
        const bruto = maximo / 4;
        const base = Math.pow(10, Math.floor(Math.log10(bruto)));
        const opciones = [1, 2, 2.5, 5, 10];

        for (let i = 0; i < opciones.length; i++) {
            if (opciones[i] * base >= bruto) {
                return opciones[i] * base;
            }
        }

        return 10 * base;
    }

    function etiquetaEje(v) {
        return '$' + (v >= 1000 ? (v / 1000).toLocaleString('en-US', { maximumFractionDigits: 1 }) + 'k' : String(Math.round(v * 100) / 100));
    }

    function el(nombre, atributos) {
        const nodo = document.createElementNS(NS, nombre);

        Object.keys(atributos || {}).forEach(function (k) { nodo.setAttribute(k, atributos[k]); });

        return nodo;
    }

    function dibujar() {
        const ancho = Math.max(280, Math.floor($g.width()));
        const alto = ancho < 576 ? 240 : 300;
        const m = { t: 12, r: 16, b: 30, l: ancho < 576 ? 46 : 56 };
        const w = ancho - m.l - m.r;
        const h = alto - m.t - m.b;
        const maximo = Math.max.apply(null, serie.map(function (d) { return Math.max(d.ventas, d.compras, d.gastos); }));
        const paso = pasoBonito(Math.max(maximo, 1));
        const tope = paso * Math.max(1, Math.ceil(maximo / paso));
        const n = serie.length;
        const x = function (i) { return n === 1 ? m.l + w / 2 : m.l + (w * i) / (n - 1); };
        const y = function (v) { return m.t + h - (v / tope) * h; };

        $g.empty();

        // Leyenda (siempre visible: el color nunca es la única pista).
        const $ley = $('<div class="dash-grafica__leyenda"></div>').html(SERIES.map(function (s) {
            return '<span class="dash-grafica__item"><i style="background:' + s.color + '"></i>' + s.nombre + '</span>';
        }).join(''));
        const $caja = $('<div class="dash-grafica__caja"></div>');
        const svg = el('svg', { viewBox: '0 0 ' + ancho + ' ' + alto, width: ancho, height: alto, role: 'img', 'aria-label': 'Ventas, compras y gastos por día' });

        // Cuadrícula y eje Y.
        for (let v = 0; v <= tope + 0.0001; v += paso) {
            svg.appendChild(el('line', { x1: m.l, x2: ancho - m.r, y1: y(v), y2: y(v), class: 'dash-grafica__rejilla' }));
            const t = el('text', { x: m.l - 8, y: y(v) + 4, 'text-anchor': 'end', class: 'dash-grafica__texto' });

            t.textContent = etiquetaEje(v);
            svg.appendChild(t);
        }

        // Eje X: unas 6 fechas.
        const cada = Math.max(1, Math.ceil(n / (ancho < 576 ? 4 : 7)));

        serie.forEach(function (d, i) {
            if (i % cada !== 0 && i !== n - 1) {
                return;
            }

            if (i === n - 1 && i % cada !== 0 && (n - 1 - Math.floor((n - 1) / cada) * cada) < cada / 2) {
                return;
            }

            const t = el('text', { x: x(i), y: alto - 8, 'text-anchor': n === 1 ? 'middle' : (i === 0 ? 'start' : (i === n - 1 ? 'end' : 'middle')), class: 'dash-grafica__texto' });

            t.textContent = App.fechaCorta(d.fecha).slice(0, 5);
            svg.appendChild(t);
        });

        // Líneas (con punto si hay un único día).
        SERIES.forEach(function (s) {
            if (n > 1) {
                svg.appendChild(el('path', {
                    d: serie.map(function (d, i) { return (i ? 'L' : 'M') + x(i).toFixed(1) + ' ' + y(d[s.clave]).toFixed(1); }).join(' '),
                    fill: 'none', stroke: s.color, 'stroke-width': 2, 'stroke-linejoin': 'round', 'stroke-linecap': 'round'
                }));
            } else {
                svg.appendChild(el('circle', { cx: x(0), cy: y(serie[0][s.clave]), r: 5, fill: s.color }));
            }
        });

        // Capa de interacción: línea guía y puntos.
        const guia = el('line', { y1: m.t, y2: m.t + h, class: 'dash-grafica__guia', visibility: 'hidden' });
        const puntos = SERIES.map(function (s) {
            const c = el('circle', { r: 4.5, fill: s.color, class: 'dash-grafica__punto', visibility: 'hidden' });

            svg.appendChild(c);

            return c;
        });
        const zona = el('rect', { x: m.l, y: m.t, width: w, height: h, fill: 'transparent' });

        svg.insertBefore(guia, svg.firstChild.nextSibling);
        svg.appendChild(zona);

        const $tip = $('<div class="dash-grafica__tooltip" hidden></div>');

        function mostrar(evento) {
            const caja = svg.getBoundingClientRect();
            const px = (evento.touches ? evento.touches[0].clientX : evento.clientX) - caja.left;
            const i = n === 1 ? 0 : Math.max(0, Math.min(n - 1, Math.round(((px - m.l) / w) * (n - 1))));
            const d = serie[i];

            guia.setAttribute('x1', x(i));
            guia.setAttribute('x2', x(i));
            guia.setAttribute('visibility', 'visible');
            puntos.forEach(function (c, k) {
                c.setAttribute('cx', x(i));
                c.setAttribute('cy', y(d[SERIES[k].clave]));
                c.setAttribute('visibility', 'visible');
            });

            $tip.html('<strong>' + App.esc(App.fechaCorta(d.fecha)) + '</strong>' + SERIES.map(function (s) {
                return '<div><i style="background:' + s.color + '"></i><span>' + s.nombre + '</span><b>' + App.dinero(d[s.clave]) + '</b></div>';
            }).join('')).prop('hidden', false);

            const ancho_tip = $tip.outerWidth();
            const izq = x(i) + 14 + ancho_tip > ancho ? x(i) - 14 - ancho_tip : x(i) + 14;

            $tip.css({ left: Math.max(0, izq), top: m.t + 4 });
        }

        function ocultar() {
            guia.setAttribute('visibility', 'hidden');
            puntos.forEach(function (c) { c.setAttribute('visibility', 'hidden'); });
            $tip.prop('hidden', true);
        }

        $(zona).on('mousemove touchstart touchmove', mostrar).on('mouseleave touchend', ocultar);

        $caja.append(svg, $tip);
        $g.append($ley, $caja);
    }

    dibujar();

    let anterior = $g.width();

    $(window).on('resize', App.debounce(function () {
        if (Math.abs($g.width() - anterior) > 4) {
            anterior = $g.width();
            dibujar();
        }
    }, 150));
});
