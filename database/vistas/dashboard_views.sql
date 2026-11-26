-- =============================================================================
--  DulceSabor_Sys - Vistas SQL del dashboard (MySQL 8 / MariaDB 10.5+)
--
--  Ejecutar UNA VEZ en la base de datos del sistema, DESPUÉS de
--  `php artisan migrate` (las vistas leen las tablas ya creadas).
--  Es seguro volver a ejecutar el archivo: usa CREATE OR REPLACE VIEW.
--
--  Cada vista tiene un modelo Eloquent de solo lectura en app/Models/Vistas.
--  Ninguna usa CURDATE() ni NOW(): "hoy" lo decide Laravel.
-- =============================================================================

-- 1. Cifras operativas del momento (una sola fila) ---------------------------
CREATE OR REPLACE VIEW v_resumen_operativo AS
SELECT
    (SELECT COUNT(*) FROM ordenes  WHERE estado = 'pendiente')     AS ordenes_pendientes,
    (SELECT COUNT(*) FROM ordenes  WHERE estado = 'en_produccion') AS ordenes_en_produccion,
    (SELECT COUNT(*) FROM ordenes  WHERE estado = 'lista')         AS ordenes_listas,
    (SELECT COUNT(*) FROM mesas    WHERE estado = 'ocupada')       AS mesas_ocupadas,
    (SELECT COUNT(*) FROM mesas    WHERE estado = 'disponible')    AS mesas_disponibles,
    (SELECT COUNT(*) FROM facturas WHERE estado = 'emitida')       AS facturas_por_cobrar,
    (SELECT COUNT(*) FROM clientes WHERE id_estado = 1)            AS clientes_activos;

-- 2. Ventas facturadas por día (facturas no anuladas, IVA incluido) ----------
CREATE OR REPLACE VIEW v_ventas_diarias AS
SELECT
    DATE(f.fecha_emision)  AS fecha,
    COUNT(*)               AS facturas,
    SUM(f.total)           AS total_ventas,
    SUM(f.descuento)       AS descuentos,
    SUM(f.base_imponible)  AS base_imponible,
    SUM(f.iva)             AS iva
FROM facturas f
WHERE f.estado <> 'anulada'
GROUP BY DATE(f.fecha_emision);

-- 3. Pedidos registrados por día y tipo (sin cancelados) ---------------------
CREATE OR REPLACE VIEW v_pedidos_diarios AS
SELECT
    DATE(p.fecha) AS fecha,
    p.tipo        AS tipo,
    COUNT(*)      AS pedidos,
    SUM(p.total)  AS total
FROM pedidos p
WHERE p.estado <> 'cancelado'
GROUP BY DATE(p.fecha), p.tipo;

-- 4. Dinero cobrado por día y método de pago ---------------------------------
CREATE OR REPLACE VIEW v_cobros_diarios AS
SELECT
    DATE(pg.fecha_pago) AS fecha,
    pg.metodo           AS metodo,
    COUNT(*)            AS pagos,
    SUM(pg.monto)       AS monto
FROM pagos pg
WHERE pg.estado = 'aplicado'
GROUP BY DATE(pg.fecha_pago), pg.metodo;

-- 5. Utilidad por factura (ADMINISTRADOR: incluye costos) -------------------
--    utilidad_bruta = base imponible (sin IVA) - costo de los productos.
CREATE OR REPLACE VIEW v_facturas_utilidad AS
SELECT
    f.id                               AS id_factura,
    DATE(f.fecha_emision)              AS fecha,
    f.estado                           AS estado,
    f.total                            AS total,
    f.base_imponible                   AS base_imponible,
    f.iva                              AS iva,
    f.cargo_mesas                      AS cargo_mesas,
    COALESCE(c.costo_ventas, 0)        AS costo_ventas,
    f.base_imponible - COALESCE(c.costo_ventas, 0) AS utilidad_bruta
FROM facturas f
LEFT JOIN (
    SELECT id_pedido, SUM(cantidad * costo_unitario) AS costo_ventas
    FROM detalle_pedidos
    GROUP BY id_pedido
) c ON c.id_pedido = f.id_pedido
WHERE f.estado <> 'anulada';

-- 6. Productos vendidos por día (pedidos no cancelados) ----------------------
--    "ingresos" incluye IVA; "costo" lo usa solo el dashboard del administrador.
CREATE OR REPLACE VIEW v_productos_vendidos_diario AS
SELECT
    DATE(p.fecha)                       AS fecha,
    dp.id_producto                      AS id_producto,
    pr.nombre                           AS producto,
    SUM(dp.cantidad)                    AS unidades,
    SUM(dp.subtotal)                    AS ingresos,
    SUM(dp.cantidad * dp.costo_unitario) AS costo
FROM detalle_pedidos dp
JOIN pedidos   p  ON p.id  = dp.id_pedido
JOIN productos pr ON pr.id = dp.id_producto
WHERE p.estado <> 'cancelado'
GROUP BY DATE(p.fecha), dp.id_producto, pr.nombre;

-- 7. Compras por día ---------------------------------------------------------
CREATE OR REPLACE VIEW v_compras_diarias AS
SELECT
    DATE(c.fecha_compra) AS fecha,
    COUNT(*)             AS compras,
    SUM(c.total)         AS total_comprado,
    SUM(CASE WHEN c.condicion_pago = 'credito' THEN c.total ELSE 0 END) AS total_credito
FROM compras c
GROUP BY DATE(c.fecha_compra);

-- 8. Pagos a proveedores por día (contado, abonos iniciales y abonos) --------
CREATE OR REPLACE VIEW v_pagos_compras_diarios AS
SELECT
    DATE(pc.fecha_pago) AS fecha,
    COUNT(*)            AS pagos,
    SUM(pc.monto)       AS monto
FROM pagos_compras pc
GROUP BY DATE(pc.fecha_pago);

-- 9. Gastos adicionales por día y tipo (sin anulados) ------------------------
CREATE OR REPLACE VIEW v_gastos_diarios AS
SELECT
    g.fecha_gasto AS fecha,
    g.tipo        AS tipo,
    COUNT(*)      AS gastos,
    SUM(g.monto)  AS monto
FROM gastos g
WHERE g.estado = 'registrado'
GROUP BY g.fecha_gasto, g.tipo;

-- 10. Deudas con proveedores (compras con saldo) -----------------------------
--     "vencida" y los días restantes los calcula Laravel con su fecha de hoy.
CREATE OR REPLACE VIEW v_deudas_proveedores AS
SELECT
    c.id                       AS id_compra,
    c.id_proveedor             AS id_proveedor,
    pv.nombre                  AS proveedor,
    c.fecha_compra             AS fecha_compra,
    c.fecha_vencimiento        AS fecha_vencimiento,
    c.total                    AS total,
    c.monto_pagado             AS monto_pagado,
    c.total - c.monto_pagado   AS saldo,
    c.estado_pago              AS estado_pago
FROM compras c
JOIN proveedores pv ON pv.id = c.id_proveedor
WHERE c.estado_pago <> 'pagada';

-- 11. Productos con existencias en el mínimo o por debajo (sin costo) --------
CREATE OR REPLACE VIEW v_stock_bajo AS
SELECT
    p.id           AS id_producto,
    p.nombre       AS producto,
    p.stock        AS stock,
    p.stock_minimo AS stock_minimo,
    p.estado       AS estado
FROM productos p
WHERE p.estado <> 'inactivo'
  AND p.stock <= p.stock_minimo;

-- 12. Pedidos programados con su orden de trabajo en curso -------------------
--     Laravel filtra los de hoy en adelante.
CREATE OR REPLACE VIEW v_pedidos_programados AS
SELECT
    p.id                 AS id_pedido,
    p.fecha_programada   AS fecha_programada,
    p.tipo               AS tipo,
    p.total              AS total,
    CASE
        WHEN c.id IS NULL THEN 'Consumidor final'
        ELSE CONCAT(c.nombre, ' ', c.apellido)
    END                  AS cliente,
    o.numero_orden       AS numero_orden,
    o.estado             AS estado_orden
FROM pedidos p
JOIN ordenes o       ON o.id_pedido = p.id
LEFT JOIN clientes c ON c.id = p.id_cliente
WHERE p.estado = 'abierto'
  AND p.fecha_programada IS NOT NULL
  AND o.estado IN ('pendiente', 'en_produccion', 'lista');

-- 13. Reservas pendientes o confirmadas, con sus mesas -----------------------
--     Laravel filtra las de hoy.
CREATE OR REPLACE VIEW v_reservas_pendientes AS
SELECT
    r.id                 AS id_reserva,
    r.fecha              AS fecha,
    r.hora_inicio        AS hora_inicio,
    r.hora_fin           AS hora_fin,
    r.cantidad_personas  AS personas,
    r.estado             AS estado,
    CONCAT(c.nombre, ' ', c.apellido) AS cliente,
    GROUP_CONCAT(m.numero ORDER BY m.numero SEPARATOR ', ') AS mesas
FROM reservas r
JOIN clientes c         ON c.id = r.id_cliente
JOIN detalle_reservas d ON d.id_reserva = r.id
JOIN mesas m            ON m.id = d.id_mesa
WHERE r.estado IN ('pendiente', 'confirmada')
GROUP BY r.id, r.fecha, r.hora_inicio, r.hora_fin, r.cantidad_personas,
         r.estado, c.nombre, c.apellido;
