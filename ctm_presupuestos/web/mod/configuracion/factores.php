<?php
/**
 * ============================================================================
 * Factores de Proyección - ABM
 * ============================================================================
 * ID menú: 105 | Tabla: ciclo_config_proyeccion
 * Multiplicadores y techos por período destino, ítem (agrupador) o categoría (específico).
 * Prioridad de aplicación: categoría > ítem > global.
 * ============================================================================
 */

$db = '1';
$accion  = Enmascarar_get('accion');
$id      = Enmascarar_get('id');
$periodo = Enmascarar_get('periodo');
$msg     = '';

// Filtro período vía POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['filtro_periodo'])) {
    $periodo = Enmascarar_post('filtro_periodo');
}

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion_post = Enmascarar_post('accion_post');

    // ── CREAR ────────────────────────────────────────────────────────────────
    if ($accion_post === 'crear') {
        $id_periodo_destino = Enmascarar_post('id_periodo_destino');
        $id_categoria       = Enmascarar_post('id_categoria');
        $id_item            = Enmascarar_post('id_item');
        $factor_ajuste      = Enmascarar_post('factor_ajuste');
        $techo_maximo       = Enmascarar_post('techo_maximo');

        // Validar período destino abierto
        $sql_per = "SELECT id FROM ciclo_periodo_fiscal WHERE id = '$id_periodo_destino' AND estado = 'abierto';";
        if (!sql_do_array(sql_do_query($sql_per, $db, $usu))) {
            $msg = generarAlertJS('Error', 'El período destino debe existir y estar abierto.', 'error');
        } elseif ((float)$factor_ajuste <= 0) {
            $msg = generarAlertJS('Error', 'El factor de ajuste debe ser mayor a 0.', 'error');
        } else {
            $cat_val  = ($id_categoria === 'null' || empty($id_categoria)) ? 'NULL' : "'$id_categoria'";
            $item_val = ($id_item === 'null' || empty($id_item)) ? 'NULL' : "'$id_item'";
            $techo_val = (empty($techo_maximo)) ? 'NULL' : "'$techo_maximo'";

            $sql_ins = "INSERT INTO ciclo_config_proyeccion (id_periodo_destino, id_categoria, id_item, factor_ajuste, techo_maximo, auditoria_usu)
                        VALUES ('$id_periodo_destino', $cat_val, $item_val, '$factor_ajuste', $techo_val, '$usu');";
            sql_do_query($sql_ins, $db, $usu);
            $msg = generarAlertJS('Factor creado', 'El factor de proyección fue creado correctamente.', 'success', "index.php?estado=$estado&periodo=$id_periodo_destino");
        }
    }

    // ── EDITAR ───────────────────────────────────────────────────────────────
    if ($accion_post === 'editar') {
        $edit_id       = Enmascarar_post('edit_id');
        $factor_ajuste = Enmascarar_post('factor_ajuste');
        $techo_maximo  = Enmascarar_post('techo_maximo');

        if ((float)$factor_ajuste <= 0) {
            $msg = generarAlertJS('Error', 'El factor de ajuste debe ser mayor a 0.', 'error');
        } else {
            $techo_val = (empty($techo_maximo)) ? 'NULL' : "'$techo_maximo'";
            $sql_upd = "UPDATE ciclo_config_proyeccion SET factor_ajuste = '$factor_ajuste', techo_maximo = $techo_val,
                        auditoria_usu = '$usu' WHERE id = '$edit_id';";
            sql_do_query($sql_upd, $db, $usu);
            $msg = generarAlertJS('Actualizado', 'El factor fue actualizado correctamente.', 'success', "index.php?estado=$estado&periodo=$periodo");
        }
    }

    // ── ELIMINAR ─────────────────────────────────────────────────────────────
    if ($accion_post === 'eliminar') {
        $del_id = Enmascarar_post('del_id');
        sql_do_query("DELETE FROM ciclo_config_proyeccion WHERE id = '$del_id';", $db, $usu);
        $msg = generarAlertJS('Eliminado', 'El factor fue eliminado correctamente.', 'success', "index.php?estado=$estado&periodo=$periodo");
    }
}

echo $msg;

// ═══════════════════════════════════════════════════════════════════════════════
// VISTA: FORMULARIO NUEVO / EDITAR
// ═══════════════════════════════════════════════════════════════════════════════
if ($accion === 'nuevo' || $accion === 'editar'):
    $row = null;
    if ($accion === 'editar' && $id) {
        $row = sql_do_array(sql_do_query("SELECT * FROM ciclo_config_proyeccion WHERE id = '$id';", $db, $usu));
        if (!$row) { echo generarAlertJS('Error', 'Factor no encontrado.', 'error', "index.php?estado=$estado"); return; }
    }
    $titulo_form = ($accion === 'nuevo') ? 'Nuevo Factor de Proyección' : 'Editar Factor de Proyección';

    $sql_periodos = "SELECT id AS ID, CONCAT(anio, ' (', estado, ')') AS NOMBRE FROM ciclo_periodo_fiscal WHERE estado = 'abierto' ORDER BY anio DESC;";
    $sql_cats     = "SELECT id AS ID, CONCAT(codigo, ' - ', nombre) AS NOMBRE FROM ciclo_categoria_gasto WHERE activo = 1 ORDER BY codigo;";
    $sql_items    = "SELECT id AS ID, CONCAT(codigo, ' - ', nombre) AS NOMBRE FROM ciclo_item_gasto WHERE activa = 1 ORDER BY orden, codigo;";
?>

<?php echo generarTituloSeccion($titulo_form, 'bi bi-sliders'); ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>&periodo=<?php echo $periodo; ?>">
            <input type="hidden" name="accion_post" value="<?php echo ($accion === 'nuevo') ? 'crear' : 'editar'; ?>">
            <?php if ($row): ?>
                <input type="hidden" name="edit_id" value="<?php echo $row['ID']; ?>">
            <?php endif; ?>

            <div class="row g-3">
                <?php if ($accion === 'nuevo'): ?>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Período Destino <span class="text-danger">*</span></label>
                    <?php echo imp_combo_w('id_periodo_destino', $sql_periodos, $db, $usu, $periodo); ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Ítem (agrupador)</label>
                    <?php echo imp_combo_w('id_item', $sql_items, $db, $usu, ''); ?>
                    <small class="text-muted">Vacío = factor global</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Categoría (específico)</label>
                    <?php echo imp_combo_w('id_categoria', $sql_cats, $db, $usu, ''); ?>
                    <small class="text-muted">Vacío = aplica a todo el ítem</small>
                </div>
                <?php endif; ?>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Factor de Ajuste <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="factor_ajuste" step="0.0001" min="0.0001"
                           value="<?php echo $row['FACTOR_AJUSTE'] ?? '1.0000'; ?>" required>
                    <small class="text-muted">1.0000 = sin cambio | 1.0500 = +5% | 0.9500 = -5%</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Techo Máximo</label>
                    <input type="number" class="form-control" name="techo_maximo" step="0.01" min="0"
                           value="<?php echo $row['TECHO_MAXIMO'] ?? ''; ?>"
                           placeholder="Sin límite">
                    <small class="text-muted">Opcional. Monto máximo permitido.</small>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-pade"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                <a href="index.php?estado=<?php echo $estado; ?>&periodo=<?php echo $periodo; ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php
// ═══════════════════════════════════════════════════════════════════════════════
// VISTA: LISTADO
// ═══════════════════════════════════════════════════════════════════════════════
else:
    // Combo de períodos para filtrar
    $sql_combo_per = "SELECT id AS ID, CONCAT(anio, ' (', estado, ')') AS NOMBRE FROM ciclo_periodo_fiscal ORDER BY anio DESC;";

    // Si no hay período seleccionado, usar el abierto
    if (empty($periodo) || $periodo === 'null') {
        $row_abierto = sql_do_array(sql_do_query("SELECT id FROM ciclo_periodo_fiscal WHERE estado = 'abierto' LIMIT 1;", $db, $usu));
        $periodo = $row_abierto['ID'] ?? '';
    }

    $sql_list = '';
    if (!empty($periodo)) {
        $sql_list = "SELECT cp.*, cg.nombre AS CATEGORIA, cg.codigo AS CAT_CODIGO, ig.nombre AS ITEM, ig.codigo AS ITEM_CODIGO
                     FROM ciclo_config_proyeccion cp
                     LEFT JOIN ciclo_categoria_gasto cg ON cp.id_categoria = cg.id
                     LEFT JOIN ciclo_item_gasto ig ON cp.id_item = ig.id
                     WHERE cp.id_periodo_destino = '$periodo'
                     ORDER BY ig.orden, cg.codigo;";
    }
?>

<?php echo generarTituloSeccion('Factores de Proyección', 'bi bi-sliders'); ?>

<div class="row mb-3 align-items-end">
    <div class="col-md-4">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <label class="form-label fw-semibold small">Período destino</label>
            <?php echo imp_combo_w_oc('filtro_periodo', $sql_combo_per, $db, $usu, $periodo); ?>
        </form>
    </div>
    <?php if ($nivel >= 6 && !empty($periodo)): ?>
    <div class="col-md-4">
        <a href="index.php?estado=<?php echo $estado; ?>&accion=nuevo&periodo=<?php echo $periodo; ?>" class="btn btn-pade">
            <i class="bi bi-plus-circle me-1"></i>Nuevo Factor
        </a>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($periodo) && !empty($sql_list)):
    $result_list = sql_do_query($sql_list, $db, $usu);
?>
<div class="table-responsive">
    <table class="table table-hover table-pade">
        <?php echo generarTablaHeader(['Alcance', 'Ítem (agrupador)', 'Categoría (específico)', 'Factor Ajuste', 'Techo Máximo', 'Acciones']); ?>
        <tbody>
<?php
    $n = 0;
    while ($row = sql_do_array($result_list)):
        $n++;
        // Determinar alcance
        if (!empty($row['ID_CATEGORIA']))     $alcance = '<span class="badge bg-info text-dark">Categoría</span>';
        elseif (!empty($row['ID_ITEM']))       $alcance = '<span class="badge bg-primary">Ítem</span>';
        else                                   $alcance = '<span class="badge bg-dark">Global</span>';

        $factor_pct = (($row['FACTOR_AJUSTE'] - 1) * 100);
        $factor_txt = ($factor_pct >= 0 ? '+' : '') . number_format($factor_pct, 2) . '%';
        $factor_class = ($factor_pct > 0) ? 'text-success' : (($factor_pct < 0) ? 'text-danger' : 'text-muted');
?>
            <tr class="<?php echo claseFilaAlternada($n); ?>">
                <td class="text-center"><?php echo $alcance; ?></td>
                <td class="text-center"><?php echo htmlspecialchars(($row['ITEM_CODIGO'] ?? '') . ' ' . ($row['ITEM'] ?? '— Todos —')); ?></td>
                <td class="text-center"><?php echo htmlspecialchars(($row['CAT_CODIGO'] ?? '') . ' ' . ($row['CATEGORIA'] ?? '— Todas —')); ?></td>
                <td class="text-center">
                    <strong><?php echo number_format($row['FACTOR_AJUSTE'], 4); ?></strong>
                    <small class="<?php echo $factor_class; ?>">(<?php echo $factor_txt; ?>)</small>
                </td>
                <td class="text-center"><?php echo $row['TECHO_MAXIMO'] ? '$ ' . number_format($row['TECHO_MAXIMO'], 2, ',', '.') : '<span class="text-muted">Sin límite</span>'; ?></td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                        <?php if ($nivel >= 6): ?>
                            <a href="index.php?estado=<?php echo $estado; ?>&accion=editar&id=<?php echo $row['ID']; ?>&periodo=<?php echo $periodo; ?>"
                               class="btn btn-pade-alt btn-sm btn-accion" title="Editar">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                            <?php echo generarBotonForm(
                                '<i class="bi bi-trash"></i>',
                                "index.php?estado=$estado&periodo=$periodo",
                                ['accion_post' => 'eliminar', 'del_id' => $row['ID']],
                                'btn btn-outline-danger btn-sm'
                            ); ?>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
<?php endwhile; ?>
<?php if ($n === 0): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No hay factores configurados para este período.</td></tr>
<?php endif; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
    <div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Seleccione un período para ver sus factores de proyección.</div>
<?php endif; ?>

<?php endif; ?>
