<?php
/**
 * ============================================================================
 * Crear Solicitud de Recurso
 * ============================================================================
 * ID menú: 401 | Tablas: ciclo_solicitud_recurso, ciclo_solicitud_partida, ciclo_partida
 * Formulario de 3 pasos: seleccionar ítems → asignar montos → especificaciones.
 * Valida saldo disponible y genera alertas de umbral post-confirmación.
 * ============================================================================
 */

$db  = '1';
$msg = '';

$unidad_usuario = $_SESSION['id_unidad'] ?? '';
$paso = Enmascarar_post('paso');
if (empty($paso)) $paso = Enmascarar_get('paso');
if (empty($paso)) $paso = '1';

// ── Obtener período abierto ──────────────────────────────────────────────────
$row_periodo = sql_do_array(sql_do_query("SELECT * FROM ciclo_periodo_fiscal WHERE estado = 'abierto' LIMIT 1;", $db, $usu));
if (!$row_periodo) {
    echo generarAlertJS('Error', 'No hay un período fiscal abierto.', 'error', "index.php?estado=1");
    return;
}
$periodo_id = $row_periodo['ID'];
$anio = $row_periodo['ANIO'];

// ── Obtener presupuesto aprobado de la unidad ────────────────────────────────
$sql_pres = "SELECT id FROM ciclo_presupuesto
             WHERE id_periodo = '$periodo_id' AND id_unidad = '$unidad_usuario' AND estado = 'aprobado' LIMIT 1;";
$row_pr = sql_do_array(sql_do_query($sql_pres, $db, $usu));
if (!$row_pr) {
    echo generarAlertJS('Atención', 'No existe un presupuesto aprobado para su unidad en el período actual.', 'warning', "index.php?estado=1");
    return;
}
$presupuesto_id = $row_pr['ID'];

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST — CONFIRMAR (PASO FINAL)
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Enmascarar_post('accion_post') === 'confirmar') {

    $descripcion      = Enmascarar_post('descripcion');
    $especificaciones = Enmascarar_post('especificaciones');
    $justificacion    = Enmascarar_post('justificacion');
    $partida_ids      = $_POST['partida_id'] ?? [];
    $montos           = $_POST['monto'] ?? [];

    if (empty($especificaciones) || empty($justificacion)) {
        $msg = generarAlertJS('Error', 'Las especificaciones y la justificación son obligatorias.', 'error');
    } elseif (empty($partida_ids)) {
        $msg = generarAlertJS('Error', 'Debe seleccionar al menos un ítem.', 'error');
    } else {
        $monto_total = 0;
        $lineas_ok = true;

        // Validar cada línea contra saldo
        for ($i = 0; $i < count($partida_ids); $i++) {
            $pid = sani_input($partida_ids[$i]);
            $mto = (float)($montos[$i] ?? 0);
            if ($mto <= 0) continue;

            $row_saldo = sql_do_array(sql_do_query(
                "SELECT saldo_disponible FROM v_saldo_partida WHERE partida_id = '$pid';", $db, $usu));
            $disponible = (float)($row_saldo['SALDO_DISPONIBLE'] ?? 0);
            if ($mto > $disponible) {
                $msg = generarAlertJS('Error', "El monto ($mto) supera el saldo disponible ($disponible) de una partida.", 'error');
                $lineas_ok = false;
                break;
            }
            $monto_total += $mto;
        }

        if ($monto_total <= 0) {
            $msg = generarAlertJS('Error', 'El monto total debe ser mayor a 0.', 'error');
            $lineas_ok = false;
        }

        if ($lineas_ok && empty($msg)) {
            // Crear solicitud
            $sql_sol = "INSERT INTO ciclo_solicitud_recurso
                          (id_periodo, id_unidad, id_usuario_solicita, descripcion,
                           especificaciones, justificacion, monto_total, estado, fecha_solicitud, auditoria_usu)
                        VALUES ('$periodo_id', '$unidad_usuario', '$usu', '$descripcion',
                                '$especificaciones', '$justificacion', '$monto_total', 'pendiente', NOW(), '$usu');";
            sql_do_query($sql_sol, $db, $usu);

            $row_sol = sql_do_array(sql_do_query(
                "SELECT MAX(id) AS MAXID FROM ciclo_solicitud_recurso WHERE id_usuario_solicita = '$usu';", $db, $usu));
            $solicitud_id = $row_sol['MAXID'];

            // Crear líneas y comprometer montos
            for ($i = 0; $i < count($partida_ids); $i++) {
                $pid = sani_input($partida_ids[$i]);
                $mto = (float)($montos[$i] ?? 0);
                if ($mto <= 0) continue;

                $desc_linea = sani_input($_POST['desc_linea'][$i] ?? '');

                $sql_lin = "INSERT INTO ciclo_solicitud_partida (id_solicitud, id_partida, monto, descripcion_linea)
                            VALUES ('$solicitud_id', '$pid', '$mto', '$desc_linea');";
                sql_do_query($sql_lin, $db, $usu);

                // Comprometer monto
                sql_do_query("UPDATE ciclo_partida SET monto_comprometido = monto_comprometido + $mto WHERE id = '$pid';", $db, $usu);

                // Verificar umbrales
                $row_pct = sql_do_array(sql_do_query(
                    "SELECT porcentaje_consumo FROM v_saldo_partida WHERE partida_id = '$pid';", $db, $usu));
                $pct = (float)($row_pct['PORCENTAJE_CONSUMO'] ?? 0);

                $res_umb = sql_do_query("SELECT * FROM ciclo_config_umbral WHERE activo = 1 ORDER BY porcentaje;", $db, $usu);
                while ($ru = sql_do_array($res_umb)) {
                    if ($pct >= (float)$ru['PORCENTAJE']) {
                        // Insertar alerta si no existe
                        $umb_id = $ru['ID'];
                        $sql_al_check = "SELECT id FROM ciclo_alerta_consumo
                                         WHERE id_partida = '$pid' AND id_umbral = '$umb_id' AND resuelta = 0;";
                        if (!sql_do_array(sql_do_query($sql_al_check, $db, $usu))) {
                            sql_do_query("INSERT INTO ciclo_alerta_consumo (id_partida, id_umbral, porcentaje_actual, fecha, resuelta)
                                          VALUES ('$pid', '$umb_id', '$pct', NOW(), 0);", $db, $usu);
                        }
                    }
                }
            }

            $msg = generarAlertJS('Solicitud creada', "Solicitud #$solicitud_id creada por \$ " . number_format($monto_total, 2, ',', '.'), 'success', "index.php?estado=402");
        }
    }
}

echo $msg;

// ═══════════════════════════════════════════════════════════════════════════════
// WIZARD DE PASOS
// ═══════════════════════════════════════════════════════════════════════════════
$etiquetas = ['Seleccionar Ítems', 'Asignar Montos', 'Especificaciones'];
echo generarTituloSeccion('Nueva Solicitud de Recurso — Período ' . $anio, 'bi bi-cart-plus');
echo generarWizardPasos((int)$paso, 3, $etiquetas);

// ── PASO 1: Selección de categoría e ítems ───────────────────────────────────
if ($paso === '1'):
    $sql_cats = "SELECT DISTINCT cg.id AS ID, CONCAT(cg.codigo, ' - ', cg.nombre) AS NOMBRE
                 FROM v_saldo_partida vsp
                 JOIN ciclo_categoria_gasto cg ON vsp.cat_codigo = cg.codigo
                 WHERE vsp.presupuesto_id = '$presupuesto_id' AND vsp.saldo_disponible > 0
                 ORDER BY cg.orden;";
?>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h6 class="mb-0"><i class="bi bi-1-circle me-2"></i>Seleccionar Categoría e Ítems</h6>
    </div>
    <div class="card-body">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <input type="hidden" name="paso" value="2">

            <div class="mb-3">
                <label class="form-label fw-semibold">Categoría de Gasto</label>
                <select class="form-select" name="cat_sel" id="cat_sel" required onchange="cargarItems()">
                    <option value="">Seleccione una categoría</option>
                    <?php
                    $res_cats = sql_do_query($sql_cats, $db, $usu);
                    while ($rc = sql_do_array($res_cats)):
                    ?>
                    <option value="<?php echo $rc['ID']; ?>"><?php echo htmlspecialchars($rc['NOMBRE']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div id="items_container" class="mb-3" style="display:none;">
                <label class="form-label fw-semibold">Ítems con saldo disponible</label>
                <div class="table-responsive">
                    <table class="table table-hover table-pade" id="tabla_items">
                        <thead class="table-primary">
                            <tr>
                                <th class="text-center"><input type="checkbox" id="check_all" onclick="toggleAll(this)"></th>
                                <th>Código</th>
                                <th>Ítem</th>
                                <th class="text-end">Disponible</th>
                            </tr>
                        </thead>
                        <tbody id="items_body"></tbody>
                    </table>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-pade">
                    Siguiente <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function cargarItems() {
    var catId = document.getElementById('cat_sel').value;
    if (!catId) { document.getElementById('items_container').style.display = 'none'; return; }

    // Cargar ítems via AJAX simulado con form submit — en producción usar fetch
    // Por ahora recargamos con los datos del servidor
    var body = document.getElementById('items_body');
    body.innerHTML = '<tr><td colspan="4" class="text-center">Seleccione la categoría y presione Siguiente para ver ítems...</td></tr>';
    document.getElementById('items_container').style.display = '';
}
function toggleAll(src) {
    var checks = document.querySelectorAll('.item-check');
    checks.forEach(function(c) { c.checked = src.checked; });
}
</script>

<?php
// ── PASO 2: Asignar montos ───────────────────────────────────────────────────
elseif ($paso === '2'):
    $cat_sel = Enmascarar_post('cat_sel');
    if (empty($cat_sel)) $cat_sel = Enmascarar_get('cat_sel');

    // Cargar ítems con saldo para la categoría
    $sql_items = "SELECT vsp.*, ig.id AS ITEM_ID
                  FROM v_saldo_partida vsp
                  JOIN ciclo_item_gasto ig ON vsp.item_codigo = ig.codigo
                  WHERE vsp.presupuesto_id = '$presupuesto_id'
                    AND vsp.cat_codigo = (SELECT codigo FROM ciclo_categoria_gasto WHERE id = '$cat_sel')
                    AND vsp.saldo_disponible > 0
                  ORDER BY vsp.item_codigo;";
    $res_items = sql_do_query($sql_items, $db, $usu);
    $items = [];
    while ($ri = sql_do_array($res_items)) $items[] = $ri;
?>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h6 class="mb-0"><i class="bi bi-2-circle me-2"></i>Asignar Montos por Ítem</h6>
    </div>
    <div class="card-body">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <input type="hidden" name="paso" value="3">
            <input type="hidden" name="cat_sel" value="<?php echo $cat_sel; ?>">

            <?php if (empty($items)): ?>
                <div class="alert alert-info">No hay ítems con saldo disponible en esta categoría.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-pade">
                    <thead class="table-primary">
                        <tr>
                            <th>Código</th>
                            <th>Ítem</th>
                            <th class="text-end">Disponible</th>
                            <th>Monto Solicitado</th>
                            <th>Descripción línea</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td class="fw-bold"><?php echo htmlspecialchars($it['ITEM_CODIGO']); ?></td>
                            <td><?php echo htmlspecialchars($it['ITEM_NOMBRE'] ?? ''); ?></td>
                            <td class="text-end text-success fw-bold">$ <?php echo number_format((float)$it['SALDO_DISPONIBLE'], 2, ',', '.'); ?></td>
                            <td>
                                <input type="hidden" name="partida_id[]" value="<?php echo $it['PARTIDA_ID']; ?>">
                                <input type="number" class="form-control form-control-sm text-end monto-sol"
                                       name="monto[]" step="0.01" min="0"
                                       max="<?php echo (float)$it['SALDO_DISPONIBLE']; ?>"
                                       value="0" onchange="validarMonto(this)">
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" name="desc_linea[]"
                                       placeholder="Detalle opcional">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-dark fw-bold">
                            <td colspan="3" class="text-end">Total solicitado:</td>
                            <td class="text-end" id="total_sol">$ 0,00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between">
                <a href="index.php?estado=<?php echo $estado; ?>&paso=1" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Anterior
                </a>
                <button type="submit" class="btn btn-pade" <?php echo empty($items) ? 'disabled' : ''; ?>>
                    Siguiente <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function validarMonto(input) {
    var max = parseFloat(input.max) || 0;
    var val = parseFloat(input.value) || 0;
    if (val > max) { input.value = max; }
    if (val < 0) { input.value = 0; }
    recalcularTotal();
}
function recalcularTotal() {
    var inputs = document.querySelectorAll('.monto-sol');
    var total = 0;
    inputs.forEach(function(inp) { total += parseFloat(inp.value) || 0; });
    document.getElementById('total_sol').textContent = '$ ' + total.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>

<?php
// ── PASO 3: Especificaciones y confirmación ──────────────────────────────────
elseif ($paso === '3'):
    $partida_ids = $_POST['partida_id'] ?? [];
    $montos      = $_POST['monto'] ?? [];
    $desc_lineas = $_POST['desc_linea'] ?? [];

    // Filtrar líneas con monto > 0
    $lineas = [];
    $total = 0;
    for ($i = 0; $i < count($partida_ids); $i++) {
        $mto = (float)($montos[$i] ?? 0);
        if ($mto > 0) {
            $row_it = sql_do_array(sql_do_query(
                "SELECT ig.codigo AS ITEM_CODIGO, ig.nombre AS ITEM_NOMBRE
                 FROM ciclo_partida pa JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
                 WHERE pa.id = '" . sani_input($partida_ids[$i]) . "';", $db, $usu));
            $lineas[] = [
                'partida_id' => $partida_ids[$i],
                'item_codigo' => $row_it['ITEM_CODIGO'] ?? '',
                'item_nombre' => $row_it['ITEM_NOMBRE'] ?? '',
                'monto' => $mto,
                'desc_linea' => $desc_lineas[$i] ?? '',
            ];
            $total += $mto;
        }
    }

    if (empty($lineas)) {
        echo generarAlertJS('Error', 'Debe asignar monto a al menos un ítem.', 'error');
    }
?>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h6 class="mb-0"><i class="bi bi-3-circle me-2"></i>Especificaciones y Confirmación</h6>
    </div>
    <div class="card-body">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <input type="hidden" name="accion_post" value="confirmar">
            <?php foreach ($lineas as $l): ?>
                <input type="hidden" name="partida_id[]" value="<?php echo $l['partida_id']; ?>">
                <input type="hidden" name="monto[]" value="<?php echo $l['monto']; ?>">
                <input type="hidden" name="desc_linea[]" value="<?php echo htmlspecialchars($l['desc_linea']); ?>">
            <?php endforeach; ?>

            <!-- Resumen de líneas -->
            <h6 class="fw-semibold mb-2">Resumen de ítems</h6>
            <div class="table-responsive mb-4">
                <table class="table table-sm table-pade">
                    <thead class="table-light">
                        <tr><th>Código</th><th>Ítem</th><th class="text-end">Monto</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($lineas as $l): ?>
                        <tr>
                            <td class="fw-bold"><?php echo htmlspecialchars($l['item_codigo']); ?></td>
                            <td><?php echo htmlspecialchars($l['item_nombre']); ?></td>
                            <td class="text-end">$ <?php echo number_format($l['monto'], 2, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                        <tr class="table-dark fw-bold">
                            <td colspan="2" class="text-end">TOTAL</td>
                            <td class="text-end">$ <?php echo number_format($total, 2, ',', '.'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Campos de texto -->
            <div class="row g-3 mb-4">
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Descripción general</label>
                    <input type="text" class="form-control" name="descripcion"
                           placeholder="Breve descripción de la solicitud">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Especificaciones técnicas <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="especificaciones" rows="4" required
                              placeholder="Detalle técnico del recurso solicitado..."></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Justificación <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="justificacion" rows="4" required
                              placeholder="¿Por qué se necesita este recurso?"></textarea>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="index.php?estado=<?php echo $estado; ?>&paso=1" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Volver al inicio
                </a>
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="bi bi-check2-all me-1"></i>Confirmar Solicitud
                </button>
            </div>
        </form>
    </div>
</div>

<?php endif; ?>
