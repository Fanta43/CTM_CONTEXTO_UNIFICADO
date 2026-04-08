-- ============================================================================
-- MIGRACIÓN DE MENÚS - ctm_morebius v2.0
-- ============================================================================
-- Actualiza more_menus para apuntar a las nuevas páginas _v2.
-- Las rutas viejas se preservan: los archivos originales NO se borran.
-- Ejecutar en la base: contexto_unificado
-- ============================================================================

-- ── 1. Actualizar menú principal "Inicio" (id=1) al nuevo dashboard ──────────
UPDATE more_menus SET direccion = 'web/mod/inicio/dashboard.php' WHERE id = 1;

-- ── 2. Inventario → PCs: apuntar a listar_v2 ────────────────────────────────
UPDATE more_menus SET direccion = 'web/mod/inventario/pc/listar_v2.php' WHERE id = 2 AND opcion LIKE '%Inventario%';

-- ── 3. Submenú PCs → listar_v2 (si existe menú de listar PCs) ───────────────
UPDATE more_menus SET direccion = 'web/mod/inventario/pc/listar_v2.php' WHERE id = 11;

-- ── 4. Resumen/detalle de PC ─────────────────────────────────────────────────
UPDATE more_menus SET direccion = 'web/mod/inventario/pc/resumen_v2.php' WHERE id = 24;

-- ── 5. Reportes → Artículos ──────────────────────────────────────────────────
UPDATE more_menus SET direccion = 'web/mod/reportes/listar_v2.php' WHERE id = 4;

-- ── 6. Personal → Plantel ────────────────────────────────────────────────────
UPDATE more_menus SET direccion = 'web/mod/personal/listar_plantel_v2.php' WHERE id = 41 AND opcion LIKE '%Prueba%';

-- ── 7. Equipos → Organigrama ─────────────────────────────────────────────────
UPDATE more_menus SET direccion = 'web/mod/equipos/organigrama_v2.php' WHERE id = 119;

-- ── 8. Equipos → por Unidad (Notebook xUnidad) ──────────────────────────────
UPDATE more_menus SET direccion = 'web/mod/equipos/xunidad_v2.php' WHERE id = 185;

-- ── 9. Admin → Marcas ────────────────────────────────────────────────────────
UPDATE more_menus SET direccion = 'web/mod/admin/marcas/listar_v2.php' WHERE id = 30;

-- ── 10. Admin → Ubicaciones ──────────────────────────────────────────────────
UPDATE more_menus SET direccion = 'web/mod/admin/ubicacion/listar_v2.php' WHERE id = 32;

-- ── 11. Admin → Tipos (era SO) ───────────────────────────────────────────────
UPDATE more_menus SET direccion = 'web/mod/admin/so/listar_v2.php', opcion = 'Tipos Artículo' WHERE id = 31;

-- ── 12. Admin → Tipos Dispositivo (era tipos_impresoras) ─────────────────────
UPDATE more_menus SET direccion = 'web/mod/admin/tipos_impresoras/listar_v2.php', opcion = 'Tipos Dispositivo' WHERE id = 33;

-- ── 13. Admin → Sectores (nueva página) ──────────────────────────────────────
-- Reutilizamos un submenu existente o creamos nuevo si no hay slot libre
-- Usaremos el estado=34 (Tablas EXT) que ya existe como menú padre
UPDATE more_menus SET direccion = 'web/mod/admin/tablas_externas/listar_v2.php' WHERE id = 34;

-- ── 14. Admin → Sectores (nueva entrada bajo Admin) ──────────────────────────
INSERT IGNORE INTO more_menus (id, opcion, estado, direccion, submenu, descripcion, menu, nivacc, tipo)
VALUES (42, 'Sectores', '1', 'web/mod/admin/sectores/listar_v2.php', 3, 'Centros de costo y sectores', 2, 9, NULL);

-- ── 15. Personal → Plantel (nueva entrada limpia) ────────────────────────────
INSERT IGNORE INTO more_menus (id, opcion, estado, direccion, submenu, descripcion, menu, nivacc, tipo)
VALUES (43, 'Plantel', '1', 'web/mod/personal/listar_plantel_v2.php', 8, 'Listado de personal con equipos', 2, 1, NULL);

-- ── 16. Equipos → por Unidad (asegurar que esté accesible) ──────────────────
UPDATE more_menus SET opcion = 'Equipos x Unidad' WHERE id = 185;

-- ============================================================================
-- VERIFICACIÓN: ejecutar después de los UPDATE
-- SELECT id, opcion, estado, direccion, submenu, menu, nivacc FROM more_menus ORDER BY id;
-- ============================================================================
