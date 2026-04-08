<?php
/**
 * ============================================================================
 * Categorías de Gasto - ABM
 * ============================================================================
 * ID menú: 103 | Tabla: ciclo_categoria_gasto
 * Columnas: id, id_item, codigo, nombre, unidad_medida, activo,
 *           gastoid_origen, recursoid_origen, auditoria_usu, auditoria_fecha
 * Cada categoría pertenece a un ítem (id_item FK → ciclo_item_gasto).
 * ============================================================================
 */

$db = '1';
$accion = Enmascarar_get('accion');
$id     = Enmascarar_get('id');
$msg    = '';

// Filtro por ítem padre via POST (combo onchange)
$filtro_item = Enmascarar_get('item');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['filtro_item'])) {
    $filtro_item = Enmascarar_post('filtro_item');
}

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion_post = Enmascarar_post('accion_post');

    // ── CREAR ────────────────────────────────────────────────────────────────
    if ($accion_post === 'crear') {
        $id_item       = Enmascarar_post('id_item');
        $codigo        = Enmascarar_post('codigo');
        $nombre        = Enmascarar_post('nombre');
        $unidad_medida = Enmascarar_post('unidad_medida');

        if (empty($codigo) || empty($nombre) || $id_item === 'null' || empty($id_item)) {
            $msg = generarAlertJS('Error', 'Código, nombre e ítem padre son obligatorios.', 'error');
        } else {
            $sql_dup = "SELECT id FROM ciclo_categoria_gasto WHERE codigo = '$codigo';";
            if (sql_do_array(sql_do_query($sql_dup, $db, $usu))) {
                $msg = generarAlertJS('Error', "Ya existe una categoría con el código '$codigo'.", 'error');
            } else {
                $sql_cat = "SELECT id FROM ciclo_item_gasto WHERE id = '$id_item' AND activo = 1;";
                if (!sql_do_array(sql_do_query($sql_cat, $db, $usu))) {
                    $msg = generarAlertJS('Error', 'El ítem padre seleccionado no está activo.', 'error');
                } else {
                    $sql_ins = "INSERT INTO ciclo_categoria_gasto (id_item, codigo, nombre, unidad_medida, activo, auditoria_usu)
                                VALUES ('$id_item', '$codigo', '$nombre', '$unidad_medida', 1, '$usu');";
                    sql_do_query($sql_ins, $db, $usu);
                    $msg = generarAlertJS('Categoría creada', "La categoría '$nombre' fue creada correctamente.", 'success', "index.php?estado=$estado");
                }
            }
        }
    }

    // ── EDITAR ───────────────────────────────────────────────────────────────
    if ($accion_post === 'editar') {
        $edit_id       = Enmascarar_post('edit_id');
        $nombre        = Enmascarar_post('nombre');
        $unidad_medida = Enmascarar_post('unidad_medida');

        if (empty($nombre)) {
            $msg = generarAlertJS('Error', 'El nombre es obligatorio.', 'error');
        } else {
            $sql_upd = "UPDATE ciclo_categoria_gasto SET nombre = '$nombre', unidad_medida = '$unidad_medida',
                        auditoria_usu = '$usu' WHERE id = '$edit_id';";
            sql_do_query($sql_upd, $db, $usu);
            $msg = generarAlertJS('Actualizado', 'La categoría fue actualizada correctamente.', 'success', "index.php?estado=$estado");
        }
    }

    // ── TOGGLE ACTIVO ────────────────────────────────────────────────────────
    if ($accion_post === 'toggle_activo') {
        $toggle_id = Enmascarar_post('toggle_id');

        $sql_act = "SELECT activo FROM ciclo_categoria_gasto WHERE id = '$toggle_id';";
        $row_act = sql_do_array(sql_do_query($sql_act, $db, $usu));

        if ($row_act['ACTIVO'] == 1) {
            // Validar que no tenga partidas en período abierto
            $sql_check = "SELECT pa.id FROM ciclo_partida pa
                          JOIN ciclo_presupuesto p ON pa.id_presupuesto = p.id
                          JOIN ciclo_periodo_fiscal pf ON p.id_periodo = pf.id AND pf.estado = 'abierto'
                          WHERE pa.id_categoria = '$toggle_id' LIMIT 1;";
            if (sql_do_array(sql_do_query($sql_check, $db, $usu))) {
                $msg = generarAlertJS('Error', 'No se puede desactivar: tiene partidas en el período abierto.', 'warning');
            } else {
                sql_do_query("UPDATE ciclo_categoria_gasto SET activo = 0, auditoria_usu = '$usu' WHERE id = '$toggle_id';", $db, $usu);
                $msg = generarAlertJS('Desactivada', 'La categoría fue desactivada.', 'success', "index.php?estado=$estado");
            }
        } else {
            sql_do_query("UPDATE ciclo_categoria_gasto SET activo = 1, auditoria_usu = '$usu' WHERE id = '$toggle_id';", $db, $usu);
            $msg = generarAlertJS('Activada', 'La categoría fue activada.', 'success', "index.php?estado=$estado");
        }
    }
}

echo $msg;

// ═══════════════════════════════════════════════════════════════════════════════
// VISTA: FORMULARIO NUEVO / EDITAR
// ═══════════════════════════════════════════════════════════════════════════════
if ($accion === 'nueva' || $accion === 'editar'):
    $row = null;
    if ($accion === 'editar' && $id) {
        $row = sql_do_array(sql_do_query("SELECT * FROM ciclo_categoria_gasto WHERE id = '$id';", $db, $usu));
        if (!$row) { echo generarAlertJS('Error', 'Categoría no encontrada.', 'error', "index.php?estado=$estado"); return; }
    }
    $titulo_form = ($accion === 'nueva') ? 'Nueva Categoría de Gasto' : 'Editar Categoría de Gasto';
    $sql_items = "SELECT id AS ID, CONCAT(codigo, ' - ', nombre) AS NOMBRE FROM ciclo_item_gasto WHERE activo = 1 ORDER BY codigo;";
?>

<?php echo generarTituloSeccion($titulo_form, 'bi bi-folder'); ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <input type="hidden" name="accion_post" value="<?php echo ($accion === 'nueva') ? 'crear' : 'editar'; ?>">
            <?php if ($row): ?>
                <input type="hidden" name="edit_id" value="<?php echo $row['ID']; ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Ítem padre <span class="text-danger">*</span></label>
                    <?php if ($accion === 'nueva'): ?>
                        <?php echo imp_combo_w('id_item', $sql_items, $db, $usu, $filtro_item); ?>
                    <?php else: ?>
                        <?php
                        $item_row = sql_do_array(sql_do_query("SELECT CONCAT(codigo, ' - ', nombre) AS NOMBRE FROM ciclo_item_gasto WHERE id = '" . $row['ID_ITEM'] . "';", $db, $usu));
                        ?>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($item_row['NOMBRE'] ?? ''); ?>" disabled>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Código <span class="text-danger">*</span></label>
                    <?php if ($accion === 'nueva'): ?>
                        <input type="text" class="form-control" name="codigo" maxlength="20" required>
                    <?php else: ?>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['CODIGO']); ?>" disabled>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Unidad de Medida</label>
                    <input type="text" class="form-control" name="unidad_medida"
                           value="<?php echo htmlspecialchars($row['UNIDAD_MEDIDA'] ?? ''); ?>"
                           placeholder="Ej: unidad, litro, kg">
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nombre"
                           value="<?php echo htmlspecialchars($row['NOMBRE'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-pade"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                <a href="index.php?estado=<?php echo $estado; ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php
// ═══════════════════════════════════════════════════════════════════════════════
// VISTA: LISTADO
// ═══════════════════════════════════════════════════════════════════════════════
else:
    $filtro_sql = '';
    if (!empty($filtro_item) && $filtro_item !== 'null') {
        $filtro_sql = "AND cg.id_item = '$filtro_item'";
    }
    $sql_list = "SELECT cg.*, ig.nombre AS ITEM_NOMBRE, ig.codigo AS ITEM_CODIGO
                 FROM ciclo_categoria_gasto cg
                 JOIN ciclo_item_gasto ig ON cg.id_item = ig.id
                 WHERE 1=1 $filtro_sql
                 ORDER BY ig.codigo, cg.codigo;";
    $result_list = sql_do_query($sql_list, $db, $usu);

    $sql_combo_items = "SELECT id AS ID, CONCAT(codigo, ' - ', nombre) AS NOMBRE FROM ciclo_item_gasto ORDER BY codigo;";
?>

<?php echo generarTituloSeccion('Categorías de Gasto', 'bi bi-folder'); ?>

<div class="row mb-3 align-items-end">
    <div class="col-md-4">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <label class="form-label fw-semibold small">Filtrar por ítem padre</label>
            <?php echo imp_combo_w_oc('filtro_item', $sql_combo_items, $db, $usu, $filtro_item); ?>
        </form>
    </div>
    <?php if ($nivel >= 6): ?>
    <div class="col-md-4">
        <a href="index.php?estado=<?php echo $estado; ?>&accion=nueva<?php echo (!empty($filtro_item) && $filtro_item !== 'null') ? '&item=' . $filtro_item : ''; ?>"
           class="btn btn-pade">
            <i class="bi bi-plus-circle me-1"></i>Nueva Categoría
        </a>
    </div>
    <?php endif; ?>
</div>

<div class="table-responsive">
    <table class="table table-hover table-pade">
        <?php echo generarTablaHeader(['Código', 'Nombre', 'Ítem Padre', 'Unidad Medida', 'Estado', 'Acciones']); ?>
        <tbody>
<?php
    $n = 0;
    while ($row = sql_do_array($result_list)):
        $n++;
        $activo_badge = ($row['ACTIVO'] == 1)
            ? '<span class="badge bg-success">Activo</span>'
            : '<span class="badge bg-secondary">Inactivo</span>';
?>
            <tr class="<?php echo claseFilaAlternada($n); ?>">
                <td class="text-center fw-bold"><?php echo htmlspecialchars($row['CODIGO']); ?></td>
                <td><?php echo htmlspecialchars($row['NOMBRE']); ?></td>
                <td class="text-center"><span class="badge bg-info text-dark"><?php echo htmlspecialchars($row['ITEM_CODIGO'] . ' - ' . $row['ITEM_NOMBRE']); ?></span></td>
                <td class="text-center"><?php echo htmlspecialchars($row['UNIDAD_MEDIDA'] ?? '—'); ?></td>
                <td class="text-center"><?php echo $activo_badge; ?></td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                        <?php if ($nivel >= 6): ?>
                            <a href="index.php?estado=<?php echo $estado; ?>&accion=editar&id=<?php echo $row['ID']; ?>"
                               class="btn btn-pade-alt btn-sm btn-accion" title="Editar">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                            <?php echo generarBotonForm(
                                ($row['ACTIVO'] == 1) ? '<i class="bi bi-toggle-on"></i>' : '<i class="bi bi-toggle-off"></i>',
                                "index.php?estado=$estado",
                                ['accion_post' => 'toggle_activo', 'toggle_id' => $row['ID']],
                                ($row['ACTIVO'] == 1) ? 'btn btn-outline-warning btn-sm' : 'btn btn-outline-success btn-sm'
                            ); ?>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
<?php endwhile; ?>
<?php if ($n === 0): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No hay categorías de gasto registradas<?php echo (!empty($filtro_item) && $filtro_item !== 'null') ? ' para este ítem' : ''; ?>.</td></tr>
<?php endif; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>
