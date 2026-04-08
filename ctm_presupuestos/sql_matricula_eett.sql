-- ============================================================================
-- MIGRACIÓN: Campos EETT y Justificación de Compra en pres_k2b_matricula_categoria
-- ============================================================================
-- Agrega campos de Especificaciones Técnicas (EETT) y Justificación de Compra
-- a la tabla pres_k2b_matricula_categoria, que ya almacena la categoría de
-- cada matrícula K2B.
--
-- Campos EETT:
--   eett_denominacion            - Denominación del material
--   eett_caracteristicas         - Características Nominales
--   eett_representacion_grafica  - Representación Gráfica
--   eett_normas_fabricacion      - Normas de Fabricación
--   eett_ensayo_recepcion        - Ensayo para la Recepción
--   eett_responsable_recepcion   - Responsable de la Recepción
--   eett_marcas                  - Marcas
--   eett_aplicacion              - Aplicación
--   eett_forma_entrega           - Forma de Entrega
--   eett_observaciones           - Observaciones
--   eett_proveedores             - Proveedores
--
-- Campo adicional:
--   justificacion_compra         - Justificación de compra (texto libre)
-- ============================================================================

ALTER TABLE pres_k2b_matricula_categoria
    ADD COLUMN eett_denominacion           TEXT DEFAULT NULL AFTER id_categoria,
    ADD COLUMN eett_caracteristicas        TEXT DEFAULT NULL AFTER eett_denominacion,
    ADD COLUMN eett_representacion_grafica TEXT DEFAULT NULL AFTER eett_caracteristicas,
    ADD COLUMN eett_normas_fabricacion     TEXT DEFAULT NULL AFTER eett_representacion_grafica,
    ADD COLUMN eett_ensayo_recepcion       TEXT DEFAULT NULL AFTER eett_normas_fabricacion,
    ADD COLUMN eett_responsable_recepcion  VARCHAR(255) DEFAULT NULL AFTER eett_ensayo_recepcion,
    ADD COLUMN eett_marcas                 TEXT DEFAULT NULL AFTER eett_responsable_recepcion,
    ADD COLUMN eett_aplicacion             TEXT DEFAULT NULL AFTER eett_marcas,
    ADD COLUMN eett_forma_entrega          TEXT DEFAULT NULL AFTER eett_aplicacion,
    ADD COLUMN eett_observaciones          TEXT DEFAULT NULL AFTER eett_forma_entrega,
    ADD COLUMN eett_proveedores            TEXT DEFAULT NULL AFTER eett_observaciones,
    ADD COLUMN justificacion_compra        TEXT DEFAULT NULL AFTER eett_proveedores;
