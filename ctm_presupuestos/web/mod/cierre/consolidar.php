<?php
/**
 * ============================================================================
 * Consolidar Ejecutado
 * ============================================================================
 * ID menú: 603 | Tablas: ciclo_partida, ciclo_solicitud_partida, ciclo_presupuesto
 * Paso 3: recalcula montos ejecutados sumando solicitudes ejecutadas.
 * Garantiza consistencia de datos. Pone comprometido a 0.
 * ============================================================================
 */

$db  = '1';
$msg = '';

$rol_usuario = <?php
/**
 * ============================================================================
 * Consolidar Ejecutado
 * ============================================================================
 * ID menú: 603 | Tablas: ciclo_partida, ciclo_solicitud_partida, ciclo_presupuesto
 * Paso 3: recalcula montos ejecutados sumando solicitudes ejecutadas.
 * Garantiza consistencia de datos. Pone comprometido a 0.
 * ============================================================================
 */

$db  = '1';
$msg = '';

$rol_usuario = $_SESSION['rol'] ?? '';

// ── Obtener período en cierre ────────────────────────────────────────────────
$row_periodo = sql_do_array(sql_do_query(
    "SELECT * FROM ciclo_periodo_fiscal WHERE estado = 'en_cierre' LIMIT 1;", $db, $usu));

if (!$row_periodo) {
    echo generarAlertJS('Sin período', 'No hay un período en estado en_cierre.', 'warning', "index.php?estado=601");
    return;
}
$periodo_id = $row_periodo['ID'];
$anio = $row_periodo['ANIO'];

// ── Validar que no haya solicitudes pendientes (Paso 2) ──────────────────────
$sql_pend = "SELECT COUNT(*) AS CANT FROM ciclo_solicitud_recurso
             WHERE id_periodo = '$periodo_id' AND estado IN ('pendiente', 'aprobada');";
$row_pend = sql_do_array(sql_do_query($sql_pend, $db, $usu));
if ((int)($row_pend['CANT'] ?? 0) > 0) {
    echo generarAlertJS('Paso incompleto', 'Aún hay solicitudes sin resolver. Complete el Paso 2 primero.', 'warning', "index.php?estado=602");
    return;
}

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST — CONSOLIDAR
// ═══════════════════════════════════════════════════════════════════════════════
$consolidado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Enmascarar_post('accion_post') === 'consolidar') {

    // Recalcular ejecutado y resetear comprometido
    $sql_upd = "UPDATE ciclo_partida pa
                JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                SET pa.monto_ejecutado = (
                    SELECT COALESCE(SUM(sp.monto), 0)
                    FROM ciclo_solicitud_partida sp
                    JOIN ciclo_solicitud_recurso sr ON sp.id_solicitud = sr.id
                    WHERE sp.id_partida = pa.id AND sr.estado = 'ejecutada'
                ),
                pa.monto_comprometido = 0,
                pa.auditoria_usu = '$usu'
                WHERE pr.id_periodo = '$periodo_id';";
    sql_do_query($sql_upd, $db, $usu);

    // Recalcular monto_total de cada presupuesto
    $sql_pres = "SELECT id FROM ciclo_presupuesto WHERE id_periodo = '$periodo_id';";
    $res_pres = sql_do_query($sql_pres, $db, $usu);
    while ($rp = sql_do_array($res_pres)) {
        $pid = $rp['ID'];
        $sql_tot = "SELECT SUM(monto_ejecutado) AS TOTAL FROM ciclo_partida WHERE id_presupuesto = '$pid';";
        $row_tot = sql_do_array(sql_do_query($sql_tot, $db, $usu));
        $total = (float)($row_tot['TOTAL'] ?? 0);
        sql_do_query("UPDATE ciclo_presupuesto SET monto_total = '$total', auditoria_usu = '$usu' WHERE id = '$pid';", $db, $usu);
    }

    $consolidado = true;
    $msg = generarAlertJS('Consolidación completada', 'Los montos ejecutados fueron recalculados y los comprometidos reseteados a 0.', 'success');
}

echo $msg;

// ── Preview: comparar actual vs recalculado ──────────────────────────────────
$sql_preview = "SELECT pa.id AS PARTIDA_ID,
                       cg.codigo AS CAT_CODIGO, cg.nombre AS CATEGORIA,
                       ig.codigo AS ITEM_CODIGO, ig.nombre AS ITEM,
                       uo.nombre AS UNIDAD,
                       pa.monto_ejecutado AS EJEC_ACTUAL,
                       pa.monto_comprometido AS COMP_ACTUAL,
                       (SELECT COALESCE(SUM(sp.monto), 0)
                        FROM ciclo_solicitud_partida sp
                        JOIN ciclo_solicitud_recurso sr ON sp.id_solicitud = sr.id
                        WHERE sp.id_partida = pa.id AND sr.estado = 'ejecutada') AS EJEC_CALCULADO
                FROM ciclo_partida pa
                JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
                JOIN ciclo_categoria_gasto cg ON pa.id_categoria = cg.id
                JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
                WHERE pr.id_periodo = '$periodo_id'
                ORDER BY uo.nombre, cg.orden, ig.codigo;";
$res_preview = sql_do_query($sql_preview, $db, $usu);

$filas = [];
$diffs = 0;
while ($rp = sql_do_array($res_preview)) {
    $filas[] = $rp;
    if (abs((float)$rp['EJEC_ACTUAL'] - (float)$rp['EJEC_CALCULADO']) > 0.01) $diffs++;
}
?>

<?php echo generarTituloSeccion('Consolidar Ejecutado — Período ' . $anio, 'bi bi-calculator'); ?>

<?php echo generarWizardPasos(3, 4, ['Iniciar', 'Validar', 'Consolidar', 'Cerrar']); ?>

<?php if ($diffs > 0 && !$consolidado): ?>
<div class="alert alert-warning mb-3">
    <i class="bi bi-exclamation-triangle me-2"></i>
    Se detectaron <strong><?php echo $diffs; ?></strong> partida(s) con diferencias entre el monto ejecutado registrado y el recalculado desde solicitudes.
    La consolidación corregirá estas inconsistencias.
</div>
<?php elseif ($consolidado): ?>
<div class="alert alert-success mb-3">
    <i class="bi bi-check-circle me-2"></i>
    Consolidación realizada. Los datos están consistentes. Puede avanzar al paso final.
</div>
<?php else: ?>
<div class="alert alert-success mb-3">
    <i class="bi bi-check-circle me-2"></i>
    Los datos están consistentes. Puede consolidar para confirmar y avanzar.
</div>
<?php endif; ?>

<!-- TABLA PREVIEW -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h6 class="mb-0"><i class="bi bi-table me-2"></i>Vista Previa de Consolidación</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-pade table-sm mb-0">
                <?php echo generarTablaHeader(['Unidad', 'Categoría', 'Ítem', 'Ejecutado Actual', 'Ejecutado Calculado', 'Diferencia', 'Comprometido']); ?>
                <tbody>
<?php
$n = 0;
$unidad_ant = '';
foreach ($filas as $f):
    $n++;
    $ea = (float)$f['EJEC_ACTUAL'];
    $ec = (float)$f['EJEC_CALCULADO'];
    $diff = $ec - $ea;
    $hay_diff = (abs($diff) > 0.01);
    $row_class = $hay_diff ? 'table-danger' : '';
    $mostrar_u = ($f['UNIDAD'] !== $unidad_ant);
    $unidad_ant = $f['UNIDAD'];
?>
                    <tr class="<?php echo $row_class; ?>">
                        <td class="fw-bold"><?php echo $mostrar_u ? htmlspecialchars($f['UNIDAD']) : ''; ?></td>
                        <td><small class="text-muted"><?php echo htmlspecialchars($f['CAT_CODIGO']); ?></small> <?php echo htmlspecialchars($f['CATEGORIA']); ?></td>
                        <td><small class="text-muted"><?php echo htmlspecialchars($f['ITEM_CODIGO']); ?></small> <?php echo htmlspecialchars($f['ITEM']); ?></td>
                        <td class="text-end">$ <?php echo number_format($ea, 2, ',', '.'); ?></td>
                        <td class="text-end fw-bold">$ <?php echo number_format($ec, 2, ',', '.'); ?></td>
                        <td class="text-end">
                            <?php if ($hay_diff): ?>
                            <span class="<?php echo ($diff > 0) ? 'text-danger' : 'text-success'; ?> fw-bold">
                                <?php echo ($diff >= 0 ? '+' : '') . number_format($diff, 2, ',', '.'); ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-muted">$ <?php echo number_format((float)$f['COMP_ACTUAL'], 2, ',', '.'); ?></td>
                    </tr>
<?php endforeach; ?>
<?php if (empty($filas)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Sin partidas para este período.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ACCIONES -->
<div class="d-flex justify-content-between">
    <a href="index.php?estado=602" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver a Validación
    </a>
    <div class="d-flex gap-2">
        <?php if (!$consolidado): ?>
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <input type="hidden" name="accion_post" value="consolidar">
            <button type="submit" class="btn btn-pade btn-lg">
                <i class="bi bi-calculator me-2"></i>Ejecutar Consolidación
            </button>
        </form>
        <?php else: ?>
        <a href="index.php?estado=604" class="btn btn-success btn-lg">
            <i class="bi bi-arrow-right me-2"></i>Continuar a Cerrar Período (Paso 4)
        </a>
        <?php endif; ?>
    </div>
</div>
SESSION['ciclo_rol_efectivo'] ?? '';

// ── Obtener período en cierre ────────────────────────────────────────────────
$row_periodo = sql_do_array(sql_do_query(
    "SELECT * FROM ciclo_periodo_fiscal WHERE estado = 'en_cierre' LIMIT 1;", $db, $usu));

if (!$row_periodo) {
    echo generarAlertJS('Sin período', 'No hay un período en estado en_cierre.', 'warning', "index.php?estado=601");
    return;
}
$periodo_id = $row_periodo['ID'];
$anio = $row_periodo['ANIO'];

// ── Validar que no haya solicitudes pendientes (Paso 2) ──────────────────────
$sql_pend = "SELECT COUNT(*) AS CANT FROM ciclo_solicitud_recurso
             WHERE id_periodo = '$periodo_id' AND estado IN ('pendiente', 'aprobada');";
$row_pend = sql_do_array(sql_do_query($sql_pend, $db, $usu));
if ((int)($row_pend['CANT'] ?? 0) > 0) {
    echo generarAlertJS('Paso incompleto', 'Aún hay solicitudes sin resolver. Complete el Paso 2 primero.', 'warning', "index.php?estado=602");
    return;
}

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST — CONSOLIDAR
// ═══════════════════════════════════════════════════════════════════════════════
$consolidado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Enmascarar_post('accion_post') === 'consolidar') {

    // Recalcular ejecutado y resetear comprometido
    $sql_upd = "UPDATE ciclo_partida pa
                JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                SET pa.monto_ejecutado = (
                    SELECT COALESCE(SUM(sp.monto), 0)
                    FROM ciclo_solicitud_partida sp
                    JOIN ciclo_solicitud_recurso sr ON sp.id_solicitud = sr.id
                    WHERE sp.id_partida = pa.id AND sr.estado = 'ejecutada'
                ),
                pa.monto_comprometido = 0,
                pa.auditoria_usu = '$usu'
                WHERE pr.id_periodo = '$periodo_id';";
    sql_do_query($sql_upd, $db, $usu);

    // Recalcular monto_total de cada presupuesto
    $sql_pres = "SELECT id FROM ciclo_presupuesto WHERE id_periodo = '$periodo_id';";
    $res_pres = sql_do_query($sql_pres, $db, $usu);
    while ($rp = sql_do_array($res_pres)) {
        $pid = $rp['ID'];
        $sql_tot = "SELECT SUM(monto_ejecutado) AS TOTAL FROM ciclo_partida WHERE id_presupuesto = '$pid';";
        $row_tot = sql_do_array(sql_do_query($sql_tot, $db, $usu));
        $total = (float)($row_tot['TOTAL'] ?? 0);
        sql_do_query("UPDATE ciclo_presupuesto SET monto_total = '$total', auditoria_usu = '$usu' WHERE id = '$pid';", $db, $usu);
    }

    $consolidado = true;
    $msg = generarAlertJS('Consolidación completada', 'Los montos ejecutados fueron recalculados y los comprometidos reseteados a 0.', 'success');
}

echo $msg;

// ── Preview: comparar actual vs recalculado ──────────────────────────────────
$sql_preview = "SELECT pa.id AS PARTIDA_ID,
                       cg.codigo AS CAT_CODIGO, cg.nombre AS CATEGORIA,
                       ig.codigo AS ITEM_CODIGO, ig.nombre AS ITEM,
                       uo.nombre AS UNIDAD,
                       pa.monto_ejecutado AS EJEC_ACTUAL,
                       pa.monto_comprometido AS COMP_ACTUAL,
                       (SELECT COALESCE(SUM(sp.monto), 0)
                        FROM ciclo_solicitud_partida sp
                        JOIN ciclo_solicitud_recurso sr ON sp.id_solicitud = sr.id
                        WHERE sp.id_partida = pa.id AND sr.estado = 'ejecutada') AS EJEC_CALCULADO
                FROM ciclo_partida pa
                JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
                JOIN ciclo_categoria_gasto cg ON pa.id_categoria = cg.id
                JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
                WHERE pr.id_periodo = '$periodo_id'
                ORDER BY uo.nombre, cg.orden, ig.codigo;";
$res_preview = sql_do_query($sql_preview, $db, $usu);

$filas = [];
$diffs = 0;
while ($rp = sql_do_array($res_preview)) {
    $filas[] = $rp;
    if (abs((float)$rp['EJEC_ACTUAL'] - (float)$rp['EJEC_CALCULADO']) > 0.01) $diffs++;
}
?>

<?php echo generarTituloSeccion('Consolidar Ejecutado — Período ' . $anio, 'bi bi-calculator'); ?>

<?php echo generarWizardPasos(3, 4, ['Iniciar', 'Validar', 'Consolidar', 'Cerrar']); ?>

<?php if ($diffs > 0 && !$consolidado): ?>
<div class="alert alert-warning mb-3">
    <i class="bi bi-exclamation-triangle me-2"></i>
    Se detectaron <strong><?php echo $diffs; ?></strong> partida(s) con diferencias entre el monto ejecutado registrado y el recalculado desde solicitudes.
    La consolidación corregirá estas inconsistencias.
</div>
<?php elseif ($consolidado): ?>
<div class="alert alert-success mb-3">
    <i class="bi bi-check-circle me-2"></i>
    Consolidación realizada. Los datos están consistentes. Puede avanzar al paso final.
</div>
<?php else: ?>
<div class="alert alert-success mb-3">
    <i class="bi bi-check-circle me-2"></i>
    Los datos están consistentes. Puede consolidar para confirmar y avanzar.
</div>
<?php endif; ?>

<!-- TABLA PREVIEW -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h6 class="mb-0"><i class="bi bi-table me-2"></i>Vista Previa de Consolidación</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-pade table-sm mb-0">
                <?php echo generarTablaHeader(['Unidad', 'Categoría', 'Ítem', 'Ejecutado Actual', 'Ejecutado Calculado', 'Diferencia', 'Comprometido']); ?>
                <tbody>
<?php
$n = 0;
$unidad_ant = '';
foreach ($filas as $f):
    $n++;
    $ea = (float)$f['EJEC_ACTUAL'];
    $ec = (float)$f['EJEC_CALCULADO'];
    $diff = $ec - $ea;
    $hay_diff = (abs($diff) > 0.01);
    $row_class = $hay_diff ? 'table-danger' : '';
    $mostrar_u = ($f['UNIDAD'] !== $unidad_ant);
    $unidad_ant = $f['UNIDAD'];
?>
                    <tr class="<?php echo $row_class; ?>">
                        <td class="fw-bold"><?php echo $mostrar_u ? htmlspecialchars($f['UNIDAD']) : ''; ?></td>
                        <td><small class="text-muted"><?php echo htmlspecialchars($f['CAT_CODIGO']); ?></small> <?php echo htmlspecialchars($f['CATEGORIA']); ?></td>
                        <td><small class="text-muted"><?php echo htmlspecialchars($f['ITEM_CODIGO']); ?></small> <?php echo htmlspecialchars($f['ITEM']); ?></td>
                        <td class="text-end">$ <?php echo number_format($ea, 2, ',', '.'); ?></td>
                        <td class="text-end fw-bold">$ <?php echo number_format($ec, 2, ',', '.'); ?></td>
                        <td class="text-end">
                            <?php if ($hay_diff): ?>
                            <span class="<?php echo ($diff > 0) ? 'text-danger' : 'text-success'; ?> fw-bold">
                                <?php echo ($diff >= 0 ? '+' : '') . number_format($diff, 2, ',', '.'); ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-muted">$ <?php echo number_format((float)$f['COMP_ACTUAL'], 2, ',', '.'); ?></td>
                    </tr>
<?php endforeach; ?>
<?php if (empty($filas)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Sin partidas para este período.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ACCIONES -->
<div class="d-flex justify-content-between">
    <a href="index.php?estado=602" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver a Validación
    </a>
    <div class="d-flex gap-2">
        <?php if (!$consolidado): ?>
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <input type="hidden" name="accion_post" value="consolidar">
            <button type="submit" class="btn btn-pade btn-lg">
                <i class="bi bi-calculator me-2"></i>Ejecutar Consolidación
            </button>
        </form>
        <?php else: ?>
        <a href="index.php?estado=604" class="btn btn-success btn-lg">
            <i class="bi bi-arrow-right me-2"></i>Continuar a Cerrar Período (Paso 4)
        </a>
        <?php endif; ?>
    </div>
</div>
SESSION['ciclo_rol_efectivo'] ?? '';

// ── Obtener período en cierre ────────────────────────────────────────────────
$row_periodo = sql_do_array(sql_do_query(
    "SELECT * FROM ciclo_periodo_fiscal WHERE estado = 'en_cierre' LIMIT 1;", $db, $usu));

if (!$row_periodo) {
    echo generarAlertJS('Sin período', 'No hay un período en estado en_cierre.', 'warning', "index.php?estado=601");
    return;
}
$periodo_id = $row_periodo['ID'];
$anio = $row_periodo['ANIO'];

// ── Validar que no haya solicitudes pendientes (Paso 2) ──────────────────────
$sql_pend = "SELECT COUNT(*) AS CANT FROM ciclo_solicitud_recurso
             WHERE id_periodo = '$periodo_id' AND estado IN ('pendiente', 'aprobada');";
$row_pend = sql_do_array(sql_do_query($sql_pend, $db, $usu));
if ((int)($row_pend['CANT'] ?? 0) > 0) {
    echo generarAlertJS('Paso incompleto', 'Aún hay solicitudes sin resolver. Complete el Paso 2 primero.', 'warning', "index.php?estado=602");
    return;
}

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST — CONSOLIDAR
// ═══════════════════════════════════════════════════════════════════════════════
$consolidado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Enmascarar_post('accion_post') === 'consolidar') {

    // Recalcular ejecutado y resetear comprometido
    $sql_upd = "UPDATE ciclo_partida pa
                JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                SET pa.monto_ejecutado = (
                    SELECT COALESCE(SUM(sp.monto), 0)
                    FROM ciclo_solicitud_partida sp
                    JOIN ciclo_solicitud_recurso sr ON sp.id_solicitud = sr.id
                    WHERE sp.id_partida = pa.id AND sr.estado = 'ejecutada'
                ),
                pa.monto_comprometido = 0,
                pa.auditoria_usu = '$usu'
                WHERE pr.id_periodo = '$periodo_id';";
    sql_do_query($sql_upd, $db, $usu);

    // Recalcular monto_total de cada presupuesto
    $sql_pres = "SELECT id FROM ciclo_presupuesto WHERE id_periodo = '$periodo_id';";
    $res_pres = sql_do_query($sql_pres, $db, $usu);
    while ($rp = sql_do_array($res_pres)) {
        $pid = $rp['ID'];
        $sql_tot = "SELECT SUM(monto_ejecutado) AS TOTAL FROM ciclo_partida WHERE id_presupuesto = '$pid';";
        $row_tot = sql_do_array(sql_do_query($sql_tot, $db, $usu));
        $total = (float)($row_tot['TOTAL'] ?? 0);
        sql_do_query("UPDATE ciclo_presupuesto SET monto_total = '$total', auditoria_usu = '$usu' WHERE id = '$pid';", $db, $usu);
    }

    $consolidado = true;
    $msg = generarAlertJS('Consolidación completada', 'Los montos ejecutados fueron recalculados y los comprometidos reseteados a 0.', 'success');
}

echo $msg;

// ── Preview: comparar actual vs recalculado ──────────────────────────────────
$sql_preview = "SELECT pa.id AS PARTIDA_ID,
                       cg.codigo AS CAT_CODIGO, cg.nombre AS CATEGORIA,
                       ig.codigo AS ITEM_CODIGO, ig.nombre AS ITEM,
                       uo.nombre AS UNIDAD,
                       pa.monto_ejecutado AS EJEC_ACTUAL,
                       pa.monto_comprometido AS COMP_ACTUAL,
                       (SELECT COALESCE(SUM(sp.monto), 0)
                        FROM ciclo_solicitud_partida sp
                        JOIN ciclo_solicitud_recurso sr ON sp.id_solicitud = sr.id
                        WHERE sp.id_partida = pa.id AND sr.estado = 'ejecutada') AS EJEC_CALCULADO
                FROM ciclo_partida pa
                JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
                JOIN ciclo_categoria_gasto cg ON pa.id_categoria = cg.id
                JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
                WHERE pr.id_periodo = '$periodo_id'
                ORDER BY uo.nombre, cg.orden, ig.codigo;";
$res_preview = sql_do_query($sql_preview, $db, $usu);

$filas = [];
$diffs = 0;
while ($rp = sql_do_array($res_preview)) {
    $filas[] = $rp;
    if (abs((float)$rp['EJEC_ACTUAL'] - (float)$rp['EJEC_CALCULADO']) > 0.01) $diffs++;
}
?>

<?php echo generarTituloSeccion('Consolidar Ejecutado — Período ' . $anio, 'bi bi-calculator'); ?>

<?php echo generarWizardPasos(3, 4, ['Iniciar', 'Validar', 'Consolidar', 'Cerrar']); ?>

<?php if ($diffs > 0 && !$consolidado): ?>
<div class="alert alert-warning mb-3">
    <i class="bi bi-exclamation-triangle me-2"></i>
    Se detectaron <strong><?php echo $diffs; ?></strong> partida(s) con diferencias entre el monto ejecutado registrado y el recalculado desde solicitudes.
    La consolidación corregirá estas inconsistencias.
</div>
<?php elseif ($consolidado): ?>
<div class="alert alert-success mb-3">
    <i class="bi bi-check-circle me-2"></i>
    Consolidación realizada. Los datos están consistentes. Puede avanzar al paso final.
</div>
<?php else: ?>
<div class="alert alert-success mb-3">
    <i class="bi bi-check-circle me-2"></i>
    Los datos están consistentes. Puede consolidar para confirmar y avanzar.
</div>
<?php endif; ?>

<!-- TABLA PREVIEW -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h6 class="mb-0"><i class="bi bi-table me-2"></i>Vista Previa de Consolidación</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-pade table-sm mb-0">
                <?php echo generarTablaHeader(['Unidad', 'Categoría', 'Ítem', 'Ejecutado Actual', 'Ejecutado Calculado', 'Diferencia', 'Comprometido']); ?>
                <tbody>
<?php
$n = 0;
$unidad_ant = '';
foreach ($filas as $f):
    $n++;
    $ea = (float)$f['EJEC_ACTUAL'];
    $ec = (float)$f['EJEC_CALCULADO'];
    $diff = $ec - $ea;
    $hay_diff = (abs($diff) > 0.01);
    $row_class = $hay_diff ? 'table-danger' : '';
    $mostrar_u = ($f['UNIDAD'] !== $unidad_ant);
    $unidad_ant = $f['UNIDAD'];
?>
                    <tr class="<?php echo $row_class; ?>">
                        <td class="fw-bold"><?php echo $mostrar_u ? htmlspecialchars($f['UNIDAD']) : ''; ?></td>
                        <td><small class="text-muted"><?php echo htmlspecialchars($f['CAT_CODIGO']); ?></small> <?php echo htmlspecialchars($f['CATEGORIA']); ?></td>
                        <td><small class="text-muted"><?php echo htmlspecialchars($f['ITEM_CODIGO']); ?></small> <?php echo htmlspecialchars($f['ITEM']); ?></td>
                        <td class="text-end">$ <?php echo number_format($ea, 2, ',', '.'); ?></td>
                        <td class="text-end fw-bold">$ <?php echo number_format($ec, 2, ',', '.'); ?></td>
                        <td class="text-end">
                            <?php if ($hay_diff): ?>
                            <span class="<?php echo ($diff > 0) ? 'text-danger' : 'text-success'; ?> fw-bold">
                                <?php echo ($diff >= 0 ? '+' : '') . number_format($diff, 2, ',', '.'); ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-muted">$ <?php echo number_format((float)$f['COMP_ACTUAL'], 2, ',', '.'); ?></td>
                    </tr>
<?php endforeach; ?>
<?php if (empty($filas)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Sin partidas para este período.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ACCIONES -->
<div class="d-flex justify-content-between">
    <a href="index.php?estado=602" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver a Validación
    </a>
    <div class="d-flex gap-2">
        <?php if (!$consolidado): ?>
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <input type="hidden" name="accion_post" value="consolidar">
            <button type="submit" class="btn btn-pade btn-lg">
                <i class="bi bi-calculator me-2"></i>Ejecutar Consolidación
            </button>
        </form>
        <?php else: ?>
        <a href="index.php?estado=604" class="btn btn-success btn-lg">
            <i class="bi bi-arrow-right me-2"></i>Continuar a Cerrar Período (Paso 4)
        </a>
        <?php endif; ?>
    </div>
</div>
SESSION['ciclo_rol_efectivo'] ?? '';

// ── Obtener período en cierre ────────────────────────────────────────────────
$row_periodo = sql_do_array(sql_do_query(
    "SELECT * FROM ciclo_periodo_fiscal WHERE estado = 'en_cierre' LIMIT 1;", $db, $usu));

if (!$row_periodo) {
    echo generarAlertJS('Sin período', 'No hay un período en estado en_cierre.', 'warning', "index.php?estado=601");
    return;
}
$periodo_id = $row_periodo['ID'];
$anio = $row_periodo['ANIO'];

// ── Validar que no haya solicitudes pendientes (Paso 2) ──────────────────────
$sql_pend = "SELECT COUNT(*) AS CANT FROM ciclo_solicitud_recurso
             WHERE id_periodo = '$periodo_id' AND estado IN ('pendiente', 'aprobada');";
$row_pend = sql_do_array(sql_do_query($sql_pend, $db, $usu));
if ((int)($row_pend['CANT'] ?? 0) > 0) {
    echo generarAlertJS('Paso incompleto', 'Aún hay solicitudes sin resolver. Complete el Paso 2 primero.', 'warning', "index.php?estado=602");
    return;
}

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST — CONSOLIDAR
// ═══════════════════════════════════════════════════════════════════════════════
$consolidado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Enmascarar_post('accion_post') === 'consolidar') {

    // Recalcular ejecutado y resetear comprometido
    $sql_upd = "UPDATE ciclo_partida pa
                JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                SET pa.monto_ejecutado = (
                    SELECT COALESCE(SUM(sp.monto), 0)
                    FROM ciclo_solicitud_partida sp
                    JOIN ciclo_solicitud_recurso sr ON sp.id_solicitud = sr.id
                    WHERE sp.id_partida = pa.id AND sr.estado = 'ejecutada'
                ),
                pa.monto_comprometido = 0,
                pa.auditoria_usu = '$usu'
                WHERE pr.id_periodo = '$periodo_id';";
    sql_do_query($sql_upd, $db, $usu);

    // Recalcular monto_total de cada presupuesto
    $sql_pres = "SELECT id FROM ciclo_presupuesto WHERE id_periodo = '$periodo_id';";
    $res_pres = sql_do_query($sql_pres, $db, $usu);
    while ($rp = sql_do_array($res_pres)) {
        $pid = $rp['ID'];
        $sql_tot = "SELECT SUM(monto_ejecutado) AS TOTAL FROM ciclo_partida WHERE id_presupuesto = '$pid';";
        $row_tot = sql_do_array(sql_do_query($sql_tot, $db, $usu));
        $total = (float)($row_tot['TOTAL'] ?? 0);
        sql_do_query("UPDATE ciclo_presupuesto SET monto_total = '$total', auditoria_usu = '$usu' WHERE id = '$pid';", $db, $usu);
    }

    $consolidado = true;
    $msg = generarAlertJS('Consolidación completada', 'Los montos ejecutados fueron recalculados y los comprometidos reseteados a 0.', 'success');
}

echo $msg;

// ── Preview: comparar actual vs recalculado ──────────────────────────────────
$sql_preview = "SELECT pa.id AS PARTIDA_ID,
                       cg.codigo AS CAT_CODIGO, cg.nombre AS CATEGORIA,
                       ig.codigo AS ITEM_CODIGO, ig.nombre AS ITEM,
                       uo.nombre AS UNIDAD,
                       pa.monto_ejecutado AS EJEC_ACTUAL,
                       pa.monto_comprometido AS COMP_ACTUAL,
                       (SELECT COALESCE(SUM(sp.monto), 0)
                        FROM ciclo_solicitud_partida sp
                        JOIN ciclo_solicitud_recurso sr ON sp.id_solicitud = sr.id
                        WHERE sp.id_partida = pa.id AND sr.estado = 'ejecutada') AS EJEC_CALCULADO
                FROM ciclo_partida pa
                JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
                JOIN ciclo_categoria_gasto cg ON pa.id_categoria = cg.id
                JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
                WHERE pr.id_periodo = '$periodo_id'
                ORDER BY uo.nombre, cg.orden, ig.codigo;";
$res_preview = sql_do_query($sql_preview, $db, $usu);

$filas = [];
$diffs = 0;
while ($rp = sql_do_array($res_preview)) {
    $filas[] = $rp;
    if (abs((float)$rp['EJEC_ACTUAL'] - (float)$rp['EJEC_CALCULADO']) > 0.01) $diffs++;
}
?>

<?php echo generarTituloSeccion('Consolidar Ejecutado — Período ' . $anio, 'bi bi-calculator'); ?>

<?php echo generarWizardPasos(3, 4, ['Iniciar', 'Validar', 'Consolidar', 'Cerrar']); ?>

<?php if ($diffs > 0 && !$consolidado): ?>
<div class="alert alert-warning mb-3">
    <i class="bi bi-exclamation-triangle me-2"></i>
    Se detectaron <strong><?php echo $diffs; ?></strong> partida(s) con diferencias entre el monto ejecutado registrado y el recalculado desde solicitudes.
    La consolidación corregirá estas inconsistencias.
</div>
<?php elseif ($consolidado): ?>
<div class="alert alert-success mb-3">
    <i class="bi bi-check-circle me-2"></i>
    Consolidación realizada. Los datos están consistentes. Puede avanzar al paso final.
</div>
<?php else: ?>
<div class="alert alert-success mb-3">
    <i class="bi bi-check-circle me-2"></i>
    Los datos están consistentes. Puede consolidar para confirmar y avanzar.
</div>
<?php endif; ?>

<!-- TABLA PREVIEW -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h6 class="mb-0"><i class="bi bi-table me-2"></i>Vista Previa de Consolidación</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-pade table-sm mb-0">
                <?php echo generarTablaHeader(['Unidad', 'Categoría', 'Ítem', 'Ejecutado Actual', 'Ejecutado Calculado', 'Diferencia', 'Comprometido']); ?>
                <tbody>
<?php
$n = 0;
$unidad_ant = '';
foreach ($filas as $f):
    $n++;
    $ea = (float)$f['EJEC_ACTUAL'];
    $ec = (float)$f['EJEC_CALCULADO'];
    $diff = $ec - $ea;
    $hay_diff = (abs($diff) > 0.01);
    $row_class = $hay_diff ? 'table-danger' : '';
    $mostrar_u = ($f['UNIDAD'] !== $unidad_ant);
    $unidad_ant = $f['UNIDAD'];
?>
                    <tr class="<?php echo $row_class; ?>">
                        <td class="fw-bold"><?php echo $mostrar_u ? htmlspecialchars($f['UNIDAD']) : ''; ?></td>
                        <td><small class="text-muted"><?php echo htmlspecialchars($f['CAT_CODIGO']); ?></small> <?php echo htmlspecialchars($f['CATEGORIA']); ?></td>
                        <td><small class="text-muted"><?php echo htmlspecialchars($f['ITEM_CODIGO']); ?></small> <?php echo htmlspecialchars($f['ITEM']); ?></td>
                        <td class="text-end">$ <?php echo number_format($ea, 2, ',', '.'); ?></td>
                        <td class="text-end fw-bold">$ <?php echo number_format($ec, 2, ',', '.'); ?></td>
                        <td class="text-end">
                            <?php if ($hay_diff): ?>
                            <span class="<?php echo ($diff > 0) ? 'text-danger' : 'text-success'; ?> fw-bold">
                                <?php echo ($diff >= 0 ? '+' : '') . number_format($diff, 2, ',', '.'); ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-muted">$ <?php echo number_format((float)$f['COMP_ACTUAL'], 2, ',', '.'); ?></td>
                    </tr>
<?php endforeach; ?>
<?php if (empty($filas)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Sin partidas para este período.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ACCIONES -->
<div class="d-flex justify-content-between">
    <a href="index.php?estado=602" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver a Validación
    </a>
    <div class="d-flex gap-2">
        <?php if (!$consolidado): ?>
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <input type="hidden" name="accion_post" value="consolidar">
            <button type="submit" class="btn btn-pade btn-lg">
                <i class="bi bi-calculator me-2"></i>Ejecutar Consolidación
            </button>
        </form>
        <?php else: ?>
        <a href="index.php?estado=604" class="btn btn-success btn-lg">
            <i class="bi bi-arrow-right me-2"></i>Continuar a Cerrar Período (Paso 4)
        </a>
        <?php endif; ?>
    </div>
</div>
