-- ============================================================================
-- SWAP: ciclo_categoria_gasto <-> ciclo_item_gasto
-- ============================================================================
-- Las tablas estaban conceptualmente intercambiadas.
-- Lo que se llamaba ciclo_categoria_gasto contenía ítems y viceversa.
-- Este script corrige los nombres para que coincidan con su contenido real.
-- Ejecutar en MySQL/MariaDB.
-- ============================================================================

-- PASO 1: Renombrar tablas (swap via tabla temporal)
RENAME TABLE ciclo_categoria_gasto TO __temp_swap_tabla,
             ciclo_item_gasto TO ciclo_categoria_gasto,
             __temp_swap_tabla TO ciclo_item_gasto;

-- PASO 2: Renombrar columnas FK en ciclo_item_gasto (ex ciclo_categoria_gasto)
-- La tabla de ítems tiene un FK id_categoria que apunta a la tabla de categorías.
-- Después del swap, esa columna ahora se llama id_item y debe llamarse id_categoria.
ALTER TABLE ciclo_item_gasto
    CHANGE COLUMN id_item id_categoria INT,
    CHANGE COLUMN ID_ITEM id_categoria INT;

-- PASO 3: Renombrar columnas FK en ciclo_partida
ALTER TABLE ciclo_partida
    CHANGE COLUMN id_categoria __temp_id INT,
    CHANGE COLUMN id_item id_categoria INT,
    CHANGE COLUMN __temp_id id_item INT;

-- PASO 4: Renombrar columnas FK en ciclo_config_proyeccion
ALTER TABLE ciclo_config_proyeccion
    CHANGE COLUMN id_categoria __temp_id INT,
    CHANGE COLUMN id_item id_categoria INT,
    CHANGE COLUMN __temp_id id_item INT;

-- PASO 5: Renombrar columnas FK en ciclo_config_umbral (si tiene id_referencia no aplica swap directo)
-- ciclo_config_umbral usa nivel + id_referencia genérico, no necesita rename de columna.

-- PASO 6: Renombrar columnas FK en ciclo_solicitud_partida (si tiene id_categoria/id_item)
-- Verificar si esta tabla tiene estas columnas. Si no, omitir.
-- ALTER TABLE ciclo_solicitud_partida
--     CHANGE COLUMN id_categoria __temp_id INT,
--     CHANGE COLUMN id_item id_categoria INT,
--     CHANGE COLUMN __temp_id id_item INT;

-- ============================================================================
-- NOTA: Revisar las vistas (v_saldo_partida, v_resumen_categoria, v_comparativo_anual)
-- y recrearlas con los nombres de tabla/columna corregidos.
-- ============================================================================
