<?php
/**
 * ============================================================================
 * Ítems de Gasto - ABM
 * ============================================================================
 * ID menú: 104 | Tabla: ciclo_item_gasto
 * Columnas: id, codigo, nombre, descripcion, activa, orden,
 *           gastoid_origen, auditoria_usu, auditoria_fecha
 * Tabla padre (agrupador). Las categorías (ciclo_categoria_gasto) son hijas.
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
        $codigo      = Enmascarar_post('codigo');
        $nombre      = Enmascarar_post('nombre');
        $descripcion = Enmascarar_post('descripcion');
        $orden       = Enmascarar_post('orden');

        if (empty($codigo) || empty($nombre)) {
            $msg = generarAlertJS('Error', 'El código y el nombre son obligatorios.', 'error');
        } else {
            $sql_dup = "SELECT id FROM ciclo_item_gasto WHERE codigo = '$codigo';";
            if (sql_do_array(sql_do_query($sql_dup, $db, $usu))) {
                $msg = generarAlertJS('Error', "Ya existe un ítem con el código '$codigo'.", 'error');
            } else {
                $sql_ins = "INSERT INTO ciclo_item_gasto (codigo, nombre, descripcion, activa, orden, auditoria_usu)
                            VALUES ('$codigo', '$nombre', '$descripcion', 1, '$orden', '$usu');";
                sql_do_query($sql_ins, $db, $usu);
                $msg = generarAlertJS('Ítem creado', "El ítem '$nombre' fue creado correctamente.", 'success', "index.php?estado=$estado");
            }
        }
    }

    // ── EDITAR ───────────────────────────────────────────────────────────────
    if ($accion_post === 'editar') {
        $edit_id     = Enmascarar_post('edit_id');
        $nombre      = Enmascarar_post('nombre');
        $descripcion = Enmascarar_post('descripcion');
        $orden       = Enmascarar_post('orden');

        if (empty($nombre)) {
            $msg = generarAlertJS('Error', 'El nombre es obligatorio.', 'error');
        } else {
            $sql_upd = "UPDATE ciclo_item_gasto SET nombre = '$nombre', descripcion = '$descripcion',
                        orden = '$orden', auditoria_usu = '$usu' WHERE id = '$edit_id';";
            sql_do_query($sql_upd, $db, $usu);
            $msg = generarAlertJS('Actualizado', 'El ítem fue actualizado correctamente.', 'success', "index.php?estado=$estado");
        }
    }

    // ── TOGGLE ACTIVA ────────────────────────────────────────────────────────
    if ($accion_post === 'toggle_activa') {
        $toggle_id = Enmascarar_post('toggle_id');

        $sql_act = "SELECT activa FROM ciclo_item_gasto WHERE id = '$toggle_id';";
        $row_act = sql_do_array(sql_do_query($sql_act, $db, $usu));

        if ($row_act['ACTIVA'] == 1) {
            // Validar que no tenga categorías con partidas en período abierto
            $sql_check = "SELECT pa.id FROM ciclo_partida pa
                          JOIN ciclo_presupuesto p ON pa.id_presupuesto = p.id
                          JOIN ciclo_periodo_fiscal pf ON p.id_periodo = pf.id AND pf.estado = 'abierto'
                          WHERE pa.id_item = '$toggle_id' LIMIT 1;";
            if (sql_do_array(sql_do_query($sql_check, $db, $usu))) {
                $msg = generarAlertJS('Error', 'No se puede desactivar: tiene partidas en el período abierto.', 'warning');
            } else {
                sql_do_query("UPDATE ciclo_item_gasto SET activa = 0, auditoria_usu = '$usu' WHERE id = '$toggle_id';", $db, $usu);
                $msg = generarAlertJS('Desactivado', 'El ítem fue desactivado.', 'success', "index.php?estado=$estado");
            }
        } else {
            sql_do_query("UPDATE ciclo_item_gasto SET activa = 1, auditoria_usu = '$usu' WHERE id = '$toggle_id';", $db, $usu);
            $msg = generarAlertJS('Activado', 'El ítem fue activado.', 'success', "index.php?estado=$estado");
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
        $row = sql_do_array(sql_do_query("SELECT * FROM ciclo_item_gasto WHERE id = '$id';", $db, $usu));
        if (!$row) { echo generarAlertJS('Error', 'Ítem no encontrado.', 'error', "index.php?estado=$estado"); return; }
    }
    $titulo_form = ($accion === 'nuevo') ? 'Nuevo Ítem de Gasto' : 'Editar Ítem de Gasto';
?>

<?php echo generarTituloSeccion($titulo_form, 'bi bi-tag'); ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <input type="hidden" name="accion_post" value="<?php echo ($accion === 'nuevo') ? 'crear' : 'editar'; ?>">
            <?php if ($row): ?>
                <input type="hidden" name="edit_id" value="<?php echo $row['ID']; ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Código <span class="text-danger">*</span></label>
                    <?php if ($accion === 'nuevo'): ?>
                        <input type="text" class="form-control" name="codigo" maxlength="10" required>
                    <?php else: ?>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['CODIGO']); ?>" disabled>
                    <?php endif; ?>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nombre"
                           value="<?php echo htmlspecialchars($row['NOMBRE'] ?? ''); ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Orden</label>
                    <input type="number" class="form-control" name="orden" min="0"
                           value="<?php echo htmlspecialchars($row['ORDEN'] ?? '0'); ?>">
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Descripción</label>
                    <textarea class="form-control" name="descripcion" rows="2"><?php echo htmlspecialchars($row['DESCRIPCION'] ?? ''); ?></textarea>
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
    $sql_list = "SELECT ig.*, COUNT(cg.id) AS CANT_CATEGORIAS
                 FROM ciclo_item_gasto ig
                 LEFT JOIN ciclo_categoria_gasto cg ON cg.id_item = ig.id AND cg.activo = 1
                 GROUP BY ig.id
                 ORDER BY ig.orden, ig.codigo;";
    $result_list = sql_do_query($sql_list, $db, $usu);
?>

<?php echo generarTituloSeccion('Ítems de Gasto', 'bi bi-tag'); ?>

<?php if ($nivel >= 6): ?>
<div class="mb-3">
    <a href="index.php?estado=<?php echo $estado; ?>&accion=nuevo" class="btn btn-pade">
        <i class="bi bi-plus-circle me-1"></i>Nuevo Ítem
    </a>
</div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-hover table-pade">
        <?php echo generarTablaHeader(['Código', 'Nombre', 'Descripción', 'Categorías Activas', 'Orden', 'Estado', 'Acciones']); ?>
        <tbody>
<?php
    $n = 0;
    while ($row = sql_do_array($result_list)):
        $n++;
        $activa_badge = ($row['ACTIVA'] == 1)
            ? '<span class="badge bg-success">Activo</span>'
            : '<span class="badge bg-secondary">Inactivo</span>';
?>
            <tr class="<?php echo claseFilaAlternada($n); ?>">
                <td class="text-center fw-bold"><?php echo htmlspecialchars($row['CODIGO']); ?></td>
                <td><?php echo htmlspecialchars($row['NOMBRE']); ?></td>
                <td class="small text-muted"><?php echo htmlspecialchars($row['DESCRIPCION'] ?? ''); ?></td>
                <td class="text-center"><span class="badge bg-primary"><?php echo $row['CANT_CATEGORIAS']; ?></span></td>
                <td class="text-center"><?php echo htmlspecialchars($row['ORDEN'] ?? '0'); ?></td>
                <td class="text-center"><?php echo $activa_badge; ?></td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                        <?php if ($nivel >= 6): ?>
                            <a href="index.php?estado=<?php echo $estado; ?>&accion=editar&id=<?php echo $row['ID']; ?>"
                               class="btn btn-pade-alt btn-sm btn-accion" title="Editar">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                            <?php echo generarBotonForm(
                                ($row['ACTIVA'] == 1) ? '<i class="bi bi-toggle-on"></i>' : '<i class="bi bi-toggle-off"></i>',
                                "index.php?estado=$estado",
                                ['accion_post' => 'toggle_activa', 'toggle_id' => $row['ID']],
                                ($row['ACTIVA'] == 1) ? 'btn btn-outline-warning btn-sm' : 'btn btn-outline-success btn-sm'
                            ); ?>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
<?php endwhile; ?>
<?php if ($n === 0): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No hay ítems de gasto registrados.</td></tr>
<?php endif; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>
