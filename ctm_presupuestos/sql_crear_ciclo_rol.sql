-- ============================================================================
-- Tabla: ciclo_rol
-- ABM de roles del sistema. Reemplaza arrays hardcodeados en usuarios.php.
-- ============================================================================

CREATE TABLE IF NOT EXISTS ciclo_rol (
    id INT(11) NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(30) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255) DEFAULT NULL,
    nivel_jerarquico TINYINT(4) NOT NULL DEFAULT 1 COMMENT '1=base, mayor=más permisos',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    auditoria_usu INT(11) DEFAULT NULL,
    auditoria_fecha DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Datos iniciales (migrados desde los arrays hardcodeados)
INSERT INTO ciclo_rol (codigo, nombre, descripcion, nivel_jerarquico, activo) VALUES
('operador',     'Operador',         'Usuario base con acceso de consulta y carga', 1, 1),
('jefe_unidad',  'Jefe de Unidad',   'Responsable de una unidad organizativa',      2, 1),
('jefe_sector',  'Jefe de Sector',   'Responsable de un sector dentro de un área',  3, 1),
('jefe_area',    'Jefe de Área',     'Responsable de un área funcional',            4, 1),
('gerente',      'Gerente',          'Gerente de departamento o dirección',          5, 1),
('gerente_gral', 'Gerente General',  'Máxima autoridad ejecutiva',                  6, 1),
('admin',        'Administrador',    'Acceso total al sistema',                      9, 1);

-- Agregar FK en ciclo_usuario (opcional, ejecutar si se desea integridad referencial)
-- ALTER TABLE ciclo_usuario ADD CONSTRAINT fk_usuario_rol FOREIGN KEY (rol) REFERENCES ciclo_rol(codigo);
