<?php
/**
 * ============================================================================
 * Reporte: Alertas de Consumo
 * ============================================================================
 * ID menú: 504 | Tablas: ciclo_alerta_consumo, ciclo_partida, ciclo_presupuesto
 * Panel de alertas generadas al superar umbrales configurados.
 * Permite marcar como leídas individualmente o todas a la vez.
 * ============================================================================
 */

$db = '1';
$msg = '';

$unidad_usuario = $_SESSION['id_unidad'] ?? '';
$rol_usuario    = <?php
/**
 * ============================================================================
 * Reporte: Alertas de Consumo
 * ============================================================================
 * ID menú: 504 | Tablas: ciclo_alerta_consumo, ciclo_partida, ciclo_presupuesto
 * Panel de alertas generadas al superar umbrales configurados.
 * Permite marcar como leídas individualmente o todas a la vez.
 * ============================================================================
 */

$db = '1';
$msg = '';

$unidad_usuario = $_SESSION['id_unidad'] ?? '';
$rol_usuario    = $_SESSION['rol'] ?? '';

// ── Filtros ──────────────────────────────────────────────────────────────────
$filtro_leida = Enmascarar_get('leida');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filtro_leida'])) {
    $filtro_leida = Enmascarar_post('filtro_leida');
}

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion_post = Enmascarar_post('accion_post');

    // ── Marcar una como leída ────────────────────────────────────────────────
    if ($accion_post === 'marcar_leida') {
        $alerta_id = Enmascarar_post('alerta_id');
        sql_do_query("UPDATE ciclo_alerta_consumo SET resuelta = 1 WHERE id = '$alerta_id';", $db, $usu);
        $msg = generarAlertJS('Marcada', 'La alerta fue marcada como leída.', 'success', "index.php?estado=$estado");
    }

    // ── Marcar todas como leídas ─────────────────────────────────────────────
    if ($accion_post === 'marcar_todas') {
        $where_u = '';
        if (in_array($rol_usuario, ['operador', 'jefe_unidad']) && !empty($unidad_usuario)) {
            $where_u = "AND pa.id_presupuesto IN (SELECT id FROM ciclo_presupuesto WHERE id_unidad = '$unidad_usuario')";
        } elseif ($rol_usuario === 'gerente' && !empty($unidad_usuario)) {
            $where_u = "AND pa.id_presupuesto IN (SELECT id FROM ciclo_presupuesto WHERE id_unidad = '$unidad_usuario'
                         OR id_unidad IN (SELECT id FROM ciclo_unidad_organizativa WHERE id_padre = '$unidad_usuario'))";
        }

        $sql_upd = "UPDATE ciclo_alerta_consumo ac
                    JOIN ciclo_partida pa ON ac.id_partida = pa.id
                    SET ac.resuelta = 1
                    WHERE ac.resuelta = 0 $where_u;";
        sql_do_query($sql_upd, $db, $usu);
        $msg = generarAlertJS('Listo', 'Todas las alertas fueron marcadas como leídas.', 'success', "index.php?estado=$estado");
    }
}

echo $msg;

// ── Restricción por rol ──────────────────────────────────────────────────────
$where_rol = '';
if (in_array($rol_usuario, ['operador', 'jefe_unidad']) && !empty($unidad_usuario)) {
    $where_rol = "AND pr.id_unidad = '$unidad_usuario'";
} elseif ($rol_usuario === 'gerente' && !empty($unidad_usuario)) {
    $where_rol = "AND (pr.id_unidad = '$unidad_usuario'
                  OR pr.id_unidad IN (SELECT id FROM ciclo_unidad_organizativa WHERE id_padre = '$unidad_usuario'))";
}

$where_leida = '';
if ($filtro_leida === '0') $where_leida = "AND ac.resuelta = 0";
elseif ($filtro_leida === '1') $where_leida = "AND ac.resuelta = 1";

// ── Consulta principal ───────────────────────────────────────────────────────
$sql_list = "SELECT ac.*, pa.id AS PARTIDA_ID,
                    cg.nombre AS CATEGORIA, cg.codigo AS CAT_CODIGO,
                    ig.nombre AS ITEM, ig.codigo AS ITEM_CODIGO,
                    cu.porcentaje AS UMBRAL_PCT,
                    uo.nombre AS UNIDAD
             FROM ciclo_alerta_consumo ac
             JOIN ciclo_partida pa ON ac.id_partida = pa.id
             JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
             JOIN ciclo_categoria_gasto cg ON pa.id_categoria = cg.id
             JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
             JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
             LEFT JOIN ciclo_config_umbral cu ON ac.id_umbral = cu.id
             WHERE 1=1 $where_rol $where_leida
             ORDER BY ac.fecha DESC;";
$result_list = sql_do_query($sql_list, $db, $usu);

// Conteo no leídas
$sql_count = "SELECT COUNT(*) AS TOTAL FROM ciclo_alerta_consumo ac
              JOIN ciclo_partida pa ON ac.id_partida = pa.id
              JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
              WHERE ac.resuelta = 0 $where_rol;";
$row_count = sql_do_array(sql_do_query($sql_count, $db, $usu));
$total_no_leidas = (int)($row_count['TOTAL'] ?? 0);
?>

<?php echo generarTituloSeccion('Alertas de Consumo', 'bi bi-bell'); ?>

<!-- INDICADOR -->
<?php if ($total_no_leidas > 0): ?>
<div class="alert alert-warning d-flex align-items-center mb-3">
    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
    <div>
        Tiene <strong><?php echo $total_no_leidas; ?></strong> alerta<?php echo ($total_no_leidas > 1) ? 's' : ''; ?> sin leer.
    </div>
</div>
<?php endif; ?>

<!-- FILTROS -->
<div class="row mb-3 align-items-end">
    <div class="col-md-3">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <label class="form-label small fw-semibold mb-1">Estado</label>
            <select class="form-select" name="filtro_leida" onchange="this.form.submit()">
                <option value="null">Todas</option>
                <option value="0" <?php echo ($filtro_leida === '0') ? 'selected' : ''; ?>>No leídas</option>
                <option value="1" <?php echo ($filtro_leida === '1') ? 'selected' : ''; ?>>Leídas</option>
            </select>
        </form>
    </div>
    <?php if ($total_no_leidas > 0): ?>
    <div class="col-md-3">
        <?php echo generarBotonForm(
            '<i class="bi bi-check-all me-1"></i>Marcar todas como leídas',
            "index.php?estado=$estado",
            ['accion_post' => 'marcar_todas'],
            'btn btn-outline-secondary btn-sm'
        ); ?>
    </div>
    <?php endif; ?>
</div>

<!-- TABLA -->
<div class="table-responsive">
    <table class="table table-hover table-pade">
        <?php echo generarTablaHeader(['Fecha', 'Unidad', 'Categoría / Ítem', '% Consumo', 'Umbral', 'Estado', 'Acciones']); ?>
        <tbody>
<?php
$n = 0;
while ($row = sql_do_array($result_list)):
    $n++;
    $pct = (float)($row['PORCENTAJE_ACTUAL'] ?? 0);
    $umbral_pct = $row['UMBRAL_PCT'] ? number_format((float)$row['UMBRAL_PCT'], 0) . '%' : 'N/A';
    $es_leida = ($row['RESUELTA'] == 1);

    // Color del % consumo
    $pct_class = 'text-success';
    if ($pct >= 100)     $pct_class = 'text-danger fw-bold';
    elseif ($pct >= 95)  $pct_class = 'text-danger';
    elseif ($pct >= 80)  $pct_class = 'text-warning fw-bold';

    $row_class = $es_leida ? '' : 'table-warning';
?>
            <tr class="<?php echo $row_class; ?>">
                <td class="text-center small"><?php echo enmascararfecha($row['FECHA'] ?? ''); ?></td>
                <td class="small"><?php echo htmlspecialchars($row['UNIDAD'] ?? ''); ?></td>
                <td>
                    <small class="text-muted"><?php echo htmlspecialchars($row['CAT_CODIGO'] ?? ''); ?></small>
                    <?php echo htmlspecialchars($row['CATEGORIA'] ?? ''); ?>
                    <br>
                    <small><i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($row['ITEM_CODIGO'] . ' - ' . $row['ITEM']); ?></small>
                </td>
                <td class="text-center">
                    <span class="<?php echo $pct_class; ?> fs-6"><?php echo number_format($pct, 1); ?>%</span>
                </td>
                <td class="text-center"><span class="badge bg-dark"><?php echo $umbral_pct; ?></span></td>
                <td class="text-center">
                    <?php if ($es_leida): ?>
                        <span class="badge bg-secondary"><i class="bi bi-check me-1"></i>Leída</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-circle me-1"></i>Nueva</span>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <?php if (!$es_leida): ?>
                        <?php echo generarBotonForm(
                            '<i class="bi bi-check"></i>',
                            "index.php?estado=$estado",
                            ['accion_post' => 'marcar_leida', 'alerta_id' => $row['ID']],
                            'btn btn-outline-success btn-sm'
                        ); ?>
                    <?php endif; ?>
                </td>
            </tr>
<?php endwhile; ?>
<?php if ($n === 0): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">
                <i class="bi bi-check-circle me-2"></i>No hay alertas<?php echo ($filtro_leida === '0') ? ' sin leer' : ''; ?>.
            </td></tr>
<?php endif; ?>
        </tbody>
    </table>
</div>
SESSION['ciclo_rol_efectivo'] ?? '';

// ── Filtros ──────────────────────────────────────────────────────────────────
$filtro_leida = Enmascarar_get('leida');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filtro_leida'])) {
    $filtro_leida = Enmascarar_post('filtro_leida');
}

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion_post = Enmascarar_post('accion_post');

    // ── Marcar una como leída ────────────────────────────────────────────────
    if ($accion_post === 'marcar_leida') {
        $alerta_id = Enmascarar_post('alerta_id');
        sql_do_query("UPDATE ciclo_alerta_consumo SET resuelta = 1 WHERE id = '$alerta_id';", $db, $usu);
        $msg = generarAlertJS('Marcada', 'La alerta fue marcada como leída.', 'success', "index.php?estado=$estado");
    }

    // ── Marcar todas como leídas ─────────────────────────────────────────────
    if ($accion_post === 'marcar_todas') {
        $where_u = '';
        if (in_array($rol_usuario, ['operador', 'jefe_unidad']) && !empty($unidad_usuario)) {
            $where_u = "AND pa.id_presupuesto IN (SELECT id FROM ciclo_presupuesto WHERE id_unidad = '$unidad_usuario')";
        } elseif ($rol_usuario === 'gerente' && !empty($unidad_usuario)) {
            $where_u = "AND pa.id_presupuesto IN (SELECT id FROM ciclo_presupuesto WHERE id_unidad = '$unidad_usuario'
                         OR id_unidad IN (SELECT id FROM ciclo_unidad_organizativa WHERE id_padre = '$unidad_usuario'))";
        }

        $sql_upd = "UPDATE ciclo_alerta_consumo ac
                    JOIN ciclo_partida pa ON ac.id_partida = pa.id
                    SET ac.resuelta = 1
                    WHERE ac.resuelta = 0 $where_u;";
        sql_do_query($sql_upd, $db, $usu);
        $msg = generarAlertJS('Listo', 'Todas las alertas fueron marcadas como leídas.', 'success', "index.php?estado=$estado");
    }
}

echo $msg;

// ── Restricción por rol ──────────────────────────────────────────────────────
$where_rol = '';
if (in_array($rol_usuario, ['operador', 'jefe_unidad']) && !empty($unidad_usuario)) {
    $where_rol = "AND pr.id_unidad = '$unidad_usuario'";
} elseif ($rol_usuario === 'gerente' && !empty($unidad_usuario)) {
    $where_rol = "AND (pr.id_unidad = '$unidad_usuario'
                  OR pr.id_unidad IN (SELECT id FROM ciclo_unidad_organizativa WHERE id_padre = '$unidad_usuario'))";
}

$where_leida = '';
if ($filtro_leida === '0') $where_leida = "AND ac.resuelta = 0";
elseif ($filtro_leida === '1') $where_leida = "AND ac.resuelta = 1";

// ── Consulta principal ───────────────────────────────────────────────────────
$sql_list = "SELECT ac.*, pa.id AS PARTIDA_ID,
                    cg.nombre AS CATEGORIA, cg.codigo AS CAT_CODIGO,
                    ig.nombre AS ITEM, ig.codigo AS ITEM_CODIGO,
                    cu.porcentaje AS UMBRAL_PCT,
                    uo.nombre AS UNIDAD
             FROM ciclo_alerta_consumo ac
             JOIN ciclo_partida pa ON ac.id_partida = pa.id
             JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
             JOIN ciclo_categoria_gasto cg ON pa.id_categoria = cg.id
             JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
             JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
             LEFT JOIN ciclo_config_umbral cu ON ac.id_umbral = cu.id
             WHERE 1=1 $where_rol $where_leida
             ORDER BY ac.fecha DESC;";
$result_list = sql_do_query($sql_list, $db, $usu);

// Conteo no leídas
$sql_count = "SELECT COUNT(*) AS TOTAL FROM ciclo_alerta_consumo ac
              JOIN ciclo_partida pa ON ac.id_partida = pa.id
              JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
              WHERE ac.resuelta = 0 $where_rol;";
$row_count = sql_do_array(sql_do_query($sql_count, $db, $usu));
$total_no_leidas = (int)($row_count['TOTAL'] ?? 0);
?>

<?php echo generarTituloSeccion('Alertas de Consumo', 'bi bi-bell'); ?>

<!-- INDICADOR -->
<?php if ($total_no_leidas > 0): ?>
<div class="alert alert-warning d-flex align-items-center mb-3">
    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
    <div>
        Tiene <strong><?php echo $total_no_leidas; ?></strong> alerta<?php echo ($total_no_leidas > 1) ? 's' : ''; ?> sin leer.
    </div>
</div>
<?php endif; ?>

<!-- FILTROS -->
<div class="row mb-3 align-items-end">
    <div class="col-md-3">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <label class="form-label small fw-semibold mb-1">Estado</label>
            <select class="form-select" name="filtro_leida" onchange="this.form.submit()">
                <option value="null">Todas</option>
                <option value="0" <?php echo ($filtro_leida === '0') ? 'selected' : ''; ?>>No leídas</option>
                <option value="1" <?php echo ($filtro_leida === '1') ? 'selected' : ''; ?>>Leídas</option>
            </select>
        </form>
    </div>
    <?php if ($total_no_leidas > 0): ?>
    <div class="col-md-3">
        <?php echo generarBotonForm(
            '<i class="bi bi-check-all me-1"></i>Marcar todas como leídas',
            "index.php?estado=$estado",
            ['accion_post' => 'marcar_todas'],
            'btn btn-outline-secondary btn-sm'
        ); ?>
    </div>
    <?php endif; ?>
</div>

<!-- TABLA -->
<div class="table-responsive">
    <table class="table table-hover table-pade">
        <?php echo generarTablaHeader(['Fecha', 'Unidad', 'Categoría / Ítem', '% Consumo', 'Umbral', 'Estado', 'Acciones']); ?>
        <tbody>
<?php
$n = 0;
while ($row = sql_do_array($result_list)):
    $n++;
    $pct = (float)($row['PORCENTAJE_ACTUAL'] ?? 0);
    $umbral_pct = $row['UMBRAL_PCT'] ? number_format((float)$row['UMBRAL_PCT'], 0) . '%' : 'N/A';
    $es_leida = ($row['RESUELTA'] == 1);

    // Color del % consumo
    $pct_class = 'text-success';
    if ($pct >= 100)     $pct_class = 'text-danger fw-bold';
    elseif ($pct >= 95)  $pct_class = 'text-danger';
    elseif ($pct >= 80)  $pct_class = 'text-warning fw-bold';

    $row_class = $es_leida ? '' : 'table-warning';
?>
            <tr class="<?php echo $row_class; ?>">
                <td class="text-center small"><?php echo enmascararfecha($row['FECHA'] ?? ''); ?></td>
                <td class="small"><?php echo htmlspecialchars($row['UNIDAD'] ?? ''); ?></td>
                <td>
                    <small class="text-muted"><?php echo htmlspecialchars($row['CAT_CODIGO'] ?? ''); ?></small>
                    <?php echo htmlspecialchars($row['CATEGORIA'] ?? ''); ?>
                    <br>
                    <small><i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($row['ITEM_CODIGO'] . ' - ' . $row['ITEM']); ?></small>
                </td>
                <td class="text-center">
                    <span class="<?php echo $pct_class; ?> fs-6"><?php echo number_format($pct, 1); ?>%</span>
                </td>
                <td class="text-center"><span class="badge bg-dark"><?php echo $umbral_pct; ?></span></td>
                <td class="text-center">
                    <?php if ($es_leida): ?>
                        <span class="badge bg-secondary"><i class="bi bi-check me-1"></i>Leída</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-circle me-1"></i>Nueva</span>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <?php if (!$es_leida): ?>
                        <?php echo generarBotonForm(
                            '<i class="bi bi-check"></i>',
                            "index.php?estado=$estado",
                            ['accion_post' => 'marcar_leida', 'alerta_id' => $row['ID']],
                            'btn btn-outline-success btn-sm'
                        ); ?>
                    <?php endif; ?>
                </td>
            </tr>
<?php endwhile; ?>
<?php if ($n === 0): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">
                <i class="bi bi-check-circle me-2"></i>No hay alertas<?php echo ($filtro_leida === '0') ? ' sin leer' : ''; ?>.
            </td></tr>
<?php endif; ?>
        </tbody>
    </table>
</div>
SESSION['ciclo_rol_efectivo'] ?? '';

// ── Filtros ──────────────────────────────────────────────────────────────────
$filtro_leida = Enmascarar_get('leida');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filtro_leida'])) {
    $filtro_leida = Enmascarar_post('filtro_leida');
}

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion_post = Enmascarar_post('accion_post');

    // ── Marcar una como leída ────────────────────────────────────────────────
    if ($accion_post === 'marcar_leida') {
        $alerta_id = Enmascarar_post('alerta_id');
        sql_do_query("UPDATE ciclo_alerta_consumo SET resuelta = 1 WHERE id = '$alerta_id';", $db, $usu);
        $msg = generarAlertJS('Marcada', 'La alerta fue marcada como leída.', 'success', "index.php?estado=$estado");
    }

    // ── Marcar todas como leídas ─────────────────────────────────────────────
    if ($accion_post === 'marcar_todas') {
        $where_u = '';
        if (in_array($rol_usuario, ['operador', 'jefe_unidad']) && !empty($unidad_usuario)) {
            $where_u = "AND pa.id_presupuesto IN (SELECT id FROM ciclo_presupuesto WHERE id_unidad = '$unidad_usuario')";
        } elseif ($rol_usuario === 'gerente' && !empty($unidad_usuario)) {
            $where_u = "AND pa.id_presupuesto IN (SELECT id FROM ciclo_presupuesto WHERE id_unidad = '$unidad_usuario'
                         OR id_unidad IN (SELECT id FROM ciclo_unidad_organizativa WHERE id_padre = '$unidad_usuario'))";
        }

        $sql_upd = "UPDATE ciclo_alerta_consumo ac
                    JOIN ciclo_partida pa ON ac.id_partida = pa.id
                    SET ac.resuelta = 1
                    WHERE ac.resuelta = 0 $where_u;";
        sql_do_query($sql_upd, $db, $usu);
        $msg = generarAlertJS('Listo', 'Todas las alertas fueron marcadas como leídas.', 'success', "index.php?estado=$estado");
    }
}

echo $msg;

// ── Restricción por rol ──────────────────────────────────────────────────────
$where_rol = '';
if (in_array($rol_usuario, ['operador', 'jefe_unidad']) && !empty($unidad_usuario)) {
    $where_rol = "AND pr.id_unidad = '$unidad_usuario'";
} elseif ($rol_usuario === 'gerente' && !empty($unidad_usuario)) {
    $where_rol = "AND (pr.id_unidad = '$unidad_usuario'
                  OR pr.id_unidad IN (SELECT id FROM ciclo_unidad_organizativa WHERE id_padre = '$unidad_usuario'))";
}

$where_leida = '';
if ($filtro_leida === '0') $where_leida = "AND ac.resuelta = 0";
elseif ($filtro_leida === '1') $where_leida = "AND ac.resuelta = 1";

// ── Consulta principal ───────────────────────────────────────────────────────
$sql_list = "SELECT ac.*, pa.id AS PARTIDA_ID,
                    cg.nombre AS CATEGORIA, cg.codigo AS CAT_CODIGO,
                    ig.nombre AS ITEM, ig.codigo AS ITEM_CODIGO,
                    cu.porcentaje AS UMBRAL_PCT,
                    uo.nombre AS UNIDAD
             FROM ciclo_alerta_consumo ac
             JOIN ciclo_partida pa ON ac.id_partida = pa.id
             JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
             JOIN ciclo_categoria_gasto cg ON pa.id_categoria = cg.id
             JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
             JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
             LEFT JOIN ciclo_config_umbral cu ON ac.id_umbral = cu.id
             WHERE 1=1 $where_rol $where_leida
             ORDER BY ac.fecha DESC;";
$result_list = sql_do_query($sql_list, $db, $usu);

// Conteo no leídas
$sql_count = "SELECT COUNT(*) AS TOTAL FROM ciclo_alerta_consumo ac
              JOIN ciclo_partida pa ON ac.id_partida = pa.id
              JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
              WHERE ac.resuelta = 0 $where_rol;";
$row_count = sql_do_array(sql_do_query($sql_count, $db, $usu));
$total_no_leidas = (int)($row_count['TOTAL'] ?? 0);
?>

<?php echo generarTituloSeccion('Alertas de Consumo', 'bi bi-bell'); ?>

<!-- INDICADOR -->
<?php if ($total_no_leidas > 0): ?>
<div class="alert alert-warning d-flex align-items-center mb-3">
    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
    <div>
        Tiene <strong><?php echo $total_no_leidas; ?></strong> alerta<?php echo ($total_no_leidas > 1) ? 's' : ''; ?> sin leer.
    </div>
</div>
<?php endif; ?>

<!-- FILTROS -->
<div class="row mb-3 align-items-end">
    <div class="col-md-3">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <label class="form-label small fw-semibold mb-1">Estado</label>
            <select class="form-select" name="filtro_leida" onchange="this.form.submit()">
                <option value="null">Todas</option>
                <option value="0" <?php echo ($filtro_leida === '0') ? 'selected' : ''; ?>>No leídas</option>
                <option value="1" <?php echo ($filtro_leida === '1') ? 'selected' : ''; ?>>Leídas</option>
            </select>
        </form>
    </div>
    <?php if ($total_no_leidas > 0): ?>
    <div class="col-md-3">
        <?php echo generarBotonForm(
            '<i class="bi bi-check-all me-1"></i>Marcar todas como leídas',
            "index.php?estado=$estado",
            ['accion_post' => 'marcar_todas'],
            'btn btn-outline-secondary btn-sm'
        ); ?>
    </div>
    <?php endif; ?>
</div>

<!-- TABLA -->
<div class="table-responsive">
    <table class="table table-hover table-pade">
        <?php echo generarTablaHeader(['Fecha', 'Unidad', 'Categoría / Ítem', '% Consumo', 'Umbral', 'Estado', 'Acciones']); ?>
        <tbody>
<?php
$n = 0;
while ($row = sql_do_array($result_list)):
    $n++;
    $pct = (float)($row['PORCENTAJE_ACTUAL'] ?? 0);
    $umbral_pct = $row['UMBRAL_PCT'] ? number_format((float)$row['UMBRAL_PCT'], 0) . '%' : 'N/A';
    $es_leida = ($row['RESUELTA'] == 1);

    // Color del % consumo
    $pct_class = 'text-success';
    if ($pct >= 100)     $pct_class = 'text-danger fw-bold';
    elseif ($pct >= 95)  $pct_class = 'text-danger';
    elseif ($pct >= 80)  $pct_class = 'text-warning fw-bold';

    $row_class = $es_leida ? '' : 'table-warning';
?>
            <tr class="<?php echo $row_class; ?>">
                <td class="text-center small"><?php echo enmascararfecha($row['FECHA'] ?? ''); ?></td>
                <td class="small"><?php echo htmlspecialchars($row['UNIDAD'] ?? ''); ?></td>
                <td>
                    <small class="text-muted"><?php echo htmlspecialchars($row['CAT_CODIGO'] ?? ''); ?></small>
                    <?php echo htmlspecialchars($row['CATEGORIA'] ?? ''); ?>
                    <br>
                    <small><i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($row['ITEM_CODIGO'] . ' - ' . $row['ITEM']); ?></small>
                </td>
                <td class="text-center">
                    <span class="<?php echo $pct_class; ?> fs-6"><?php echo number_format($pct, 1); ?>%</span>
                </td>
                <td class="text-center"><span class="badge bg-dark"><?php echo $umbral_pct; ?></span></td>
                <td class="text-center">
                    <?php if ($es_leida): ?>
                        <span class="badge bg-secondary"><i class="bi bi-check me-1"></i>Leída</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-circle me-1"></i>Nueva</span>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <?php if (!$es_leida): ?>
                        <?php echo generarBotonForm(
                            '<i class="bi bi-check"></i>',
                            "index.php?estado=$estado",
                            ['accion_post' => 'marcar_leida', 'alerta_id' => $row['ID']],
                            'btn btn-outline-success btn-sm'
                        ); ?>
                    <?php endif; ?>
                </td>
            </tr>
<?php endwhile; ?>
<?php if ($n === 0): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">
                <i class="bi bi-check-circle me-2"></i>No hay alertas<?php echo ($filtro_leida === '0') ? ' sin leer' : ''; ?>.
            </td></tr>
<?php endif; ?>
        </tbody>
    </table>
</div>
SESSION['ciclo_rol_efectivo'] ?? '';

// ── Filtros ──────────────────────────────────────────────────────────────────
$filtro_leida = Enmascarar_get('leida');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filtro_leida'])) {
    $filtro_leida = Enmascarar_post('filtro_leida');
}

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion_post = Enmascarar_post('accion_post');

    // ── Marcar una como leída ────────────────────────────────────────────────
    if ($accion_post === 'marcar_leida') {
        $alerta_id = Enmascarar_post('alerta_id');
        sql_do_query("UPDATE ciclo_alerta_consumo SET resuelta = 1 WHERE id = '$alerta_id';", $db, $usu);
        $msg = generarAlertJS('Marcada', 'La alerta fue marcada como leída.', 'success', "index.php?estado=$estado");
    }

    // ── Marcar todas como leídas ─────────────────────────────────────────────
    if ($accion_post === 'marcar_todas') {
        $where_u = '';
        if (in_array($rol_usuario, ['operador', 'jefe_unidad']) && !empty($unidad_usuario)) {
            $where_u = "AND pa.id_presupuesto IN (SELECT id FROM ciclo_presupuesto WHERE id_unidad = '$unidad_usuario')";
        } elseif ($rol_usuario === 'gerente' && !empty($unidad_usuario)) {
            $where_u = "AND pa.id_presupuesto IN (SELECT id FROM ciclo_presupuesto WHERE id_unidad = '$unidad_usuario'
                         OR id_unidad IN (SELECT id FROM ciclo_unidad_organizativa WHERE id_padre = '$unidad_usuario'))";
        }

        $sql_upd = "UPDATE ciclo_alerta_consumo ac
                    JOIN ciclo_partida pa ON ac.id_partida = pa.id
                    SET ac.resuelta = 1
                    WHERE ac.resuelta = 0 $where_u;";
        sql_do_query($sql_upd, $db, $usu);
        $msg = generarAlertJS('Listo', 'Todas las alertas fueron marcadas como leídas.', 'success', "index.php?estado=$estado");
    }
}

echo $msg;

// ── Restricción por rol ──────────────────────────────────────────────────────
$where_rol = '';
if (in_array($rol_usuario, ['operador', 'jefe_unidad']) && !empty($unidad_usuario)) {
    $where_rol = "AND pr.id_unidad = '$unidad_usuario'";
} elseif ($rol_usuario === 'gerente' && !empty($unidad_usuario)) {
    $where_rol = "AND (pr.id_unidad = '$unidad_usuario'
                  OR pr.id_unidad IN (SELECT id FROM ciclo_unidad_organizativa WHERE id_padre = '$unidad_usuario'))";
}

$where_leida = '';
if ($filtro_leida === '0') $where_leida = "AND ac.resuelta = 0";
elseif ($filtro_leida === '1') $where_leida = "AND ac.resuelta = 1";

// ── Consulta principal ───────────────────────────────────────────────────────
$sql_list = "SELECT ac.*, pa.id AS PARTIDA_ID,
                    cg.nombre AS CATEGORIA, cg.codigo AS CAT_CODIGO,
                    ig.nombre AS ITEM, ig.codigo AS ITEM_CODIGO,
                    cu.porcentaje AS UMBRAL_PCT,
                    uo.nombre AS UNIDAD
             FROM ciclo_alerta_consumo ac
             JOIN ciclo_partida pa ON ac.id_partida = pa.id
             JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
             JOIN ciclo_categoria_gasto cg ON pa.id_categoria = cg.id
             JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
             JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
             LEFT JOIN ciclo_config_umbral cu ON ac.id_umbral = cu.id
             WHERE 1=1 $where_rol $where_leida
             ORDER BY ac.fecha DESC;";
$result_list = sql_do_query($sql_list, $db, $usu);

// Conteo no leídas
$sql_count = "SELECT COUNT(*) AS TOTAL FROM ciclo_alerta_consumo ac
              JOIN ciclo_partida pa ON ac.id_partida = pa.id
              JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
              WHERE ac.resuelta = 0 $where_rol;";
$row_count = sql_do_array(sql_do_query($sql_count, $db, $usu));
$total_no_leidas = (int)($row_count['TOTAL'] ?? 0);
?>

<?php echo generarTituloSeccion('Alertas de Consumo', 'bi bi-bell'); ?>

<!-- INDICADOR -->
<?php if ($total_no_leidas > 0): ?>
<div class="alert alert-warning d-flex align-items-center mb-3">
    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
    <div>
        Tiene <strong><?php echo $total_no_leidas; ?></strong> alerta<?php echo ($total_no_leidas > 1) ? 's' : ''; ?> sin leer.
    </div>
</div>
<?php endif; ?>

<!-- FILTROS -->
<div class="row mb-3 align-items-end">
    <div class="col-md-3">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <label class="form-label small fw-semibold mb-1">Estado</label>
            <select class="form-select" name="filtro_leida" onchange="this.form.submit()">
                <option value="null">Todas</option>
                <option value="0" <?php echo ($filtro_leida === '0') ? 'selected' : ''; ?>>No leídas</option>
                <option value="1" <?php echo ($filtro_leida === '1') ? 'selected' : ''; ?>>Leídas</option>
            </select>
        </form>
    </div>
    <?php if ($total_no_leidas > 0): ?>
    <div class="col-md-3">
        <?php echo generarBotonForm(
            '<i class="bi bi-check-all me-1"></i>Marcar todas como leídas',
            "index.php?estado=$estado",
            ['accion_post' => 'marcar_todas'],
            'btn btn-outline-secondary btn-sm'
        ); ?>
    </div>
    <?php endif; ?>
</div>

<!-- TABLA -->
<div class="table-responsive">
    <table class="table table-hover table-pade">
        <?php echo generarTablaHeader(['Fecha', 'Unidad', 'Categoría / Ítem', '% Consumo', 'Umbral', 'Estado', 'Acciones']); ?>
        <tbody>
<?php
$n = 0;
while ($row = sql_do_array($result_list)):
    $n++;
    $pct = (float)($row['PORCENTAJE_ACTUAL'] ?? 0);
    $umbral_pct = $row['UMBRAL_PCT'] ? number_format((float)$row['UMBRAL_PCT'], 0) . '%' : 'N/A';
    $es_leida = ($row['RESUELTA'] == 1);

    // Color del % consumo
    $pct_class = 'text-success';
    if ($pct >= 100)     $pct_class = 'text-danger fw-bold';
    elseif ($pct >= 95)  $pct_class = 'text-danger';
    elseif ($pct >= 80)  $pct_class = 'text-warning fw-bold';

    $row_class = $es_leida ? '' : 'table-warning';
?>
            <tr class="<?php echo $row_class; ?>">
                <td class="text-center small"><?php echo enmascararfecha($row['FECHA'] ?? ''); ?></td>
                <td class="small"><?php echo htmlspecialchars($row['UNIDAD'] ?? ''); ?></td>
                <td>
                    <small class="text-muted"><?php echo htmlspecialchars($row['CAT_CODIGO'] ?? ''); ?></small>
                    <?php echo htmlspecialchars($row['CATEGORIA'] ?? ''); ?>
                    <br>
                    <small><i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($row['ITEM_CODIGO'] . ' - ' . $row['ITEM']); ?></small>
                </td>
                <td class="text-center">
                    <span class="<?php echo $pct_class; ?> fs-6"><?php echo number_format($pct, 1); ?>%</span>
                </td>
                <td class="text-center"><span class="badge bg-dark"><?php echo $umbral_pct; ?></span></td>
                <td class="text-center">
                    <?php if ($es_leida): ?>
                        <span class="badge bg-secondary"><i class="bi bi-check me-1"></i>Leída</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-circle me-1"></i>Nueva</span>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <?php if (!$es_leida): ?>
                        <?php echo generarBotonForm(
                            '<i class="bi bi-check"></i>',
                            "index.php?estado=$estado",
                            ['accion_post' => 'marcar_leida', 'alerta_id' => $row['ID']],
                            'btn btn-outline-success btn-sm'
                        ); ?>
                    <?php endif; ?>
                </td>
            </tr>
<?php endwhile; ?>
<?php if ($n === 0): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">
                <i class="bi bi-check-circle me-2"></i>No hay alertas<?php echo ($filtro_leida === '0') ? ' sin leer' : ''; ?>.
            </td></tr>
<?php endif; ?>
        </tbody>
    </table>
</div>
