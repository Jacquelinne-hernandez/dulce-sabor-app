$(function () {
    const $raiz = $('#modulo-catalogo');
    const $grid = $('#catalogo-grid');
    let productos = [];

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

        return '<article class="cat-tarjeta cat-tarjeta--' + n + '">' +
            '<div class="cat-tarjeta__cabeza"><span class="cat-tarjeta__icono"><i class="fa-solid fa-cake-candles"></i></span>' +
            '<span class="badge text-bg-' + color + '">' + etiqueta + '</span></div>' +
            '<h2 class="cat-tarjeta__nombre">' + App.esc(p.nombre) + '</h2>' +
            '<div class="cat-tarjeta__categoria">' + App.esc(p.categoria ? p.categoria.nombre : 'Sin categoría') + '</div>' +
            (p.descripcion ? '<p class="cat-tarjeta__descripcion">' + App.esc(p.descripcion) + '</p>' : '') +
            '<div class="cat-tarjeta__pie"><div><span class="cat-tarjeta__etiqueta">Precio</span>' +
            '<span class="cat-tarjeta__precio">' + App.dinero(p.precio) + '</span></div>' +
            '<div class="text-end"><span class="cat-tarjeta__etiqueta">Existencias</span>' +
            '<span class="cat-tarjeta__stock">' + Number(p.stock) + '</span></div></div>' +
            '</article>';
    }

    function pintar() {
        const texto = $('#catalogo-buscar').val().trim().toLowerCase();
        const filtro = $('#catalogo-disponibilidad').val();

        const lista = productos.filter(function (p) {
            const coincide = !texto ||
                p.nombre.toLowerCase().indexOf(texto) !== -1 ||
                (p.categoria && p.categoria.nombre.toLowerCase().indexOf(texto) !== -1);

            return coincide && (!filtro || nivel(p) === filtro);
        });

        $('#catalogo-total').text(lista.length + (lista.length === 1 ? ' producto' : ' productos'));

        $grid.html(lista.length
            ? lista.map(tarjeta).join('')
            : '<div class="cat-vacio">No hay productos para mostrar.</div>');
    }

    App.peticion('GET', $raiz.data('urlTodos'))
        .done(function (r) {
            productos = r.productos || [];
            pintar();
        })
        .fail(function (xhr) {
            $grid.html('<div class="cat-vacio text-danger">No se pudo cargar el catálogo.</div>');
            App.notificarError(xhr);
        });

    $('#catalogo-buscar').on('input', pintar);
    $('#catalogo-disponibilidad').on('change', pintar);
});
