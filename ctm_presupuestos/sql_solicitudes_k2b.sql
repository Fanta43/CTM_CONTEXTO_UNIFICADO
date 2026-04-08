-- ============================================================================
-- MIGRACIÓN: Módulo Solicitudes → SR K2B
-- ============================================================================
-- Reemplaza las entradas de menú del antiguo módulo de solicitudes internas
-- (ciclo_solicitud_recurso) por el nuevo módulo de SR K2B (pres_k2b_sr_logs).
--
-- Archivos nuevos:
--   solicitudes/listar.php      → Listado de SR agrupadas por ítem
--   solicitudes/detalle_sr.php  → Detalle individual de una SR (incluido en listar.php)
--   solicitudes/saldos.php      → Saldos por ítem (ppto K2B vs OC)
--   solicitudes/notificar.php   → Notificación por email (incluido en listar.php)
--   solicitudes/borradores.php  → Gestión de borradores de SR proyectadas
--
-- Archivos reemplazados (respaldados como *_old.php):
--   crear.php, mis_solicitudes.php, aprobar.php, ejecutar.php, saldos.php
-- ============================================================================

-- ── Actualizar menú Solicitudes (submenu = 40) ─────────────────────────────

-- 401: Antes "Crear Solicitud" → ahora "Listado de SR"
UPDATE ciclo_menus SET
    opcion     = 'Listado de SR',
    direccion  = 'web/mod/solicitudes/listar.php',
    descripcion = 'Solicitudes de recurso K2B por ítem',
    nivacc     = 1
WHERE id = 401;

-- 402: Antes "Mis Solicitudes" → ahora "Saldos por Ítem"
UPDATE ciclo_menus SET
    opcion     = 'Saldos por Item',
    direccion  = 'web/mod/solicitudes/saldos.php',
    descripcion = 'Presupuesto K2B vs OC por ítem de gasto',
    nivacc     = 1
WHERE id = 402;

-- 403: Antes "Aprobar SR" → ahora "Borradores SR"
UPDATE ciclo_menus SET
    opcion     = 'Borradores SR',
    estado     = '1',
    direccion  = 'web/mod/solicitudes/borradores.php',
    descripcion = 'BORRADORES SR PROY'
WHERE id = 403;

-- 404-405: Deshabilitar las entradas que ya no se usan
UPDATE ciclo_menus SET estado = '0' WHERE id IN (404, 405);

-- ── Actualizar entrada 205 de k2b_sr.php para redirigir al nuevo módulo ────
-- Opción A: redirigir al nuevo listar.php
UPDATE ciclo_menus SET
    direccion  = 'web/mod/solicitudes/listar.php',
    descripcion = 'SR importadas desde K2B (redirigido a Solicitudes)'
WHERE id = 205;

-- ── Renombrar el menú padre "Solicitudes" ──────────────────────────────────
-- NOTA: En la BD real el menú padre es id=4, no id=40 (sql_crear_tablas.sql usa 40)
UPDATE ciclo_menus SET
    opcion      = 'SOLICITUDES K2B',
    direccion   = 'web/mod/solicitudes/listar.php',
    descripcion = 'SOLICITUDES DE RECURSO K2B'
WHERE id = 4;
-- Fallback para instalaciones que usen id=40
UPDATE ciclo_menus SET
    opcion      = 'Solicitudes K2B',
    descripcion = 'SOLICITUDES DE RECURSO K2B'
WHERE id = 40;
