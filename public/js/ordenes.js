$(function () {
    const $raiz = $('#modulo-ordenes');
    const $contenido = $('#ordenes-contenido');
    const url = {
        activas: $raiz.data('urlActivas'),
        todos: $raiz.data('urlTodos'),
        lista: $raiz.data('urlEstadoLista'),
        actualizar: $raiz.data('urlActualizar'),
        facturas: $raiz.data('urlFacturas')
    };
    const SIGUIENTE = {
        pendiente: { estado: 'en_produccion', texto: 'Iniciar producción', icono: 'fa-fire-burner', clase: 'btn-primary' },
        en_produccion: { estado: 'lista', texto: 'Marcar como lista', icono: 'fa-check', clase: 'btn-primary' },
        lista: { estado: 'entregada', texto: 'Entregar', icono: 'fa-hand-holding-heart', clase: 'btn-success' }
    };
    const PASOS = [
        { clave: 'pendiente', texto: 'Pendiente' },
        { clave: 'en_produccion', texto: 'En producción' },
        { clave: 'lista', texto: 'Lista' },
        { clave: 'entregada', texto: 'Entregada' }
    ];
    let filtro = 'activas';
    let ordenes = [];

    function momento(o) {
        const p = o.pedido || {};

        return new Date(p.fecha_programada || p.fecha);
    }

    function hace(valor) {
        const minutos = Math.max(0, Math.round((Date.now() - new Date(valor).getTime()) / 60000));

        if (minutos < 1) {
            return 'justo ahora';
        }

        if (minutos < 60) {
            return 'hace ' + minutos + ' min';
        }

        const horas = Math.floor(minutos / 60);

        return horas < 24 ? 'hace ' + horas + ' h' : 'hace ' + Math.floor(horas / 24) + ' d';
    }

    function lugar(p) {
        if (p.tipo === 'local') {
            return p.mesa ? 'Mesa ' + p.mesa.numero : 'En el local';
        }

        return p.tipo === 'reserva' ? 'Pedido de reserva' : 'Para llevar';
    }

    function pasos(o) {
        const indice = PASOS.map(function (x) { return x.clave; }).indexOf(o.estado);

        if (indice === -1) {
            return '';
        }

        return '<div class="pasos">' + PASOS.map(function (x, i) {
            const clase = i < indice ? 'is-hecho' : (i === indice ? 'is-actual' : '');

            return (i ? '<i class="fa-solid fa-chevron-right pasos__sep"></i>' : '') +
                '<span class="pasos__paso ' + clase + '">' + x.texto + '</span>';
        }).join('') + '</div>';
    }

    function tarjeta(o) {
        const p = o.pedido || {};
        const programada = p.fecha_programada ? new Date(p.fecha_programada) : null;
        const activa = ['pendiente', 'en_produccion', 'lista'].indexOf(o.estado) !== -1;
        const atrasada = programada && activa && programada.getTime() < Date.now();
        const items = (p.detalles || []).map(function (d) {
            return '<li><span class="orden-item__cant">' + d.cantidad + '×</span> ' +
                App.esc(d.producto ? d.producto.nombre : '—') + '</li>';
        }).join('');
        const sig = SIGUIENTE[o.estado];
        let accion = '';

        if (sig) {
            accion = '<button type="button" class="btn ' + sig.clase + ' w-100" data-avanzar="' + o.id + '" ' +
                'data-estado="' + sig.estado + '"><i class="fa-solid ' + sig.icono + ' me-2"></i>' + sig.texto + '</button>';
        } else if (o.estado === 'entregada' && p.estado === 'abierto') {
            accion = '<a class="btn btn-outline-success w-100" href="' + url.facturas + '?pedido=' + p.id +
                '"><i class="fa-solid fa-file-invoice-dollar me-2"></i>Facturar</a>';
        }

        return '<article class="orden-card orden-card--' + o.estado + '">' +
            '<header class="orden-card__cabeza"><div><div class="orden-card__numero">' + App.esc(o.numero_orden) + '</div>' +
            '<div class="texto-pequeno">Pedido #' + p.id + ' · ' + hace(p.fecha) + '</div></div>' +
            App.badgeEstado(o.estado) + '</header>' +
            '<div class="orden-card__lugar"><span class="pedido-tipo pedido-tipo--' + p.tipo + '"><i class="fa-solid ' +
            App.iconoTipo(p.tipo) + '"></i>' + App.esc(lugar(p)) + '</span>' +
            '<span class="orden-card__cliente">' + App.esc(p.cliente ? p.cliente.nombre_completo : 'Consumidor final') + '</span></div>' +
            (programada ? '<div class="orden-card__programada' + (atrasada ? ' is-atrasada' : '') + '">' +
                '<i class="fa-solid fa-clock me-1"></i>Entrega: ' + App.esc(App.fechaHora(p.fecha_programada)) +
                (atrasada ? ' · <strong>atrasada</strong>' : '') + '</div>' : '') +
            '<ul class="orden-card__items">' + items + '</ul>' +
            (o.notas ? '<div class="orden-card__notas"><i class="fa-regular fa-note-sticky me-1"></i>' + App.esc(o.notas) + '</div>' : '') +
            pasos(o) +
            (accion ? '<div class="orden-card__accion">' + accion + '</div>' : '') +
            '</article>';
    }

    function pintar() {
        if (!ordenes.length) {
            $contenido.html('<div class="orden-vacio"><i class="fa-regular fa-face-smile me-2"></i>No hay órdenes para mostrar.</div>');

            return;
        }

        if (filtro !== 'activas') {
            $contenido.html('<div class="orden-grid">' + ordenes.map(tarjeta).join('') + '</div>');

            return;
        }

        const columnas = [
            { estado: 'pendiente', titulo: 'Pendientes', icono: 'fa-hourglass-half' },
            { estado: 'en_produccion', titulo: 'En producción', icono: 'fa-fire-burner' },
            { estado: 'lista', titulo: 'Listas para entregar', icono: 'fa-bell-concierge' }
        ];

        $contenido.html('<div class="orden-tablero">' + columnas.map(function (c) {
            const lista = ordenes.filter(function (o) { return o.estado === c.estado; });

            return '<section class="orden-columna orden-columna--' + c.estado + '">' +
                '<h2 class="orden-columna__titulo"><i class="fa-solid ' + c.icono + '"></i>' + c.titulo +
                '<span class="badge text-bg-secondary">' + lista.length + '</span></h2>' +
                (lista.length ? lista.map(tarjeta).join('') : '<div class="orden-columna__vacia">Sin órdenes</div>') +
                '</section>';
        }).join('') + '</div>');
    }

    function cargar(silencioso) {
        let peticion;

        if (!silencioso) {
            $contenido.html('<div class="orden-vacio"><span class="spinner-border spinner-border-sm me-2"></span>Cargando...</div>');
        }

        if (filtro === 'activas') {
            peticion = App.peticion('GET', url.activas);
        } else if (filtro === 'todas') {
            peticion = App.peticion('GET', url.todos);
        } else {
            peticion = App.peticion('GET', url.lista, { estado: filtro });
        }

        return peticion
            .done(function (r) {
                ordenes = r.ordenes || [];
                pintar();
            })
            .fail(function (xhr) {
                if (!silencioso) {
                    $contenido.html('<div class="orden-vacio text-danger">No se pudieron cargar las órdenes.</div>');
                    App.notificarError(xhr);
                }
            });
    }

    $('#ordenes-filtros').on('click', '[data-filtro-orden]', function () {
        filtro = $(this).data('filtroOrden');
        $('#ordenes-filtros .btn').removeClass('is-activo');
        $(this).addClass('is-activo');
        cargar();
    });

    $('#ordenes-refrescar').on('click', function () { cargar(); });

    $contenido.on('click', '[data-avanzar]', function () {
        const $boton = $(this);
        const id = $boton.data('avanzar');

        App.cargando($boton, true);

        App.peticion('PATCH', App.url(url.actualizar, id), { estado: $boton.data('estado') })
            .done(function (r) {
                App.notificar('success', r.mensaje || 'Orden actualizada.');
                cargar(true);
            })
            .fail(function (xhr) {
                App.cargando($boton, false);
                App.notificarError(xhr);
                cargar(true);
            });
    });

    // El tablero se mantiene al día sin recargar la página.
    setInterval(function () {
        if (!document.hidden && (filtro === 'activas' || filtro === 'todas')) {
            cargar(true);
        }
    }, 60000);

    cargar();
});
