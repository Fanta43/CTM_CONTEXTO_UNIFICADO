<?php
/**
 * ============================================================================
 * Comparativo de Presupuestos
 * ============================================================================
 * ID menú: 204 | Tablas: ciclo_partida, ciclo_presupuesto, ciclo_item_gasto,
 *                         pres_k2b_sr_logs, pres_k2b_presupuestos
 * Compara dos presupuestos (A y B) lado a lado, agrupados por ítem de gasto.
 * Usa LEFT JOIN para soportar id_categoria NULL (datos K2B).
 * Integra consumo real K2B OC como ejecución efectiva.
 * Resalta variaciones >20% (naranja) y >50% (rojo). Exportable a CSV.
 * ============================================================================
 */

$db  = '1';
$msg = '';

// ── Selección de presupuestos A y B ──────────────────────────────────────────
$pres_a = Enmascarar_get('id');
$pres_b = Enmascarar_get('id_b');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['pres_a'])) $pres_a = Enmascarar_post('pres_a');
    if (!empty($_POST['pres_b'])) $pres_b = Enmascarar_post('pres_b');
}

// Si llega solo A sin B, buscar automáticamente el del año anterior de la misma unidad
if (!empty($pres_a) && (empty($pres_b) || $pres_b === 'null')) {
    $row_a_tmp = sql_do_array(sql_do_query(
        "SELECT pr.id_unidad, pf.anio FROM ciclo_presupuesto pr
         JOIN ciclo_periodo_fiscal pf ON pr.id_periodo = pf.id WHERE pr.id = '$pres_a';", $db, $usu));
    if ($row_a_tmp) {
        $anio_ant = (int)$row_a_tmp['ANIO'] - 1;
        $uid = $row_a_tmp['ID_UNIDAD'];
        $row_b_auto = sql_do_array(sql_do_query(
            "SELECT pr.id FROM ciclo_presupuesto pr
             JOIN ciclo_periodo_fiscal pf ON pr.id_periodo = pf.id
             WHERE pf.anio = '$anio_ant' AND pr.id_unidad = '$uid'
             ORDER BY FIELD(pr.estado, 'aprobado', 'en_revision', 'borrador') LIMIT 1;", $db, $usu));
        if ($row_b_auto) $pres_b = $row_b_auto['ID'];
    }
}

// ── Combo de todos los presupuestos ──────────────────────────────────────────
$sql_combo_pres = "SELECT pr.id AS ID, CONCAT(pf.anio, ' - ', uo.nombre, ' (', pr.estado, ')') AS NOMBRE
                   FROM ciclo_presupuesto pr
                   JOIN ciclo_periodo_fiscal pf ON pr.id_periodo = pf.id
                   JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
                   ORDER BY pf.anio DESC, uo.nombre;";

// ── Cargar datos de ambos presupuestos ───────────────────────────────────────
$row_a = null; $row_b = null;
$label_a = ''; $label_b = '';

if (!empty($pres_a) && $pres_a !== 'null') {
    $row_a = sql_do_array(sql_do_query(
        "SELECT pr.*, pf.anio, uo.nombre AS UNIDAD, uo.cc AS UNIDAD_CC FROM ciclo_presupuesto pr
         JOIN ciclo_periodo_fiscal pf ON pr.id_periodo = pf.id
         JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id WHERE pr.id = '$pres_a';", $db, $usu));
    if ($row_a) $label_a = $row_a['ANIO'] . ' - ' . $row_a['UNIDAD'];
}
if (!empty($pres_b) && $pres_b !== 'null') {
    $row_b = sql_do_array(sql_do_query(
        "SELECT pr.*, pf.anio, uo.nombre AS UNIDAD, uo.cc AS UNIDAD_CC FROM ciclo_presupuesto pr
         JOIN ciclo_periodo_fiscal pf ON pr.id_periodo = pf.id
         JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id WHERE pr.id = '$pres_b';", $db, $usu));
    if ($row_b) $label_b = $row_b['ANIO'] . ' - ' . $row_b['UNIDAD'];
}

// ── Query comparativo: agrupado por ítem de gasto (sin depender de id_categoria) ─
// Pre-agrega por ítem en subqueries para evitar cross-product con múltiples partidas
$sql_comp = '';
$tiene_datos = false;
if ($row_a && $row_b) {
    $sql_comp = "SELECT ig.codigo AS ITEM_CODIGO, ig.nombre AS ITEM_NOMBRE,
                        COALESCE(sa.APROBADO, 0)     AS APROBADO_A,
                        COALESCE(sa.COMPROMETIDO, 0) AS COMPROMETIDO_A,
                        COALESCE(sa.EJECUTADO, 0)    AS EJECUTADO_A,
                        COALESCE(sb.APROBADO, 0)     AS APROBADO_B,
                        COALESCE(sb.COMPROMETIDO, 0) AS COMPROMETIDO_B,
                        COALESCE(sb.EJECUTADO, 0)    AS EJECUTADO_B
                 FROM (
                     SELECT DISTINCT id_item FROM ciclo_partida WHERE id_presupuesto IN ('$pres_a', '$pres_b')
                 ) items
                 JOIN ciclo_item_gasto ig ON items.id_item = ig.id
                 LEFT JOIN (
                     SELECT id_item, SUM(monto_aprobado) AS APROBADO, SUM(monto_comprometido) AS COMPROMETIDO, SUM(monto_ejecutado) AS EJECUTADO
                     FROM ciclo_partida WHERE id_presupuesto = '$pres_a' GROUP BY id_item
                 ) sa ON sa.id_item = items.id_item
                 LEFT JOIN (
                     SELECT id_item, SUM(monto_aprobado) AS APROBADO, SUM(monto_comprometido) AS COMPROMETIDO, SUM(monto_ejecutado) AS EJECUTADO
                     FROM ciclo_partida WHERE id_presupuesto = '$pres_b' GROUP BY id_item
                 ) sb ON sb.id_item = items.id_item
                 ORDER BY ig.codigo;";
    $tiene_datos = true;
} elseif ($row_a) {
    $sql_comp = "SELECT ig.codigo AS ITEM_CODIGO, ig.nombre AS ITEM_NOMBRE,
                        SUM(pa.monto_aprobado)     AS APROBADO_A,
                        SUM(pa.monto_comprometido) AS COMPROMETIDO_A,
                        SUM(pa.monto_ejecutado)    AS EJECUTADO_A,
                        0 AS APROBADO_B, 0 AS COMPROMETIDO_B, 0 AS EJECUTADO_B
                 FROM ciclo_partida pa
                 JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
                 WHERE pa.id_presupuesto = '$pres_a'
                 GROUP BY ig.codigo, ig.nombre
                 ORDER BY ig.codigo;";
    $tiene_datos = true;
}

// ── K2B OC por ítem con fallback (para A y B) ────────────────────────────────
function cargar_k2b_oc($anio, $cc, $db, $usu) {
    $result = [];
    if (empty($anio) || empty($cc)) return $result;
    $sql = "SELECT ig.codigo AS ITEM_CODIGO,
                   COALESCE(oc_item.total_oc, 0) AS TOTAL_OC,
                   COUNT(DISTINCT sr.codigo) AS CANT_SR
            FROM pres_k2b_sr_logs sr
            INNER JOIN ciclo_item_gasto ig ON sr.esquema_gastoid = ig.codigo
            LEFT JOIN (
                SELECT esquema_gastoid, SUM(sr_monto) AS total_oc FROM (
                    SELECT esquema_gastoid, codigo,
                           CASE WHEN SUM(monto_linea_oc_usd) > 0 THEN SUM(monto_linea_oc_usd)
                                ELSE MAX(importe_total) END AS sr_monto
                    FROM pres_k2b_sr_logs
                    WHERE esquema_anio = '$anio' AND esquema_cc = '$cc'
                          AND codigo IS NOT NULL AND codigo != ''
                    GROUP BY esquema_gastoid, codigo
                ) sr_oc GROUP BY esquema_gastoid
            ) oc_item ON oc_item.esquema_gastoid = ig.codigo
            WHERE sr.esquema_anio = '$anio' AND sr.esquema_cc = '$cc'
                  AND sr.codigo IS NOT NULL AND sr.codigo != ''
            GROUP BY ig.codigo";
    $res = sql_do_query($sql, $db, $usu);
    while ($r = sql_do_array($res)) {
        $result[$r['ITEM_CODIGO']] = $r;
    }
    return $result;
}

function cargar_k2b_ppto($anio, $db, $usu) {
    $result = [];
    $anio_corto = !empty($anio) ? substr($anio, -2) : '';
    if (empty($anio_corto)) return $result;
    $sql = "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(p.campo0, '-', 3), '-', -1) AS item_code,
                   p.importe_planificado AS PPTO_AUTORIZADO
            FROM pres_k2b_presupuestos p
            WHERE p.campo0 REGEXP '^[0-9]{2}-[0-9]+-[0-9]+-'
              AND SUBSTRING_INDEX(p.campo0, '-', 1) = '$anio_corto'
              AND p.id IN (SELECT MAX(id) FROM pres_k2b_presupuestos GROUP BY campo0)";
    $res = sql_do_query($sql, $db, $usu);
    while ($r = sql_do_array($res)) {
        $result[$r['ITEM_CODE']] = (float)($r['PPTO_AUTORIZADO'] ?? 0);
    }
    return $result;
}

$k2b_oc_a = []; $k2b_oc_b = []; $k2b_ppto_a = []; $k2b_ppto_b = [];
if ($row_a) {
    $k2b_oc_a   = cargar_k2b_oc($row_a['ANIO'], $row_a['UNIDAD_CC'], $db, $usu);
    $k2b_ppto_a = cargar_k2b_ppto($row_a['ANIO'], $db, $usu);
}
if ($row_b) {
    $k2b_oc_b   = cargar_k2b_oc($row_b['ANIO'], $row_b['UNIDAD_CC'], $db, $usu);
    $k2b_ppto_b = cargar_k2b_ppto($row_b['ANIO'], $db, $usu);
}
$tiene_k2b = !empty($k2b_oc_a) || !empty($k2b_oc_b);

// ── Exportar CSV ─────────────────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv' && $tiene_datos) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="comparativo_' . ($pres_a ?? '') . '_vs_' . ($pres_b ?? '') . '.csv"');
    $out = fopen('php://output', 'w');
    $csv_headers = ['Codigo', 'Item',
        'Aprobado A (' . $label_a . ')', 'OC K2B A', 'Ejec.Efect A',
        'Aprobado B (' . $label_b . ')', 'OC K2B B', 'Ejec.Efect B', 'Var Aprobado %'];
    fputcsv($out, $csv_headers, ';');
    $res_csv = sql_do_query($sql_comp, $db, $usu);
    while ($rc = sql_do_array($res_csv)) {
        $ic = $rc['ITEM_CODIGO'];
        $aa = (float)($rc['APROBADO_A'] ?? 0);
        $ab = (float)($rc['APROBADO_B'] ?? 0);
        $oc_a = (float)(($k2b_oc_a[$ic] ?? [])['TOTAL_OC'] ?? 0);
        $oc_b = (float)(($k2b_oc_b[$ic] ?? [])['TOTAL_OC'] ?? 0);
        $eff_a = $oc_a > 0 ? $oc_a : (float)($rc['EJECUTADO_A'] ?? 0);
        $eff_b = $oc_b > 0 ? $oc_b : (float)($rc['EJECUTADO_B'] ?? 0);
        $var = ($ab > 0) ? round(($aa - $ab) / $ab * 100, 2) : 0;
        fputcsv($out, [
            $ic, $rc['ITEM_NOMBRE'],
            number_format($aa, 2, ',', '.'), number_format($oc_a, 2, ',', '.'), number_format($eff_a, 2, ',', '.'),
            number_format($ab, 2, ',', '.'), number_format($oc_b, 2, ',', '.'), number_format($eff_b, 2, ',', '.'),
            number_format($var, 2, ',', '.') . '%'
        ], ';');
    }
    fclose($out);
    exit;
}
?>

<?php echo generarTituloSeccion('Comparativo de Presupuestos', 'bi bi-arrow-left-right'); ?>

<!-- SELECTORES A y B -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h6 class="mb-0"><i class="bi bi-sliders me-2"></i>Seleccionar Presupuestos a Comparar</h6>
    </div>
    <div class="card-body">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label fw-semibold"><span class="badge bg-primary me-1">A</span> Presupuesto Principal</label>
                <?php echo imp_combo_w('pres_a', $sql_combo_pres, $db, $usu, $pres_a ?? ''); ?>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-semibold"><span class="badge bg-secondary me-1">B</span> Presupuesto a Comparar</label>
                <?php echo imp_combo_w('pres_b', $sql_combo_pres, $db, $usu, $pres_b ?? ''); ?>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-pade w-100">
                    <i class="bi bi-arrow-left-right me-1"></i>Comparar
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($row_a): ?>
<!-- RESUMEN CARDS A vs B -->
<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card shadow-sm border-start border-4 border-primary">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">Presupuesto A</small>
                        <div class="fw-bold"><?php echo htmlspecialchars($label_a); ?></div>
                        <span class="badge bg-<?php echo ($row_a['ESTADO'] === 'aprobado') ? 'success' : 'secondary'; ?>" style="font-size:0.65rem;">
                            <?php echo ucfirst(htmlspecialchars($row_a['ESTADO'])); ?>
                        </span>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">Monto Aprobado</small>
                        <div class="fs-5 fw-bold text-primary">$ <?php echo number_format((float)$row_a['MONTO_TOTAL'], 2, ',', '.'); ?></div>
                        <?php if (!empty($k2b_oc_a)): ?>
                            <small class="text-muted">OC K2B: U$D <?php echo number_format(array_sum(array_column($k2b_oc_a, 'TOTAL_OC')), 2, ',', '.'); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php if ($row_b): ?>
    <div class="col-md-6">
        <div class="card shadow-sm border-start border-4 border-secondary">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">Presupuesto B</small>
                        <div class="fw-bold"><?php echo htmlspecialchars($label_b); ?></div>
                        <span class="badge bg-<?php echo ($row_b['ESTADO'] === 'aprobado') ? 'success' : 'secondary'; ?>" style="font-size:0.65rem;">
                            <?php echo ucfirst(htmlspecialchars($row_b['ESTADO'])); ?>
                        </span>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">Monto Aprobado</small>
                        <div class="fs-5 fw-bold text-secondary">$ <?php echo number_format((float)$row_b['MONTO_TOTAL'], 2, ',', '.'); ?></div>
                        <?php if (!empty($k2b_oc_b)): ?>
                            <small class="text-muted">OC K2B: U$D <?php echo number_format(array_sum(array_column($k2b_oc_b, 'TOTAL_OC')), 2, ',', '.'); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="mb-3 d-flex gap-2">
    <a href="index.php?estado=202" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver al listado
    </a>
    <a href="index.php?estado=203&id=<?php echo $pres_a; ?>" class="btn btn-pade-alt btn-sm">
        <i class="bi bi-eye me-1"></i>Detalle A
    </a>
    <?php if ($row_b): ?>
    <a href="index.php?estado=203&id=<?php echo $pres_b; ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-eye me-1"></i>Detalle B
    </a>
    <?php endif; ?>
    <?php if ($tiene_datos): ?>
    <a href="index.php?estado=<?php echo $estado; ?>&id=<?php echo $pres_a; ?>&id_b=<?php echo $pres_b; ?>&export=csv"
       class="btn btn-outline-success btn-sm">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Exportar CSV
    </a>
    <?php endif; ?>
</div>

<!-- TABLA COMPARATIVA -->
<?php if ($tiene_datos):
    $res_comp = sql_do_query($sql_comp, $db, $usu);
    $col_count = $tiene_k2b ? 10 : 7;
?>
<div class="card shadow-sm">
    <div class="card-header bg-light py-2">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-table me-2"></i>Comparativo por Ítem de Gasto</h6>
            <?php if ($tiene_k2b): ?>
                <span class="badge bg-info text-dark"><i class="bi bi-box-arrow-in-down-right me-1"></i>Ejecución = K2B OC</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead>
                    <tr class="table-light">
                        <th rowspan="2">Ítem de Gasto</th>
                        <th class="text-center bg-primary text-white" colspan="<?php echo $tiene_k2b ? 4 : 2; ?>">A: <?php echo htmlspecialchars($row_a['ANIO']); ?></th>
                        <th class="text-center bg-secondary text-white" colspan="<?php echo $tiene_k2b ? 4 : 2; ?>">B: <?php echo htmlspecialchars($row_b ? $row_b['ANIO'] : '—'); ?></th>
                        <th class="text-center" rowspan="2" style="width:100px;">Var Aprob.</th>
                    </tr>
                    <tr class="table-light small">
                        <th class="text-end">Aprobado</th>
                        <?php if ($tiene_k2b): ?>
                        <th class="text-end">OC K2B</th>
                        <th class="text-end">Saldo</th>
                        <?php endif; ?>
                        <th style="width:100px;">% Ejec.</th>
                        <th class="text-end">Aprobado</th>
                        <?php if ($tiene_k2b): ?>
                        <th class="text-end">OC K2B</th>
                        <th class="text-end">Saldo</th>
                        <?php endif; ?>
                        <th style="width:100px;">% Ejec.</th>
                    </tr>
                </thead>
                <tbody>
<?php
$n = 0;
$t_aa = 0; $t_ab = 0; $t_oc_a = 0; $t_oc_b = 0;

while ($rc = sql_do_array($res_comp)):
    $n++;
    $ic = $rc['ITEM_CODIGO'] ?? '';
    $aa = (float)($rc['APROBADO_A'] ?? 0);
    $ab = (float)($rc['APROBADO_B'] ?? 0);
    $oc_a = (float)(($k2b_oc_a[$ic] ?? [])['TOTAL_OC'] ?? 0);
    $oc_b = (float)(($k2b_oc_b[$ic] ?? [])['TOTAL_OC'] ?? 0);
    $eff_a = $oc_a > 0 ? $oc_a : (float)($rc['EJECUTADO_A'] ?? 0);
    $eff_b = $oc_b > 0 ? $oc_b : (float)($rc['EJECUTADO_B'] ?? 0);
    $saldo_a = $aa - $eff_a;
    $saldo_b = $ab - $eff_b;
    $pct_a = ($aa > 0) ? round(($eff_a / $aa) * 100, 1) : 0;
    $pct_b = ($ab > 0) ? round(($eff_b / $ab) * 100, 1) : 0;
    $var = ($ab > 0) ? round(($aa - $ab) / $ab * 100, 2) : ($aa > 0 ? 100 : 0);

    $t_aa += $aa; $t_ab += $ab; $t_oc_a += $eff_a; $t_oc_b += $eff_b;

    $var_class = '';
    if (abs($var) > 50)      $var_class = 'text-danger fw-bold';
    elseif (abs($var) > 20)  $var_class = 'text-warning fw-bold';

    // Progress bar helpers
    $pb_a_class = ($pct_a >= 95) ? 'bg-danger' : (($pct_a >= 80) ? 'bg-warning' : 'bg-success');
    $pb_b_class = ($pct_b >= 95) ? 'bg-danger' : (($pct_b >= 80) ? 'bg-warning' : 'bg-success');
?>
                    <tr>
                        <td>
                            <span class="fw-bold"><?php echo htmlspecialchars($ic); ?></span>
                            <span class="text-muted mx-1">—</span>
                            <span class="small"><?php echo htmlspecialchars($rc['ITEM_NOMBRE'] ?? ''); ?></span>
                        </td>
                        <td class="text-end small">$ <?php echo number_format($aa, 2, ',', '.'); ?></td>
                        <?php if ($tiene_k2b): ?>
                        <td class="text-end small fw-semibold"><?php echo $oc_a > 0 ? 'U$D ' . number_format($oc_a, 2, ',', '.') : '<span class="text-muted">—</span>'; ?></td>
                        <td class="text-end small" style="color:<?php echo ($saldo_a >= 0) ? '#198754' : '#dc3545'; ?>">
                            $ <?php echo number_format($saldo_a, 2, ',', '.'); ?>
                        </td>
                        <?php endif; ?>
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                <div class="progress flex-grow-1" style="height:6px;">
                                    <div class="progress-bar <?php echo $pb_a_class; ?>" style="width:<?php echo min($pct_a, 100); ?>%;"></div>
                                </div>
                                <span class="small" style="min-width:32px;text-align:right;"><?php echo number_format($pct_a, 0); ?>%</span>
                            </div>
                        </td>
                        <td class="text-end small">$ <?php echo number_format($ab, 2, ',', '.'); ?></td>
                        <?php if ($tiene_k2b): ?>
                        <td class="text-end small fw-semibold"><?php echo $oc_b > 0 ? 'U$D ' . number_format($oc_b, 2, ',', '.') : '<span class="text-muted">—</span>'; ?></td>
                        <td class="text-end small" style="color:<?php echo ($saldo_b >= 0) ? '#198754' : '#dc3545'; ?>">
                            $ <?php echo number_format($saldo_b, 2, ',', '.'); ?>
                        </td>
                        <?php endif; ?>
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                <div class="progress flex-grow-1" style="height:6px;">
                                    <div class="progress-bar <?php echo $pb_b_class; ?>" style="width:<?php echo min($pct_b, 100); ?>%;"></div>
                                </div>
                                <span class="small" style="min-width:32px;text-align:right;"><?php echo number_format($pct_b, 0); ?>%</span>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="<?php echo $var_class; ?>"><?php echo ($var >= 0 ? '+' : '') . number_format($var, 1); ?>%</span>
                        </td>
                    </tr>
<?php endwhile; ?>
<?php if ($n > 0):
    $vt = ($t_ab > 0) ? round(($t_aa - $t_ab) / $t_ab * 100, 2) : 0;
    $vt_class = (abs($vt) > 50) ? 'text-danger fw-bold' : ((abs($vt) > 20) ? 'text-warning fw-bold' : '');
    $t_pct_a = ($t_aa > 0) ? round(($t_oc_a / $t_aa) * 100, 1) : 0;
    $t_pct_b = ($t_ab > 0) ? round(($t_oc_b / $t_ab) * 100, 1) : 0;
?>
                    <tr class="table-dark fw-bold">
                        <td>TOTALES</td>
                        <td class="text-end">$ <?php echo number_format($t_aa, 2, ',', '.'); ?></td>
                        <?php if ($tiene_k2b): ?>
                        <td class="text-end">U$D <?php echo number_format($t_oc_a, 2, ',', '.'); ?></td>
                        <td class="text-end" style="color:<?php echo (($t_aa - $t_oc_a) >= 0) ? '#8fbc8f' : '#f08080'; ?>">
                            $ <?php echo number_format($t_aa - $t_oc_a, 2, ',', '.'); ?>
                        </td>
                        <?php endif; ?>
                        <td class="small text-center"><?php echo number_format($t_pct_a, 0); ?>%</td>
                        <td class="text-end">$ <?php echo number_format($t_ab, 2, ',', '.'); ?></td>
                        <?php if ($tiene_k2b): ?>
                        <td class="text-end">U$D <?php echo number_format($t_oc_b, 2, ',', '.'); ?></td>
                        <td class="text-end" style="color:<?php echo (($t_ab - $t_oc_b) >= 0) ? '#8fbc8f' : '#f08080'; ?>">
                            $ <?php echo number_format($t_ab - $t_oc_b, 2, ',', '.'); ?>
                        </td>
                        <?php endif; ?>
                        <td class="small text-center"><?php echo number_format($t_pct_b, 0); ?>%</td>
                        <td class="text-center"><span class="<?php echo $vt_class; ?>"><?php echo ($vt >= 0 ? '+' : '') . number_format($vt, 1); ?>%</span></td>
                    </tr>
<?php endif; ?>
<?php if ($n === 0): ?>
                    <tr><td colspan="<?php echo $col_count; ?>" class="text-center text-muted py-4">No hay partidas para comparar.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>
