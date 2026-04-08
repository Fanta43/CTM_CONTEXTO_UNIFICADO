-- ============================================================================
-- EETT por renglón de SR (pres_k2b_sr_logs)
-- ============================================================================
-- Cada renglón de SR puede tener EETT distintas aunque comparta matrícula.
-- Ej: matrícula 474705 (notebook) → renglón 1 alta gama, renglón 2 económica.
-- PK compuesta: (codigo, lin, estado, matricula) igual que pres_k2b_sr_logs.
-- ============================================================================

CREATE TABLE IF NOT EXISTS pres_k2b_sr_logs_eett (
    codigo                      VARCHAR(56)  NOT NULL,
    matricula                   VARCHAR(56)  NOT NULL,
    lin                         INT          NOT NULL,
    estado                      VARCHAR(255) NOT NULL,

    eett_denominacion           TEXT         DEFAULT NULL,
    eett_caracteristicas        TEXT         DEFAULT NULL,
    eett_representacion_grafica TEXT         DEFAULT NULL,
    eett_normas_fabricacion     TEXT         DEFAULT NULL,
    eett_ensayo_recepcion       TEXT         DEFAULT NULL,
    eett_responsable_recepcion  VARCHAR(255) DEFAULT NULL,
    eett_marcas                 TEXT         DEFAULT NULL,
    eett_aplicacion             TEXT         DEFAULT NULL,
    eett_forma_entrega          TEXT         DEFAULT NULL,
    eett_observaciones          TEXT         DEFAULT NULL,
    eett_proveedores            TEXT         DEFAULT NULL,
    justificacion_compra        TEXT         DEFAULT NULL,

    auditoria_usu               VARCHAR(100) DEFAULT NULL,
    auditoria_fecha             DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (codigo, matricula, lin, estado),
    CONSTRAINT fk_sr_logs_eett
        FOREIGN KEY (codigo, matricula, lin, estado)
        REFERENCES pres_k2b_sr_logs (codigo, matricula, lin, estado)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
