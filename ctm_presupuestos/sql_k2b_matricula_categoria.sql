-- ============================================================================
-- Mapeo Matrícula → Categoría de Gasto
-- ============================================================================
-- Tabla para vincular cada matrícula K2B con una categoría de gasto
-- dentro de su ítem correspondiente.
-- ============================================================================

CREATE TABLE IF NOT EXISTS pres_k2b_matricula_categoria (
    matricula VARCHAR(50) NOT NULL,
    id_categoria INT(11) DEFAULT NULL,
    auditoria_usu VARCHAR(50) DEFAULT NULL,
    auditoria_fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (matricula),
    KEY idx_id_categoria (id_categoria),
    CONSTRAINT fk_matcat_cat FOREIGN KEY (id_categoria) REFERENCES ciclo_categoria_gasto(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
