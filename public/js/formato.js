/*
 * formato.js
 * Formato y validación de campos EN TIEMPO REAL, para todos los formularios.
 * Se carga después de extras.js en el layout. Detecta el tipo de cada campo
 * por su atributo "name" (o por data-formato) y:
 *   - da formato mientras se escribe (guion del teléfono, grupos de la tarjeta...)
 *   - impide caracteres no válidos y avisa con un mensaje breve bajo el campo
 *   - corta cuando se llega al máximo (dígitos, decimales, longitud)
 * Para excluir un campo: data-sin-formato.
 * Para forzar un tipo: data-formato="telefono|correo|persona|tarjeta|vencimiento|cvv|dinero|entero|texto".
 */
(function (window, $) {
    'use strict';

    const App = window.App || (window.App = {});

    const LETRAS = /[^\p{L}\s.'’-]/gu;
    const CORREO = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

    // Cantidad máxima de dígitos de los campos enteros.
    const DIGITOS_ENTERO = {
        puntos_canjeados: 6, stock: 6, stock_minimo: 6,
        cantidad_personas: 3, numero: 3, capacidad: 3
    };

    const NOMBRES_DINERO = ['monto', 'precio', 'costo', 'tarifa_hora', 'efectivo_recibido', 'abono_inicial'];
    const FORMULARIOS_PERSONA = ['form-clientes', 'form-usuarios'];

    /* ------------------------------------------------------------------
     * Tipo de cada campo
     * ------------------------------------------------------------------ */
    function tipoDe(el) {
        if (!el) {
            return null;
        }

        const t = (el.type || '').toLowerCase();

        if (t === 'password' && el.name === 'tarjeta_cvv') {
            return 'cvv';
        }

        if (['password', 'file', 'date', 'time', 'datetime-local', 'search', 'radio', 'checkbox',
            'hidden', 'button', 'submit', 'range', 'color'].indexOf(t) !== -1) {
            return null;
        }

        if (el.hasAttribute('data-sin-formato') || el.readOnly) {
            return null;
        }

        const explicito = el.getAttribute('data-formato');
        if (explicito) {
            return explicito;
        }

        const n = el.name || '';
        const formulario = el.form ? el.form.id : '';

        if (n === 'telefono') return 'telefono';
        if (n === 'correo' || n === 'email') return 'correo';
        if (n === 'tarjeta_numero') return 'tarjeta';
        if (n === 'tarjeta_vencimiento') return 'vencimiento';
        if (n === 'tarjeta_cvv') return 'cvv';
        if (n === 'tarjeta_titular' || n === 'nombre_contacto') return 'persona';
        if ((n === 'nombre' || n === 'apellido') && FORMULARIOS_PERSONA.indexOf(formulario) !== -1) return 'persona';
        if (NOMBRES_DINERO.indexOf(n) !== -1) return 'dinero';
        if (DIGITOS_ENTERO[n]) return 'entero';

        return 'texto';
    }

    /* ------------------------------------------------------------------
     * Aviso breve bajo el campo
     * ------------------------------------------------------------------ */
    function referencia($campo) {
        const $grupo = $campo.closest('.input-group');

        return $grupo.length ? $grupo : $campo;
    }

    function aviso($campo, mensaje) {
        const $ref = referencia($campo);

        $ref.nextAll('.formato-aviso').first().remove();

        const $a = $('<div class="formato-aviso" role="status"></div>')
            .append('<i class="fa-solid fa-triangle-exclamation me-1"></i>')
            .append(document.createTextNode(mensaje));

        $ref.after($a);

        $campo.removeClass('formato-sacudir');
        void $campo[0].offsetWidth;
        $campo.addClass('formato-sacudir');

        clearTimeout($ref.data('formatoTemporizador'));
        $ref.data('formatoTemporizador', setTimeout(function () {
            $a.fadeOut(180, function () { $a.remove(); });
        }, 2600));
    }

    function quitarAviso($campo) {
        const $ref = referencia($campo);

        clearTimeout($ref.data('formatoTemporizador'));
        $ref.nextAll('.formato-aviso').first().remove();
    }

    /* ------------------------------------------------------------------
     * Formateadores: devuelven { valor, aviso }
     * ------------------------------------------------------------------ */
    function fmtTelefono(v) {
        let d = v.replace(/\D/g, '');
        let mensaje = null;

        if (/[^\d\s\-+()]/.test(v)) {
            mensaje = 'El teléfono solo lleva números.';
        }

        // Pegado con el prefijo del país (+503 7777-8888).
        if (d.length > 8 && d.indexOf('503') === 0) {
            d = d.slice(3);
        }

        if (d.length > 8) {
            d = d.slice(0, 8);
            mensaje = 'El teléfono tiene 8 dígitos.';
        }

        return { valor: d.length > 4 ? d.slice(0, 4) + '-' + d.slice(4) : d, aviso: mensaje };
    }

    function fmtTarjeta(v) {
        let d = v.replace(/\D/g, '');
        let mensaje = null;

        if (/[^\d\s]/.test(v)) {
            mensaje = 'El número de tarjeta solo lleva dígitos.';
        }

        if (d.length > 16) {
            d = d.slice(0, 16);
            mensaje = 'La tarjeta tiene 16 dígitos.';
        }

        return { valor: d.replace(/(.{4})(?=.)/g, '$1 '), aviso: mensaje };
    }

    function fmtVencimiento(v, el, borrando) {
        let d = v.replace(/\D/g, '');
        let mensaje = null;

        if (/[^\d/]/.test(v)) {
            mensaje = 'El vencimiento se escribe con números (MM/AA).';
        }

        if (d.length > 4) {
            // Pegado con año de 4 cifras (12/2028): se toman las 2 últimas.
            d = d.length === 6 ? d.slice(0, 2) + d.slice(4) : d.slice(0, 4);
            mensaje = mensaje || 'El vencimiento se escribe como MM/AA.';
        }

        // Un mes que empieza en 2-9 es de un solo dígito: 5 -> 05
        if (d.length >= 1 && Number(d[0]) > 1) {
            d = '0' + d;
            d = d.slice(0, 4);
        }

        if (d.length >= 2 && (Number(d.slice(0, 2)) > 12 || d.slice(0, 2) === '00')) {
            d = (d.slice(0, 2) === '00' ? '01' : '12') + d.slice(2);
            mensaje = 'El mes debe estar entre 01 y 12.';
        }

        if (d.length > 2) {
            return { valor: d.slice(0, 2) + '/' + d.slice(2), aviso: mensaje };
        }

        return { valor: d.length === 2 && !borrando ? d + '/' : d, aviso: mensaje };
    }

    function fmtCvv(v) {
        let d = v.replace(/\D/g, '');
        let mensaje = null;

        if (/\D/.test(v)) {
            mensaje = 'El CVV solo lleva números.';
        }

        if (d.length > 4) {
            d = d.slice(0, 4);
            mensaje = 'El CVV tiene 3 o 4 dígitos.';
        }

        return { valor: d, aviso: mensaje };
    }

    function fmtCorreo(v) {
        const limpio = v.replace(/\s+/g, '').toLowerCase();

        return { valor: limpio, aviso: /\s/.test(v) ? 'El correo no lleva espacios.' : null };
    }

    function fmtPersona(v) {
        let valor = v.replace(LETRAS, '');
        const mensaje = valor !== v ? 'Solo se permiten letras (sin números ni símbolos).' : null;

        valor = valor.replace(/^\s+/, '').replace(/\s{2,}/g, ' ');

        return { valor: valor, aviso: mensaje };
    }

    function fmtTexto(v, el) {
        let valor = v.replace(/^\s+/, '');

        if (el.tagName !== 'TEXTAREA') {
            valor = valor.replace(/\s{2,}/g, ' ');
        }

        return { valor: valor, aviso: null };
    }

    function fmtDinero(v, el) {
        let mensaje = null;
        let s = v.replace(',', '.');

        if (s.indexOf('-') !== -1) {
            mensaje = 'No se permiten valores negativos.';
        } else if (/[^\d.]/.test(s)) {
            mensaje = 'Solo números y punto decimal (ej. 12.50).';
        }

        s = s.replace(/[^\d.]/g, '');

        const i = s.indexOf('.');
        if (i >= 0) {
            s = s.slice(0, i + 1) + s.slice(i + 1).replace(/\./g, '');
        }

        let partes = s.split('.');
        let entero = partes[0].replace(/^0+(?=\d)/, '');
        let decimal = partes[1];

        if (entero.length > 8) {
            entero = entero.slice(0, 8);
            mensaje = 'El monto es demasiado grande.';
        }

        if (decimal !== undefined && decimal.length > 2) {
            decimal = decimal.slice(0, 2);
            mensaje = 'Máximo 2 decimales.';
        }

        if (entero === '' && decimal !== undefined) {
            entero = '0';
        }

        let valor = decimal === undefined ? entero : entero + '.' + decimal;

        const max = parseFloat(el.getAttribute('max'));
        if (!isNaN(max) && valor !== '' && parseFloat(valor) > max) {
            valor = el._previo !== undefined && parseFloat(el._previo) <= max ? el._previo : String(max);
            mensaje = 'El valor máximo es ' + max + '.';
        }

        return { valor: valor, aviso: mensaje };
    }

    function fmtEntero(v, el) {
        let mensaje = null;

        if (/\D/.test(v)) {
            mensaje = 'Solo se permiten números enteros.';
        }

        let d = v.replace(/\D/g, '').replace(/^0+(?=\d)/, '');
        const limite = DIGITOS_ENTERO[el.name] || 6;

        if (d.length > limite) {
            d = d.slice(0, limite);
            mensaje = 'Máximo ' + limite + ' dígitos.';
        }

        const max = parseFloat(el.getAttribute('max'));
        if (!isNaN(max) && d !== '' && parseInt(d, 10) > max) {
            d = el._previo !== undefined && parseInt(el._previo, 10) <= max ? el._previo : String(max);
            mensaje = 'El valor máximo es ' + max + '.';
        }

        return { valor: d, aviso: mensaje };
    }

    const FORMATEADORES = {
        telefono: fmtTelefono,
        tarjeta: fmtTarjeta,
        vencimiento: fmtVencimiento,
        cvv: fmtCvv,
        correo: fmtCorreo,
        persona: fmtPersona,
        texto: fmtTexto,
        dinero: fmtDinero,
        entero: fmtEntero
    };

    /* ------------------------------------------------------------------
     * Preparación de los campos (una sola vez por campo)
     * ------------------------------------------------------------------ */
    function prepararTelefono($campo) {
        if (!$campo.closest('.input-group').length) {
            $campo.wrap('<div class="input-group"></div>');
            $campo.before('<span class="input-group-text formato-prefijo">+503</span>');
        }

        // Sin maxlength: al pegar "+503 7777-8888" el navegador lo cortaría
        // antes de que formato.js pueda quitar el prefijo. El formato corta solo.
        $campo.removeAttr('maxlength').attr({
            inputmode: 'numeric',
            autocomplete: 'off',
            placeholder: '0000-0000'
        });
    }

    function prepararContador($campo) {
        const max = parseInt($campo.attr('maxlength'), 10);

        if (!max || $campo.next('.formato-contador').length) {
            return;
        }

        $campo.after('<div class="formato-contador" aria-hidden="true"></div>');
        actualizarContador($campo);
    }

    function actualizarContador($campo) {
        const max = parseInt($campo.attr('maxlength'), 10);
        const $c = $campo.next('.formato-contador');

        if (!$c.length || !max) {
            return;
        }

        const n = ($campo.val() || '').length;

        $c.text(n + ' / ' + max).toggleClass('formato-contador--limite', n >= max);
    }

    function preparar(el) {
        if (el.getAttribute('data-formato-listo')) {
            return;
        }

        const tipo = tipoDe(el);
        const $campo = $(el);

        el.setAttribute('data-formato-listo', tipo || 'no');

        if (!tipo) {
            return;
        }

        switch (tipo) {
            case 'telefono':
                prepararTelefono($campo);
                break;
            case 'tarjeta':
                $campo.removeAttr('maxlength').attr({ inputmode: 'numeric', autocomplete: 'off' });
                break;
            case 'vencimiento':
                $campo.removeAttr('maxlength').attr({ inputmode: 'numeric', autocomplete: 'off' });
                break;
            case 'cvv':
                $campo.removeAttr('maxlength').attr({ inputmode: 'numeric', autocomplete: 'off' });
                break;
            case 'correo':
                $campo.attr({ autocomplete: 'off', autocapitalize: 'off', spellcheck: 'false' });
                break;
            case 'dinero':
                // Se pasa a texto para controlar los decimales mientras se escribe.
                if (el.type === 'number') {
                    el.type = 'text';
                }
                $campo.attr({ inputmode: 'decimal', autocomplete: 'off' });
                if (!$campo.attr('placeholder')) {
                    $campo.attr('placeholder', '0.00');
                }
                break;
            case 'entero':
                $campo.attr('inputmode', 'numeric');
                break;
            case 'texto':
                if (el.tagName === 'TEXTAREA') {
                    prepararContador($campo);
                }
                break;
        }
    }

    function prepararTodo(contexto) {
        $(contexto || document).find('input, textarea').each(function () {
            preparar(this);
        });
    }

    /* ------------------------------------------------------------------
     * Aplicar el formato conservando la posición del cursor
     * ------------------------------------------------------------------ */
    function aplicar(el, silencioso, borrando) {
        const tipo = el.getAttribute('data-formato-listo');
        const f = FORMATEADORES[tipo];

        if (!f) {
            return;
        }

        const original = el.value;
        const enfocado = document.activeElement === el;
        let pos = null;

        try {
            pos = enfocado ? el.selectionStart : null;
        } catch (e) { /* type=number no admite selección */ }

        const resultado = f(original, el, borrando);
        const nuevo = resultado.valor;

        if (nuevo !== original) {
            // Cuenta de caracteres "significativos" antes del cursor y de
            // separadores pegados al cursor (para no quedar antes de un espacio).
            const significativo = /[\d\p{L}]/u;
            let antes = 0;
            let separadores = 0;

            if (pos !== null) {
                for (let i = 0; i < pos; i++) {
                    if (significativo.test(original[i])) {
                        antes++;
                        separadores = 0;
                    } else {
                        separadores++;
                    }
                }
            }

            el.value = nuevo;

            if (pos !== null && pos >= original.length) {
                // Se estaba escribiendo al final: el cursor sigue al final.
                try { el.setSelectionRange(nuevo.length, nuevo.length); } catch (e) { /* nada */ }
            } else if (pos !== null) {
                let cuenta = 0;
                let p = 0;

                if (antes > 0) {
                    for (p = 0; p < nuevo.length; p++) {
                        if (significativo.test(nuevo[p])) { cuenta++; }
                        if (cuenta === antes) { p++; break; }
                    }
                }

                while (separadores > 0 && p < nuevo.length && !significativo.test(nuevo[p])) {
                    p++;
                    separadores--;
                }

                try { el.setSelectionRange(p, p); } catch (e) { /* nada */ }
            }
        }

        if (resultado.aviso && !silencioso) {
            aviso($(el), resultado.aviso);
        }

        el._previo = el.value;

        if (tipo === 'texto') {
            actualizarContador($(el));
        }
    }

    /* ------------------------------------------------------------------
     * Eventos (captura: el formato ya está aplicado cuando otros
     * manejadores del campo leen el valor)
     * ------------------------------------------------------------------ */
    document.addEventListener('input', function (e) {
        const el = e.target;

        if (!el || !el.tagName || (el.tagName !== 'INPUT' && el.tagName !== 'TEXTAREA')) {
            return;
        }

        preparar(el);
        aplicar(el, false, e.inputType && e.inputType.indexOf('delete') === 0);
    }, true);

    // En campos numéricos se bloquean las teclas que no son dígitos; en los
    // de texto se avisa cuando ya no cabe más (el navegador lo impide en silencio).
    document.addEventListener('keydown', function (e) {
        const el = e.target;

        if (!el || (el.tagName !== 'INPUT' && el.tagName !== 'TEXTAREA')) {
            return;
        }

        if (e.ctrlKey || e.metaKey || e.altKey || e.key.length > 1) {
            return;
        }

        const tipo = el.getAttribute('data-formato-listo');

        if (tipo === 'entero' && !/\d/.test(e.key)) {
            e.preventDefault();
            aviso($(el), 'Solo se permiten números enteros.');
            return;
        }

        const max = parseInt(el.getAttribute('maxlength'), 10);
        let seleccion = 0;

        try { seleccion = el.selectionEnd - el.selectionStart; } catch (err) { /* nada */ }

        if (max && el.value.length >= max && seleccion === 0 && tipo && tipo !== 'no') {
            aviso($(el), 'Llegó al límite de ' + max + ' caracteres.');
        }
    }, true);

    // Validaciones al salir del campo.
    $(document).on('focusout', 'input, textarea', function () {
        const el = this;
        const tipo = el.getAttribute('data-formato-listo');
        const v = el.value;

        if (!tipo || !v) {
            return;
        }

        const $c = $(el);

        if (tipo === 'telefono' && !/^\d{4}-\d{4}$/.test(v)) {
            aviso($c, 'Complete los 8 dígitos del teléfono (0000-0000).');
        } else if (tipo === 'correo' && !CORREO.test(v)) {
            aviso($c, 'Escriba un correo válido, por ejemplo nombre@dominio.com.');
        } else if (tipo === 'tarjeta' && v.replace(/\D/g, '').length < 13) {
            aviso($c, 'El número de tarjeta está incompleto.');
        } else if (tipo === 'vencimiento') {
            if (!/^\d{2}\/\d{2}$/.test(v)) {
                aviso($c, 'Escriba el vencimiento como MM/AA.');
            } else {
                const mes = parseInt(v.slice(0, 2), 10);
                const anio = 2000 + parseInt(v.slice(3), 10);
                const hoy = new Date();

                if (anio < hoy.getFullYear() || (anio === hoy.getFullYear() && mes < hoy.getMonth() + 1)) {
                    aviso($c, 'La tarjeta está vencida.');
                }
            }
        } else if (tipo === 'cvv' && v.length < 3) {
            aviso($c, 'El CVV tiene 3 o 4 dígitos.');
        } else if (tipo === 'persona') {
            const limpio = v.trim();
            const titulo = (limpio === limpio.toLowerCase() || limpio === limpio.toUpperCase())
                ? limpio.toLowerCase().replace(/(^|[\s'’-])(\p{L})/gu, function (m, a, b) { return a + b.toUpperCase(); })
                : limpio;

            if (titulo !== v) {
                el.value = titulo;
            }
        } else if (tipo === 'dinero' && /\.$/.test(v)) {
            el.value = v.slice(0, -1);
        } else if (tipo === 'dinero' && /^\d+\.\d$/.test(v)) {
            el.value = v + '0';
        }
    });

    // Al abrir un modal se vuelve a dar formato a los valores cargados.
    $(document).on('shown.bs.modal', '.modal', function () {
        const $modal = $(this);

        prepararTodo($modal);

        $modal.find('input, textarea').each(function () {
            if (this.getAttribute('data-formato-listo') && this.value) {
                aplicar(this, true, false);
            }

            quitarAviso($(this));
        });
    });

    $(function () {
        prepararTodo(document);
    });

    /* ------------------------------------------------------------------
     * API pública
     * ------------------------------------------------------------------ */
    App.formato = {
        telefono: function (v) { return fmtTelefono(String(v || '')).valor; },
        tarjeta: function (v) { return fmtTarjeta(String(v || '')).valor; },
        aviso: aviso,
        preparar: prepararTodo
    };

    // "+503 7777-8888" para mostrar en pantalla.
    App.telefono = function (valor) {
        const t = fmtTelefono(String(valor || '')).valor;

        return t ? '+503 ' + t : '—';
    };
})(window, jQuery);
