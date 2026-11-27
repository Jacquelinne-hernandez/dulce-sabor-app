$(function () {
    const $raiz = $('#modulo-catalogo');
    const $grid = $('#catalogo-grid');

    // La búsqueda, el filtro y la paginación los resuelve el servidor
    // (productos.catalogo.todos): el navegador solo recibe una página.
    const TAMANOS = [12, 24, 48];
    const estado = { pagina: 1, tamano: TAMANOS[0] };

    let secuencia = 0;
    let peticion = null;
    let temporizador = null;

    const $pie = $(
        '<div class="paginacion d-none" id="catalogo-paginacion">' +
            '<div class="paginacion__info" aria-live="polite"></div>' +
            '<div class="paginacion__controles">' +
                '<label class="paginacion__tamano">Productos ' +
                    '<select class="form-select form-select-sm" aria-label="Productos por página">' +
                    TAMANOS.map(function (n) { return '<option value="' + n + '">' + n + '</option>'; }).join('') +
                    '</select></label>' +
                '<nav aria-label="Paginación del catálogo"><ul class="pagination pagination-sm mb-0"></ul></nav>' +
            '</div>' +
        '</div>'
    );

    $grid.after($pie);

    function nivel(p) {
        if (p.estado === 'agotado' || p.stock <= 0) {
            return 'agotado';
        }

        return p.stock <= p.stock_minimo ? 'bajo' : 'disponible';
    }

    function tarjeta(p) {
        const n = nivel(p);
        const etiqueta = { disponible: 'Disponible', bajo: 'Stock bajo', agotado: 'Agotado' }[n];
        const color = { disponible: 'success', bajo: 'warning', agotado: 'danger' }[n];
        const insignia = '<span class="badge text-bg-' + color + '">' + etiqueta + '</span>';

        // Con foto: la imagen encabezada por la insignia. Sin foto: el icono de siempre.
        const cabeza = p.imagen_url
            ? '<div class="cat-tarjeta__foto"><img src="' + App.esc(p.imagen_url) + '" alt="' + App.esc(p.nombre) +
              '" loading="lazy">' + insignia + '</div>'
            : '<div class="cat-tarjeta__cabeza"><span class="cat-tarjeta__icono"><i class="fa-solid fa-cake-candles"></i></span>' +
              insignia + '</div>';

        return '<article class="cat-tarjeta cat-tarjeta--' + n + '">' + cabeza +
            '<h2 class="cat-tarjeta__nombre">' + App.esc(p.nombre) + '</h2>' +
            '<div class="cat-tarjeta__categoria">' + App.esc(p.categoria ? p.categoria.nombre : 'Sin categoría') + '</div>' +
            (p.descripcion ? '<p class="cat-tarjeta__descripcion">' + App.esc(p.descripcion) + '</p>' : '') +
            '<div class="cat-tarjeta__pie"><div><span class="cat-tarjeta__etiqueta">Precio</span>' +
            '<span class="cat-tarjeta__precio">' + App.dinero(p.precio) + '</span></div>' +
            '<div class="text-end"><span class="cat-tarjeta__etiqueta">Existencias</span>' +
            '<span class="cat-tarjeta__stock">' + Number(p.stock) + '</span></div></div>' +
            '</article>';
    }

    // Si una foto no carga, la tarjeta vuelve al icono (nunca queda un hueco roto).
    $grid[0].addEventListener('error', function (e) {
        const img = e.target;

        if (!img || img.tagName !== 'IMG') {
            return;
        }

        $(img).closest('.cat-tarjeta__foto').addClass('cat-tarjeta__foto--vacia');
        $(img).replaceWith('<i class="fa-solid fa-cake-candles cat-tarjeta__foto-icono"></i>');
    }, true);

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

        $pie.find('.paginacion__info').text(
            'Mostrando ' + pag.desde + '–' + pag.hasta + ' de ' + pag.total + ' productos'
        );
        $pie.find('select').val(String(estado.tamano));

        const actual = pag.pagina;
        let html = '<li class="page-item' + (actual === 1 ? ' disabled' : '') + '">' +
            '<button type="button" class="page-link" data-pagina="' + (actual - 1) + '" aria-label="Anterior">' +
            '<i class="fa-solid fa-chevron-left"></i></button></li>';

        numerosDePagina(actual, pag.ultima).forEach(function (n) {
            if (n === null) {
                html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
            } else {
                html += '<li class="page-item' + (n === actual ? ' active' : '') + '">' +
                    '<button type="button" class="page-link" data-pagina="' + n + '"' +
                    (n === actual ? ' aria-current="page"' : '') + '>' + n + '</button></li>';
            }
        });

        html += '<li class="page-item' + (actual === pag.ultima ? ' disabled' : '') + '">' +
            '<button type="button" class="page-link" data-pagina="' + (actual + 1) + '" aria-label="Siguiente">' +
            '<i class="fa-solid fa-chevron-right"></i></button></li>';

        $pie.find('.pagination').html(html);
    }

    function pintar(lista, pag) {
        $('#catalogo-total').text(pag.total + (pag.total === 1 ? ' producto' : ' productos'));

        $grid.html(lista.length
            ? lista.map(tarjeta).join('')
            : '<div class="cat-vacio">No hay productos para mostrar.</div>');

        pintarPie(pag);
    }

    function cargar() {
        const mia = ++secuencia;

        if (peticion) {
            peticion.abort();
        }

        $grid.attr('aria-busy', 'true');

        peticion = App.peticion('GET', $raiz.data('urlTodos'), {
            q: $('#catalogo-buscar').val().trim(),
            disponibilidad: $('#catalogo-disponibilidad').val(),
            pagina: estado.pagina,
            por_pagina: estado.tamano
        })
            .done(function (r) {
                if (mia !== secuencia) {
                    return;
                }

                const pag = r.paginacion || { pagina: 1, ultima: 1, total: 0, desde: 0, hasta: 0 };

                // Si la página pedida ya no existe (por ejemplo, tras filtrar), se pasa a la última.
                if (pag.ultima > 0 && estado.pagina > pag.ultima) {
                    estado.pagina = pag.ultima;
                    cargar();

                    return;
                }

                $grid.attr('aria-busy', 'false');
                pintar(r.productos || [], pag);
            })
            .fail(function (xhr, texto) {
                if (texto === 'abort' || mia !== secuencia) {
                    return;
                }

                $grid.attr('aria-busy', 'false');
                $grid.html('<div class="cat-vacio text-danger">No se pudo cargar el catálogo.</div>');
                $pie.addClass('d-none');
                App.notificarError(xhr);
            });
    }

    $pie.on('click', '[data-pagina]', function (e) {
        e.preventDefault();

        const destino = parseInt($(this).attr('data-pagina'), 10);

        if (!isNaN(destino) && destino !== estado.pagina) {
            estado.pagina = destino;
            cargar();

            const caja = $raiz[0].getBoundingClientRect();

            if (caja.top < 0) {
                $raiz[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    });

    $pie.on('change', 'select', function () {
        estado.tamano = parseInt(this.value, 10) || TAMANOS[0];
        estado.pagina = 1;
        cargar();
    });

    $('#catalogo-buscar').on('input', function () {
        clearTimeout(temporizador);
        temporizador = setTimeout(function () {
            estado.pagina = 1;
            cargar();
        }, 300);
    });

    $('#catalogo-disponibilidad').on('change', function () {
        estado.pagina = 1;
        cargar();
    });

    cargar();
});
