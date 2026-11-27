#!/usr/bin/env bash
# =============================================================================
#  DulceSabor_Sys - FASE 6B: CORRECCIONES (mesas ocupadas, paginacion, fotos)
# -----------------------------------------------------------------------------
#  Uso:   bash fase6b_correcciones.sh [ruta_del_proyecto]
#         (si no se indica, se usa el directorio actual)
#
#  Requiere la Fase 6. Solo crea y edita archivos; no hay migraciones nuevas.
#
#   1. MESA OCUPADA: un pedido para consumir en el local ya no se puede
#      registrar en una mesa ocupada (antes se permitia si la mesa tenia otro
#      pedido abierto). Se valida en el servidor y, ademas, la lista de mesas
#      del formulario las muestra deshabilitadas. Reservada y en mantenimiento
#      siguen igual; los demas tipos de pedido no cambian.
#   2. PAGINACION DEL LADO DEL SERVIDOR en lo que puede crecer sin limite:
#        - Catalogo (busqueda, filtro y paginas: 12 / 24 / 48 por pagina).
#        - Vitrina de productos del formulario de pedidos (9 por pagina).
#        - Bitacora (25 registros por pagina, conserva los filtros).
#      Las tablas de los modulos ya se paginaban en pantalla (10/25/50/100).
#   3. CATALOGO CON FOTO: cada tarjeta muestra la foto del producto; si no
#      tiene foto o no carga, conserva el icono de siempre.
#
#  Los archivos modificados se respaldan en .respaldo/fase6b_<fecha>/
# =============================================================================
set -euo pipefail

RAIZ="${1:-$(pwd)}"
RAIZ="$(cd "$RAIZ" && pwd)"
MARCA="$(date +%Y%m%d_%H%M%S)"
RESPALDO="$RAIZ/.respaldo/fase6b_$MARCA"

# ---------------------------------------------------------------- validaciones
if [ ! -f "$RAIZ/artisan" ] || [ ! -d "$RAIZ/resources/views" ]; then
    echo "✘ '$RAIZ' no parece la raíz de un proyecto Laravel."
    exit 1
fi

for requerido in \
    "app/Models/Pedido.php:Fase 2C" \
    "app/Models/Producto.php:Fase 6" \
    "app/Models/Bitacora.php:Fase 1" \
    "app/Http/Controllers/ProductoController.php:Fase 6" \
    "app/Http/Controllers/PedidoController.php:Fase 2C" \
    "app/Http/Controllers/BitacoraController.php:Fase 1" \
    "resources/views/bitacora/bitacora.blade.php:Fase 1" \
    "public/js/catalogo.js:Fase 4" \
    "public/js/pedidos.js:Fase 4C" \
    "public/css/catalogo.css:Fase 4" \
    "public/css/paginacion.css:Fase 5B"
do
    archivo="${requerido%%:*}"
    fase="${requerido##*:}"
    if [ ! -f "$RAIZ/$archivo" ]; then
        echo "✘ Falta $archivo ($fase). Ejecute antes las fases anteriores."
        exit 1
    fi
done

if ! grep -q "imagen_url" "$RAIZ/app/Models/Producto.php"; then
    echo "✘ Falta la Fase 6 (fase6_mejoras.sh). Ejecútela antes de esta corrección."
    exit 1
fi

if ! command -v python3 >/dev/null 2>&1; then
    echo "✘ Se necesita python3 para editar archivos existentes de forma segura."
    exit 1
fi

# ------------------------------------------------------------------- utilidades
respaldar() {
    local rel="$1"
    if [ -f "$RAIZ/$rel" ]; then
        mkdir -p "$(dirname "$RESPALDO/$rel")"
        [ -f "$RESPALDO/$rel" ] || cp "$RAIZ/$rel" "$RESPALDO/$rel"
    fi
}

# Crea o sobrescribe un archivo con lo que llegue por la entrada estándar.
guardar() {
    local rel="$1"
    local destino="$RAIZ/$rel"
    mkdir -p "$(dirname "$destino")"
    if [ -f "$destino" ]; then
        respaldar "$rel"
        cat > "$destino"
        echo "  ✎ editado : $rel"
    else
        cat > "$destino"
        echo "  ✚ creado  : $rel"
    fi
}

# Edita un archivo: reemplaza la primera coincidencia de una expresión regular
# (Python, DOTALL). Si el archivo ya contiene la marca, no hace nada, así el
# script se puede repetir sin duplicar cambios.
#   editar <archivo> <marca> <regex> <<'NUEVO'  ...texto de reemplazo... NUEVO
editar() {
    local rel="$1" marca="$2" patron="$3"
    local destino="$RAIZ/$rel"
    local nuevo
    nuevo="$(cat)"
    if [ ! -f "$destino" ]; then
        echo "  ⚠ no existe $rel (se omite este cambio)"
        return 0
    fi
    respaldar "$rel"
    ARCHIVO="$destino" MARCA_E="$marca" PATRON="$patron" NUEVO="$nuevo" REL="$rel" python3 - <<'PY'
import os, re, sys
ruta = os.environ["ARCHIVO"]
with open(ruta, encoding="utf-8") as f:
    texto = f.read()
if os.environ["MARCA_E"] and os.environ["MARCA_E"] in texto:
    print("  = sin cambios (ya aplicado): " + os.environ["REL"])
    sys.exit(0)
nuevo = os.environ["NUEVO"]
resultado, n = re.subn(os.environ["PATRON"], lambda m: nuevo.replace("\\1", m.group(1) if m.groups() else ""), texto, count=1, flags=re.DOTALL)
if n == 0:
    print("  ⚠ no se encontró el punto de edición en " + os.environ["REL"] + " (revise ese archivo a mano)")
    sys.exit(0)
with open(ruta, "w", encoding="utf-8") as f:
    f.write(resultado)
print("  ✎ editado : " + os.environ["REL"])
PY
}

# Agrega texto al final de un archivo (si no tiene ya la marca).
agregar() {
    local rel="$1" marca="$2"
    local destino="$RAIZ/$rel"
    local nuevo
    nuevo="$(cat)"
    mkdir -p "$(dirname "$destino")"
    if [ -f "$destino" ] && grep -qF -- "$marca" "$destino"; then
        echo "  = sin cambios (ya aplicado): $rel"
        return 0
    fi
    respaldar "$rel"
    printf '\n%s\n' "$nuevo" >> "$destino"
    echo "  ✎ ampliado: $rel"
}

echo "== DulceSabor_Sys · Fase 6B: correcciones =="
echo "Proyecto: $RAIZ"
echo

# =============================================================================
#  1. MESA OCUPADA
# =============================================================================

echo "-- Pedidos: una mesa ocupada no admite pedidos nuevos --"

editar "app/Models/Pedido.php" "Elija otra mesa o espere a que se libere" '    // La mesa debe poder usarse.*?(?=\n\n        // No sentar clientes)' <<'NUEVO'
    // La mesa debe poder usarse para consumo en el local (sin hora de
    // término). Solo se aceptan mesas disponibles: una mesa ocupada,
    // reservada o en mantenimiento no admite pedidos nuevos.
    private static function validarMesaParaConsumo(Mesa $mesa): void
    {
        if ($mesa->estado === 'mantenimiento') {
            throw ValidationException::withMessages([
                'id_mesa' => ["La mesa {$mesa->numero} se encuentra en mantenimiento."],
            ]);
        }

        if ($mesa->estado === 'reservada') {
            throw ValidationException::withMessages([
                'id_mesa' => ["La mesa {$mesa->numero} está marcada como reservada."],
            ]);
        }

        if ($mesa->estado === 'ocupada') {
            throw ValidationException::withMessages([
                'id_mesa' => [
                    "La mesa {$mesa->numero} se encuentra ocupada. Elija otra mesa o espere a que se libere.",
                ],
            ]);
        }
NUEVO

editar "public/js/pedidos.js" "m.estado === 'ocupada'" 'm\.estado === \x27reservada\x27;' <<'NUEVO'
m.estado === 'reservada' || m.estado === 'ocupada';
NUEVO

editar "resources/views/pedido/pedidos.blade.php" "no se pueden elegir" '<div class="form-text">Una mesa ocupada solo se puede elegir[^\n]*</div>' <<'NUEVO'
<div class="form-text">Las mesas ocupadas, reservadas o en mantenimiento no se pueden elegir.</div>
NUEVO

# =============================================================================
#  2. PAGINACIÓN EN EL SERVIDOR
# =============================================================================

echo
echo "-- Catálogo: búsqueda, filtro y paginación en el servidor --"

editar "app/Models/Producto.php" "function paginarCatalogo" '(    public static function buscarProductoXId\(\$id\))' <<'NUEVO'
    // Catálogo paginado (búsqueda y filtro de existencias incluidos). Lo usan
    // el catálogo y la vitrina del formulario de pedidos, así el navegador
    // solo recibe una página aunque el inventario crezca.
    public const CATALOGO_POR_PAGINA = 12;
    public const CATALOGO_POR_PAGINA_MAX = 60;

    public static function paginarCatalogo(array $filtros = [])
    {
        $porPagina = (int) ($filtros['por_pagina'] ?? self::CATALOGO_POR_PAGINA);
        $porPagina = max(1, min($porPagina, self::CATALOGO_POR_PAGINA_MAX));
        $pagina = max(1, (int) ($filtros['pagina'] ?? 1));
        $texto = trim((string) ($filtros['q'] ?? ''));

        $consulta = self::select([
            'id',
            'id_categoria',
            'nombre',
            'precio',
            'stock',
            'stock_minimo',
            'descripcion',
            'imagen',
            'estado',
            'origen',
        ])
            ->with('categoria')
            ->where('estado', '!=', 'inactivo')
            ->orderBy('nombre')
            ->orderBy('id');

        if ($texto !== '') {
            $patron = '%' . addcslashes($texto, '\\%_') . '%';

            $consulta->where(function ($q) use ($patron) {
                $q->where('nombre', 'like', $patron)
                    ->orWhereHas('categoria', fn ($c) => $c->where('nombre', 'like', $patron));
            });
        }

        switch ($filtros['disponibilidad'] ?? null) {
            // Lo que se puede vender ahora mismo.
            case 'vendible':
                $consulta->where('estado', 'disponible')->where('stock', '>', 0);
                break;

            case 'disponible':
                $consulta->where('estado', '!=', 'agotado')
                    ->where('stock', '>', 0)
                    ->whereColumn('stock', '>', 'stock_minimo');
                break;

            case 'bajo':
                $consulta->where('estado', '!=', 'agotado')
                    ->where('stock', '>', 0)
                    ->whereColumn('stock', '<=', 'stock_minimo');
                break;

            case 'agotado':
                $consulta->where(function ($q) {
                    $q->where('estado', 'agotado')->orWhere('stock', '<=', 0);
                });
                break;
        }

        return $consulta->paginate($porPagina, ['*'], 'page', $pagina);
    }

\1
NUEVO

editar "app/Http/Controllers/ProductoController.php" "Producto::paginarCatalogo" '    public function catalogoTodos\(\)\n    \{.*?\n    \}(?=\n\})' <<'NUEVO'
    // Una página del catálogo. Parámetros opcionales: q (texto),
    // disponibilidad (disponible|bajo|agotado|vendible), pagina y por_pagina.
    public function catalogoTodos(Request $request)
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'disponibilidad' => ['nullable', 'string', 'in:disponible,bajo,agotado,vendible'],
            'pagina' => ['nullable', 'integer', 'min:1'],
            'por_pagina' => ['nullable', 'integer', 'min:1', 'max:' . Producto::CATALOGO_POR_PAGINA_MAX],
        ], [
            'q.max' => 'La búsqueda no debe superar los 100 caracteres.',
            'disponibilidad.in' => 'El filtro de existencias no es válido.',
            'pagina.integer' => 'La página solicitada no es válida.',
            'por_pagina.max' => 'No se pueden pedir tantos productos por página.',
        ]);

        $pagina = Producto::paginarCatalogo($filtros);

        return response()->json([
            'success' => true,
            'productos' => $pagina->items(),
            'paginacion' => [
                'pagina' => $pagina->currentPage(),
                'por_pagina' => $pagina->perPage(),
                'total' => $pagina->total(),
                'ultima' => $pagina->lastPage(),
                'desde' => $pagina->firstItem() ?? 0,
                'hasta' => $pagina->lastItem() ?? 0,
            ],
        ]);
    }
NUEVO

guardar "public/js/catalogo.js" <<'JSEOF'
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
JSEOF

agregar "public/css/catalogo.css" ".cat-tarjeta__foto" <<'NUEVO'
/* Foto del producto (Fase 6B): ocupa el ancho de la tarjeta, sobre el nombre. */
.cat-tarjeta__foto {
    position: relative;
    display: grid;
    place-items: center;
    height: 170px;
    margin: -1.2rem -1.3rem .9rem;
    overflow: hidden;
    background: radial-gradient(circle at 30% 25%, #fff 0%, #ffd9e8 85%);
    border-radius: calc(var(--radio-lg) - 2px) calc(var(--radio-lg) - 2px) 0 0;
}
.cat-tarjeta__foto img { width: 100%; height: 100%; object-fit: cover; display: block; }
.cat-tarjeta__foto .badge { position: absolute; top: .7rem; right: .7rem; box-shadow: 0 4px 10px rgba(0, 0, 0, .15); }
.cat-tarjeta__foto-icono { font-size: 2.4rem; color: #d6336c; }

#catalogo-paginacion {
    margin-top: 1.2rem;
    border: 1.5px solid #fff;
    border-radius: var(--radio-lg);
    box-shadow: var(--sombra-suave);
}
NUEVO

echo
echo "-- Formulario de pedidos: vitrina de productos paginada --"

editar "app/Http/Controllers/PedidoController.php" "compact('mesas'));" '        \$productos = Producto::mostrarCatalogo\(\).*?return view\(\x27pedido\.pedidos\x27, compact\(\x27mesas\x27, \x27productos\x27\)\);' <<'NUEVO'
        return view('pedido.pedidos', compact('mesas'));
NUEVO

editar "public/js/pedidos.js" "catalogoUltima" '    let catalogo = \[\];' <<'NUEVO'
    let catalogo = [];                      // productos de la página que se muestra
    let catalogoPagina = 1;
    let catalogoUltima = 1;
    let catalogoTotal = 0;
    let catalogoSecuencia = 0;
    let catalogoPeticion = null;
    let catalogoTemporizador = null;
    const CATALOGO_POR_PAGINA = 9;
NUEVO

editar "public/js/pedidos.js" "pedidos-catalogo-pie" '    function cargarCatalogo\(\) \{.*?(?=\n\n    function pintarCarrito\(\) \{)' <<'NUEVO'
    // La vitrina pide al servidor una página a la vez (búsqueda incluida),
    // así sigue ágil aunque el catálogo tenga cientos de productos.
    const $pieCatalogo = $(
        '<div class="mt-2" id="pedidos-catalogo-pie"><div class="d-flex align-items-center justify-content-between gap-2">' +
            '<span class="texto-pequeno" data-info></span>' +
            '<div class="btn-group btn-group-sm" role="group" aria-label="Páginas de la vitrina">' +
                '<button type="button" class="btn btn-outline-secondary" data-pag="-1" aria-label="Página anterior"><i class="fa-solid fa-chevron-left"></i></button>' +
                '<button type="button" class="btn btn-outline-secondary" data-pag="1" aria-label="Página siguiente"><i class="fa-solid fa-chevron-right"></i></button>' +
            '</div>' +
        '</div></div>'
    ).hide();

    $('#pedidos-catalogo').after($pieCatalogo);

    function cargarCatalogo() {
        const mia = ++catalogoSecuencia;

        if (catalogoPeticion) {
            catalogoPeticion.abort();
        }

        catalogoPeticion = App.peticion('GET', url.catalogo, {
            q: $('#pedidos-buscar-producto').val().trim(),
            disponibilidad: 'vendible',
            pagina: catalogoPagina,
            por_pagina: CATALOGO_POR_PAGINA
        }).done(function (r) {
            if (mia !== catalogoSecuencia) {
                return;
            }

            const pag = r.paginacion || {};

            // La página pedida ya no existe (por ejemplo, tras buscar): se va a la última.
            if (pag.ultima > 0 && catalogoPagina > pag.ultima) {
                catalogoPagina = pag.ultima;
                cargarCatalogo();

                return;
            }

            catalogo = r.productos || [];
            catalogoUltima = pag.ultima || 1;
            catalogoTotal = pag.total || 0;
            pintarCatalogo();
        }).fail(function (xhr, texto) {
            if (texto !== 'abort') {
                App.notificarError(xhr);
            }
        });

        return catalogoPeticion;
    }

    function pintarCatalogo() {
        $('#pedidos-catalogo').html(catalogo.length ? catalogo.map(function (p) {
            const enCarrito = carrito[p.id] ? carrito[p.id].cantidad : 0;

            return '<button type="button" class="pedido-prod" data-agregar="' + p.id + '"' +
                (enCarrito >= p.stock ? ' disabled' : '') + '>' +
                '<span class="pedido-prod__nombre">' + App.esc(p.nombre) + '</span>' +
                '<span class="pedido-prod__meta">' + App.dinero(p.precio) + ' · quedan ' + (p.stock - enCarrito) + '</span></button>';
        }).join('') : '<div class="texto-pequeno p-2">No hay productos disponibles.</div>');

        const hayPaginas = catalogoUltima > 1;

        $pieCatalogo.toggle(hayPaginas);
        $pieCatalogo.find('[data-info]').text('Página ' + catalogoPagina + ' de ' + catalogoUltima + ' · ' + catalogoTotal + ' productos');
        $pieCatalogo.find('[data-pag="-1"]').prop('disabled', catalogoPagina <= 1);
        $pieCatalogo.find('[data-pag="1"]').prop('disabled', catalogoPagina >= catalogoUltima);
    }

    $pieCatalogo.on('click', '[data-pag]', function () {
        const destino = catalogoPagina + Number($(this).data('pag'));

        if (destino >= 1 && destino <= catalogoUltima) {
            catalogoPagina = destino;
            cargarCatalogo();
        }
    });
NUEVO

editar "public/js/pedidos.js" "catalogoTemporizador = setTimeout" '    \$\(\x27#pedidos-buscar-producto\x27\)\.on\(\x27input\x27, pintarCatalogo\);' <<'NUEVO'
    $('#pedidos-buscar-producto').on('input', function () {
        clearTimeout(catalogoTemporizador);
        catalogoTemporizador = setTimeout(function () {
            catalogoPagina = 1;
            cargarCatalogo();
        }, 300);
    });
NUEVO

editar "public/js/pedidos.js" "// vuelve a la primera página de la vitrina" '        \$\(\x27#pedidos-buscar-producto\x27\)\.val\(\x27\x27\);' <<'NUEVO'
        $('#pedidos-buscar-producto').val('');
        catalogoPagina = 1; // vuelve a la primera página de la vitrina
NUEVO

echo
echo "-- Bitácora: paginación en el servidor --"

editar "app/Models/Bitacora.php" "function paginar" '(    public static function registrar\()' <<'NUEVO'

    // Misma consulta que mostrarTodos, pero por páginas (la bitácora crece
    // con cada acción del sistema). Conserva los filtros en los enlaces.
    public static function paginar(
        ?string $evento = null,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null,
        int $porPagina = 25
    ): \Illuminate\Pagination\LengthAwarePaginator {
        $consulta = static::with('usuario')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($evento) {
            $consulta->where('evento', $evento);
        }

        if ($fechaDesde) {
            $consulta->whereDate('created_at', '>=', $fechaDesde);
        }

        if ($fechaHasta) {
            $consulta->whereDate('created_at', '<=', $fechaHasta);
        }

        return $consulta->paginate($porPagina)->withQueryString();
    }

\1
NUEVO

editar "app/Http/Controllers/BitacoraController.php" "Bitacora::paginar(" 'Bitacora::mostrarTodos\(' <<'NUEVO'
Bitacora::paginar(
NUEVO

editar "resources/views/bitacora/bitacora.blade.php" "Buscar en esta página" 'placeholder="Buscar en los resultados\.\.\."' <<'NUEVO'
placeholder="Buscar en esta página..."
NUEVO

editar "resources/views/bitacora/bitacora.blade.php" "number_format(\$bitacoras->total())" '\{\{ \$bitacoras->count\(\) \}\} registro\(s\)' <<'NUEVO'
{{ number_format($bitacoras->total()) }} registro(s)
NUEVO

editar "resources/views/bitacora/bitacora.blade.php" "data-sin-paginar" 'id="tabla-bitacora">' <<'NUEVO'
id="tabla-bitacora" data-sin-paginar>
NUEVO

editar "resources/views/bitacora/bitacora.blade.php" "pagination::bootstrap-5" '(                </table>\n            </div>)' <<'NUEVO'
\1

            @if($bitacoras->hasPages())
                <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="small text-muted">
                        Mostrando {{ $bitacoras->firstItem() }}–{{ $bitacoras->lastItem() }} de {{ number_format($bitacoras->total()) }}
                    </span>
                    {{ $bitacoras->links('pagination::bootstrap-5') }}
                </div>
            @endif
NUEVO

# ---- Verificación de sintaxis ------------------------------------------------------
echo
if command -v node >/dev/null 2>&1; then
    echo "-- Verificando JavaScript --"
    errores=0
    for archivo in public/js/catalogo.js public/js/pedidos.js; do
        if ! node --check "$RAIZ/$archivo" 2>/dev/null; then
            echo "  ✘ error de sintaxis en $archivo"
            errores=1
        fi
    done
    [ "$errores" -eq 0 ] && echo "  ✔ JavaScript sin errores de sintaxis"
fi

if command -v php >/dev/null 2>&1; then
    echo "-- Verificando PHP --"
    errores=0
    for archivo in \
        app/Models/Pedido.php app/Models/Producto.php app/Models/Bitacora.php \
        app/Http/Controllers/ProductoController.php app/Http/Controllers/PedidoController.php \
        app/Http/Controllers/BitacoraController.php; do
        if ! php -l "$RAIZ/$archivo" >/dev/null 2>&1; then
            echo "  ✘ error de sintaxis en $archivo"
            errores=1
        fi
    done
    [ "$errores" -eq 0 ] && echo "  ✔ PHP sin errores de sintaxis"
fi

echo
echo "== Fase 6B completada =="
if [ -d "$RESPALDO" ]; then
    echo "Respaldo de lo modificado: ${RESPALDO#"$RAIZ"/}"
fi
echo
echo "No hay migraciones nuevas. Ejecute:"
echo "  php artisan optimize:clear"
echo
echo "Luego recargue el navegador con Ctrl+F5."
