-- ============================================================================
-- CICLO PRESUPUESTARIO — CREATE TABLE + INSERT SEED DATA
-- ============================================================================
-- Base de datos: contexto_unificado
-- Ejecutar en orden (respeta dependencias FK).
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ──────────────────────────────────────────────────────────────────────────────
-- 1. ciclo_periodo_fiscal
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ciclo_periodo_fiscal (
    id INT(11) NOT NULL AUTO_INCREMENT,
    anio YEAR NOT NULL,
    fecha_inicio DATE DEFAULT NULL,
    fecha_fin DATE DEFAULT NULL,
    estado ENUM('abierto','en_cierre','cerrado') NOT NULL DEFAULT 'abierto',
    auditoria_usu INT(11) DEFAULT NULL,
    auditoria_fecha DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_anio (anio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO ciclo_periodo_fiscal (anio, fecha_inicio, fecha_fin, estado) VALUES
(2025, '2025-01-01', '2025-12-31', 'cerrado'),
(2026, '2026-01-01', '2026-12-31', 'abierto');

-- ──────────────────────────────────────────────────────────────────────────────
-- 2. ciclo_unidad_organizativa
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ciclo_unidad_organizativa (
    id INT(11) NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(150) NOT NULL,
    id_padre INT(11) DEFAULT NULL,
    nivel_aprobacion TINYINT(4) NOT NULL DEFAULT 1 COMMENT '1=unidad, 2=area, 3=direccion',
    responsable VARCHAR(150) DEFAULT NULL,
    cc INT(4) DEFAULT NULL COMMENT 'Centro de costos',
    itemid_origen INT(11) DEFAULT NULL,
    auditoria_usu INT(11) DEFAULT NULL,
    auditoria_fecha DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY fk_unidad_padre (id_padre),
    CONSTRAINT fk_unidad_padre FOREIGN KEY (id_padre) REFERENCES ciclo_unidad_organizativa(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO ciclo_unidad_organizativa (id, nombre, id_padre, nivel_aprobacion, responsable) VALUES
(1, 'Unidad General (por defecto)', NULL, 3, 'Administrador');

-- ──────────────────────────────────────────────────────────────────────────────
-- 3. ciclo_rol
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ciclo_rol (
    id INT(11) NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(30) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255) DEFAULT NULL,
    nivel_jerarquico TINYINT(4) NOT NULL DEFAULT 1 COMMENT '1=base, 9=maximo',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    auditoria_usu INT(11) DEFAULT NULL,
    auditoria_fecha DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO ciclo_rol (codigo, nombre, descripcion, nivel_jerarquico, activo) VALUES
('operador',     'Operador',         'Usuario base con acceso de consulta y carga', 1, 1),
('jefe_unidad',  'Jefe de Unidad',   'Responsable de una unidad organizativa',      2, 1),
('jefe_sector',  'Jefe de Sector',   'Responsable de un sector dentro de un area',  3, 1),
('jefe_area',    'Jefe de Area',     'Responsable de un area funcional',             4, 1),
('gerente',      'Gerente',          'Gerente de departamento o direccion',          5, 1),
('gerente_gral', 'Gerente General',  'Maxima autoridad ejecutiva',                   6, 1),
('admin',        'Administrador',    'Acceso total al sistema',                      9, 1);

-- ──────────────────────────────────────────────────────────────────────────────
-- 4. ciclo_usuario
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ciclo_usuario (
    id INT(11) NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    clave_hash VARCHAR(255) NOT NULL,
    id_unidad INT(11) NOT NULL,
    rol VARCHAR(20) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    auditoria_usu INT(11) DEFAULT NULL,
    auditoria_fecha DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_email (email),
    KEY fk_usuario_unidad (id_unidad),
    CONSTRAINT fk_usuario_unidad FOREIGN KEY (id_unidad) REFERENCES ciclo_unidad_organizativa(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO ciclo_usuario (id, nombre, email, clave_hash, id_unidad, rol, activo) VALUES
(1, 'Administrador', 'admin@sistema.local', SHA2('admin123', 256), 1, 'admin', 1);

-- ──────────────────────────────────────────────────────────────────────────────
-- 5. ciclo_item_gasto (PADRE / agrupador)
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ciclo_item_gasto (
    id INT(11) NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(10) NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(255) DEFAULT NULL,
    activa TINYINT(1) NOT NULL DEFAULT 1,
    orden INT(11) NOT NULL DEFAULT 0,
    gastoid_origen INT(11) DEFAULT NULL,
    auditoria_usu INT(11) DEFAULT NULL,
    auditoria_fecha DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO ciclo_item_gasto (codigo, nombre, descripcion, activa, orden) VALUES
('PERS', 'Personal',            'Gastos de personal y remuneraciones',     1, 1),
('BSRV', 'Bienes y Servicios',  'Bienes de consumo y servicios no personales', 1, 2),
('BINV', 'Bienes de Capital',   'Inversiones en activos fijos',            1, 3),
('TRSF', 'Transferencias',      'Transferencias corrientes y de capital',  1, 4);

-- ──────────────────────────────────────────────────────────────────────────────
-- 6. ciclo_categoria_gasto (HIJO, pertenece a un item)
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ciclo_categoria_gasto (
    id INT(11) NOT NULL AUTO_INCREMENT,
    id_item INT(11) NOT NULL,
    codigo VARCHAR(20) NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    unidad_medida VARCHAR(50) DEFAULT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    gastoid_origen INT(11) DEFAULT NULL,
    recursoid_origen INT(11) DEFAULT NULL,
    auditoria_usu INT(11) DEFAULT NULL,
    auditoria_fecha DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_codigo (codigo),
    KEY fk_cat_item (id_item),
    CONSTRAINT fk_cat_item FOREIGN KEY (id_item) REFERENCES ciclo_item_gasto(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO ciclo_categoria_gasto (id_item, codigo, nombre, unidad_medida, activo) VALUES
((SELECT id FROM ciclo_item_gasto WHERE codigo='PERS'), 'PERS-01', 'Sueldos basicos',         'mes',    1),
((SELECT id FROM ciclo_item_gasto WHERE codigo='PERS'), 'PERS-02', 'Horas extra',              'hora',   1),
((SELECT id FROM ciclo_item_gasto WHERE codigo='PERS'), 'PERS-03', 'Cargas sociales',          'mes',    1),
((SELECT id FROM ciclo_item_gasto WHERE codigo='BSRV'), 'BSRV-01', 'Materiales de oficina',    'unidad', 1),
((SELECT id FROM ciclo_item_gasto WHERE codigo='BSRV'), 'BSRV-02', 'Servicios de limpieza',    'mes',    1),
((SELECT id FROM ciclo_item_gasto WHERE codigo='BSRV'), 'BSRV-03', 'Mantenimiento equipos',    'unidad', 1),
((SELECT id FROM ciclo_item_gasto WHERE codigo='BINV'), 'BINV-01', 'Equipamiento informatico',  'unidad', 1),
((SELECT id FROM ciclo_item_gasto WHERE codigo='BINV'), 'BINV-02', 'Mobiliario',                'unidad', 1),
((SELECT id FROM ciclo_item_gasto WHERE codigo='TRSF'), 'TRSF-01', 'Becas y subsidios',         'unidad', 1);

-- ──────────────────────────────────────────────────────────────────────────────
-- 7. ciclo_presupuesto
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ciclo_presupuesto (
    id INT(11) NOT NULL AUTO_INCREMENT,
    id_periodo INT(11) NOT NULL,
    id_unidad INT(11) NOT NULL,
    estado ENUM('borrador','en_revision','aprobado','rechazado') NOT NULL DEFAULT 'borrador',
    monto_total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_aprobacion DATETIME DEFAULT NULL,
    auditoria_usu INT(11) DEFAULT NULL,
    auditoria_fecha DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_per_uni (id_periodo, id_unidad),
    KEY fk_pres_periodo (id_periodo),
    KEY fk_pres_unidad (id_unidad),
    CONSTRAINT fk_pres_periodo FOREIGN KEY (id_periodo) REFERENCES ciclo_periodo_fiscal(id),
    CONSTRAINT fk_pres_unidad FOREIGN KEY (id_unidad) REFERENCES ciclo_unidad_organizativa(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────────────────────────
-- 8. ciclo_partida
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ciclo_partida (
    id INT(11) NOT NULL AUTO_INCREMENT,
    id_presupuesto INT(11) NOT NULL,
    id_item INT(11) NOT NULL,
    id_categoria INT(11) NOT NULL,
    monto_proyectado DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    monto_aprobado DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    monto_comprometido DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    monto_ejecutado DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    id_partida_origen INT(11) DEFAULT NULL COMMENT 'FK a partida del periodo anterior',
    auditoria_usu INT(11) DEFAULT NULL,
    auditoria_fecha DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY fk_part_pres (id_presupuesto),
    KEY fk_part_item (id_item),
    KEY fk_part_cat (id_categoria),
    CONSTRAINT fk_part_pres FOREIGN KEY (id_presupuesto) REFERENCES ciclo_presupuesto(id),
    CONSTRAINT fk_part_item FOREIGN KEY (id_item) REFERENCES ciclo_item_gasto(id),
    CONSTRAINT fk_part_cat FOREIGN KEY (id_categoria) REFERENCES ciclo_categoria_gasto(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────────────────────────
-- 9. ciclo_config_proyeccion
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ciclo_config_proyeccion (
    id INT(11) NOT NULL AUTO_INCREMENT,
    id_periodo_destino INT(11) NOT NULL,
    id_item INT(11) DEFAULT NULL,
    id_categoria INT(11) DEFAULT NULL,
    factor_ajuste DECIMAL(5,4) NOT NULL DEFAULT 1.0000,
    techo_maximo DECIMAL(15,2) DEFAULT NULL,
    auditoria_usu INT(11) DEFAULT NULL,
    auditoria_fecha DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY fk_proj_periodo (id_periodo_destino),
    CONSTRAINT fk_proj_periodo FOREIGN KEY (id_periodo_destino) REFERENCES ciclo_periodo_fiscal(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────────────────────────
-- 10. ciclo_config_umbral
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ciclo_config_umbral (
    id INT(11) NOT NULL AUTO_INCREMENT,
    nivel ENUM('global','categoria','item') NOT NULL DEFAULT 'global',
    id_referencia INT(11) DEFAULT NULL COMMENT 'ID de categoria o item segun nivel',
    porcentaje DECIMAL(5,2) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    auditoria_usu INT(11) DEFAULT NULL,
    auditoria_fecha DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO ciclo_config_umbral (nivel, id_referencia, porcentaje, activo) VALUES
('global', NULL, 80.00, 1),
('global', NULL, 95.00, 1),
('global', NULL, 100.00, 1);

-- ──────────────────────────────────────────────────────────────────────────────
-- 11. ciclo_revision
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ciclo_revision (
    id INT(11) NOT NULL AUTO_INCREMENT,
    id_presupuesto INT(11) NOT NULL,
    id_usuario INT(11) NOT NULL,
    nivel TINYINT(4) NOT NULL DEFAULT 1,
    accion ENUM('modificar','aprobar','devolver','rechazar') NOT NULL,
    observaciones TEXT DEFAULT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY fk_rev_pres (id_presupuesto),
    KEY fk_rev_usu (id_usuario),
    CONSTRAINT fk_rev_pres FOREIGN KEY (id_presupuesto) REFERENCES ciclo_presupuesto(id),
    CONSTRAINT fk_rev_usu FOREIGN KEY (id_usuario) REFERENCES ciclo_usuario(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────────────────────────
-- 12. ciclo_revision_detalle
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ciclo_revision_detalle (
    id INT(11) NOT NULL AUTO_INCREMENT,
    id_revision INT(11) NOT NULL,
    id_partida INT(11) NOT NULL,
    monto_anterior DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    monto_nuevo DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    motivo VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (id),
    KEY fk_revdet_rev (id_revision),
    KEY fk_revdet_part (id_partida),
    CONSTRAINT fk_revdet_rev FOREIGN KEY (id_revision) REFERENCES ciclo_revision(id),
    CONSTRAINT fk_revdet_part FOREIGN KEY (id_partida) REFERENCES ciclo_partida(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────────────────────────
-- 13. ciclo_solicitud_recurso
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ciclo_solicitud_recurso (
    id INT(11) NOT NULL AUTO_INCREMENT,
    id_periodo INT(11) NOT NULL,
    id_unidad INT(11) NOT NULL,
    id_usuario_solicita INT(11) NOT NULL,
    id_usuario_aprueba INT(11) DEFAULT NULL,
    descripcion VARCHAR(255) DEFAULT NULL,
    especificaciones TEXT DEFAULT NULL,
    justificacion TEXT DEFAULT NULL,
    monto_total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    estado ENUM('pendiente','aprobada','ejecutada','rechazada','anulada') NOT NULL DEFAULT 'pendiente',
    numero_oc VARCHAR(50) DEFAULT NULL,
    observaciones_rechazo TEXT DEFAULT NULL,
    fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_aprobacion DATETIME DEFAULT NULL,
    fecha_ejecucion DATETIME DEFAULT NULL,
    auditoria_usu INT(11) DEFAULT NULL,
    auditoria_fecha DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY fk_sol_periodo (id_periodo),
    KEY fk_sol_unidad (id_unidad),
    KEY fk_sol_usu_sol (id_usuario_solicita),
    KEY fk_sol_usu_apr (id_usuario_aprueba),
    CONSTRAINT fk_sol_periodo FOREIGN KEY (id_periodo) REFERENCES ciclo_periodo_fiscal(id),
    CONSTRAINT fk_sol_unidad FOREIGN KEY (id_unidad) REFERENCES ciclo_unidad_organizativa(id),
    CONSTRAINT fk_sol_usu_sol FOREIGN KEY (id_usuario_solicita) REFERENCES ciclo_usuario(id),
    CONSTRAINT fk_sol_usu_apr FOREIGN KEY (id_usuario_aprueba) REFERENCES ciclo_usuario(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────────────────────────
-- 14. ciclo_solicitud_partida
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ciclo_solicitud_partida (
    id INT(11) NOT NULL AUTO_INCREMENT,
    id_solicitud INT(11) NOT NULL,
    id_partida INT(11) NOT NULL,
    monto DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    descripcion_linea VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (id),
    KEY fk_solpart_sol (id_solicitud),
    KEY fk_solpart_part (id_partida),
    CONSTRAINT fk_solpart_sol FOREIGN KEY (id_solicitud) REFERENCES ciclo_solicitud_recurso(id),
    CONSTRAINT fk_solpart_part FOREIGN KEY (id_partida) REFERENCES ciclo_partida(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────────────────────────
-- 15. ciclo_alerta_consumo
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ciclo_alerta_consumo (
    id INT(11) NOT NULL AUTO_INCREMENT,
    id_partida INT(11) NOT NULL,
    id_umbral INT(11) DEFAULT NULL,
    porcentaje_actual DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    resuelta TINYINT(1) NOT NULL DEFAULT 0,
    observaciones VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (id),
    KEY fk_alerta_part (id_partida),
    KEY fk_alerta_umb (id_umbral),
    CONSTRAINT fk_alerta_part FOREIGN KEY (id_partida) REFERENCES ciclo_partida(id),
    CONSTRAINT fk_alerta_umb FOREIGN KEY (id_umbral) REFERENCES ciclo_config_umbral(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────────────────────────
-- 16. ciclo_menus
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ciclo_menus (
    id INT(11) NOT NULL AUTO_INCREMENT,
    opcion VARCHAR(100) NOT NULL,
    estado VARCHAR(10) DEFAULT '1',
    direccion VARCHAR(255) DEFAULT NULL,
    submenu INT(11) DEFAULT NULL,
    descripcion VARCHAR(255) DEFAULT NULL,
    menu VARCHAR(10) DEFAULT '0',
    nivacc INT(11) DEFAULT 1,
    tipo VARCHAR(10) DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Limpiar menus existentes (opcional)
-- DELETE FROM ciclo_menus;

-- Menú raíz (presentación)
INSERT INTO ciclo_menus (id, opcion, estado, direccion, submenu, descripcion, menu, nivacc) VALUES
(1, 'Inicio', '1', 'web/int/presentacion.php', NULL, 'Pantalla principal', '0', 1);

-- Menú principal (barra de navegación)
INSERT INTO ciclo_menus (id, opcion, estado, direccion, submenu, descripcion, menu, nivacc) VALUES
(10, 'Configuracion', '1', NULL, NULL, 'CONFIGURACION', '1', 6),
(20, 'Presupuestos',  '1', NULL, NULL, 'PRESUPUESTOS',  '1', 1),
(30, 'Revision',      '1', NULL, NULL, 'REVISION',      '1', 4),
(40, 'Solicitudes K2B','1', NULL, NULL, 'SOLICITUDES DE RECURSO K2B', '1', 1),
(50, 'Reportes',      '1', NULL, NULL, 'REPORTES',      '1', 1),
(60, 'Cierre',        '1', NULL, NULL, 'CIERRE',        '1', 9);

-- Submenú Configuración (submenu = 10)
INSERT INTO ciclo_menus (id, opcion, estado, direccion, submenu, descripcion, menu, nivacc) VALUES
(101, 'Periodos Fiscales',     '1', 'web/mod/configuracion/periodos.php',   10, 'ABM periodos fiscales',       '0', 6),
(102, 'Unidades Organizativas','1', 'web/mod/configuracion/unidades.php',   10, 'Arbol de unidades',           '0', 6),
(103, 'Categorias de Gasto',   '1', 'web/mod/configuracion/categorias.php', 10, 'ABM categorias',              '0', 6),
(104, 'Items de Gasto',        '1', 'web/mod/configuracion/items.php',      10, 'ABM items (agrupadores)',      '0', 6),
(105, 'Factores Proyeccion',   '1', 'web/mod/configuracion/factores.php',   10, 'Multiplicadores y techos',    '0', 6),
(106, 'Usuarios y Roles',      '1', 'web/mod/configuracion/usuarios.php',   10, 'ABM usuarios',                '0', 9),
(107, 'Umbrales de Alerta',    '1', 'web/mod/configuracion/umbrales.php',   10, 'Porcentajes de alerta',       '0', 6),
(108, 'Roles del Sistema',     '1', 'web/mod/configuracion/roles.php',      10, 'ABM roles asignables',        '0', 9);

-- Submenú Presupuestos (submenu = 20)
INSERT INTO ciclo_menus (id, opcion, estado, direccion, submenu, descripcion, menu, nivacc) VALUES
(201, 'Generar Borrador',      '1', 'web/mod/presupuestos/generar_borrador.php', 20, 'Proyectar presupuesto', '0', 6),
(202, 'Listar Presupuestos',   '1', 'web/mod/presupuestos/listar.php',           20, 'Todos los presupuestos','0', 1),
(203, 'Detalle Partidas',      '1', 'web/mod/presupuestos/detalle.php',           20, 'Detalle por categoria', '0', 1),
(204, 'Comparativo Anual',     '1', 'web/mod/presupuestos/comparativo.php',       20, 'Comparar con anterior', '0', 1);

-- Submenú Revisión (submenu = 30)
INSERT INTO ciclo_menus (id, opcion, estado, direccion, submenu, descripcion, menu, nivacc) VALUES
(301, 'Bandeja de Revision',   '1', 'web/mod/revision/bandeja.php',           30, 'Pendientes de revision',    '0', 4),
(302, 'Revisar Presupuesto',   '1', 'web/mod/revision/revisar.php',           30, 'Editar montos',             '0', 4),
(303, 'Aprobar / Devolver',    '1', 'web/mod/revision/aprobar_devolver.php',  30, 'Acciones de aprobacion',    '0', 4),
(304, 'Historial de Cambios',  '1', 'web/mod/revision/historial.php',         30, 'Timeline de revisiones',    '0', 1);

-- Submenú Solicitudes K2B (submenu = 40)
INSERT INTO ciclo_menus (id, opcion, estado, direccion, submenu, descripcion, menu, nivacc) VALUES
(401, 'Listado de SR',         '1', 'web/mod/solicitudes/listar.php',          40, 'Solicitudes de recurso K2B por item','0', 1),
(402, 'Saldos por Item',       '1', 'web/mod/solicitudes/saldos.php',          40, 'Presupuesto K2B vs OC por item',    '0', 1);

-- Submenú Reportes (submenu = 50)
INSERT INTO ciclo_menus (id, opcion, estado, direccion, submenu, descripcion, menu, nivacc) VALUES
(501, 'Ejecucion Presupuestaria','1', 'web/mod/reportes/ejecucion.php',          50, 'Reporte principal',       '0', 1),
(502, 'Comparativo Anual',       '1', 'web/mod/reportes/comparativo_anual.php',  50, 'Multiples periodos',      '0', 1),
(503, 'Proyeccion vs Real',      '1', 'web/mod/reportes/proyeccion_vs_real.php', 50, 'Calidad de factores',     '0', 1),
(504, 'Alertas de Consumo',      '1', 'web/mod/reportes/alertas.php',            50, 'Panel de alertas',        '0', 1);

-- Submenú Cierre (submenu = 60)
INSERT INTO ciclo_menus (id, opcion, estado, direccion, submenu, descripcion, menu, nivacc) VALUES
(601, 'Iniciar Cierre',        '1', 'web/mod/cierre/iniciar.php',     60, 'Paso 1: bloquear solicitudes', '0', 9),
(602, 'Validar',               '1', 'web/mod/cierre/validar.php',     60, 'Paso 2: resolver pendientes',  '0', 9),
(603, 'Consolidar Ejecutado',  '1', 'web/mod/cierre/consolidar.php',  60, 'Paso 3: recalcular montos',    '0', 9),
(604, 'Cerrar Periodo',        '1', 'web/mod/cierre/cerrar.php',      60, 'Paso 4: cierre definitivo',    '0', 9);

-- ──────────────────────────────────────────────────────────────────────────────
-- VISTAS
-- ──────────────────────────────────────────────────────────────────────────────

-- v_saldo_partida: saldo disponible por partida
CREATE OR REPLACE VIEW v_saldo_partida AS
SELECT
    pa.id AS partida_id,
    pr.id AS presupuesto_id,
    ig.codigo AS cat_codigo,
    ig.nombre AS categoria,
    cg.codigo AS item_codigo,
    cg.nombre AS item_nombre,
    pa.monto_proyectado,
    pa.monto_aprobado AS aprobado,
    pa.monto_comprometido AS comprometido,
    pa.monto_ejecutado AS ejecutado,
    (pa.monto_aprobado - pa.monto_comprometido - pa.monto_ejecutado) AS saldo_disponible,
    CASE WHEN pa.monto_aprobado > 0
         THEN ROUND((pa.monto_ejecutado / pa.monto_aprobado) * 100, 2)
         ELSE 0 END AS porcentaje_consumo
FROM ciclo_partida pa
JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
JOIN ciclo_categoria_gasto cg ON pa.id_categoria = cg.id;

-- v_resumen_categoria: totales agrupados por item (agrupador) para un presupuesto
CREATE OR REPLACE VIEW v_resumen_categoria AS
SELECT
    pr.id AS presupuesto_id,
    ig.codigo AS cat_codigo,
    ig.nombre AS categoria,
    SUM(pa.monto_aprobado) AS aprobado,
    SUM(pa.monto_comprometido) AS comprometido,
    SUM(pa.monto_ejecutado) AS ejecutado,
    SUM(pa.monto_aprobado - pa.monto_comprometido - pa.monto_ejecutado) AS disponible
FROM ciclo_partida pa
JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
GROUP BY pr.id, ig.codigo, ig.nombre;

-- v_comparativo_anual: comparar presupuesto actual con ejecutado del anterior
CREATE OR REPLACE VIEW v_comparativo_anual AS
SELECT
    pa_act.id AS partida_actual_id,
    pr_act.id AS presupuesto_actual_id,
    ig.nombre AS categoria,
    cg.codigo AS item_codigo,
    cg.nombre AS item_nombre,
    COALESCE(pa_ant.monto_ejecutado, 0) AS ejecutado_anterior,
    pa_act.monto_proyectado AS proyectado_actual,
    pa_act.monto_aprobado AS aprobado_actual
FROM ciclo_partida pa_act
JOIN ciclo_presupuesto pr_act ON pa_act.id_presupuesto = pr_act.id
JOIN ciclo_item_gasto ig ON pa_act.id_item = ig.id
JOIN ciclo_categoria_gasto cg ON pa_act.id_categoria = cg.id
LEFT JOIN ciclo_partida pa_ant ON pa_act.id_partida_origen = pa_ant.id;

SET FOREIGN_KEY_CHECKS = 1;
