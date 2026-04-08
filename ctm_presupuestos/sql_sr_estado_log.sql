-- ============================================================================
-- HISTORIAL DE CAMBIOS DE ESTADO DE SR
-- ============================================================================
-- Registra cada transición de estado detectada en pres_k2b_sr_logs.
-- La fecha_deteccion es CUANDO el sistema registró el cambio (NOW()),
-- no la fecha de la SR en sí.
-- ============================================================================

-- ── Tabla de historial ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS pres_k2b_sr_estado_log (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    codigo          VARCHAR(50)  NOT NULL,
    lin             INT          DEFAULT NULL,
    matricula       VARCHAR(100) DEFAULT NULL,
    estado_anterior VARCHAR(100) DEFAULT NULL,
    estado_nuevo    VARCHAR(100) NOT NULL,
    fecha_deteccion DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo),
    INDEX idx_codigo_lin (codigo, lin),
    INDEX idx_estado_nuevo (estado_nuevo),
    INDEX idx_fecha (fecha_deteccion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Trigger: registra automáticamente al actualizar estado ──────────────────
DROP TRIGGER IF EXISTS trg_sr_estado_cambio;

DELIMITER $$
CREATE TRIGGER trg_sr_estado_cambio
AFTER UPDATE ON pres_k2b_sr_logs
FOR EACH ROW
BEGIN
    IF OLD.estado != NEW.estado OR (OLD.estado IS NULL AND NEW.estado IS NOT NULL) THEN
        INSERT INTO pres_k2b_sr_estado_log
            (codigo, lin, matricula, estado_anterior, estado_nuevo, fecha_deteccion)
        VALUES
            (NEW.codigo, NEW.lin, NEW.matricula, OLD.estado, NEW.estado, NOW());
    END IF;
END$$
DELIMITER ;

-- ── Trigger: registra estado inicial en INSERT ─────────────────────────────
DROP TRIGGER IF EXISTS trg_sr_estado_insert;

DELIMITER $$
CREATE TRIGGER trg_sr_estado_insert
AFTER INSERT ON pres_k2b_sr_logs
FOR EACH ROW
BEGIN
    IF NEW.estado IS NOT NULL AND NEW.estado != '' THEN
        INSERT INTO pres_k2b_sr_estado_log
            (codigo, lin, matricula, estado_anterior, estado_nuevo, fecha_deteccion)
        VALUES
            (NEW.codigo, NEW.lin, NEW.matricula, NULL, NEW.estado, NOW());
    END IF;
END$$
DELIMITER ;

-- ── Semilla: cargar estado actual de todos los registros existentes ─────────
-- Esto genera un registro "inicial" para cada combinación codigo/lin existente
-- usando fecha_log como fecha_deteccion aproximada.
INSERT INTO pres_k2b_sr_estado_log (codigo, lin, matricula, estado_anterior, estado_nuevo, fecha_deteccion)
SELECT codigo, lin, matricula, NULL, estado, COALESCE(fecha_log, NOW())
FROM pres_k2b_sr_logs
WHERE estado IS NOT NULL AND estado != ''
  AND codigo IS NOT NULL AND codigo != ''
ON DUPLICATE KEY UPDATE id = id;
