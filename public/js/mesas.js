$(function () {
    const $raiz = $('#modulo-mesas');
    const urlPorEstado = $raiz.data('urlEstado');
    const urlCambiarEstado = $raiz.data('urlCambiarEstado');
    const urlBuscar = $raiz.data('urlBuscar');
    const ESTADOS = ['disponible', 'reservada', 'ocupada', 'mantenimiento'];
    let idEditando = null;

    function capitalizar(texto) {
        return texto.charAt(0).toUpperCase() + texto.slice(1);
    }

    const crud = App.crud({
        raiz: '#modulo-mesas',
        tabla: '#tabla-mesas tbody',
        clave: 'mesas',
        llave: 'mesa',
        modal: '#modal-mesas',
        form: '#form-mesas',
        tituloNuevo: 'Nueva mesa',
        tituloEditar: 'Editar mesa',
        urlLista: function () {
            const estado = $('#filtro-estado-mesas').val();

            return estado ? urlPorEstado.replace('__ESTADO__', estado) : $raiz.data('urlTodos');
        },
        fila: function (m, ctx) {
            const opciones = ESTADOS.map(function (e) {
                return '<option value="' + e + '"' + (e === m.estado ? ' selected' : '') + '>' +
                    capitalizar(e) + '</option>';
            }).join('');

            return '<tr>' +
                '<td class="fw-semibold">Mesa ' + m.numero + '</td>' +
                '<td>' + m.capacidad + ' personas</td>' +
                '<td>' + App.badgeEstado(m.estado) + '</td>' +
                '<td>' +
                '<select class="form-select form-select-sm" data-cambiar-estado data-id="' + m.id + '" ' +
                'aria-label="Cambiar estado de la mesa ' + m.numero + '">' + opciones + '</select></td>' +
                '<td class="text-end">' + (ctx.puedeEditar ? App.botonesCrud(m.id, { eliminar: false }) : '') + '</td>' +
                '</tr>';
        }
    });

    // Al abrir "Nueva mesa", ocultamos el selector de estado (inicia disponible por defecto)
    $raiz.on('click', '[data-accion="nuevo"]', function () {
        idEditando = null;
        $('#grupo-mesas-estado').addClass('d-none');
        $('#mesas-estado').val('disponible');
    });

    // Al dar clic en el lápiz (editar), mostramos el selector y cargamos el estado actual de la mesa
    $('#tabla-mesas').on('click', 'button', function () {
        const $btn = $(this);
        const $fila = $btn.closest('tr');
        const $selectFila = $fila.find('[data-cambiar-estado]');
        const id = $btn.data('id') || $btn.data('editar') || $selectFila.data('id');

        if (!id) return;
        idEditando = id;
        $('#grupo-mesas-estado').removeClass('d-none');

        if ($selectFila.length) {
            $('#mesas-estado').val($selectFila.val());
        } else if (urlBuscar) {
            $.getJSON(App.url(urlBuscar, id), function (r) {
                const mesa = r.mesa || r.data || r;
                if (mesa && mesa.estado) {
                    $('#mesas-estado').val(mesa.estado);
                }
            });
        }
    });

    // Al guardar el formulario en modo edición, también actualizamos el estado mediante PATCH
    $('#form-mesas').on('submit', function () {
        if (!idEditando) return;
        const idActual = idEditando;
        const nuevoEstado = $('#mesas-estado').val();

        setTimeout(function () {
            App.peticion('PATCH', App.url(urlCambiarEstado, idActual), { estado: nuevoEstado })
                .done(function () {
                    crud.cargar();
                });
        }, 250);
    });

    // Filtro superior por estado
    $('#filtro-estado-mesas').on('change', function () {
        crud.cargar();
    });
});
