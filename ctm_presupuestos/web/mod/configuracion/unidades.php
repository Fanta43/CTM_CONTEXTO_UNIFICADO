<?php
/**
 * ============================================================================
 * Unidades Organizativas - ABM
 * ============================================================================
 * ID menú: 102 | Tabla: ciclo_unidad_organizativa
 * Gestión del árbol jerárquico de departamentos.
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
        $nombre           = Enmascarar_post('nombre');
        $id_padre         = Enmascarar_post('id_padre');
        $nivel_aprobacion = Enmascarar_post('nivel_aprobacion');
        $responsable      = Enmascarar_post('responsable');

        if (empty($nombre)) {
            $msg = generarAlertJS('Error', 'El nombre de la unidad es obligatorio.', 'error');
        } else {
            // Validar nombre único dentro del mismo padre
            $padre_cond = ($id_padre === 'null' || empty($id_padre)) ? "id_padre IS NULL" : "id_padre = '$id_padre'";
            $sql_dup = "SELECT id FROM ciclo_unidad_organizativa WHERE nombre = '$nombre' AND $padre_cond;";
            if (sql_do_array(sql_do_query($sql_dup, $db, $usu))) {
                $msg = generarAlertJS('Error', 'Ya existe una unidad con ese nombre en el mismo nivel.', 'error');
            } else {
                $padre_val = ($id_padre === 'null' || empty($id_padre)) ? 'NULL' : "'$id_padre'";
                $sql_ins = "INSERT INTO ciclo_unidad_organizativa (nombre, id_padre, nivel_aprobacion, responsable, auditoria_usu)
                            VALUES ('$nombre', $padre_val, '$nivel_aprobacion', '$responsable', '$usu');";
                sql_do_query($sql_ins, $db, $usu);
                $msg = generarAlertJS('Unidad creada', "La unidad '$nombre' fue creada correctamente.", 'success', "index.php?estado=$estado");
            }
        }
    }

    // ── EDITAR ───────────────────────────────────────────────────────────────
    if ($accion_post === 'editar') {
        $edit_id          = Enmascarar_post('edit_id');
        $nombre           = Enmascarar_post('nombre');
        $nivel_aprobacion = Enmascarar_post('nivel_aprobacion');
        $responsable      = Enmascarar_post('responsable');

        if (empty($nombre)) {
            $msg = generarAlertJS('Error', 'El nombre es obligatorio.', 'error');
        } else {
            $sql_upd = "UPDATE ciclo_unidad_organizativa
                        SET nombre = '$nombre', nivel_aprobacion = '$nivel_aprobacion',
                            responsable = '$responsable', auditoria_usu = '$usu'
                        WHERE id = '$edit_id';";
            sql_do_query($sql_upd, $db, $usu);
            $msg = generarAlertJS('Actualizado', 'La unidad fue actualizada correctamente.', 'success', "index.php?estado=$estado");
        }
    }


    // ── ELIMINAR ──────────────────────────────────────────────────────────────
    if ($accion_post === 'eliminar') {
        $del_id = Enmascarar_post('del_id');

        if ($del_id == '1') {
            $msg = generarAlertJS('Error', 'No se puede eliminar la unidad por defecto (ID 1).', 'error');
        } else {
            // Obtener todos los presupuestos de esta unidad
            $sql_pres = "SELECT id FROM ciclo_presupuesto WHERE id_unidad = '$del_id';";
            $res_pres = sql_do_query($sql_pres, $db, $usu);
            while ($rp = sql_do_array($res_pres)) {
                $pid = $rp['ID'];
                // a) Eliminar solicitud_partida que referencian partidas de este presupuesto
                sql_do_query("DELETE sp FROM ciclo_solicitud_partida sp
                              JOIN ciclo_partida pa ON sp.id_partida = pa.id
                              WHERE pa.id_presupuesto = '$pid';", $db, $usu);
                // b) Eliminar revision_detalle que referencian partidas de este presupuesto
                sql_do_query("DELETE rd FROM ciclo_revision_detalle rd
                              JOIN ciclo_partida pa ON rd.id_partida = pa.id
                              WHERE pa.id_presupuesto = '$pid';", $db, $usu);
                // c) Eliminar revisiones del presupuesto
                sql_do_query("DELETE FROM ciclo_revision WHERE id_presupuesto = '$pid';", $db, $usu);
                // d) Eliminar partidas
                sql_do_query("DELETE FROM ciclo_partida WHERE id_presupuesto = '$pid';", $db, $usu);
                // e) Eliminar alertas huérfanas
                sql_do_query("DELETE FROM ciclo_alerta_consumo WHERE id_partida NOT IN (SELECT id FROM ciclo_partida);", $db, $usu);
            }
            // f) Eliminar presupuestos de la unidad
            sql_do_query("DELETE FROM ciclo_presupuesto WHERE id_unidad = '$del_id';", $db, $usu);

            // 2. Solicitudes de recurso de la unidad
            sql_do_query("DELETE sp FROM ciclo_solicitud_partida sp
                          JOIN ciclo_solicitud_recurso sr ON sp.id_solicitud = sr.id
                          WHERE sr.id_unidad = '$del_id';", $db, $usu);
            sql_do_query("DELETE FROM ciclo_solicitud_recurso WHERE id_unidad = '$del_id';", $db, $usu);

            // 3. Migrar usuarios y unidades hijas
            sql_do_query("UPDATE ciclo_usuario SET id_unidad = 1 WHERE id_unidad = '$del_id';", $db, $usu);
            sql_do_query("UPDATE ciclo_unidad_organizativa SET id_padre = NULL WHERE id_padre = '$del_id';", $db, $usu);

            // 4. Eliminar la unidad
            sql_do_query("DELETE FROM ciclo_unidad_organizativa WHERE id = '$del_id';", $db, $usu);
            $msg = generarAlertJS('Eliminada', 'La unidad fue eliminada. Los registros asociados fueron migrados o eliminados.', 'success', "index.php?estado=$estado");
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
        $row = sql_do_array(sql_do_query("SELECT * FROM ciclo_unidad_organizativa WHERE id = '$id';", $db, $usu));
        if (!$row) { echo generarAlertJS('Error', 'Unidad no encontrada.', 'error', "index.php?estado=$estado"); return; }
    }
    $titulo_form = ($accion === 'nueva') ? 'Nueva Unidad Organizativa' : 'Editar Unidad Organizativa';

    // Combo de padres
    $sql_padres = "SELECT id AS ID, nombre AS NOMBRE FROM ciclo_unidad_organizativa ORDER BY nombre;";
?>

<?php echo generarTituloSeccion($titulo_form, 'bi bi-diagram-3'); ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <input type="hidden" name="accion_post" value="<?php echo ($accion === 'nueva') ? 'crear' : 'editar'; ?>">
            <?php if ($row): ?>
                <input type="hidden" name="edit_id" value="<?php echo $row['ID']; ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nombre"
                           value="<?php echo htmlspecialchars($row['NOMBRE'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Unidad Padre</label>
                    <?php echo imp_combo_w('id_padre', $sql_padres, $db, $usu, $row['ID_PADRE'] ?? ''); ?>
                    <small class="text-muted">Dejar en "Seleccione" para unidad raíz.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nivel de Aprobación <span class="text-danger">*</span></label>
                    <select class="form-select" name="nivel_aprobacion" required>
                        <option value="1" <?php echo (($row['NIVEL_APROBACION'] ?? '') == '1') ? 'selected' : ''; ?>>1 - Unidad</option>
                        <option value="2" <?php echo (($row['NIVEL_APROBACION'] ?? '') == '2') ? 'selected' : ''; ?>>2 - Área</option>
                        <option value="3" <?php echo (($row['NIVEL_APROBACION'] ?? '') == '3') ? 'selected' : ''; ?>>3 - Dirección</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Responsable</label>
                    <input type="text" class="form-control" name="responsable"
                           value="<?php echo htmlspecialchars($row['RESPONSABLE'] ?? ''); ?>">
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
// VISTA: LISTADO ÁRBOL
// ═══════════════════════════════════════════════════════════════════════════════
else:
    $sql_list = "SELECT u.*, p.nombre AS PADRE_NOMBRE
                 FROM ciclo_unidad_organizativa u
                 LEFT JOIN ciclo_unidad_organizativa p ON u.id_padre = p.id
                 ORDER BY COALESCE(u.id_padre, 0), u.nombre;";
    $result_list = sql_do_query($sql_list, $db, $usu);
?>

<?php echo generarTituloSeccion('Unidades Organizativas', 'bi bi-diagram-3'); ?>

<?php if ($nivel >= 6): ?>
<div class="mb-3">
    <a href="index.php?estado=<?php echo $estado; ?>&accion=nueva" class="btn btn-pade">
        <i class="bi bi-plus-circle me-1"></i>Nueva Unidad
    </a>
</div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-hover table-pade">
        <?php echo generarTablaHeader(['Nombre', 'Unidad Padre', 'Nivel', 'Responsable', 'CC', 'Acciones']); ?>
        <tbody>
<?php
    $n = 0;
    $niveles_txt = [1 => 'Unidad', 2 => 'Área', 3 => 'Dirección'];
    while ($row = sql_do_array($result_list)):
        $n++;
        $nivel_txt = $niveles_txt[$row['NIVEL_APROBACION']] ?? $row['NIVEL_APROBACION'];
        $indent = empty($row['PADRE_NOMBRE']) ? '' : '<i class="bi bi-arrow-return-right me-1 text-muted"></i>';
?>
            <tr class="<?php echo claseFilaAlternada($n); ?>">
                <td><?php echo $indent . htmlspecialchars($row['NOMBRE']); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($row['PADRE_NOMBRE'] ?? '— Raíz —'); ?></td>
                <td class="text-center"><span class="badge bg-info text-dark"><?php echo $nivel_txt; ?></span></td>
                <td class="text-center"><?php echo htmlspecialchars($row['RESPONSABLE'] ?? ''); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($row['CC'] ?? ''); ?></td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                        <?php if ($nivel >= 6): ?>
                            <a href="index.php?estado=<?php echo $estado; ?>&accion=editar&id=<?php echo $row['ID']; ?>"
                               class="btn btn-pade-alt btn-sm btn-accion" title="Editar">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                        <?php endif; ?>
                        <?php if ($nivel >= 9 && $row['ID'] != '1'): ?>
                            <?php echo generarBotonForm(
                                '<i class="bi bi-trash"></i>',
                                "index.php?estado=$estado",
                                ['accion_post' => 'eliminar', 'del_id' => $row['ID']],
                                'btn btn-outline-danger btn-sm'
                            ); ?>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
<?php endwhile; ?>
<?php if ($n === 0): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No hay unidades organizativas registradas.</td></tr>
<?php endif; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>
