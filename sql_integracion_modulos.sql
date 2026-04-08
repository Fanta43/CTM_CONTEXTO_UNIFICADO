-- ============================================================================
-- INTEGRACION ENTRE MODULOS CTM
-- ============================================================================
-- Base de datos: contexto_unificado
-- Crea: 1 tabla de eventos + 5 vistas cross-module
-- Prefijo: integ_ / vw_integ_
-- Fecha: 2026-04-08
-- ============================================================================

-- ──────────────────────────────────────────────────────────────────────────────
-- 1. TABLA integ_eventos - Log de eventos cross-module
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS integ_eventos (
    id INT(11) NOT NULL AUTO_INCREMENT,
    modulo_origen VARCHAR(30) NOT NULL COMMENT 'ctm_inventarios|ctm_morebius|ctm_presupuestos',
    tipo VARCHAR(50) NOT NULL COMMENT 'nueva_compra|nuevo_prestamo|nueva_reparacion|solicitud_aprobada',
    cc VARCHAR(10) DEFAULT NULL COMMENT 'Centro de costos relacionado',
    legajo VARCHAR(20) DEFAULT NULL COMMENT 'Legajo del empleado involucrado',
    referencia_id VARCHAR(50) DEFAULT NULL COMMENT 'ID del registro origen (pcid, prestamoid, etc)',
    descripcion VARCHAR(255) DEFAULT NULL,
    datos_json TEXT DEFAULT NULL COMMENT 'Datos adicionales en formato JSON',
    leido TINYINT(1) NOT NULL DEFAULT 0,
    auditoria_usu VARCHAR(20) DEFAULT NULL,
    auditoria_fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_integ_modulo (modulo_origen),
    KEY idx_integ_tipo (tipo),
    KEY idx_integ_cc (cc),
    KEY idx_integ_fecha (auditoria_fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────────────────────────
-- 2. VISTA vw_integ_cc_equipos_presupuesto
--    Cruza equipos de inventario con presupuesto BINV por centro de costo
-- ──────────────────────────────────────────────────────────────────────────────
-- Columnas de centro de costo por tabla:
--   inve_pc:          centrocosto (VARCHAR, ej: '882')
--   inve_impresoras:  conidcc (INT, mapea a vw_accc.conidcc, no a cc directo)
--   inve_monitores:   sin columna cc directa (solo pcc)
--   inve_otros:       sin columna cc directa (solo pcc)
-- Solo inve_pc tiene mapeo directo a ciclo_unidad_organizativa.cc

CREATE OR REPLACE VIEW vw_integ_cc_equipos_presupuesto AS
SELECT
    uo.id AS unidad_id,
    uo.nombre AS unidad_nombre,
    uo.cc AS centro_costo,
    -- PCs: vinculadas por centrocosto directo
    COALESCE(eq_pc.total_pc, 0) AS total_pc,
    -- Impresoras: vinculadas por conidcc (referencia indirecta)
    COALESCE(eq_imp.total_impresoras, 0) AS total_impresoras,
    -- Monitores y Otros: sin vinculo directo a cc, se cuentan globalmente
    (COALESCE(eq_pc.total_pc, 0) + COALESCE(eq_imp.total_impresoras, 0)) AS total_equipos_vinculados,
    pr.id AS presupuesto_id,
    pr.estado AS presupuesto_estado,
    pr.monto_total AS presupuesto_total,
    COALESCE(pa_binv.monto_aprobado, 0) AS binv_aprobado,
    COALESCE(pa_binv.monto_ejecutado, 0) AS binv_ejecutado,
    COALESCE(pa_binv.monto_comprometido, 0) AS binv_comprometido,
    (COALESCE(pa_binv.monto_aprobado, 0) - COALESCE(pa_binv.monto_ejecutado, 0)
     - COALESCE(pa_binv.monto_comprometido, 0)) AS binv_disponible
FROM ciclo_unidad_organizativa uo
LEFT JOIN (
    SELECT centrocosto, COUNT(*) AS total_pc
      FROM inve_pc
     WHERE estadoid = '1' AND (baja = '0' OR baja IS NULL)
     GROUP BY centrocosto
) eq_pc ON eq_pc.centrocosto = CAST(uo.cc AS CHAR)
LEFT JOIN (
    SELECT CAST(conidcc AS CHAR) AS cc_str, COUNT(*) AS total_impresoras
      FROM inve_impresoras
     WHERE estadoid = '1'
     GROUP BY conidcc
) eq_imp ON eq_imp.cc_str = CAST(uo.cc AS CHAR)
LEFT JOIN ciclo_periodo_fiscal pf ON pf.estado = 'abierto'
LEFT JOIN ciclo_presupuesto pr ON pr.id_unidad = uo.id AND pr.id_periodo = pf.id
LEFT JOIN ciclo_partida pa_binv ON pa_binv.id_presupuesto = pr.id
    AND pa_binv.id_item = (SELECT id FROM ciclo_item_gasto WHERE codigo = 'BINV' LIMIT 1)
WHERE uo.cc IS NOT NULL;

-- ──────────────────────────────────────────────────────────────────────────────
-- 3. VISTA vw_integ_personal_equipos
--    Personal con equipos asignados y prestamos activos
--    Nota: solo existe inve_prestamos (more_prestamos no existe en BD)
-- ──────────────────────────────────────────────────────────────────────────────
CREATE OR REPLACE VIEW vw_integ_personal_equipos AS
SELECT
    vp.usuarioid,
    vp.ndoc,
    vp.apellido,
    vp.nombres,
    vp.organicaid AS cc,
    COALESCE(inv.cant_pc, 0) AS cant_pc_inventario,
    COALESCE(ip.cant_prestamos, 0) AS prestamos_activos
FROM vw_personas vp
LEFT JOIN (
    SELECT legajo, COUNT(*) AS cant_pc
      FROM inve_pc
     WHERE estadoid = '1' AND (baja = '0' OR baja IS NULL)
     GROUP BY legajo
) inv ON inv.legajo = vp.usuarioid
LEFT JOIN (
    SELECT legajo, COUNT(*) AS cant_prestamos
      FROM inve_prestamos
     WHERE devuelto = '0'
     GROUP BY legajo
) ip ON ip.legajo = vp.usuarioid
WHERE vp.estadoid = '1';

-- ──────────────────────────────────────────────────────────────────────────────
-- 4. VISTA vw_integ_prestamos_unificados
--    Prestamos de inve_prestamos (unica tabla de prestamos existente)
--    Preparada para agregar UNION ALL si se crea more_prestamos en el futuro
-- ──────────────────────────────────────────────────────────────────────────────
CREATE OR REPLACE VIEW vw_integ_prestamos_unificados AS
SELECT
    'inventario' AS origen,
    ip.prestamoid,
    ip.id_pc,
    ip.id_impresoras,
    ip.id_monitores,
    ip.id_otros,
    ip.legajo,
    ip.centrocosto,
    ip.responsable,
    ip.fechaprestamo,
    ip.devolucion_estimada,
    ip.devuelto,
    ip.fechadevolucion,
    ip.observaciones
FROM inve_prestamos ip;

-- ──────────────────────────────────────────────────────────────────────────────
-- 5. VISTA vw_integ_reparaciones_unificadas
--    Reparaciones de inve_reparaciones (unica tabla existente)
--    Columnas reales: fecha_envio, fecha_devolucion, problema (no centrocosto)
-- ──────────────────────────────────────────────────────────────────────────────
CREATE OR REPLACE VIEW vw_integ_reparaciones_unificadas AS
SELECT
    'inventario' AS origen,
    ir.reparacionid,
    ir.id_pc,
    ir.id_impresoras,
    ir.id_monitores,
    ir.id_otros,
    ir.reparado,
    ir.fecha_envio,
    ir.devolucion_estimada,
    ir.fecha_devolucion,
    ir.problema,
    ir.observaciones,
    ir.id_proveedores,
    ir.id_zonas_inventario
FROM inve_reparaciones ir;

-- ──────────────────────────────────────────────────────────────────────────────
-- 6. VISTA vw_integ_ejecucion_inventario
--    Ejecucion presupuestaria BINV vs compras reales de inventario
-- ──────────────────────────────────────────────────────────────────────────────
CREATE OR REPLACE VIEW vw_integ_ejecucion_inventario AS
SELECT
    pf.anio,
    uo.nombre AS unidad,
    uo.cc AS centro_costo,
    ig.nombre AS item_gasto,
    cg.nombre AS categoria,
    pa.monto_aprobado,
    pa.monto_ejecutado,
    pa.monto_comprometido,
    (pa.monto_aprobado - pa.monto_ejecutado - pa.monto_comprometido) AS saldo_disponible,
    COALESCE(compras.total_compras, 0) AS total_compras_inventario,
    COALESCE(compras.cant_equipos, 0) AS cant_equipos_comprados
FROM ciclo_partida pa
JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
JOIN ciclo_periodo_fiscal pf ON pr.id_periodo = pf.id
JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
JOIN ciclo_categoria_gasto cg ON pa.id_categoria = cg.id
LEFT JOIN (
    SELECT centrocosto,
           YEAR(fechacompra) AS anio_compra,
           SUM(CAST(NULLIF(monto, '0') AS DECIMAL(15,2))) AS total_compras,
           COUNT(*) AS cant_equipos
      FROM inve_pc
     WHERE estadoid = '1' AND monto IS NOT NULL AND monto != '' AND monto != '0'
     GROUP BY centrocosto, YEAR(fechacompra)
) compras ON compras.centrocosto = CAST(uo.cc AS CHAR) AND compras.anio_compra = pf.anio
WHERE ig.codigo = 'BINV';
