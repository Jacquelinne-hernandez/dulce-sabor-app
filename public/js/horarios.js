$(function () {
    const $horas = $('#form-horarios [name="hora_apertura"], #form-horarios [name="hora_cierre"]');

    // Las horas solo se editan si el día es de atención.
    function alternarHoras(atiende) {
        $horas.prop('disabled', !atiende).prop('required', atiende);
    }

    $('#horarios-activo').on('change', function () {
        alternarHoras(this.checked);
    });

    App.crud({
        raiz: '#modulo-horarios',
        tabla: '#tabla-horarios tbody',
        clave: 'horarios',
        llave: 'horario',
        modal: '#modal-horarios',
        form: '#form-horarios',
        tituloNuevo: 'Editar horario',
        tituloEditar: 'Editar horario',
        alEditar: function (h) {
            $('#horarios-dia').val(h.nombre_dia);
            $('#horarios-hora_apertura').val(App.hora(h.hora_apertura));
            $('#horarios-hora_cierre').val(App.hora(h.hora_cierre));
            alternarHoras(h.activo);
        },
        fila: function (h) {
            return '<tr>' +
                '<td class="fw-semibold">' + App.esc(h.nombre_dia) + '</td>' +
                '<td>' + (h.activo
                    ? '<span class="badge text-bg-success">Atiende</span>'
                    : '<span class="badge text-bg-secondary">Cerrado</span>') + '</td>' +
                '<td>' + (h.activo ? App.hora(h.hora_apertura) : '—') + '</td>' +
                '<td>' + (h.activo ? App.hora(h.hora_cierre) : '—') + '</td>' +
                '<td class="text-end">' + App.botonesCrud(h.id, { eliminar: false }) + '</td>' +
                '</tr>';
        }
    });
});
