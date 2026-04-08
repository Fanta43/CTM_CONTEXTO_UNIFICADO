-- ============================================================================
-- SYNC K2B → Ciclo Presupuestario (desde pres_k2b_presupuestos)
-- ============================================================================
-- Prepara pres_k2b_presupuestos para vincular con ciclo_* y permite
-- crear presupuestos aprobados automáticamente desde datos K2B.
--
-- Flujo:
--   1) Agrega columnas anio/cc a pres_k2b_presupuestos (contexto K2B).
--   2) Agrega k2b_nombre a ciclo_item_gasto (mapeo explícito campo0→item).
--   3) Agrega origen a ciclo_presupuesto (informativo: manual/k2b).
--   4) SP que UPSERT un ciclo_presupuesto aprobado con partidas K2B.
--      Respeta la UK (id_periodo, id_unidad): un solo presupuesto por
--      período/unidad. Si ya existe (manual o k2b) se actualiza in-place.
-- ============================================================================

-- ─────────────────────────────────────────────────────────────────────────────
-- PASO 1: Columnas de contexto en pres_k2b_presupuestos
-- ─────────────────────────────────────────────────────────────────────────────
ALTER TABLE pres_k2b_presupuestos
    ADD COLUMN IF NOT EXISTS anio YEAR DEFAULT NULL AFTER importe_disponible,
    ADD COLUMN IF NOT EXISTS cc   INT  DEFAULT NULL AFTER anio;

ALTER TABLE pres_k2b_presupuestos
    ADD INDEX IF NOT EXISTS idx_k2b_pres_anio (anio),
    ADD INDEX IF NOT EXISTS idx_k2b_pres_cc   (cc);

-- ─────────────────────────────────────────────────────────────────────────────
-- PASO 2: Columna de mapeo K2B en ciclo_item_gasto
-- ─────────────────────────────────────────────────────────────────────────────
ALTER TABLE ciclo_item_gasto
    ADD COLUMN IF NOT EXISTS k2b_nombre VARCHAR(255) DEFAULT NULL AFTER gastoid_origen;

-- ─────────────────────────────────────────────────────────────────────────────
-- PASO 3: Columna origen en ciclo_presupuesto (informativa)
-- ─────────────────────────────────────────────────────────────────────────────
ALTER TABLE ciclo_presupuesto
    ADD COLUMN IF NOT EXISTS origen ENUM('manual','k2b') NOT NULL DEFAULT 'manual' AFTER estado;

-- ─────────────────────────────────────────────────────────────────────────────
-- PASO 4: Stored Procedure de sincronización
-- ─────────────────────────────────────────────────────────────────────────────
DELIMITER $$

DROP PROCEDURE IF EXISTS sp_sync_k2b_presupuesto$$

CREATE PROCEDURE sp_sync_k2b_presupuesto(IN p_anio YEAR, IN p_cc INT, IN p_usu INT)
proc_body: BEGIN
    DECLARE v_id_periodo     INT DEFAULT NULL;
    DECLARE v_id_unidad      INT DEFAULT NULL;
    DECLARE v_id_presupuesto INT DEFAULT NULL;
    DECLARE v_monto_total    DECIMAL(15,2) DEFAULT 0.00;
    DECLARE v_now            DATETIME DEFAULT NOW();
    DECLARE v_es_nuevo       TINYINT DEFAULT 0;

    -- ── 1. Resolver período fiscal ──────────────────────────────────────────
    SELECT id INTO v_id_periodo
    FROM ciclo_periodo_fiscal
    WHERE anio = p_anio
    LIMIT 1;

    IF v_id_periodo IS NULL THEN
        INSERT INTO ciclo_periodo_fiscal
            (anio, fecha_inicio, fecha_fin, estado, auditoria_usu)
        VALUES
            (p_anio, CONCAT(p_anio,'-01-01'), CONCAT(p_anio,'-12-31'), 'abierto', p_usu);
        SET v_id_periodo = LAST_INSERT_ID();
    END IF;

    -- ── 2. Resolver unidad organizativa por CC ──────────────────────────────
    SELECT id INTO v_id_unidad
    FROM ciclo_unidad_organizativa
    WHERE cc = p_cc
    LIMIT 1;

    IF v_id_unidad IS NULL THEN
        INSERT INTO ciclo_unidad_organizativa
            (nombre, cc, nivel_aprobacion, auditoria_usu)
        VALUES
            (CONCAT('Unidad CC ', p_cc), p_cc, 3, p_usu);
        SET v_id_unidad = LAST_INSERT_ID();
    END IF;

    -- ── 3. Buscar presupuesto existente (UK: id_periodo + id_unidad) ────────
    SELECT id INTO v_id_presupuesto
    FROM ciclo_presupuesto
    WHERE id_periodo = v_id_periodo
      AND id_unidad  = v_id_unidad
    LIMIT 1;

    IF v_id_presupuesto IS NOT NULL THEN
        -- Limpiar partidas previas (se recrean desde K2B)
        -- Primero eliminar dependencias en ciclo_revision_detalle
        DELETE rd FROM ciclo_revision_detalle rd
        JOIN ciclo_partida pa ON rd.id_partida = pa.id
        WHERE pa.id_presupuesto = v_id_presupuesto;

        -- Eliminar solicitud_partida vinculadas
        DELETE sp FROM ciclo_solicitud_partida sp
        JOIN ciclo_partida pa ON sp.id_partida = pa.id
        WHERE pa.id_presupuesto = v_id_presupuesto;

        -- Eliminar alertas vinculadas
        DELETE ac FROM ciclo_alerta_consumo ac
        JOIN ciclo_partida pa ON ac.id_partida = pa.id
        WHERE pa.id_presupuesto = v_id_presupuesto;

        -- Ahora sí, eliminar partidas
        DELETE FROM ciclo_partida WHERE id_presupuesto = v_id_presupuesto;

        -- Limpiar revisiones anteriores de tipo K2B
        DELETE FROM ciclo_revision
        WHERE id_presupuesto = v_id_presupuesto
          AND observaciones LIKE 'Aprobación automática desde K2B%';
    ELSE
        -- Crear presupuesto nuevo
        INSERT INTO ciclo_presupuesto
            (id_periodo, id_unidad, estado, origen, monto_total,
             fecha_creacion, fecha_aprobacion, auditoria_usu, auditoria_fecha)
        VALUES
            (v_id_periodo, v_id_unidad, 'aprobado', 'k2b', 0.00,
             v_now, v_now, p_usu, v_now);
        SET v_id_presupuesto = LAST_INSERT_ID();
        SET v_es_nuevo = 1;
    END IF;

    -- ── 4. Insertar partidas desde pres_k2b_presupuestos ────────────────────
    --    Solo filas formato directo YY-CC-CODE-DESC (excluye PPAA).
    --    Usa MAX(id) por campo0 para tomar solo el último registro (dedup).
    --    Match exacto del 3er segmento de campo0 con ciclo_item_gasto.codigo.
    --    Filtra por anio/cc parseados desde campo0 (no depende de columnas anio/cc).
    --    Agrupa por item/categoria para sumar si hay múltiples filas.
    INSERT INTO ciclo_partida
        (id_presupuesto, id_item, id_categoria,
         monto_proyectado, monto_aprobado, monto_comprometido, monto_ejecutado,
         auditoria_usu, auditoria_fecha)
    SELECT
        v_id_presupuesto,
        ig.id,
        ig.id_categoria,
        SUM(k.importe_planificado),
        SUM(k.importe_planificado),
        SUM(k.importe_definitivo),
        SUM(k.importe_causado),
        p_usu,
        v_now
    FROM pres_k2b_presupuestos k
    JOIN (
        SELECT MAX(id) AS max_id FROM pres_k2b_presupuestos
        WHERE campo0 REGEXP '^[0-9]{2}-[0-9]+-[0-9]+-'
        GROUP BY campo0
    ) ult ON k.id = ult.max_id
    JOIN ciclo_item_gasto ig
        ON SUBSTRING_INDEX(SUBSTRING_INDEX(k.campo0, '-', 3), '-', -1) = ig.codigo
    WHERE CONCAT('20', SUBSTRING(k.campo0, 1, 2)) = p_anio
      AND CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(k.campo0, '-', 2), '-', -1) AS UNSIGNED) = p_cc
    GROUP BY ig.id, ig.id_categoria;

    -- ── 5. Actualizar totales del presupuesto ───────────────────────────────
    SELECT COALESCE(SUM(monto_aprobado), 0) INTO v_monto_total
    FROM ciclo_partida
    WHERE id_presupuesto = v_id_presupuesto;

    UPDATE ciclo_presupuesto
    SET monto_total      = v_monto_total,
        estado           = 'aprobado',
        origen           = 'k2b',
        fecha_aprobacion = v_now,
        auditoria_usu    = p_usu,
        auditoria_fecha  = v_now
    WHERE id = v_id_presupuesto;

    -- ── 6. Registrar aprobación automática (todos los niveles) ──────────────
    BEGIN
        DECLARE v_nivel_max  TINYINT DEFAULT 3;
        DECLARE v_nivel      INT DEFAULT 1;
        DECLARE v_id_usuario INT DEFAULT 1;

        SELECT nivel_aprobacion INTO v_nivel_max
        FROM ciclo_unidad_organizativa WHERE id = v_id_unidad;

        SELECT id INTO v_id_usuario
        FROM ciclo_usuario WHERE rol = 'admin' LIMIT 1;
        IF v_id_usuario IS NULL THEN SET v_id_usuario = 1; END IF;

        WHILE v_nivel <= v_nivel_max DO
            INSERT INTO ciclo_revision
                (id_presupuesto, id_usuario, nivel, accion, observaciones, fecha)
            VALUES
                (v_id_presupuesto, v_id_usuario, v_nivel, 'aprobar',
                 CONCAT('Aprobación automática desde K2B - Nivel ', v_nivel),
                 v_now);
            SET v_nivel = v_nivel + 1;
        END WHILE;
    END;

    -- ── 7. Resultado ────────────────────────────────────────────────────────
    SELECT
        v_id_presupuesto AS presupuesto_id,
        v_id_periodo     AS periodo_id,
        v_id_unidad      AS unidad_id,
        v_monto_total    AS monto_total,
        v_es_nuevo       AS es_nuevo,
        (SELECT COUNT(*) FROM ciclo_partida
         WHERE id_presupuesto = v_id_presupuesto) AS cant_partidas;

END$$

DELIMITER ;

-- ============================================================================
-- USO:
--   CALL sp_sync_k2b_presupuesto(2025, 880, 9999);
--
-- MAPEO (automático):
--   campo0 tiene formato: 'YY-CC-CODIGO-DESCRIPCION (USUARIOS)'
--   ej: '25-880-1790-EQ COMPONENTES SERV HW (USUARIOS)'
--   El SP matchea CODIGO (1790) contra ciclo_item_gasto.codigo via LIKE.
--   Si se necesita override, usar k2b_nombre:
--     UPDATE ciclo_item_gasto SET k2b_nombre = '25-880-1790-EQ COMPONENTES...'
--     WHERE codigo = '1790';
--
-- BACKFILL anio/cc desde campo0 existente:
--   UPDATE pres_k2b_presupuestos
--   SET anio = CASE
--       WHEN campo0 REGEXP '^PPAA[0-9]{2}-' THEN
--           CONCAT('20', SUBSTRING(campo0, 5, 2))
--       WHEN campo0 REGEXP '^[0-9]{2}-' THEN
--           CONCAT('20', SUBSTRING(campo0, 1, 2))
--       ELSE NULL END,
--     cc = CASE
--       WHEN campo0 REGEXP '^PPAA[0-9]{2}-[0-9]+-' THEN
--           CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(campo0, '-', 2), '-', -1) AS UNSIGNED)
--       WHEN campo0 REGEXP '^[0-9]{2}-[0-9]+-' THEN
--           CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(campo0, '-', 2), '-', -1) AS UNSIGNED)
--       ELSE NULL END
--   WHERE anio IS NULL;
-- ============================================================================
