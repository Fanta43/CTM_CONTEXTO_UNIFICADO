-- ============================================================================
-- MIGRACIÓN: Tabla paralela pres_sr_borrador_eett
-- ============================================================================
-- Almacena EETT y Justificación de Compra para cada borrador de SR.
-- Relación 1:1 con pres_sr_borrador (id_borrador es PK y FK).
-- Los campos EETT se pre-cargan desde pres_k2b_matricula_categoria
-- (datos de la matrícula) y pueden editarse independientemente por borrador.
-- ============================================================================

CREATE TABLE IF NOT EXISTS pres_sr_borrador_eett (
    id_borrador                  INT          NOT NULL PRIMARY KEY,
    eett_denominacion            TEXT         DEFAULT NULL,
    eett_caracteristicas         TEXT         DEFAULT NULL,
    eett_representacion_grafica  TEXT         DEFAULT NULL,
    eett_normas_fabricacion      TEXT         DEFAULT NULL,
    eett_ensayo_recepcion        TEXT         DEFAULT NULL,
    eett_responsable_recepcion   VARCHAR(255) DEFAULT NULL,
    eett_marcas                  TEXT         DEFAULT NULL,
    eett_aplicacion              TEXT         DEFAULT NULL,
    eett_forma_entrega           TEXT         DEFAULT NULL,
    eett_observaciones           TEXT         DEFAULT NULL,
    eett_proveedores             TEXT         DEFAULT NULL,
    justificacion_compra         TEXT         DEFAULT NULL,
    auditoria_usu                VARCHAR(50)  DEFAULT NULL,
    auditoria_fecha              DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_borrador_eett_borrador
        FOREIGN KEY (id_borrador) REFERENCES pres_sr_borrador(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
