<?php
/**
 * ============================================================================
 * Revisar Presupuesto
 * ============================================================================
 * ID menú: 302 | Tablas: ciclo_presupuesto, ciclo_partida, ciclo_revision, ciclo_revision_detalle
 * Pantalla de trabajo del revisor. Permite ver detalle completo y modificar
 * montos de partidas individuales con observaciones obligatorias.
 * ============================================================================
 */

$db  = '1';
$msg = '';
$pres_id = Enmascarar_get('id');

if (empty($pres_id)) {
    echo generarAlertJS('Error', 'No se especificó un presupuesto.', 'error', "index.php?estado=301");
    return;
}

// ── Cargar presupuesto ───────────────────────────────────────────────────────
$sql_pres = "SELECT pr.*, pf.anio, uo.nombre AS UNIDAD
             FROM ciclo_presupuesto pr
             JOIN ciclo_periodo_fiscal pf ON pr.id_periodo = pf.id
             JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
             WHERE pr.id = '$pres_id';";
$row_pres = sql_do_array(sql_do_query($sql_pres, $db, $usu));

if (!$row_pres) {
    echo generarAlertJS('Error', 'Presupuesto no encontrado.', 'error', "index.php?estado=301");
    return;
}

if ($row_pres['ESTADO'] !== 'en_revision') {
    echo generarAlertJS('Error', 'Este presupuesto no está en estado de revisión.', 'warning', "index.php?estado=301");
    return;
}

// ── Nivel del revisor ────────────────────────────────────────────────────────
$rol_usuario = <?php
/**
 * ============================================================================
 * Revisar Presupuesto
 * ============================================================================
 * ID menú: 302 | Tablas: ciclo_presupuesto, ciclo_partida, ciclo_revision, ciclo_revision_detalle
 * Pantalla de trabajo del revisor. Permite ver detalle completo y modificar
 * montos de partidas individuales con observaciones obligatorias.
 * ============================================================================
 */

$db  = '1';
$msg = '';
$pres_id = Enmascarar_get('id');

if (empty($pres_id)) {
    echo generarAlertJS('Error', 'No se especificó un presupuesto.', 'error', "index.php?estado=301");
    return;
}

// ── Cargar presupuesto ───────────────────────────────────────────────────────
$sql_pres = "SELECT pr.*, pf.anio, uo.nombre AS UNIDAD
             FROM ciclo_presupuesto pr
             JOIN ciclo_periodo_fiscal pf ON pr.id_periodo = pf.id
             JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
             WHERE pr.id = '$pres_id';";
$row_pres = sql_do_array(sql_do_query($sql_pres, $db, $usu));

if (!$row_pres) {
    echo generarAlertJS('Error', 'Presupuesto no encontrado.', 'error', "index.php?estado=301");
    return;
}

if ($row_pres['ESTADO'] !== 'en_revision') {
    echo generarAlertJS('Error', 'Este presupuesto no está en estado de revisión.', 'warning', "index.php?estado=301");
    return;
}

// ── Nivel del revisor ────────────────────────────────────────────────────────
$rol_usuario = $_SESSION['rol'] ?? '';
$nivel_revisor = 1;
if ($rol_usuario === 'admin' || $rol_usuario === 'director') $nivel_revisor = 3;
elseif ($rol_usuario === 'gerente') $nivel_revisor = 2;

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST — GUARDAR REVISIÓN
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Enmascarar_post('accion_post') === 'guardar') {

    $obs_general = Enmascarar_post('observaciones_general');
    $partida_ids = $_POST['partida_id'] ?? [];
    $montos_nuevos = $_POST['monto_aprobado'] ?? [];
    $observaciones = $_POST['observacion'] ?? [];

    // Registrar revisión principal
    $sql_rev = "INSERT INTO ciclo_revision (id_presupuesto, id_usuario, nivel, accion, observaciones, fecha)
                VALUES ('$pres_id', '$usu', '$nivel_revisor', 'modificar', '$obs_general', NOW());";
    sql_do_query($sql_rev, $db, $usu);

    // Obtener ID de la revisión recién creada
    $row_rev = sql_do_array(sql_do_query("SELECT MAX(id) AS MAXID FROM ciclo_revision WHERE id_presupuesto = '$pres_id';", $db, $usu));
    $revision_id = $row_rev['MAXID'];

    $cambios = 0;
    $monto_total_nuevo = 0;

    for ($i = 0; $i < count($partida_ids); $i++) {
        $pid = sani_input($partida_ids[$i]);
        $monto_nuevo = (float)($montos_nuevos[$i] ?? 0);
        $obs_partida = sani_input($observaciones[$i] ?? '');

        if ($monto_nuevo < 0) $monto_nuevo = 0;

        // Leer monto anterior
        $row_pa = sql_do_array(sql_do_query("SELECT monto_aprobado, monto_proyectado FROM ciclo_partida WHERE id = '$pid';", $db, $usu));
        $monto_anterior = (float)($row_pa['MONTO_APROBADO'] ?? $row_pa['MONTO_PROYECTADO'] ?? 0);

        $monto_total_nuevo += $monto_nuevo;

        // Si cambió, registrar detalle y validar observación
        if (abs($monto_nuevo - $monto_anterior) > 0.01) {
            if (empty($obs_partida)) {
                $obs_partida = 'Ajuste de revisión nivel ' . $nivel_revisor;
            }
            $sql_det = "INSERT INTO ciclo_revision_detalle (id_revision, id_partida, monto_anterior, monto_nuevo, motivo)
                        VALUES ('$revision_id', '$pid', '$monto_anterior', '$monto_nuevo', '$obs_partida');";
            sql_do_query($sql_det, $db, $usu);
            $cambios++;
        }

        // Actualizar partida
        sql_do_query("UPDATE ciclo_partida SET monto_aprobado = '$monto_nuevo', auditoria_usu = '$usu' WHERE id = '$pid';", $db, $usu);
    }

    // Recalcular monto total del presupuesto
    sql_do_query("UPDATE ciclo_presupuesto SET monto_total = '$monto_total_nuevo', auditoria_usu = '$usu' WHERE id = '$pres_id';", $db, $usu);

    $msg = generarAlertJS('Revisión guardada', "Se registraron $cambios cambios. Monto total actualizado.", 'success', "index.php?estado=302&id=$pres_id");
}

echo $msg;

// ── Cargar partidas ──────────────────────────────────────────────────────────
$sql_partidas = "SELECT pa.*, cg.nombre AS CATEGORIA, cg.codigo AS CAT_CODIGO, cg.orden AS CAT_ORDEN,
                        ig.codigo AS ITEM_CODIGO, ig.nombre AS ITEM
                 FROM ciclo_partida pa
                 JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
                 JOIN ciclo_categoria_gasto cg ON pa.id_categoria = cg.id
                 WHERE pa.id_presupuesto = '$pres_id'
                 ORDER BY cg.orden, ig.codigo;";
$res_partidas = sql_do_query($sql_partidas, $db, $usu);

$partidas = [];
while ($rp = sql_do_array($res_partidas)) $partidas[] = $rp;

// Badge de estado
$estado_badge = '<span class="badge bg-warning text-dark fs-6">En Revisión</span>';
?>

<?php echo generarTituloSeccion('Revisar Presupuesto', 'bi bi-pencil-square'); ?>

<!-- CABECERA -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <small class="text-muted">Período</small>
                <div class="fw-bold fs-5"><?php echo htmlspecialchars($row_pres['ANIO']); ?></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Unidad</small>
                <div class="fw-bold"><?php echo htmlspecialchars($row_pres['UNIDAD']); ?></div>
            </div>
            <div class="col-md-2">
                <small class="text-muted">Estado</small>
                <div><?php echo $estado_badge; ?></div>
            </div>
            <div class="col-md-2">
                <small class="text-muted">Monto Total</small>
                <div class="fw-bold text-primary" id="monto_total_display">$ <?php echo number_format((float)$row_pres['MONTO_TOTAL'], 2, ',', '.'); ?></div>
            </div>
            <div class="col-md-2">
                <small class="text-muted">Su nivel</small>
                <div><span class="badge bg-primary fs-6">Nivel <?php echo $nivel_revisor; ?></span></div>
            </div>
        </div>
    </div>
</div>

<div class="mb-3 d-flex gap-2">
    <a href="index.php?estado=301" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver a bandeja
    </a>
    <a href="index.php?estado=303&id=<?php echo $pres_id; ?>" class="btn btn-success btn-sm">
        <i class="bi bi-check2-all me-1"></i>Aprobar / Devolver
    </a>
    <a href="index.php?estado=304&id=<?php echo $pres_id; ?>" class="btn btn-outline-info btn-sm">
        <i class="bi bi-clock-history me-1"></i>Historial
    </a>
</div>

<!-- FORMULARIO DE REVISIÓN -->
<form method="post" action="index.php?estado=<?php echo $estado; ?>&id=<?php echo $pres_id; ?>" id="formRevision">
    <input type="hidden" name="accion_post" value="guardar">

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0"><i class="bi bi-tag me-2"></i>Partidas — Edición de Montos</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-pade mb-0" id="tablaPartidas">
                    <?php echo generarTablaHeader(['Categoría', 'Código', 'Ítem', 'Proyectado', 'Monto Aprobado', 'Diferencia', 'Observación']); ?>
                    <tbody>
<?php
$cat_anterior = '';
$idx = 0;
foreach ($partidas as $p):
    $monto_proy = (float)($p['MONTO_PROYECTADO'] ?? 0);
    $monto_apro = (float)($p['MONTO_APROBADO'] ?? $monto_proy);
    $mostrar_cat = ($p['CATEGORIA'] !== $cat_anterior);
    $cat_anterior = $p['CATEGORIA'];
?>
                        <tr>
                            <td class="fw-bold"><?php echo $mostrar_cat ? '<small class="text-muted">' . htmlspecialchars($p['CAT_CODIGO']) . '</small> ' . htmlspecialchars($p['CATEGORIA']) : ''; ?></td>
                            <td class="text-center"><small><?php echo htmlspecialchars($p['ITEM_CODIGO']); ?></small></td>
                            <td><?php echo htmlspecialchars($p['ITEM']); ?></td>
                            <td class="text-end text-muted">$ <?php echo number_format($monto_proy, 2, ',', '.'); ?></td>
                            <td>
                                <input type="hidden" name="partida_id[]" value="<?php echo $p['ID']; ?>">
                                <input type="number" class="form-control form-control-sm text-end monto-input"
                                       name="monto_aprobado[]" step="0.01" min="0"
                                       value="<?php echo number_format($monto_apro, 2, '.', ''); ?>"
                                       data-proyectado="<?php echo $monto_proy; ?>"
                                       data-idx="<?php echo $idx; ?>"
                                       onchange="calcularDiff(this)">
                            </td>
                            <td class="text-end diff-cell" id="diff_<?php echo $idx; ?>">
                                <?php
                                $diff = $monto_apro - $monto_proy;
                                $diff_class = ($diff > 0) ? 'text-success' : (($diff < 0) ? 'text-danger' : 'text-muted');
                                ?>
                                <span class="<?php echo $diff_class; ?>"><?php echo ($diff >= 0 ? '+' : '') . number_format($diff, 2, ',', '.'); ?></span>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" name="observacion[]"
                                       placeholder="Motivo del cambio" value="">
                            </td>
                        </tr>
<?php $idx++; endforeach; ?>
<?php if (empty($partidas)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No hay partidas para este presupuesto.</td></tr>
<?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Observaciones generales -->
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <label class="form-label fw-semibold">Observaciones generales de la revisión</label>
            <textarea class="form-control" name="observaciones_general" rows="3"
                      placeholder="Comentarios generales sobre esta revisión..."></textarea>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <a href="index.php?estado=301" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-pade btn-lg">
            <i class="bi bi-check-lg me-1"></i>Guardar Revisión
        </button>
    </div>
</form>

<script>
function calcularDiff(input) {
    var proyectado = parseFloat(input.dataset.proyectado) || 0;
    var nuevo = parseFloat(input.value) || 0;
    var diff = nuevo - proyectado;
    var idx = input.dataset.idx;
    var cell = document.getElementById('diff_' + idx);
    var cls = diff > 0 ? 'text-success' : (diff < 0 ? 'text-danger' : 'text-muted');
    var prefix = diff >= 0 ? '+' : '';
    cell.innerHTML = '<span class="' + cls + '">' + prefix + diff.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</span>';

    // Recalcular total
    var inputs = document.querySelectorAll('.monto-input');
    var total = 0;
    inputs.forEach(function(inp) { total += parseFloat(inp.value) || 0; });
    document.getElementById('monto_total_display').textContent = '$ ' + total.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>
SESSION['ciclo_rol_efectivo'] ?? '';
$nivel_revisor = 1;
if ($rol_usuario === 'admin' || $rol_usuario === 'director') $nivel_revisor = 3;
elseif ($rol_usuario === 'gerente') $nivel_revisor = 2;

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST — GUARDAR REVISIÓN
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Enmascarar_post('accion_post') === 'guardar') {

    $obs_general = Enmascarar_post('observaciones_general');
    $partida_ids = $_POST['partida_id'] ?? [];
    $montos_nuevos = $_POST['monto_aprobado'] ?? [];
    $observaciones = $_POST['observacion'] ?? [];

    // Registrar revisión principal
    $sql_rev = "INSERT INTO ciclo_revision (id_presupuesto, id_usuario, nivel, accion, observaciones, fecha)
                VALUES ('$pres_id', '$usu', '$nivel_revisor', 'modificar', '$obs_general', NOW());";
    sql_do_query($sql_rev, $db, $usu);

    // Obtener ID de la revisión recién creada
    $row_rev = sql_do_array(sql_do_query("SELECT MAX(id) AS MAXID FROM ciclo_revision WHERE id_presupuesto = '$pres_id';", $db, $usu));
    $revision_id = $row_rev['MAXID'];

    $cambios = 0;
    $monto_total_nuevo = 0;

    for ($i = 0; $i < count($partida_ids); $i++) {
        $pid = sani_input($partida_ids[$i]);
        $monto_nuevo = (float)($montos_nuevos[$i] ?? 0);
        $obs_partida = sani_input($observaciones[$i] ?? '');

        if ($monto_nuevo < 0) $monto_nuevo = 0;

        // Leer monto anterior
        $row_pa = sql_do_array(sql_do_query("SELECT monto_aprobado, monto_proyectado FROM ciclo_partida WHERE id = '$pid';", $db, $usu));
        $monto_anterior = (float)($row_pa['MONTO_APROBADO'] ?? $row_pa['MONTO_PROYECTADO'] ?? 0);

        $monto_total_nuevo += $monto_nuevo;

        // Si cambió, registrar detalle y validar observación
        if (abs($monto_nuevo - $monto_anterior) > 0.01) {
            if (empty($obs_partida)) {
                $obs_partida = 'Ajuste de revisión nivel ' . $nivel_revisor;
            }
            $sql_det = "INSERT INTO ciclo_revision_detalle (id_revision, id_partida, monto_anterior, monto_nuevo, motivo)
                        VALUES ('$revision_id', '$pid', '$monto_anterior', '$monto_nuevo', '$obs_partida');";
            sql_do_query($sql_det, $db, $usu);
            $cambios++;
        }

        // Actualizar partida
        sql_do_query("UPDATE ciclo_partida SET monto_aprobado = '$monto_nuevo', auditoria_usu = '$usu' WHERE id = '$pid';", $db, $usu);
    }

    // Recalcular monto total del presupuesto
    sql_do_query("UPDATE ciclo_presupuesto SET monto_total = '$monto_total_nuevo', auditoria_usu = '$usu' WHERE id = '$pres_id';", $db, $usu);

    $msg = generarAlertJS('Revisión guardada', "Se registraron $cambios cambios. Monto total actualizado.", 'success', "index.php?estado=302&id=$pres_id");
}

echo $msg;

// ── Cargar partidas ──────────────────────────────────────────────────────────
$sql_partidas = "SELECT pa.*, cg.nombre AS CATEGORIA, cg.codigo AS CAT_CODIGO, cg.orden AS CAT_ORDEN,
                        ig.codigo AS ITEM_CODIGO, ig.nombre AS ITEM
                 FROM ciclo_partida pa
                 JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
                 JOIN ciclo_categoria_gasto cg ON pa.id_categoria = cg.id
                 WHERE pa.id_presupuesto = '$pres_id'
                 ORDER BY cg.orden, ig.codigo;";
$res_partidas = sql_do_query($sql_partidas, $db, $usu);

$partidas = [];
while ($rp = sql_do_array($res_partidas)) $partidas[] = $rp;

// Badge de estado
$estado_badge = '<span class="badge bg-warning text-dark fs-6">En Revisión</span>';
?>

<?php echo generarTituloSeccion('Revisar Presupuesto', 'bi bi-pencil-square'); ?>

<!-- CABECERA -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <small class="text-muted">Período</small>
                <div class="fw-bold fs-5"><?php echo htmlspecialchars($row_pres['ANIO']); ?></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Unidad</small>
                <div class="fw-bold"><?php echo htmlspecialchars($row_pres['UNIDAD']); ?></div>
            </div>
            <div class="col-md-2">
                <small class="text-muted">Estado</small>
                <div><?php echo $estado_badge; ?></div>
            </div>
            <div class="col-md-2">
                <small class="text-muted">Monto Total</small>
                <div class="fw-bold text-primary" id="monto_total_display">$ <?php echo number_format((float)$row_pres['MONTO_TOTAL'], 2, ',', '.'); ?></div>
            </div>
            <div class="col-md-2">
                <small class="text-muted">Su nivel</small>
                <div><span class="badge bg-primary fs-6">Nivel <?php echo $nivel_revisor; ?></span></div>
            </div>
        </div>
    </div>
</div>

<div class="mb-3 d-flex gap-2">
    <a href="index.php?estado=301" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver a bandeja
    </a>
    <a href="index.php?estado=303&id=<?php echo $pres_id; ?>" class="btn btn-success btn-sm">
        <i class="bi bi-check2-all me-1"></i>Aprobar / Devolver
    </a>
    <a href="index.php?estado=304&id=<?php echo $pres_id; ?>" class="btn btn-outline-info btn-sm">
        <i class="bi bi-clock-history me-1"></i>Historial
    </a>
</div>

<!-- FORMULARIO DE REVISIÓN -->
<form method="post" action="index.php?estado=<?php echo $estado; ?>&id=<?php echo $pres_id; ?>" id="formRevision">
    <input type="hidden" name="accion_post" value="guardar">

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0"><i class="bi bi-tag me-2"></i>Partidas — Edición de Montos</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-pade mb-0" id="tablaPartidas">
                    <?php echo generarTablaHeader(['Categoría', 'Código', 'Ítem', 'Proyectado', 'Monto Aprobado', 'Diferencia', 'Observación']); ?>
                    <tbody>
<?php
$cat_anterior = '';
$idx = 0;
foreach ($partidas as $p):
    $monto_proy = (float)($p['MONTO_PROYECTADO'] ?? 0);
    $monto_apro = (float)($p['MONTO_APROBADO'] ?? $monto_proy);
    $mostrar_cat = ($p['CATEGORIA'] !== $cat_anterior);
    $cat_anterior = $p['CATEGORIA'];
?>
                        <tr>
                            <td class="fw-bold"><?php echo $mostrar_cat ? '<small class="text-muted">' . htmlspecialchars($p['CAT_CODIGO']) . '</small> ' . htmlspecialchars($p['CATEGORIA']) : ''; ?></td>
                            <td class="text-center"><small><?php echo htmlspecialchars($p['ITEM_CODIGO']); ?></small></td>
                            <td><?php echo htmlspecialchars($p['ITEM']); ?></td>
                            <td class="text-end text-muted">$ <?php echo number_format($monto_proy, 2, ',', '.'); ?></td>
                            <td>
                                <input type="hidden" name="partida_id[]" value="<?php echo $p['ID']; ?>">
                                <input type="number" class="form-control form-control-sm text-end monto-input"
                                       name="monto_aprobado[]" step="0.01" min="0"
                                       value="<?php echo number_format($monto_apro, 2, '.', ''); ?>"
                                       data-proyectado="<?php echo $monto_proy; ?>"
                                       data-idx="<?php echo $idx; ?>"
                                       onchange="calcularDiff(this)">
                            </td>
                            <td class="text-end diff-cell" id="diff_<?php echo $idx; ?>">
                                <?php
                                $diff = $monto_apro - $monto_proy;
                                $diff_class = ($diff > 0) ? 'text-success' : (($diff < 0) ? 'text-danger' : 'text-muted');
                                ?>
                                <span class="<?php echo $diff_class; ?>"><?php echo ($diff >= 0 ? '+' : '') . number_format($diff, 2, ',', '.'); ?></span>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" name="observacion[]"
                                       placeholder="Motivo del cambio" value="">
                            </td>
                        </tr>
<?php $idx++; endforeach; ?>
<?php if (empty($partidas)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No hay partidas para este presupuesto.</td></tr>
<?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Observaciones generales -->
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <label class="form-label fw-semibold">Observaciones generales de la revisión</label>
            <textarea class="form-control" name="observaciones_general" rows="3"
                      placeholder="Comentarios generales sobre esta revisión..."></textarea>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <a href="index.php?estado=301" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-pade btn-lg">
            <i class="bi bi-check-lg me-1"></i>Guardar Revisión
        </button>
    </div>
</form>

<script>
function calcularDiff(input) {
    var proyectado = parseFloat(input.dataset.proyectado) || 0;
    var nuevo = parseFloat(input.value) || 0;
    var diff = nuevo - proyectado;
    var idx = input.dataset.idx;
    var cell = document.getElementById('diff_' + idx);
    var cls = diff > 0 ? 'text-success' : (diff < 0 ? 'text-danger' : 'text-muted');
    var prefix = diff >= 0 ? '+' : '';
    cell.innerHTML = '<span class="' + cls + '">' + prefix + diff.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</span>';

    // Recalcular total
    var inputs = document.querySelectorAll('.monto-input');
    var total = 0;
    inputs.forEach(function(inp) { total += parseFloat(inp.value) || 0; });
    document.getElementById('monto_total_display').textContent = '$ ' + total.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>
SESSION['ciclo_rol_efectivo'] ?? '';
$nivel_revisor = 1;
if ($rol_usuario === 'admin' || $rol_usuario === 'director') $nivel_revisor = 3;
elseif ($rol_usuario === 'gerente') $nivel_revisor = 2;

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST — GUARDAR REVISIÓN
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Enmascarar_post('accion_post') === 'guardar') {

    $obs_general = Enmascarar_post('observaciones_general');
    $partida_ids = $_POST['partida_id'] ?? [];
    $montos_nuevos = $_POST['monto_aprobado'] ?? [];
    $observaciones = $_POST['observacion'] ?? [];

    // Registrar revisión principal
    $sql_rev = "INSERT INTO ciclo_revision (id_presupuesto, id_usuario, nivel, accion, observaciones, fecha)
                VALUES ('$pres_id', '$usu', '$nivel_revisor', 'modificar', '$obs_general', NOW());";
    sql_do_query($sql_rev, $db, $usu);

    // Obtener ID de la revisión recién creada
    $row_rev = sql_do_array(sql_do_query("SELECT MAX(id) AS MAXID FROM ciclo_revision WHERE id_presupuesto = '$pres_id';", $db, $usu));
    $revision_id = $row_rev['MAXID'];

    $cambios = 0;
    $monto_total_nuevo = 0;

    for ($i = 0; $i < count($partida_ids); $i++) {
        $pid = sani_input($partida_ids[$i]);
        $monto_nuevo = (float)($montos_nuevos[$i] ?? 0);
        $obs_partida = sani_input($observaciones[$i] ?? '');

        if ($monto_nuevo < 0) $monto_nuevo = 0;

        // Leer monto anterior
        $row_pa = sql_do_array(sql_do_query("SELECT monto_aprobado, monto_proyectado FROM ciclo_partida WHERE id = '$pid';", $db, $usu));
        $monto_anterior = (float)($row_pa['MONTO_APROBADO'] ?? $row_pa['MONTO_PROYECTADO'] ?? 0);

        $monto_total_nuevo += $monto_nuevo;

        // Si cambió, registrar detalle y validar observación
        if (abs($monto_nuevo - $monto_anterior) > 0.01) {
            if (empty($obs_partida)) {
                $obs_partida = 'Ajuste de revisión nivel ' . $nivel_revisor;
            }
            $sql_det = "INSERT INTO ciclo_revision_detalle (id_revision, id_partida, monto_anterior, monto_nuevo, motivo)
                        VALUES ('$revision_id', '$pid', '$monto_anterior', '$monto_nuevo', '$obs_partida');";
            sql_do_query($sql_det, $db, $usu);
            $cambios++;
        }

        // Actualizar partida
        sql_do_query("UPDATE ciclo_partida SET monto_aprobado = '$monto_nuevo', auditoria_usu = '$usu' WHERE id = '$pid';", $db, $usu);
    }

    // Recalcular monto total del presupuesto
    sql_do_query("UPDATE ciclo_presupuesto SET monto_total = '$monto_total_nuevo', auditoria_usu = '$usu' WHERE id = '$pres_id';", $db, $usu);

    $msg = generarAlertJS('Revisión guardada', "Se registraron $cambios cambios. Monto total actualizado.", 'success', "index.php?estado=302&id=$pres_id");
}

echo $msg;

// ── Cargar partidas ──────────────────────────────────────────────────────────
$sql_partidas = "SELECT pa.*, cg.nombre AS CATEGORIA, cg.codigo AS CAT_CODIGO, cg.orden AS CAT_ORDEN,
                        ig.codigo AS ITEM_CODIGO, ig.nombre AS ITEM
                 FROM ciclo_partida pa
                 JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
                 JOIN ciclo_categoria_gasto cg ON pa.id_categoria = cg.id
                 WHERE pa.id_presupuesto = '$pres_id'
                 ORDER BY cg.orden, ig.codigo;";
$res_partidas = sql_do_query($sql_partidas, $db, $usu);

$partidas = [];
while ($rp = sql_do_array($res_partidas)) $partidas[] = $rp;

// Badge de estado
$estado_badge = '<span class="badge bg-warning text-dark fs-6">En Revisión</span>';
?>

<?php echo generarTituloSeccion('Revisar Presupuesto', 'bi bi-pencil-square'); ?>

<!-- CABECERA -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <small class="text-muted">Período</small>
                <div class="fw-bold fs-5"><?php echo htmlspecialchars($row_pres['ANIO']); ?></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Unidad</small>
                <div class="fw-bold"><?php echo htmlspecialchars($row_pres['UNIDAD']); ?></div>
            </div>
            <div class="col-md-2">
                <small class="text-muted">Estado</small>
                <div><?php echo $estado_badge; ?></div>
            </div>
            <div class="col-md-2">
                <small class="text-muted">Monto Total</small>
                <div class="fw-bold text-primary" id="monto_total_display">$ <?php echo number_format((float)$row_pres['MONTO_TOTAL'], 2, ',', '.'); ?></div>
            </div>
            <div class="col-md-2">
                <small class="text-muted">Su nivel</small>
                <div><span class="badge bg-primary fs-6">Nivel <?php echo $nivel_revisor; ?></span></div>
            </div>
        </div>
    </div>
</div>

<div class="mb-3 d-flex gap-2">
    <a href="index.php?estado=301" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver a bandeja
    </a>
    <a href="index.php?estado=303&id=<?php echo $pres_id; ?>" class="btn btn-success btn-sm">
        <i class="bi bi-check2-all me-1"></i>Aprobar / Devolver
    </a>
    <a href="index.php?estado=304&id=<?php echo $pres_id; ?>" class="btn btn-outline-info btn-sm">
        <i class="bi bi-clock-history me-1"></i>Historial
    </a>
</div>

<!-- FORMULARIO DE REVISIÓN -->
<form method="post" action="index.php?estado=<?php echo $estado; ?>&id=<?php echo $pres_id; ?>" id="formRevision">
    <input type="hidden" name="accion_post" value="guardar">

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0"><i class="bi bi-tag me-2"></i>Partidas — Edición de Montos</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-pade mb-0" id="tablaPartidas">
                    <?php echo generarTablaHeader(['Categoría', 'Código', 'Ítem', 'Proyectado', 'Monto Aprobado', 'Diferencia', 'Observación']); ?>
                    <tbody>
<?php
$cat_anterior = '';
$idx = 0;
foreach ($partidas as $p):
    $monto_proy = (float)($p['MONTO_PROYECTADO'] ?? 0);
    $monto_apro = (float)($p['MONTO_APROBADO'] ?? $monto_proy);
    $mostrar_cat = ($p['CATEGORIA'] !== $cat_anterior);
    $cat_anterior = $p['CATEGORIA'];
?>
                        <tr>
                            <td class="fw-bold"><?php echo $mostrar_cat ? '<small class="text-muted">' . htmlspecialchars($p['CAT_CODIGO']) . '</small> ' . htmlspecialchars($p['CATEGORIA']) : ''; ?></td>
                            <td class="text-center"><small><?php echo htmlspecialchars($p['ITEM_CODIGO']); ?></small></td>
                            <td><?php echo htmlspecialchars($p['ITEM']); ?></td>
                            <td class="text-end text-muted">$ <?php echo number_format($monto_proy, 2, ',', '.'); ?></td>
                            <td>
                                <input type="hidden" name="partida_id[]" value="<?php echo $p['ID']; ?>">
                                <input type="number" class="form-control form-control-sm text-end monto-input"
                                       name="monto_aprobado[]" step="0.01" min="0"
                                       value="<?php echo number_format($monto_apro, 2, '.', ''); ?>"
                                       data-proyectado="<?php echo $monto_proy; ?>"
                                       data-idx="<?php echo $idx; ?>"
                                       onchange="calcularDiff(this)">
                            </td>
                            <td class="text-end diff-cell" id="diff_<?php echo $idx; ?>">
                                <?php
                                $diff = $monto_apro - $monto_proy;
                                $diff_class = ($diff > 0) ? 'text-success' : (($diff < 0) ? 'text-danger' : 'text-muted');
                                ?>
                                <span class="<?php echo $diff_class; ?>"><?php echo ($diff >= 0 ? '+' : '') . number_format($diff, 2, ',', '.'); ?></span>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" name="observacion[]"
                                       placeholder="Motivo del cambio" value="">
                            </td>
                        </tr>
<?php $idx++; endforeach; ?>
<?php if (empty($partidas)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No hay partidas para este presupuesto.</td></tr>
<?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Observaciones generales -->
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <label class="form-label fw-semibold">Observaciones generales de la revisión</label>
            <textarea class="form-control" name="observaciones_general" rows="3"
                      placeholder="Comentarios generales sobre esta revisión..."></textarea>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <a href="index.php?estado=301" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-pade btn-lg">
            <i class="bi bi-check-lg me-1"></i>Guardar Revisión
        </button>
    </div>
</form>

<script>
function calcularDiff(input) {
    var proyectado = parseFloat(input.dataset.proyectado) || 0;
    var nuevo = parseFloat(input.value) || 0;
    var diff = nuevo - proyectado;
    var idx = input.dataset.idx;
    var cell = document.getElementById('diff_' + idx);
    var cls = diff > 0 ? 'text-success' : (diff < 0 ? 'text-danger' : 'text-muted');
    var prefix = diff >= 0 ? '+' : '';
    cell.innerHTML = '<span class="' + cls + '">' + prefix + diff.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</span>';

    // Recalcular total
    var inputs = document.querySelectorAll('.monto-input');
    var total = 0;
    inputs.forEach(function(inp) { total += parseFloat(inp.value) || 0; });
    document.getElementById('monto_total_display').textContent = '$ ' + total.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>
SESSION['ciclo_rol_efectivo'] ?? '';
$nivel_revisor = 1;
if ($rol_usuario === 'admin' || $rol_usuario === 'director') $nivel_revisor = 3;
elseif ($rol_usuario === 'gerente') $nivel_revisor = 2;

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST — GUARDAR REVISIÓN
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Enmascarar_post('accion_post') === 'guardar') {

    $obs_general = Enmascarar_post('observaciones_general');
    $partida_ids = $_POST['partida_id'] ?? [];
    $montos_nuevos = $_POST['monto_aprobado'] ?? [];
    $observaciones = $_POST['observacion'] ?? [];

    // Registrar revisión principal
    $sql_rev = "INSERT INTO ciclo_revision (id_presupuesto, id_usuario, nivel, accion, observaciones, fecha)
                VALUES ('$pres_id', '$usu', '$nivel_revisor', 'modificar', '$obs_general', NOW());";
    sql_do_query($sql_rev, $db, $usu);

    // Obtener ID de la revisión recién creada
    $row_rev = sql_do_array(sql_do_query("SELECT MAX(id) AS MAXID FROM ciclo_revision WHERE id_presupuesto = '$pres_id';", $db, $usu));
    $revision_id = $row_rev['MAXID'];

    $cambios = 0;
    $monto_total_nuevo = 0;

    for ($i = 0; $i < count($partida_ids); $i++) {
        $pid = sani_input($partida_ids[$i]);
        $monto_nuevo = (float)($montos_nuevos[$i] ?? 0);
        $obs_partida = sani_input($observaciones[$i] ?? '');

        if ($monto_nuevo < 0) $monto_nuevo = 0;

        // Leer monto anterior
        $row_pa = sql_do_array(sql_do_query("SELECT monto_aprobado, monto_proyectado FROM ciclo_partida WHERE id = '$pid';", $db, $usu));
        $monto_anterior = (float)($row_pa['MONTO_APROBADO'] ?? $row_pa['MONTO_PROYECTADO'] ?? 0);

        $monto_total_nuevo += $monto_nuevo;

        // Si cambió, registrar detalle y validar observación
        if (abs($monto_nuevo - $monto_anterior) > 0.01) {
            if (empty($obs_partida)) {
                $obs_partida = 'Ajuste de revisión nivel ' . $nivel_revisor;
            }
            $sql_det = "INSERT INTO ciclo_revision_detalle (id_revision, id_partida, monto_anterior, monto_nuevo, motivo)
                        VALUES ('$revision_id', '$pid', '$monto_anterior', '$monto_nuevo', '$obs_partida');";
            sql_do_query($sql_det, $db, $usu);
            $cambios++;
        }

        // Actualizar partida
        sql_do_query("UPDATE ciclo_partida SET monto_aprobado = '$monto_nuevo', auditoria_usu = '$usu' WHERE id = '$pid';", $db, $usu);
    }

    // Recalcular monto total del presupuesto
    sql_do_query("UPDATE ciclo_presupuesto SET monto_total = '$monto_total_nuevo', auditoria_usu = '$usu' WHERE id = '$pres_id';", $db, $usu);

    $msg = generarAlertJS('Revisión guardada', "Se registraron $cambios cambios. Monto total actualizado.", 'success', "index.php?estado=302&id=$pres_id");
}

echo $msg;

// ── Cargar partidas ──────────────────────────────────────────────────────────
$sql_partidas = "SELECT pa.*, cg.nombre AS CATEGORIA, cg.codigo AS CAT_CODIGO, cg.orden AS CAT_ORDEN,
                        ig.codigo AS ITEM_CODIGO, ig.nombre AS ITEM
                 FROM ciclo_partida pa
                 JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
                 JOIN ciclo_categoria_gasto cg ON pa.id_categoria = cg.id
                 WHERE pa.id_presupuesto = '$pres_id'
                 ORDER BY cg.orden, ig.codigo;";
$res_partidas = sql_do_query($sql_partidas, $db, $usu);

$partidas = [];
while ($rp = sql_do_array($res_partidas)) $partidas[] = $rp;

// Badge de estado
$estado_badge = '<span class="badge bg-warning text-dark fs-6">En Revisión</span>';
?>

<?php echo generarTituloSeccion('Revisar Presupuesto', 'bi bi-pencil-square'); ?>

<!-- CABECERA -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <small class="text-muted">Período</small>
                <div class="fw-bold fs-5"><?php echo htmlspecialchars($row_pres['ANIO']); ?></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Unidad</small>
                <div class="fw-bold"><?php echo htmlspecialchars($row_pres['UNIDAD']); ?></div>
            </div>
            <div class="col-md-2">
                <small class="text-muted">Estado</small>
                <div><?php echo $estado_badge; ?></div>
            </div>
            <div class="col-md-2">
                <small class="text-muted">Monto Total</small>
                <div class="fw-bold text-primary" id="monto_total_display">$ <?php echo number_format((float)$row_pres['MONTO_TOTAL'], 2, ',', '.'); ?></div>
            </div>
            <div class="col-md-2">
                <small class="text-muted">Su nivel</small>
                <div><span class="badge bg-primary fs-6">Nivel <?php echo $nivel_revisor; ?></span></div>
            </div>
        </div>
    </div>
</div>

<div class="mb-3 d-flex gap-2">
    <a href="index.php?estado=301" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver a bandeja
    </a>
    <a href="index.php?estado=303&id=<?php echo $pres_id; ?>" class="btn btn-success btn-sm">
        <i class="bi bi-check2-all me-1"></i>Aprobar / Devolver
    </a>
    <a href="index.php?estado=304&id=<?php echo $pres_id; ?>" class="btn btn-outline-info btn-sm">
        <i class="bi bi-clock-history me-1"></i>Historial
    </a>
</div>

<!-- FORMULARIO DE REVISIÓN -->
<form method="post" action="index.php?estado=<?php echo $estado; ?>&id=<?php echo $pres_id; ?>" id="formRevision">
    <input type="hidden" name="accion_post" value="guardar">

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0"><i class="bi bi-tag me-2"></i>Partidas — Edición de Montos</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-pade mb-0" id="tablaPartidas">
                    <?php echo generarTablaHeader(['Categoría', 'Código', 'Ítem', 'Proyectado', 'Monto Aprobado', 'Diferencia', 'Observación']); ?>
                    <tbody>
<?php
$cat_anterior = '';
$idx = 0;
foreach ($partidas as $p):
    $monto_proy = (float)($p['MONTO_PROYECTADO'] ?? 0);
    $monto_apro = (float)($p['MONTO_APROBADO'] ?? $monto_proy);
    $mostrar_cat = ($p['CATEGORIA'] !== $cat_anterior);
    $cat_anterior = $p['CATEGORIA'];
?>
                        <tr>
                            <td class="fw-bold"><?php echo $mostrar_cat ? '<small class="text-muted">' . htmlspecialchars($p['CAT_CODIGO']) . '</small> ' . htmlspecialchars($p['CATEGORIA']) : ''; ?></td>
                            <td class="text-center"><small><?php echo htmlspecialchars($p['ITEM_CODIGO']); ?></small></td>
                            <td><?php echo htmlspecialchars($p['ITEM']); ?></td>
                            <td class="text-end text-muted">$ <?php echo number_format($monto_proy, 2, ',', '.'); ?></td>
                            <td>
                                <input type="hidden" name="partida_id[]" value="<?php echo $p['ID']; ?>">
                                <input type="number" class="form-control form-control-sm text-end monto-input"
                                       name="monto_aprobado[]" step="0.01" min="0"
                                       value="<?php echo number_format($monto_apro, 2, '.', ''); ?>"
                                       data-proyectado="<?php echo $monto_proy; ?>"
                                       data-idx="<?php echo $idx; ?>"
                                       onchange="calcularDiff(this)">
                            </td>
                            <td class="text-end diff-cell" id="diff_<?php echo $idx; ?>">
                                <?php
                                $diff = $monto_apro - $monto_proy;
                                $diff_class = ($diff > 0) ? 'text-success' : (($diff < 0) ? 'text-danger' : 'text-muted');
                                ?>
                                <span class="<?php echo $diff_class; ?>"><?php echo ($diff >= 0 ? '+' : '') . number_format($diff, 2, ',', '.'); ?></span>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" name="observacion[]"
                                       placeholder="Motivo del cambio" value="">
                            </td>
                        </tr>
<?php $idx++; endforeach; ?>
<?php if (empty($partidas)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No hay partidas para este presupuesto.</td></tr>
<?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Observaciones generales -->
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <label class="form-label fw-semibold">Observaciones generales de la revisión</label>
            <textarea class="form-control" name="observaciones_general" rows="3"
                      placeholder="Comentarios generales sobre esta revisión..."></textarea>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <a href="index.php?estado=301" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-pade btn-lg">
            <i class="bi bi-check-lg me-1"></i>Guardar Revisión
        </button>
    </div>
</form>

<script>
function calcularDiff(input) {
    var proyectado = parseFloat(input.dataset.proyectado) || 0;
    var nuevo = parseFloat(input.value) || 0;
    var diff = nuevo - proyectado;
    var idx = input.dataset.idx;
    var cell = document.getElementById('diff_' + idx);
    var cls = diff > 0 ? 'text-success' : (diff < 0 ? 'text-danger' : 'text-muted');
    var prefix = diff >= 0 ? '+' : '';
    cell.innerHTML = '<span class="' + cls + '">' + prefix + diff.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</span>';

    // Recalcular total
    var inputs = document.querySelectorAll('.monto-input');
    var total = 0;
    inputs.forEach(function(inp) { total += parseFloat(inp.value) || 0; });
    document.getElementById('monto_total_display').textContent = '$ ' + total.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>
