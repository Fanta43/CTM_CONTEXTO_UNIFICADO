<?php
/**
 * ============================================================================
 * Reporte: Ejecución Presupuestaria
 * ============================================================================
 * ID menú: 501 | Vistas: v_saldo_partida, v_resumen_categoria
 * Reporte principal: ejecución con 3 niveles (unidad → categoría → ítem).
 * Exportable a CSV. Solo lectura.
 * ============================================================================
 */

$db = '1';

$rol_usuario    = <?php
/**
 * ============================================================================
 * Reporte: Ejecución Presupuestaria
 * ============================================================================
 * ID menú: 501 | Vistas: v_saldo_partida, v_resumen_categoria
 * Reporte principal: ejecución con 3 niveles (unidad → categoría → ítem).
 * Exportable a CSV. Solo lectura.
 * ============================================================================
 */

$db = '1';

$rol_usuario    = $_SESSION['rol'] ?? '';
$unidad_usuario = $_SESSION['id_unidad'] ?? '';

// ── Filtros ──────────────────────────────────────────────────────────────────
$periodo_sel = Enmascarar_get('periodo');
$unidad_sel  = Enmascarar_get('unidad');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['filtro_periodo'])) $periodo_sel = Enmascarar_post('filtro_periodo');
    if (!empty($_POST['filtro_unidad']))  $unidad_sel  = Enmascarar_post('filtro_unidad');
}

// Default: período abierto
if (empty($periodo_sel) || $periodo_sel === 'null') {
    $row_ab = sql_do_array(sql_do_query("SELECT id FROM ciclo_periodo_fiscal WHERE estado = 'abierto' LIMIT 1;", $db, $usu));
    $periodo_sel = $row_ab['ID'] ?? '';
}

// Restricción por rol
$where_unidad = '';
if (in_array($rol_usuario, ['operador', 'jefe_unidad']) && !empty($unidad_usuario)) {
    $where_unidad = "AND pr.id_unidad = '$unidad_usuario'";
    $unidad_sel = $unidad_usuario;
} elseif ($rol_usuario === 'gerente' && !empty($unidad_usuario)) {
    $where_unidad = "AND (pr.id_unidad = '$unidad_usuario'
                     OR pr.id_unidad IN (SELECT id FROM ciclo_unidad_organizativa WHERE id_padre = '$unidad_usuario'))";
} else {
    if (!empty($unidad_sel) && $unidad_sel !== 'null') {
        $where_unidad = "AND pr.id_unidad = '$unidad_sel'";
    }
}

// ── Exportar CSV ─────────────────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv' && !empty($periodo_sel)) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ejecucion_presupuestaria_' . $periodo_sel . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Unidad', 'Categoría', 'Código Ítem', 'Ítem', 'Aprobado', 'Comprometido', 'Ejecutado', 'Disponible', '% Ejecución'], ';');

    $sql_csv = "SELECT uo.nombre AS UNIDAD, cg.nombre AS CATEGORIA, ig.codigo AS ITEM_CODIGO, ig.nombre AS ITEM,
                       pa.monto_aprobado, pa.monto_comprometido, pa.monto_ejecutado,
                       (pa.monto_aprobado - pa.monto_comprometido - pa.monto_ejecutado) AS disponible,
                       ROUND(pa.monto_ejecutado / NULLIF(pa.monto_aprobado, 0) * 100, 2) AS pct
                FROM ciclo_partida pa
                JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
                JOIN ciclo_categoria_gasto cg ON pa.id_categoria = cg.id
                JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
                WHERE pr.id_periodo = '$periodo_sel' $where_unidad
                ORDER BY uo.nombre, cg.orden, ig.codigo;";
    $res_csv = sql_do_query($sql_csv, $db, $usu);
    while ($rc = sql_do_array($res_csv)) {
        fputcsv($out, [
            $rc['UNIDAD'], $rc['CATEGORIA'], $rc['ITEM_CODIGO'], $rc['ITEM'],
            number_format((float)$rc['MONTO_APROBADO'], 2, ',', '.'),
            number_format((float)$rc['MONTO_COMPROMETIDO'], 2, ',', '.'),
            number_format((float)$rc['MONTO_EJECUTADO'], 2, ',', '.'),
            number_format((float)$rc['DISPONIBLE'], 2, ',', '.'),
            $rc['PCT'] . '%'
        ], ';');
    }
    fclose($out);
    exit;
}

// ── Datos nivel 1: por unidad ────────────────────────────────────────────────
$sql_unidades = "SELECT uo.id AS UID, uo.nombre AS UNIDAD,
                        SUM(pa.monto_aprobado) AS APROBADO,
                        SUM(pa.monto_comprometido) AS COMPROMETIDO,
                        SUM(pa.monto_ejecutado) AS EJECUTADO,
                        SUM(pa.monto_aprobado - pa.monto_comprometido - pa.monto_ejecutado) AS DISPONIBLE,
                        ROUND(SUM(pa.monto_ejecutado) / NULLIF(SUM(pa.monto_aprobado), 0) * 100, 2) AS PCT
                 FROM ciclo_partida pa
                 JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                 JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
                 WHERE pr.id_periodo = '$periodo_sel' $where_unidad
                 GROUP BY uo.id, uo.nombre
                 ORDER BY uo.nombre;";

// Combos
$sql_combo_per = "SELECT id AS ID, CONCAT(anio, ' (', estado, ')') AS NOMBRE FROM ciclo_periodo_fiscal ORDER BY anio DESC;";
$sql_combo_uni = "SELECT id AS ID, nombre AS NOMBRE FROM ciclo_unidad_organizativa WHERE 1=1 ORDER BY nombre;";

// Unidad expandida
$expand_uid = Enmascarar_get('uid');
$expand_cat = Enmascarar_get('ecat');
?>

<?php echo generarTituloSeccion('Ejecución Presupuestaria', 'bi bi-bar-chart-line'); ?>

<!-- FILTROS -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Período</label>
                <?php echo imp_combo_w_oc('filtro_periodo', $sql_combo_per, $db, $usu, $periodo_sel); ?>
            </div>
            <?php if (in_array($rol_usuario, ['director', 'admin'])): ?>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Unidad</label>
                <?php echo imp_combo_w_oc('filtro_unidad', $sql_combo_uni, $db, $usu, $unidad_sel); ?>
            </div>
            <?php endif; ?>
            <div class="col-md-3">
                <a href="index.php?estado=<?php echo $estado; ?>&periodo=<?php echo $periodo_sel; ?>&unidad=<?php echo $unidad_sel; ?>&export=csv"
                   class="btn btn-outline-success btn-sm">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>Exportar CSV
                </a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($periodo_sel)): ?>
<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Seleccione un período.</div>
<?php else:
    $res_unidades = sql_do_query($sql_unidades, $db, $usu);
    $unidades = [];
    $gt_apro = 0; $gt_comp = 0; $gt_ejec = 0; $gt_disp = 0;
    while ($ru = sql_do_array($res_unidades)) {
        $unidades[] = $ru;
        $gt_apro += (float)$ru['APROBADO'];
        $gt_comp += (float)$ru['COMPROMETIDO'];
        $gt_ejec += (float)$ru['EJECUTADO'];
        $gt_disp += (float)$ru['DISPONIBLE'];
    }
?>

<!-- NIVEL 1: UNIDADES -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-pade mb-0">
                <?php echo generarTablaHeader(['Unidad', 'Aprobado', 'Comprometido', 'Ejecutado', 'Disponible', '% Ejec.', '']); ?>
                <tbody>
<?php foreach ($unidades as $u):
    $pct = (float)($u['PCT'] ?? 0);
    $pct_class = 'text-success';
    if ($pct >= 95) $pct_class = 'text-danger fw-bold';
    elseif ($pct >= 80) $pct_class = 'text-warning fw-bold';
    $es_expandida = ($expand_uid == $u['UID']);
?>
                    <tr style="cursor:pointer;" class="<?php echo $es_expandida ? 'table-active' : ''; ?>"
                        onclick="window.location='index.php?estado=<?php echo $estado; ?>&periodo=<?php echo $periodo_sel; ?>&uid=<?php echo $u['UID']; ?>'">
                        <td class="fw-bold">
                            <i class="bi bi-<?php echo $es_expandida ? 'dash-square' : 'plus-square'; ?> me-1"></i>
                            <?php echo htmlspecialchars($u['UNIDAD']); ?>
                        </td>
                        <td class="text-end">$ <?php echo number_format((float)$u['APROBADO'], 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format((float)$u['COMPROMETIDO'], 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format((float)$u['EJECUTADO'], 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format((float)$u['DISPONIBLE'], 2, ',', '.'); ?></td>
                        <td class="text-center"><span class="<?php echo $pct_class; ?>"><?php echo number_format($pct, 1); ?>%</span></td>
                        <td class="text-center"><i class="bi bi-chevron-<?php echo $es_expandida ? 'down' : 'right'; ?>"></i></td>
                    </tr>

<?php
    // NIVEL 2: Categorías de la unidad expandida
    if ($es_expandida):
        $sql_cats = "SELECT cg.id AS CID, cg.codigo AS CAT_CODIGO, cg.nombre AS CATEGORIA,
                            SUM(pa.monto_aprobado) AS APROBADO, SUM(pa.monto_comprometido) AS COMPROMETIDO,
                            SUM(pa.monto_ejecutado) AS EJECUTADO,
                            SUM(pa.monto_aprobado - pa.monto_comprometido - pa.monto_ejecutado) AS DISPONIBLE,
                            ROUND(SUM(pa.monto_ejecutado) / NULLIF(SUM(pa.monto_aprobado), 0) * 100, 2) AS PCT
                     FROM ciclo_partida pa
                     JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                     JOIN ciclo_categoria_gasto cg ON pa.id_categoria = cg.id
                     WHERE pr.id_periodo = '$periodo_sel' AND pr.id_unidad = '{$u['UID']}'
                     GROUP BY cg.id, cg.codigo, cg.nombre
                     ORDER BY cg.orden;";
        $res_cats = sql_do_query($sql_cats, $db, $usu);
        while ($rc = sql_do_array($res_cats)):
            $cpct = (float)($rc['PCT'] ?? 0);
            $cpct_class = ($cpct >= 95) ? 'text-danger fw-bold' : (($cpct >= 80) ? 'text-warning fw-bold' : 'text-success');
            $cat_expandida = ($expand_cat == $rc['CID']);
?>
                    <tr class="table-light" style="cursor:pointer;"
                        onclick="window.location='index.php?estado=<?php echo $estado; ?>&periodo=<?php echo $periodo_sel; ?>&uid=<?php echo $u['UID']; ?>&ecat=<?php echo $rc['CID']; ?>'">
                        <td class="ps-5">
                            <i class="bi bi-<?php echo $cat_expandida ? 'folder2-open' : 'folder'; ?> me-1 text-primary"></i>
                            <small class="text-muted"><?php echo htmlspecialchars($rc['CAT_CODIGO']); ?></small>
                            <?php echo htmlspecialchars($rc['CATEGORIA']); ?>
                        </td>
                        <td class="text-end">$ <?php echo number_format((float)$rc['APROBADO'], 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format((float)$rc['COMPROMETIDO'], 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format((float)$rc['EJECUTADO'], 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format((float)$rc['DISPONIBLE'], 2, ',', '.'); ?></td>
                        <td class="text-center"><span class="<?php echo $cpct_class; ?>"><?php echo number_format($cpct, 1); ?>%</span></td>
                        <td></td>
                    </tr>
<?php
            // NIVEL 3: Ítems de la categoría expandida
            if ($cat_expandida):
                $sql_items = "SELECT ig.codigo AS ITEM_CODIGO, ig.nombre AS ITEM,
                                     pa.monto_aprobado AS APROBADO, pa.monto_comprometido AS COMPROMETIDO,
                                     pa.monto_ejecutado AS EJECUTADO,
                                     (pa.monto_aprobado - pa.monto_comprometido - pa.monto_ejecutado) AS DISPONIBLE,
                                     ROUND(pa.monto_ejecutado / NULLIF(pa.monto_aprobado, 0) * 100, 2) AS PCT
                              FROM ciclo_partida pa
                              JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                              JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
                              WHERE pr.id_periodo = '$periodo_sel' AND pr.id_unidad = '{$u['UID']}' AND pa.id_categoria = '{$rc['CID']}'
                              ORDER BY ig.codigo;";
                $res_items = sql_do_query($sql_items, $db, $usu);
                while ($ri = sql_do_array($res_items)):
                    $ipct = (float)($ri['PCT'] ?? 0);
                    $ipct_class = ($ipct >= 95) ? 'text-danger fw-bold' : (($ipct >= 80) ? 'text-warning fw-bold' : 'text-success');
                    $irow = ($ipct >= 95) ? 'table-danger' : (($ipct >= 80) ? 'table-warning' : '');
?>
                    <tr class="<?php echo $irow; ?>">
                        <td class="ps-5 ps-lg-5">
                            <span class="ms-4"><i class="bi bi-tag me-1 text-muted"></i>
                            <small class="text-muted"><?php echo htmlspecialchars($ri['ITEM_CODIGO']); ?></small>
                            <?php echo htmlspecialchars($ri['ITEM']); ?></span>
                        </td>
                        <td class="text-end small">$ <?php echo number_format((float)$ri['APROBADO'], 2, ',', '.'); ?></td>
                        <td class="text-end small">$ <?php echo number_format((float)$ri['COMPROMETIDO'], 2, ',', '.'); ?></td>
                        <td class="text-end small">$ <?php echo number_format((float)$ri['EJECUTADO'], 2, ',', '.'); ?></td>
                        <td class="text-end small">$ <?php echo number_format((float)$ri['DISPONIBLE'], 2, ',', '.'); ?></td>
                        <td class="text-center small"><span class="<?php echo $ipct_class; ?>"><?php echo number_format($ipct, 1); ?>%</span></td>
                        <td></td>
                    </tr>
<?php           endwhile;
            endif;
        endwhile;
    endif;
endforeach; ?>

<?php if (!empty($unidades)):
    $gt_pct = ($gt_apro > 0) ? round($gt_ejec / $gt_apro * 100, 2) : 0;
?>
                    <tr class="table-dark fw-bold">
                        <td>TOTALES GENERALES</td>
                        <td class="text-end">$ <?php echo number_format($gt_apro, 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format($gt_comp, 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format($gt_ejec, 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format($gt_disp, 2, ',', '.'); ?></td>
                        <td class="text-center"><?php echo number_format($gt_pct, 1); ?>%</td>
                        <td></td>
                    </tr>
<?php endif; ?>
<?php if (empty($unidades)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Sin datos para el período seleccionado.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
SESSION['ciclo_rol_efectivo'] ?? '';
$unidad_usuario = $_SESSION['id_unidad'] ?? '';

// ── Filtros ──────────────────────────────────────────────────────────────────
$periodo_sel = Enmascarar_get('periodo');
$unidad_sel  = Enmascarar_get('unidad');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['filtro_periodo'])) $periodo_sel = Enmascarar_post('filtro_periodo');
    if (!empty($_POST['filtro_unidad']))  $unidad_sel  = Enmascarar_post('filtro_unidad');
}

// Default: período abierto
if (empty($periodo_sel) || $periodo_sel === 'null') {
    $row_ab = sql_do_array(sql_do_query("SELECT id FROM ciclo_periodo_fiscal WHERE estado = 'abierto' LIMIT 1;", $db, $usu));
    $periodo_sel = $row_ab['ID'] ?? '';
}

// Restricción por rol
$where_unidad = '';
if (in_array($rol_usuario, ['operador', 'jefe_unidad']) && !empty($unidad_usuario)) {
    $where_unidad = "AND pr.id_unidad = '$unidad_usuario'";
    $unidad_sel = $unidad_usuario;
} elseif ($rol_usuario === 'gerente' && !empty($unidad_usuario)) {
    $where_unidad = "AND (pr.id_unidad = '$unidad_usuario'
                     OR pr.id_unidad IN (SELECT id FROM ciclo_unidad_organizativa WHERE id_padre = '$unidad_usuario'))";
} else {
    if (!empty($unidad_sel) && $unidad_sel !== 'null') {
        $where_unidad = "AND pr.id_unidad = '$unidad_sel'";
    }
}

// ── Exportar CSV ─────────────────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv' && !empty($periodo_sel)) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ejecucion_presupuestaria_' . $periodo_sel . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Unidad', 'Categoría', 'Código Ítem', 'Ítem', 'Aprobado', 'Comprometido', 'Ejecutado', 'Disponible', '% Ejecución'], ';');

    $sql_csv = "SELECT uo.nombre AS UNIDAD, cg.nombre AS CATEGORIA, ig.codigo AS ITEM_CODIGO, ig.nombre AS ITEM,
                       pa.monto_aprobado, pa.monto_comprometido, pa.monto_ejecutado,
                       (pa.monto_aprobado - pa.monto_comprometido - pa.monto_ejecutado) AS disponible,
                       ROUND(pa.monto_ejecutado / NULLIF(pa.monto_aprobado, 0) * 100, 2) AS pct
                FROM ciclo_partida pa
                JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
                JOIN ciclo_categoria_gasto cg ON pa.id_categoria = cg.id
                JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
                WHERE pr.id_periodo = '$periodo_sel' $where_unidad
                ORDER BY uo.nombre, cg.orden, ig.codigo;";
    $res_csv = sql_do_query($sql_csv, $db, $usu);
    while ($rc = sql_do_array($res_csv)) {
        fputcsv($out, [
            $rc['UNIDAD'], $rc['CATEGORIA'], $rc['ITEM_CODIGO'], $rc['ITEM'],
            number_format((float)$rc['MONTO_APROBADO'], 2, ',', '.'),
            number_format((float)$rc['MONTO_COMPROMETIDO'], 2, ',', '.'),
            number_format((float)$rc['MONTO_EJECUTADO'], 2, ',', '.'),
            number_format((float)$rc['DISPONIBLE'], 2, ',', '.'),
            $rc['PCT'] . '%'
        ], ';');
    }
    fclose($out);
    exit;
}

// ── Datos nivel 1: por unidad ────────────────────────────────────────────────
$sql_unidades = "SELECT uo.id AS UID, uo.nombre AS UNIDAD,
                        SUM(pa.monto_aprobado) AS APROBADO,
                        SUM(pa.monto_comprometido) AS COMPROMETIDO,
                        SUM(pa.monto_ejecutado) AS EJECUTADO,
                        SUM(pa.monto_aprobado - pa.monto_comprometido - pa.monto_ejecutado) AS DISPONIBLE,
                        ROUND(SUM(pa.monto_ejecutado) / NULLIF(SUM(pa.monto_aprobado), 0) * 100, 2) AS PCT
                 FROM ciclo_partida pa
                 JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                 JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
                 WHERE pr.id_periodo = '$periodo_sel' $where_unidad
                 GROUP BY uo.id, uo.nombre
                 ORDER BY uo.nombre;";

// Combos
$sql_combo_per = "SELECT id AS ID, CONCAT(anio, ' (', estado, ')') AS NOMBRE FROM ciclo_periodo_fiscal ORDER BY anio DESC;";
$sql_combo_uni = "SELECT id AS ID, nombre AS NOMBRE FROM ciclo_unidad_organizativa WHERE 1=1 ORDER BY nombre;";

// Unidad expandida
$expand_uid = Enmascarar_get('uid');
$expand_cat = Enmascarar_get('ecat');
?>

<?php echo generarTituloSeccion('Ejecución Presupuestaria', 'bi bi-bar-chart-line'); ?>

<!-- FILTROS -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Período</label>
                <?php echo imp_combo_w_oc('filtro_periodo', $sql_combo_per, $db, $usu, $periodo_sel); ?>
            </div>
            <?php if (in_array($rol_usuario, ['director', 'admin'])): ?>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Unidad</label>
                <?php echo imp_combo_w_oc('filtro_unidad', $sql_combo_uni, $db, $usu, $unidad_sel); ?>
            </div>
            <?php endif; ?>
            <div class="col-md-3">
                <a href="index.php?estado=<?php echo $estado; ?>&periodo=<?php echo $periodo_sel; ?>&unidad=<?php echo $unidad_sel; ?>&export=csv"
                   class="btn btn-outline-success btn-sm">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>Exportar CSV
                </a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($periodo_sel)): ?>
<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Seleccione un período.</div>
<?php else:
    $res_unidades = sql_do_query($sql_unidades, $db, $usu);
    $unidades = [];
    $gt_apro = 0; $gt_comp = 0; $gt_ejec = 0; $gt_disp = 0;
    while ($ru = sql_do_array($res_unidades)) {
        $unidades[] = $ru;
        $gt_apro += (float)$ru['APROBADO'];
        $gt_comp += (float)$ru['COMPROMETIDO'];
        $gt_ejec += (float)$ru['EJECUTADO'];
        $gt_disp += (float)$ru['DISPONIBLE'];
    }
?>

<!-- NIVEL 1: UNIDADES -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-pade mb-0">
                <?php echo generarTablaHeader(['Unidad', 'Aprobado', 'Comprometido', 'Ejecutado', 'Disponible', '% Ejec.', '']); ?>
                <tbody>
<?php foreach ($unidades as $u):
    $pct = (float)($u['PCT'] ?? 0);
    $pct_class = 'text-success';
    if ($pct >= 95) $pct_class = 'text-danger fw-bold';
    elseif ($pct >= 80) $pct_class = 'text-warning fw-bold';
    $es_expandida = ($expand_uid == $u['UID']);
?>
                    <tr style="cursor:pointer;" class="<?php echo $es_expandida ? 'table-active' : ''; ?>"
                        onclick="window.location='index.php?estado=<?php echo $estado; ?>&periodo=<?php echo $periodo_sel; ?>&uid=<?php echo $u['UID']; ?>'">
                        <td class="fw-bold">
                            <i class="bi bi-<?php echo $es_expandida ? 'dash-square' : 'plus-square'; ?> me-1"></i>
                            <?php echo htmlspecialchars($u['UNIDAD']); ?>
                        </td>
                        <td class="text-end">$ <?php echo number_format((float)$u['APROBADO'], 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format((float)$u['COMPROMETIDO'], 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format((float)$u['EJECUTADO'], 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format((float)$u['DISPONIBLE'], 2, ',', '.'); ?></td>
                        <td class="text-center"><span class="<?php echo $pct_class; ?>"><?php echo number_format($pct, 1); ?>%</span></td>
                        <td class="text-center"><i class="bi bi-chevron-<?php echo $es_expandida ? 'down' : 'right'; ?>"></i></td>
                    </tr>

<?php
    // NIVEL 2: Categorías de la unidad expandida
    if ($es_expandida):
        $sql_cats = "SELECT cg.id AS CID, cg.codigo AS CAT_CODIGO, cg.nombre AS CATEGORIA,
                            SUM(pa.monto_aprobado) AS APROBADO, SUM(pa.monto_comprometido) AS COMPROMETIDO,
                            SUM(pa.monto_ejecutado) AS EJECUTADO,
                            SUM(pa.monto_aprobado - pa.monto_comprometido - pa.monto_ejecutado) AS DISPONIBLE,
                            ROUND(SUM(pa.monto_ejecutado) / NULLIF(SUM(pa.monto_aprobado), 0) * 100, 2) AS PCT
                     FROM ciclo_partida pa
                     JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                     JOIN ciclo_categoria_gasto cg ON pa.id_categoria = cg.id
                     WHERE pr.id_periodo = '$periodo_sel' AND pr.id_unidad = '{$u['UID']}'
                     GROUP BY cg.id, cg.codigo, cg.nombre
                     ORDER BY cg.orden;";
        $res_cats = sql_do_query($sql_cats, $db, $usu);
        while ($rc = sql_do_array($res_cats)):
            $cpct = (float)($rc['PCT'] ?? 0);
            $cpct_class = ($cpct >= 95) ? 'text-danger fw-bold' : (($cpct >= 80) ? 'text-warning fw-bold' : 'text-success');
            $cat_expandida = ($expand_cat == $rc['CID']);
?>
                    <tr class="table-light" style="cursor:pointer;"
                        onclick="window.location='index.php?estado=<?php echo $estado; ?>&periodo=<?php echo $periodo_sel; ?>&uid=<?php echo $u['UID']; ?>&ecat=<?php echo $rc['CID']; ?>'">
                        <td class="ps-5">
                            <i class="bi bi-<?php echo $cat_expandida ? 'folder2-open' : 'folder'; ?> me-1 text-primary"></i>
                            <small class="text-muted"><?php echo htmlspecialchars($rc['CAT_CODIGO']); ?></small>
                            <?php echo htmlspecialchars($rc['CATEGORIA']); ?>
                        </td>
                        <td class="text-end">$ <?php echo number_format((float)$rc['APROBADO'], 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format((float)$rc['COMPROMETIDO'], 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format((float)$rc['EJECUTADO'], 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format((float)$rc['DISPONIBLE'], 2, ',', '.'); ?></td>
                        <td class="text-center"><span class="<?php echo $cpct_class; ?>"><?php echo number_format($cpct, 1); ?>%</span></td>
                        <td></td>
                    </tr>
<?php
            // NIVEL 3: Ítems de la categoría expandida
            if ($cat_expandida):
                $sql_items = "SELECT ig.codigo AS ITEM_CODIGO, ig.nombre AS ITEM,
                                     pa.monto_aprobado AS APROBADO, pa.monto_comprometido AS COMPROMETIDO,
                                     pa.monto_ejecutado AS EJECUTADO,
                                     (pa.monto_aprobado - pa.monto_comprometido - pa.monto_ejecutado) AS DISPONIBLE,
                                     ROUND(pa.monto_ejecutado / NULLIF(pa.monto_aprobado, 0) * 100, 2) AS PCT
                              FROM ciclo_partida pa
                              JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                              JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
                              WHERE pr.id_periodo = '$periodo_sel' AND pr.id_unidad = '{$u['UID']}' AND pa.id_categoria = '{$rc['CID']}'
                              ORDER BY ig.codigo;";
                $res_items = sql_do_query($sql_items, $db, $usu);
                while ($ri = sql_do_array($res_items)):
                    $ipct = (float)($ri['PCT'] ?? 0);
                    $ipct_class = ($ipct >= 95) ? 'text-danger fw-bold' : (($ipct >= 80) ? 'text-warning fw-bold' : 'text-success');
                    $irow = ($ipct >= 95) ? 'table-danger' : (($ipct >= 80) ? 'table-warning' : '');
?>
                    <tr class="<?php echo $irow; ?>">
                        <td class="ps-5 ps-lg-5">
                            <span class="ms-4"><i class="bi bi-tag me-1 text-muted"></i>
                            <small class="text-muted"><?php echo htmlspecialchars($ri['ITEM_CODIGO']); ?></small>
                            <?php echo htmlspecialchars($ri['ITEM']); ?></span>
                        </td>
                        <td class="text-end small">$ <?php echo number_format((float)$ri['APROBADO'], 2, ',', '.'); ?></td>
                        <td class="text-end small">$ <?php echo number_format((float)$ri['COMPROMETIDO'], 2, ',', '.'); ?></td>
                        <td class="text-end small">$ <?php echo number_format((float)$ri['EJECUTADO'], 2, ',', '.'); ?></td>
                        <td class="text-end small">$ <?php echo number_format((float)$ri['DISPONIBLE'], 2, ',', '.'); ?></td>
                        <td class="text-center small"><span class="<?php echo $ipct_class; ?>"><?php echo number_format($ipct, 1); ?>%</span></td>
                        <td></td>
                    </tr>
<?php           endwhile;
            endif;
        endwhile;
    endif;
endforeach; ?>

<?php if (!empty($unidades)):
    $gt_pct = ($gt_apro > 0) ? round($gt_ejec / $gt_apro * 100, 2) : 0;
?>
                    <tr class="table-dark fw-bold">
                        <td>TOTALES GENERALES</td>
                        <td class="text-end">$ <?php echo number_format($gt_apro, 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format($gt_comp, 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format($gt_ejec, 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format($gt_disp, 2, ',', '.'); ?></td>
                        <td class="text-center"><?php echo number_format($gt_pct, 1); ?>%</td>
                        <td></td>
                    </tr>
<?php endif; ?>
<?php if (empty($unidades)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Sin datos para el período seleccionado.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
SESSION['ciclo_rol_efectivo'] ?? '';
$unidad_usuario = $_SESSION['id_unidad'] ?? '';

// ── Filtros ──────────────────────────────────────────────────────────────────
$periodo_sel = Enmascarar_get('periodo');
$unidad_sel  = Enmascarar_get('unidad');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['filtro_periodo'])) $periodo_sel = Enmascarar_post('filtro_periodo');
    if (!empty($_POST['filtro_unidad']))  $unidad_sel  = Enmascarar_post('filtro_unidad');
}

// Default: período abierto
if (empty($periodo_sel) || $periodo_sel === 'null') {
    $row_ab = sql_do_array(sql_do_query("SELECT id FROM ciclo_periodo_fiscal WHERE estado = 'abierto' LIMIT 1;", $db, $usu));
    $periodo_sel = $row_ab['ID'] ?? '';
}

// Restricción por rol
$where_unidad = '';
if (in_array($rol_usuario, ['operador', 'jefe_unidad']) && !empty($unidad_usuario)) {
    $where_unidad = "AND pr.id_unidad = '$unidad_usuario'";
    $unidad_sel = $unidad_usuario;
} elseif ($rol_usuario === 'gerente' && !empty($unidad_usuario)) {
    $where_unidad = "AND (pr.id_unidad = '$unidad_usuario'
                     OR pr.id_unidad IN (SELECT id FROM ciclo_unidad_organizativa WHERE id_padre = '$unidad_usuario'))";
} else {
    if (!empty($unidad_sel) && $unidad_sel !== 'null') {
        $where_unidad = "AND pr.id_unidad = '$unidad_sel'";
    }
}

// ── Exportar CSV ─────────────────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv' && !empty($periodo_sel)) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ejecucion_presupuestaria_' . $periodo_sel . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Unidad', 'Categoría', 'Código Ítem', 'Ítem', 'Aprobado', 'Comprometido', 'Ejecutado', 'Disponible', '% Ejecución'], ';');

    $sql_csv = "SELECT uo.nombre AS UNIDAD, cg.nombre AS CATEGORIA, ig.codigo AS ITEM_CODIGO, ig.nombre AS ITEM,
                       pa.monto_aprobado, pa.monto_comprometido, pa.monto_ejecutado,
                       (pa.monto_aprobado - pa.monto_comprometido - pa.monto_ejecutado) AS disponible,
                       ROUND(pa.monto_ejecutado / NULLIF(pa.monto_aprobado, 0) * 100, 2) AS pct
                FROM ciclo_partida pa
                JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
                JOIN ciclo_categoria_gasto cg ON pa.id_categoria = cg.id
                JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
                WHERE pr.id_periodo = '$periodo_sel' $where_unidad
                ORDER BY uo.nombre, cg.orden, ig.codigo;";
    $res_csv = sql_do_query($sql_csv, $db, $usu);
    while ($rc = sql_do_array($res_csv)) {
        fputcsv($out, [
            $rc['UNIDAD'], $rc['CATEGORIA'], $rc['ITEM_CODIGO'], $rc['ITEM'],
            number_format((float)$rc['MONTO_APROBADO'], 2, ',', '.'),
            number_format((float)$rc['MONTO_COMPROMETIDO'], 2, ',', '.'),
            number_format((float)$rc['MONTO_EJECUTADO'], 2, ',', '.'),
            number_format((float)$rc['DISPONIBLE'], 2, ',', '.'),
            $rc['PCT'] . '%'
        ], ';');
    }
    fclose($out);
    exit;
}

// ── Datos nivel 1: por unidad ────────────────────────────────────────────────
$sql_unidades = "SELECT uo.id AS UID, uo.nombre AS UNIDAD,
                        SUM(pa.monto_aprobado) AS APROBADO,
                        SUM(pa.monto_comprometido) AS COMPROMETIDO,
                        SUM(pa.monto_ejecutado) AS EJECUTADO,
                        SUM(pa.monto_aprobado - pa.monto_comprometido - pa.monto_ejecutado) AS DISPONIBLE,
                        ROUND(SUM(pa.monto_ejecutado) / NULLIF(SUM(pa.monto_aprobado), 0) * 100, 2) AS PCT
                 FROM ciclo_partida pa
                 JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                 JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
                 WHERE pr.id_periodo = '$periodo_sel' $where_unidad
                 GROUP BY uo.id, uo.nombre
                 ORDER BY uo.nombre;";

// Combos
$sql_combo_per = "SELECT id AS ID, CONCAT(anio, ' (', estado, ')') AS NOMBRE FROM ciclo_periodo_fiscal ORDER BY anio DESC;";
$sql_combo_uni = "SELECT id AS ID, nombre AS NOMBRE FROM ciclo_unidad_organizativa WHERE 1=1 ORDER BY nombre;";

// Unidad expandida
$expand_uid = Enmascarar_get('uid');
$expand_cat = Enmascarar_get('ecat');
?>

<?php echo generarTituloSeccion('Ejecución Presupuestaria', 'bi bi-bar-chart-line'); ?>

<!-- FILTROS -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Período</label>
                <?php echo imp_combo_w_oc('filtro_periodo', $sql_combo_per, $db, $usu, $periodo_sel); ?>
            </div>
            <?php if (in_array($rol_usuario, ['director', 'admin'])): ?>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Unidad</label>
                <?php echo imp_combo_w_oc('filtro_unidad', $sql_combo_uni, $db, $usu, $unidad_sel); ?>
            </div>
            <?php endif; ?>
            <div class="col-md-3">
                <a href="index.php?estado=<?php echo $estado; ?>&periodo=<?php echo $periodo_sel; ?>&unidad=<?php echo $unidad_sel; ?>&export=csv"
                   class="btn btn-outline-success btn-sm">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>Exportar CSV
                </a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($periodo_sel)): ?>
<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Seleccione un período.</div>
<?php else:
    $res_unidades = sql_do_query($sql_unidades, $db, $usu);
    $unidades = [];
    $gt_apro = 0; $gt_comp = 0; $gt_ejec = 0; $gt_disp = 0;
    while ($ru = sql_do_array($res_unidades)) {
        $unidades[] = $ru;
        $gt_apro += (float)$ru['APROBADO'];
        $gt_comp += (float)$ru['COMPROMETIDO'];
        $gt_ejec += (float)$ru['EJECUTADO'];
        $gt_disp += (float)$ru['DISPONIBLE'];
    }
?>

<!-- NIVEL 1: UNIDADES -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-pade mb-0">
                <?php echo generarTablaHeader(['Unidad', 'Aprobado', 'Comprometido', 'Ejecutado', 'Disponible', '% Ejec.', '']); ?>
                <tbody>
<?php foreach ($unidades as $u):
    $pct = (float)($u['PCT'] ?? 0);
    $pct_class = 'text-success';
    if ($pct >= 95) $pct_class = 'text-danger fw-bold';
    elseif ($pct >= 80) $pct_class = 'text-warning fw-bold';
    $es_expandida = ($expand_uid == $u['UID']);
?>
                    <tr style="cursor:pointer;" class="<?php echo $es_expandida ? 'table-active' : ''; ?>"
                        onclick="window.location='index.php?estado=<?php echo $estado; ?>&periodo=<?php echo $periodo_sel; ?>&uid=<?php echo $u['UID']; ?>'">
                        <td class="fw-bold">
                            <i class="bi bi-<?php echo $es_expandida ? 'dash-square' : 'plus-square'; ?> me-1"></i>
                            <?php echo htmlspecialchars($u['UNIDAD']); ?>
                        </td>
                        <td class="text-end">$ <?php echo number_format((float)$u['APROBADO'], 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format((float)$u['COMPROMETIDO'], 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format((float)$u['EJECUTADO'], 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format((float)$u['DISPONIBLE'], 2, ',', '.'); ?></td>
                        <td class="text-center"><span class="<?php echo $pct_class; ?>"><?php echo number_format($pct, 1); ?>%</span></td>
                        <td class="text-center"><i class="bi bi-chevron-<?php echo $es_expandida ? 'down' : 'right'; ?>"></i></td>
                    </tr>

<?php
    // NIVEL 2: Categorías de la unidad expandida
    if ($es_expandida):
        $sql_cats = "SELECT cg.id AS CID, cg.codigo AS CAT_CODIGO, cg.nombre AS CATEGORIA,
                            SUM(pa.monto_aprobado) AS APROBADO, SUM(pa.monto_comprometido) AS COMPROMETIDO,
                            SUM(pa.monto_ejecutado) AS EJECUTADO,
                            SUM(pa.monto_aprobado - pa.monto_comprometido - pa.monto_ejecutado) AS DISPONIBLE,
                            ROUND(SUM(pa.monto_ejecutado) / NULLIF(SUM(pa.monto_aprobado), 0) * 100, 2) AS PCT
                     FROM ciclo_partida pa
                     JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                     JOIN ciclo_categoria_gasto cg ON pa.id_categoria = cg.id
                     WHERE pr.id_periodo = '$periodo_sel' AND pr.id_unidad = '{$u['UID']}'
                     GROUP BY cg.id, cg.codigo, cg.nombre
                     ORDER BY cg.orden;";
        $res_cats = sql_do_query($sql_cats, $db, $usu);
        while ($rc = sql_do_array($res_cats)):
            $cpct = (float)($rc['PCT'] ?? 0);
            $cpct_class = ($cpct >= 95) ? 'text-danger fw-bold' : (($cpct >= 80) ? 'text-warning fw-bold' : 'text-success');
            $cat_expandida = ($expand_cat == $rc['CID']);
?>
                    <tr class="table-light" style="cursor:pointer;"
                        onclick="window.location='index.php?estado=<?php echo $estado; ?>&periodo=<?php echo $periodo_sel; ?>&uid=<?php echo $u['UID']; ?>&ecat=<?php echo $rc['CID']; ?>'">
                        <td class="ps-5">
                            <i class="bi bi-<?php echo $cat_expandida ? 'folder2-open' : 'folder'; ?> me-1 text-primary"></i>
                            <small class="text-muted"><?php echo htmlspecialchars($rc['CAT_CODIGO']); ?></small>
                            <?php echo htmlspecialchars($rc['CATEGORIA']); ?>
                        </td>
                        <td class="text-end">$ <?php echo number_format((float)$rc['APROBADO'], 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format((float)$rc['COMPROMETIDO'], 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format((float)$rc['EJECUTADO'], 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format((float)$rc['DISPONIBLE'], 2, ',', '.'); ?></td>
                        <td class="text-center"><span class="<?php echo $cpct_class; ?>"><?php echo number_format($cpct, 1); ?>%</span></td>
                        <td></td>
                    </tr>
<?php
            // NIVEL 3: Ítems de la categoría expandida
            if ($cat_expandida):
                $sql_items = "SELECT ig.codigo AS ITEM_CODIGO, ig.nombre AS ITEM,
                                     pa.monto_aprobado AS APROBADO, pa.monto_comprometido AS COMPROMETIDO,
                                     pa.monto_ejecutado AS EJECUTADO,
                                     (pa.monto_aprobado - pa.monto_comprometido - pa.monto_ejecutado) AS DISPONIBLE,
                                     ROUND(pa.monto_ejecutado / NULLIF(pa.monto_aprobado, 0) * 100, 2) AS PCT
                              FROM ciclo_partida pa
                              JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                              JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
                              WHERE pr.id_periodo = '$periodo_sel' AND pr.id_unidad = '{$u['UID']}' AND pa.id_categoria = '{$rc['CID']}'
                              ORDER BY ig.codigo;";
                $res_items = sql_do_query($sql_items, $db, $usu);
                while ($ri = sql_do_array($res_items)):
                    $ipct = (float)($ri['PCT'] ?? 0);
                    $ipct_class = ($ipct >= 95) ? 'text-danger fw-bold' : (($ipct >= 80) ? 'text-warning fw-bold' : 'text-success');
                    $irow = ($ipct >= 95) ? 'table-danger' : (($ipct >= 80) ? 'table-warning' : '');
?>
                    <tr class="<?php echo $irow; ?>">
                        <td class="ps-5 ps-lg-5">
                            <span class="ms-4"><i class="bi bi-tag me-1 text-muted"></i>
                            <small class="text-muted"><?php echo htmlspecialchars($ri['ITEM_CODIGO']); ?></small>
                            <?php echo htmlspecialchars($ri['ITEM']); ?></span>
                        </td>
                        <td class="text-end small">$ <?php echo number_format((float)$ri['APROBADO'], 2, ',', '.'); ?></td>
                        <td class="text-end small">$ <?php echo number_format((float)$ri['COMPROMETIDO'], 2, ',', '.'); ?></td>
                        <td class="text-end small">$ <?php echo number_format((float)$ri['EJECUTADO'], 2, ',', '.'); ?></td>
                        <td class="text-end small">$ <?php echo number_format((float)$ri['DISPONIBLE'], 2, ',', '.'); ?></td>
                        <td class="text-center small"><span class="<?php echo $ipct_class; ?>"><?php echo number_format($ipct, 1); ?>%</span></td>
                        <td></td>
                    </tr>
<?php           endwhile;
            endif;
        endwhile;
    endif;
endforeach; ?>

<?php if (!empty($unidades)):
    $gt_pct = ($gt_apro > 0) ? round($gt_ejec / $gt_apro * 100, 2) : 0;
?>
                    <tr class="table-dark fw-bold">
                        <td>TOTALES GENERALES</td>
                        <td class="text-end">$ <?php echo number_format($gt_apro, 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format($gt_comp, 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format($gt_ejec, 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format($gt_disp, 2, ',', '.'); ?></td>
                        <td class="text-center"><?php echo number_format($gt_pct, 1); ?>%</td>
                        <td></td>
                    </tr>
<?php endif; ?>
<?php if (empty($unidades)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Sin datos para el período seleccionado.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
SESSION['ciclo_rol_efectivo'] ?? '';
$unidad_usuario = $_SESSION['id_unidad'] ?? '';

// ── Filtros ──────────────────────────────────────────────────────────────────
$periodo_sel = Enmascarar_get('periodo');
$unidad_sel  = Enmascarar_get('unidad');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['filtro_periodo'])) $periodo_sel = Enmascarar_post('filtro_periodo');
    if (!empty($_POST['filtro_unidad']))  $unidad_sel  = Enmascarar_post('filtro_unidad');
}

// Default: período abierto
if (empty($periodo_sel) || $periodo_sel === 'null') {
    $row_ab = sql_do_array(sql_do_query("SELECT id FROM ciclo_periodo_fiscal WHERE estado = 'abierto' LIMIT 1;", $db, $usu));
    $periodo_sel = $row_ab['ID'] ?? '';
}

// Restricción por rol
$where_unidad = '';
if (in_array($rol_usuario, ['operador', 'jefe_unidad']) && !empty($unidad_usuario)) {
    $where_unidad = "AND pr.id_unidad = '$unidad_usuario'";
    $unidad_sel = $unidad_usuario;
} elseif ($rol_usuario === 'gerente' && !empty($unidad_usuario)) {
    $where_unidad = "AND (pr.id_unidad = '$unidad_usuario'
                     OR pr.id_unidad IN (SELECT id FROM ciclo_unidad_organizativa WHERE id_padre = '$unidad_usuario'))";
} else {
    if (!empty($unidad_sel) && $unidad_sel !== 'null') {
        $where_unidad = "AND pr.id_unidad = '$unidad_sel'";
    }
}

// ── Exportar CSV ─────────────────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv' && !empty($periodo_sel)) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ejecucion_presupuestaria_' . $periodo_sel . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Unidad', 'Categoría', 'Código Ítem', 'Ítem', 'Aprobado', 'Comprometido', 'Ejecutado', 'Disponible', '% Ejecución'], ';');

    $sql_csv = "SELECT uo.nombre AS UNIDAD, cg.nombre AS CATEGORIA, ig.codigo AS ITEM_CODIGO, ig.nombre AS ITEM,
                       pa.monto_aprobado, pa.monto_comprometido, pa.monto_ejecutado,
                       (pa.monto_aprobado - pa.monto_comprometido - pa.monto_ejecutado) AS disponible,
                       ROUND(pa.monto_ejecutado / NULLIF(pa.monto_aprobado, 0) * 100, 2) AS pct
                FROM ciclo_partida pa
                JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
                JOIN ciclo_categoria_gasto cg ON pa.id_categoria = cg.id
                JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
                WHERE pr.id_periodo = '$periodo_sel' $where_unidad
                ORDER BY uo.nombre, cg.orden, ig.codigo;";
    $res_csv = sql_do_query($sql_csv, $db, $usu);
    while ($rc = sql_do_array($res_csv)) {
        fputcsv($out, [
            $rc['UNIDAD'], $rc['CATEGORIA'], $rc['ITEM_CODIGO'], $rc['ITEM'],
            number_format((float)$rc['MONTO_APROBADO'], 2, ',', '.'),
            number_format((float)$rc['MONTO_COMPROMETIDO'], 2, ',', '.'),
            number_format((float)$rc['MONTO_EJECUTADO'], 2, ',', '.'),
            number_format((float)$rc['DISPONIBLE'], 2, ',', '.'),
            $rc['PCT'] . '%'
        ], ';');
    }
    fclose($out);
    exit;
}

// ── Datos nivel 1: por unidad ────────────────────────────────────────────────
$sql_unidades = "SELECT uo.id AS UID, uo.nombre AS UNIDAD,
                        SUM(pa.monto_aprobado) AS APROBADO,
                        SUM(pa.monto_comprometido) AS COMPROMETIDO,
                        SUM(pa.monto_ejecutado) AS EJECUTADO,
                        SUM(pa.monto_aprobado - pa.monto_comprometido - pa.monto_ejecutado) AS DISPONIBLE,
                        ROUND(SUM(pa.monto_ejecutado) / NULLIF(SUM(pa.monto_aprobado), 0) * 100, 2) AS PCT
                 FROM ciclo_partida pa
                 JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                 JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
                 WHERE pr.id_periodo = '$periodo_sel' $where_unidad
                 GROUP BY uo.id, uo.nombre
                 ORDER BY uo.nombre;";

// Combos
$sql_combo_per = "SELECT id AS ID, CONCAT(anio, ' (', estado, ')') AS NOMBRE FROM ciclo_periodo_fiscal ORDER BY anio DESC;";
$sql_combo_uni = "SELECT id AS ID, nombre AS NOMBRE FROM ciclo_unidad_organizativa WHERE 1=1 ORDER BY nombre;";

// Unidad expandida
$expand_uid = Enmascarar_get('uid');
$expand_cat = Enmascarar_get('ecat');
?>

<?php echo generarTituloSeccion('Ejecución Presupuestaria', 'bi bi-bar-chart-line'); ?>

<!-- FILTROS -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Período</label>
                <?php echo imp_combo_w_oc('filtro_periodo', $sql_combo_per, $db, $usu, $periodo_sel); ?>
            </div>
            <?php if (in_array($rol_usuario, ['director', 'admin'])): ?>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Unidad</label>
                <?php echo imp_combo_w_oc('filtro_unidad', $sql_combo_uni, $db, $usu, $unidad_sel); ?>
            </div>
            <?php endif; ?>
            <div class="col-md-3">
                <a href="index.php?estado=<?php echo $estado; ?>&periodo=<?php echo $periodo_sel; ?>&unidad=<?php echo $unidad_sel; ?>&export=csv"
                   class="btn btn-outline-success btn-sm">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>Exportar CSV
                </a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($periodo_sel)): ?>
<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Seleccione un período.</div>
<?php else:
    $res_unidades = sql_do_query($sql_unidades, $db, $usu);
    $unidades = [];
    $gt_apro = 0; $gt_comp = 0; $gt_ejec = 0; $gt_disp = 0;
    while ($ru = sql_do_array($res_unidades)) {
        $unidades[] = $ru;
        $gt_apro += (float)$ru['APROBADO'];
        $gt_comp += (float)$ru['COMPROMETIDO'];
        $gt_ejec += (float)$ru['EJECUTADO'];
        $gt_disp += (float)$ru['DISPONIBLE'];
    }
?>

<!-- NIVEL 1: UNIDADES -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-pade mb-0">
                <?php echo generarTablaHeader(['Unidad', 'Aprobado', 'Comprometido', 'Ejecutado', 'Disponible', '% Ejec.', '']); ?>
                <tbody>
<?php foreach ($unidades as $u):
    $pct = (float)($u['PCT'] ?? 0);
    $pct_class = 'text-success';
    if ($pct >= 95) $pct_class = 'text-danger fw-bold';
    elseif ($pct >= 80) $pct_class = 'text-warning fw-bold';
    $es_expandida = ($expand_uid == $u['UID']);
?>
                    <tr style="cursor:pointer;" class="<?php echo $es_expandida ? 'table-active' : ''; ?>"
                        onclick="window.location='index.php?estado=<?php echo $estado; ?>&periodo=<?php echo $periodo_sel; ?>&uid=<?php echo $u['UID']; ?>'">
                        <td class="fw-bold">
                            <i class="bi bi-<?php echo $es_expandida ? 'dash-square' : 'plus-square'; ?> me-1"></i>
                            <?php echo htmlspecialchars($u['UNIDAD']); ?>
                        </td>
                        <td class="text-end">$ <?php echo number_format((float)$u['APROBADO'], 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format((float)$u['COMPROMETIDO'], 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format((float)$u['EJECUTADO'], 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format((float)$u['DISPONIBLE'], 2, ',', '.'); ?></td>
                        <td class="text-center"><span class="<?php echo $pct_class; ?>"><?php echo number_format($pct, 1); ?>%</span></td>
                        <td class="text-center"><i class="bi bi-chevron-<?php echo $es_expandida ? 'down' : 'right'; ?>"></i></td>
                    </tr>

<?php
    // NIVEL 2: Categorías de la unidad expandida
    if ($es_expandida):
        $sql_cats = "SELECT cg.id AS CID, cg.codigo AS CAT_CODIGO, cg.nombre AS CATEGORIA,
                            SUM(pa.monto_aprobado) AS APROBADO, SUM(pa.monto_comprometido) AS COMPROMETIDO,
                            SUM(pa.monto_ejecutado) AS EJECUTADO,
                            SUM(pa.monto_aprobado - pa.monto_comprometido - pa.monto_ejecutado) AS DISPONIBLE,
                            ROUND(SUM(pa.monto_ejecutado) / NULLIF(SUM(pa.monto_aprobado), 0) * 100, 2) AS PCT
                     FROM ciclo_partida pa
                     JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                     JOIN ciclo_categoria_gasto cg ON pa.id_categoria = cg.id
                     WHERE pr.id_periodo = '$periodo_sel' AND pr.id_unidad = '{$u['UID']}'
                     GROUP BY cg.id, cg.codigo, cg.nombre
                     ORDER BY cg.orden;";
        $res_cats = sql_do_query($sql_cats, $db, $usu);
        while ($rc = sql_do_array($res_cats)):
            $cpct = (float)($rc['PCT'] ?? 0);
            $cpct_class = ($cpct >= 95) ? 'text-danger fw-bold' : (($cpct >= 80) ? 'text-warning fw-bold' : 'text-success');
            $cat_expandida = ($expand_cat == $rc['CID']);
?>
                    <tr class="table-light" style="cursor:pointer;"
                        onclick="window.location='index.php?estado=<?php echo $estado; ?>&periodo=<?php echo $periodo_sel; ?>&uid=<?php echo $u['UID']; ?>&ecat=<?php echo $rc['CID']; ?>'">
                        <td class="ps-5">
                            <i class="bi bi-<?php echo $cat_expandida ? 'folder2-open' : 'folder'; ?> me-1 text-primary"></i>
                            <small class="text-muted"><?php echo htmlspecialchars($rc['CAT_CODIGO']); ?></small>
                            <?php echo htmlspecialchars($rc['CATEGORIA']); ?>
                        </td>
                        <td class="text-end">$ <?php echo number_format((float)$rc['APROBADO'], 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format((float)$rc['COMPROMETIDO'], 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format((float)$rc['EJECUTADO'], 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format((float)$rc['DISPONIBLE'], 2, ',', '.'); ?></td>
                        <td class="text-center"><span class="<?php echo $cpct_class; ?>"><?php echo number_format($cpct, 1); ?>%</span></td>
                        <td></td>
                    </tr>
<?php
            // NIVEL 3: Ítems de la categoría expandida
            if ($cat_expandida):
                $sql_items = "SELECT ig.codigo AS ITEM_CODIGO, ig.nombre AS ITEM,
                                     pa.monto_aprobado AS APROBADO, pa.monto_comprometido AS COMPROMETIDO,
                                     pa.monto_ejecutado AS EJECUTADO,
                                     (pa.monto_aprobado - pa.monto_comprometido - pa.monto_ejecutado) AS DISPONIBLE,
                                     ROUND(pa.monto_ejecutado / NULLIF(pa.monto_aprobado, 0) * 100, 2) AS PCT
                              FROM ciclo_partida pa
                              JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                              JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
                              WHERE pr.id_periodo = '$periodo_sel' AND pr.id_unidad = '{$u['UID']}' AND pa.id_categoria = '{$rc['CID']}'
                              ORDER BY ig.codigo;";
                $res_items = sql_do_query($sql_items, $db, $usu);
                while ($ri = sql_do_array($res_items)):
                    $ipct = (float)($ri['PCT'] ?? 0);
                    $ipct_class = ($ipct >= 95) ? 'text-danger fw-bold' : (($ipct >= 80) ? 'text-warning fw-bold' : 'text-success');
                    $irow = ($ipct >= 95) ? 'table-danger' : (($ipct >= 80) ? 'table-warning' : '');
?>
                    <tr class="<?php echo $irow; ?>">
                        <td class="ps-5 ps-lg-5">
                            <span class="ms-4"><i class="bi bi-tag me-1 text-muted"></i>
                            <small class="text-muted"><?php echo htmlspecialchars($ri['ITEM_CODIGO']); ?></small>
                            <?php echo htmlspecialchars($ri['ITEM']); ?></span>
                        </td>
                        <td class="text-end small">$ <?php echo number_format((float)$ri['APROBADO'], 2, ',', '.'); ?></td>
                        <td class="text-end small">$ <?php echo number_format((float)$ri['COMPROMETIDO'], 2, ',', '.'); ?></td>
                        <td class="text-end small">$ <?php echo number_format((float)$ri['EJECUTADO'], 2, ',', '.'); ?></td>
                        <td class="text-end small">$ <?php echo number_format((float)$ri['DISPONIBLE'], 2, ',', '.'); ?></td>
                        <td class="text-center small"><span class="<?php echo $ipct_class; ?>"><?php echo number_format($ipct, 1); ?>%</span></td>
                        <td></td>
                    </tr>
<?php           endwhile;
            endif;
        endwhile;
    endif;
endforeach; ?>

<?php if (!empty($unidades)):
    $gt_pct = ($gt_apro > 0) ? round($gt_ejec / $gt_apro * 100, 2) : 0;
?>
                    <tr class="table-dark fw-bold">
                        <td>TOTALES GENERALES</td>
                        <td class="text-end">$ <?php echo number_format($gt_apro, 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format($gt_comp, 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format($gt_ejec, 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format($gt_disp, 2, ',', '.'); ?></td>
                        <td class="text-center"><?php echo number_format($gt_pct, 1); ?>%</td>
                        <td></td>
                    </tr>
<?php endif; ?>
<?php if (empty($unidades)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Sin datos para el período seleccionado.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
