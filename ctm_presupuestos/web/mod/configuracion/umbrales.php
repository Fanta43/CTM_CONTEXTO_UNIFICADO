<?php
/**
 * ============================================================================
 * Umbrales de Alerta - ABM
 * ============================================================================
 * ID menú: 107 | Tabla: ciclo_config_umbral
 * Configurar porcentajes de consumo que generan alertas automáticas.
 * ============================================================================
 */

$db = '1';
$accion = Enmascarar_get('accion');
$id     = Enmascarar_get('id');
$msg    = '';

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion_post = Enmascarar_post('accion_post');

    // ── CREAR ────────────────────────────────────────────────────────────────
    if ($accion_post === 'crear') {
        $umbral_nivel   = Enmascarar_post('umbral_nivel');
        $id_referencia  = Enmascarar_post('id_referencia');
        $porcentaje     = Enmascarar_post('porcentaje');

        if ((float)$porcentaje < 1 || (float)$porcentaje > 100) {
            $msg = generarAlertJS('Error', 'El porcentaje debe estar entre 1.00 y 100.00.', 'error');
        } elseif (!in_array($umbral_nivel, ['global', 'categoria', 'item'])) {
            $msg = generarAlertJS('Error', 'El nivel debe ser global, categoría o ítem.', 'error');
        } else {
            // Validar referencia según nivel
            $ref_val = 'NULL';
            $ok = true;

            if ($umbral_nivel === 'categoria') {
                if ($id_referencia === 'null' || empty($id_referencia)) {
                    $msg = generarAlertJS('Error', 'Debe seleccionar una categoría.', 'error'); $ok = false;
                } else {
                    $sql_ref = "SELECT id FROM ciclo_categoria_gasto WHERE id = '$id_referencia';";
                    if (!sql_do_array(sql_do_query($sql_ref, $db, $usu))) {
                        $msg = generarAlertJS('Error', 'La categoría seleccionada no existe.', 'error'); $ok = false;
                    } else {
                        $ref_val = "'$id_referencia'";
                    }
                }
            } elseif ($umbral_nivel === 'item') {
                if ($id_referencia === 'null' || empty($id_referencia)) {
                    $msg = generarAlertJS('Error', 'Debe seleccionar un ítem.', 'error'); $ok = false;
                } else {
                    $sql_ref = "SELECT id FROM ciclo_item_gasto WHERE id = '$id_referencia';";
                    if (!sql_do_array(sql_do_query($sql_ref, $db, $usu))) {
                        $msg = generarAlertJS('Error', 'El ítem seleccionado no existe.', 'error'); $ok = false;
                    } else {
                        $ref_val = "'$id_referencia'";
                    }
                }
            }

            if ($ok) {
                // No duplicar mismo nivel + referencia + porcentaje
                $ref_cond = ($ref_val === 'NULL') ? "id_referencia IS NULL" : "id_referencia = $ref_val";
                $sql_dup = "SELECT id FROM ciclo_config_umbral WHERE nivel = '$umbral_nivel' AND $ref_cond AND porcentaje = '$porcentaje';";
                if (sql_do_array(sql_do_query($sql_dup, $db, $usu))) {
                    $msg = generarAlertJS('Error', 'Ya existe un umbral con ese nivel, referencia y porcentaje.', 'error');
                } else {
                    $sql_ins = "INSERT INTO ciclo_config_umbral (nivel, id_referencia, porcentaje, activo, auditoria_usu)
                                VALUES ('$umbral_nivel', $ref_val, '$porcentaje', 1, '$usu');";
                    sql_do_query($sql_ins, $db, $usu);
                    $msg = generarAlertJS('Umbral creado', "El umbral del $porcentaje% fue creado correctamente.", 'success', "index.php?estado=$estado");
                }
            }
        }
    }

    // ── EDITAR ───────────────────────────────────────────────────────────────
    if ($accion_post === 'editar') {
        $edit_id    = Enmascarar_post('edit_id');
        $porcentaje = Enmascarar_post('porcentaje');

        if ((float)$porcentaje < 1 || (float)$porcentaje > 100) {
            $msg = generarAlertJS('Error', 'El porcentaje debe estar entre 1.00 y 100.00.', 'error');
        } else {
            $sql_upd = "UPDATE ciclo_config_umbral SET porcentaje = '$porcentaje', auditoria_usu = '$usu' WHERE id = '$edit_id';";
            sql_do_query($sql_upd, $db, $usu);
            $msg = generarAlertJS('Actualizado', 'El umbral fue actualizado correctamente.', 'success', "index.php?estado=$estado");
        }
    }

    // ── ELIMINAR ─────────────────────────────────────────────────────────────
    if ($accion_post === 'eliminar') {
        $del_id = Enmascarar_post('del_id');

        // No eliminar umbrales globales por defecto (80%, 95%, 100%)
        $sql_check = "SELECT nivel, porcentaje FROM ciclo_config_umbral WHERE id = '$del_id';";
        $row_check = sql_do_array(sql_do_query($sql_check, $db, $usu));
        if ($row_check['NIVEL'] === 'global' && in_array((float)$row_check['PORCENTAJE'], [80.00, 95.00, 100.00])) {
            $msg = generarAlertJS('Error', 'Los umbrales globales por defecto (80%, 95%, 100%) no se pueden eliminar, solo modificar.', 'warning');
        } else {
            sql_do_query("DELETE FROM ciclo_config_umbral WHERE id = '$del_id';", $db, $usu);
            $msg = generarAlertJS('Eliminado', 'El umbral fue eliminado correctamente.', 'success', "index.php?estado=$estado");
        }
    }
}

echo $msg;

// ═══════════════════════════════════════════════════════════════════════════════
// VISTA: FORMULARIO NUEVO / EDITAR
// ═══════════════════════════════════════════════════════════════════════════════
if ($accion === 'nuevo' || $accion === 'editar'):
    $row = null;
    if ($accion === 'editar' && $id) {
        $row = sql_do_array(sql_do_query("SELECT * FROM ciclo_config_umbral WHERE id = '$id';", $db, $usu));
        if (!$row) { echo generarAlertJS('Error', 'Umbral no encontrado.', 'error', "index.php?estado=$estado"); return; }
    }
    $titulo_form = ($accion === 'nuevo') ? 'Nuevo Umbral de Alerta' : 'Editar Umbral de Alerta';

    $sql_cats  = "SELECT id AS ID, CONCAT(codigo, ' - ', nombre) AS NOMBRE FROM ciclo_categoria_gasto WHERE activa = 1 ORDER BY orden;";
    $sql_items = "SELECT id AS ID, CONCAT(codigo, ' - ', nombre) AS NOMBRE FROM ciclo_item_gasto WHERE activo = 1 ORDER BY codigo;";
?>

<?php echo generarTituloSeccion($titulo_form, 'bi bi-bell'); ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <input type="hidden" name="accion_post" value="<?php echo ($accion === 'nuevo') ? 'crear' : 'editar'; ?>">
            <?php if ($row): ?>
                <input type="hidden" name="edit_id" value="<?php echo $row['ID']; ?>">
            <?php endif; ?>

            <div class="row g-3">
                <?php if ($accion === 'nuevo'): ?>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Nivel <span class="text-danger">*</span></label>
                    <select class="form-select" name="umbral_nivel" id="umbral_nivel" required onchange="toggleReferencia()">
                        <option value="global">Global</option>
                        <option value="categoria">Por Categoría</option>
                        <option value="item">Por Ítem</option>
                    </select>
                </div>
                <div class="col-md-4" id="div_ref_cat" style="display:none;">
                    <label class="form-label fw-semibold">Categoría</label>
                    <?php echo imp_combo_w('id_referencia_cat', $sql_cats, $db, $usu, ''); ?>
                </div>
                <div class="col-md-4" id="div_ref_item" style="display:none;">
                    <label class="form-label fw-semibold">Ítem</label>
                    <?php echo imp_combo_w('id_referencia_item', $sql_items, $db, $usu, ''); ?>
                </div>
                <?php else: ?>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Nivel</label>
                    <input type="text" class="form-control" value="<?php echo ucfirst($row['NIVEL']); ?>" disabled>
                </div>
                <?php endif; ?>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Porcentaje (%) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="porcentaje" step="0.01" min="1" max="100"
                           value="<?php echo $row['PORCENTAJE'] ?? '80.00'; ?>" required>
                </div>
            </div>

            <?php if ($accion === 'nuevo'): ?>
            <!-- Campo oculto que se llena por JS según el nivel -->
            <input type="hidden" name="id_referencia" id="id_referencia_hidden" value="">
            <?php endif; ?>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-pade" <?php if ($accion === 'nuevo'): ?>onclick="prepareRef()"<?php endif; ?>>
                    <i class="bi bi-check-lg me-1"></i>Guardar
                </button>
                <a href="index.php?estado=<?php echo $estado; ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php if ($accion === 'nuevo'): ?>
<script>
function toggleReferencia() {
    var nivel = document.getElementById('umbral_nivel').value;
    document.getElementById('div_ref_cat').style.display  = (nivel === 'categoria') ? '' : 'none';
    document.getElementById('div_ref_item').style.display = (nivel === 'item') ? '' : 'none';
}
function prepareRef() {
    var nivel = document.getElementById('umbral_nivel').value;
    var val = '';
    if (nivel === 'categoria') val = document.getElementById('id_referencia_cat').value;
    if (nivel === 'item')      val = document.getElementById('id_referencia_item').value;
    document.getElementById('id_referencia_hidden').value = val;
}
</script>
<?php endif; ?>

<?php
// ═══════════════════════════════════════════════════════════════════════════════
// VISTA: LISTADO
// ═══════════════════════════════════════════════════════════════════════════════
else:
    $sql_list = "SELECT cu.*, cg.nombre AS CATEGORIA, cg.codigo AS CAT_CODIGO,
                        ig.nombre AS ITEM, ig.codigo AS ITEM_CODIGO
                 FROM ciclo_config_umbral cu
                 LEFT JOIN ciclo_categoria_gasto cg ON cu.nivel = 'categoria' AND cu.id_referencia = cg.id
                 LEFT JOIN ciclo_item_gasto ig ON cu.nivel = 'item' AND cu.id_referencia = ig.id
                 WHERE cu.activo = 1
                 ORDER BY cu.nivel, cu.porcentaje;";
    $result_list = sql_do_query($sql_list, $db, $usu);
?>

<?php echo generarTituloSeccion('Umbrales de Alerta', 'bi bi-bell'); ?>

<?php if ($nivel >= 6): ?>
<div class="mb-3">
    <a href="index.php?estado=<?php echo $estado; ?>&accion=nuevo" class="btn btn-pade">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Umbral
    </a>
</div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-hover table-pade">
        <?php echo generarTablaHeader(['Nivel', 'Referencia', 'Porcentaje', 'Acciones']); ?>
        <tbody>
<?php
    $n = 0;
    while ($row = sql_do_array($result_list)):
        $n++;
        // Badge de nivel
        switch ($row['NIVEL']) {
            case 'global':    $nivel_badge = '<span class="badge bg-dark">Global</span>'; break;
            case 'categoria': $nivel_badge = '<span class="badge bg-primary">Categoría</span>'; break;
            case 'item':      $nivel_badge = '<span class="badge bg-info text-dark">Ítem</span>'; break;
            default:          $nivel_badge = '<span class="badge bg-secondary">' . htmlspecialchars($row['NIVEL']) . '</span>';
        }

        // Referencia
        $referencia = '— Aplica a todo —';
        if ($row['NIVEL'] === 'categoria' && !empty($row['CATEGORIA'])) {
            $referencia = htmlspecialchars($row['CAT_CODIGO'] . ' - ' . $row['CATEGORIA']);
        } elseif ($row['NIVEL'] === 'item' && !empty($row['ITEM'])) {
            $referencia = htmlspecialchars($row['ITEM_CODIGO'] . ' - ' . $row['ITEM']);
        }

        // Color del porcentaje según severidad
        $pct = (float)$row['PORCENTAJE'];
        $pct_class = 'text-success';
        if ($pct >= 95) $pct_class = 'text-danger fw-bold';
        elseif ($pct >= 80) $pct_class = 'text-warning fw-bold';

        // Es umbral por defecto?
        $es_default = ($row['NIVEL'] === 'global' && in_array($pct, [80.00, 95.00, 100.00]));
?>
            <tr class="<?php echo claseFilaAlternada($n); ?>">
                <td class="text-center"><?php echo $nivel_badge; ?></td>
                <td class="text-center"><?php echo $referencia; ?></td>
                <td class="text-center">
                    <span class="<?php echo $pct_class; ?>"><?php echo number_format($pct, 2); ?>%</span>
                    <?php if ($es_default): ?><small class="text-muted ms-1">(default)</small><?php endif; ?>
                </td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                        <?php if ($nivel >= 6): ?>
                            <a href="index.php?estado=<?php echo $estado; ?>&accion=editar&id=<?php echo $row['ID']; ?>"
                               class="btn btn-pade-alt btn-sm btn-accion" title="Editar porcentaje">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                            <?php if (!$es_default): ?>
                                <?php echo generarBotonForm(
                                    '<i class="bi bi-trash"></i>',
                                    "index.php?estado=$estado",
                                    ['accion_post' => 'eliminar', 'del_id' => $row['ID']],
                                    'btn btn-outline-danger btn-sm'
                                ); ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
<?php endwhile; ?>
<?php if ($n === 0): ?>
            <tr><td colspan="4" class="text-center text-muted py-4">No hay umbrales de alerta configurados.</td></tr>
<?php endif; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>
