-- ============================================================================
-- INTEGRACIÓN K2B → Ciclo Presupuestario
-- ============================================================================
-- Parsea el campo esquema de pres_k2b_sr_logs para vincularlo con las tablas
-- del ciclo presupuestario.
-- Formato esquema: "25-880-1792-C1-MATERIALES CTA CTE (USUAR"
--   Parte 0: año corto (25 = 2025)
--   Parte 1: centro de costos (880 → ciclo_unidad_organizativa.cc)
--   Parte 2: id origen gasto (1792 → ciclo_item_gasto.gastoid_origen)
-- ============================================================================

-- PASO 1: Agregar columnas parseadas a pres_k2b_sr_logs
ALTER TABLE pres_k2b_sr_logs
    ADD COLUMN IF NOT EXISTS esquema_anio SMALLINT DEFAULT NULL AFTER esquema,
    ADD COLUMN IF NOT EXISTS esquema_cc INT DEFAULT NULL AFTER esquema_anio,
    ADD COLUMN IF NOT EXISTS esquema_gastoid INT DEFAULT NULL AFTER esquema_cc,
    ADD COLUMN IF NOT EXISTS id_periodo INT DEFAULT NULL AFTER esquema_gastoid,
    ADD COLUMN IF NOT EXISTS id_unidad INT DEFAULT NULL AFTER id_periodo,
    ADD COLUMN IF NOT EXISTS id_item INT DEFAULT NULL AFTER id_unidad;

-- PASO 2: Indices para performance
ALTER TABLE pres_k2b_sr_logs
    ADD INDEX IF NOT EXISTS idx_esquema_anio (esquema_anio),
    ADD INDEX IF NOT EXISTS idx_esquema_cc (esquema_cc),
    ADD INDEX IF NOT EXISTS idx_id_periodo (id_periodo),
    ADD INDEX IF NOT EXISTS idx_id_unidad (id_unidad),
    ADD INDEX IF NOT EXISTS idx_id_item (id_item);

-- PASO 3: Popular columnas parseadas desde esquema existente
UPDATE pres_k2b_sr_logs
SET esquema_anio = CASE
        WHEN esquema IS NOT NULL AND esquema != '' AND esquema LIKE '%-%'
        THEN CAST(SUBSTRING_INDEX(esquema, '-', 1) AS UNSIGNED) + 2000
        ELSE NULL END,
    esquema_cc = CASE
        WHEN esquema IS NOT NULL AND esquema != '' AND LENGTH(esquema) - LENGTH(REPLACE(esquema, '-', '')) >= 1
        THEN CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(esquema, '-', 2), '-', -1) AS UNSIGNED)
        ELSE NULL END,
    esquema_gastoid = CASE
        WHEN esquema IS NOT NULL AND esquema != '' AND LENGTH(esquema) - LENGTH(REPLACE(esquema, '-', '')) >= 2
        THEN CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(esquema, '-', 3), '-', -1) AS UNSIGNED)
        ELSE NULL END
WHERE esquema IS NOT NULL AND esquema != '';

-- PASO 4: Vincular con tablas del ciclo
UPDATE pres_k2b_sr_logs sr
JOIN ciclo_periodo_fiscal pf ON pf.anio = sr.esquema_anio
SET sr.id_periodo = pf.id
WHERE sr.esquema_anio IS NOT NULL AND sr.id_periodo IS NULL;

UPDATE pres_k2b_sr_logs sr
JOIN ciclo_unidad_organizativa uo ON uo.cc = sr.esquema_cc
SET sr.id_unidad = uo.id
WHERE sr.esquema_cc IS NOT NULL AND sr.id_unidad IS NULL;

UPDATE pres_k2b_sr_logs sr
JOIN ciclo_item_gasto ig ON ig.gastoid_origen = sr.esquema_gastoid
SET sr.id_item = ig.id
WHERE sr.esquema_gastoid IS NOT NULL AND sr.id_item IS NULL;

-- PASO 5: Menú para la página K2B SR
INSERT INTO ciclo_menus (id, opcion, estado, direccion, submenu, descripcion, menu, nivacc) VALUES
(205, 'Solicitudes K2B', '1', 'web/mod/presupuestos/k2b_sr.php', 20, 'SR importadas desde K2B', '0', 1)
ON DUPLICATE KEY UPDATE direccion = 'web/mod/presupuestos/k2b_sr.php';

-- PASO 6: Vista de integración K2B ↔ Presupuesto
-- Nota: pres_k2b_sr_logs NO tiene columna 'id'. PK = (codigo, matricula, descripcion)
CREATE OR REPLACE VIEW v_k2b_presupuesto AS
SELECT
    sr.codigo AS sr_codigo,
    sr.codigo_link AS sr_link,
    sr.fecha AS sr_fecha,
    sr.estado AS sr_estado,
    sr.usuario AS sr_usuario,
    sr.descripcion AS sr_descripcion,
    sr.precio_unitario,
    sr.cantidad_solicitada,
    sr.cantidad_autorizada,
    sr.importe_total,
    sr.esquema,
    sr.esquema_anio,
    sr.esquema_cc,
    sr.esquema_gastoid,
    sr.orden_compra AS sr_orden_compra,
    sr.proveedor AS sr_proveedor,
    sr.monto_linea_oc_usd,
    sr.orden_stock AS sr_orden_stock,
    -- Datos del presupuesto
    pf.anio AS periodo_anio,
    pf.estado AS periodo_estado,
    uo.id AS unidad_id,
    uo.nombre AS unidad_nombre,
    uo.cc AS unidad_cc,
    ig.id AS item_id,
    ig.codigo AS item_codigo,
    ig.nombre AS item_nombre,
    -- Presupuesto y partida (si existe)
    pr.id AS presupuesto_id,
    pr.estado AS presupuesto_estado,
    pa.id AS partida_id,
    pa.monto_aprobado AS partida_aprobado,
    pa.monto_comprometido AS partida_comprometido,
    pa.monto_ejecutado AS partida_ejecutado,
    (pa.monto_aprobado - pa.monto_comprometido - pa.monto_ejecutado) AS partida_disponible
FROM pres_k2b_sr_logs sr
LEFT JOIN ciclo_periodo_fiscal pf ON sr.id_periodo = pf.id
LEFT JOIN ciclo_unidad_organizativa uo ON sr.id_unidad = uo.id
LEFT JOIN ciclo_item_gasto ig ON sr.id_item = ig.id
LEFT JOIN ciclo_presupuesto pr ON pr.id_periodo = pf.id AND pr.id_unidad = uo.id
LEFT JOIN ciclo_partida pa ON pa.id_presupuesto = pr.id AND pa.id_item = ig.id;

-- PASO 6: Vista resumen por item/unidad para dashboards
CREATE OR REPLACE VIEW v_k2b_resumen_item AS
SELECT
    sr.esquema_anio AS anio,
    uo.nombre AS unidad,
    ig.codigo AS item_codigo,
    ig.nombre AS item_nombre,
    COUNT(DISTINCT sr.codigo) AS cant_sr,
    SUM(sr.importe_total) AS total_solicitado,
    SUM(CASE WHEN sr.estado IN ('CUMPLIDA','CUMPLIMIENTOSTOCK') THEN sr.importe_total ELSE 0 END) AS total_cumplido,
    SUM(sr.monto_linea_oc_usd) AS total_oc_usd,
    pa.monto_aprobado AS presupuesto_aprobado,
    pa.monto_ejecutado AS presupuesto_ejecutado,
    (pa.monto_aprobado - pa.monto_comprometido - pa.monto_ejecutado) AS presupuesto_disponible
FROM pres_k2b_sr_logs sr
LEFT JOIN ciclo_unidad_organizativa uo ON sr.id_unidad = uo.id
LEFT JOIN ciclo_item_gasto ig ON sr.id_item = ig.id
LEFT JOIN ciclo_presupuesto pr ON pr.id_periodo = sr.id_periodo AND pr.id_unidad = uo.id
LEFT JOIN ciclo_partida pa ON pa.id_presupuesto = pr.id AND pa.id_item = ig.id
WHERE sr.esquema_anio IS NOT NULL
GROUP BY sr.esquema_anio, uo.nombre, ig.codigo, ig.nombre,
         pa.monto_aprobado, pa.monto_ejecutado, pa.monto_comprometido;
