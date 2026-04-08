<?php
/**
 * ============================================================================
 * Detalle de Presupuesto
 * ============================================================================
 * ID menú: 203 | Tablas: ciclo_partida, ciclo_presupuesto, ciclo_item_gasto,
 *                         ciclo_categoria_gasto, pres_k2b_sr_logs, pres_k2b_presupuestos
 * Vista desglosada de un presupuesto con datos presupuestarios (ciclo_*) y
 * consumo real K2B (SR + OC), siguiendo el patrón de k2b_sr.php.
 * ============================================================================
 */

$db  = '1';
$msg = '';
$pres_id = Enmascarar_get('id');
$cat_sel = Enmascarar_get('cat');

if (empty($pres_id)) {
    echo generarAlertJS('Error', 'No se especificó un presupuesto.', 'error', "index.php?estado=202");
    return;
}

// ── Cargar cabecera del presupuesto ──────────────────────────────────────────
$sql_pres = "SELECT pr.*, pf.anio, uo.nombre AS UNIDAD, uo.cc AS UNIDAD_CC
             FROM ciclo_presupuesto pr
             JOIN ciclo_periodo_fiscal pf ON pr.id_periodo = pf.id
             JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
             WHERE pr.id = '$pres_id';";
$row_pres = sql_do_array(sql_do_query($sql_pres, $db, $usu));

if (!$row_pres) {
    echo generarAlertJS('Error', 'Presupuesto no encontrado.', 'error', "index.php?estado=202");
    return;
}

$anio_pres  = $row_pres['ANIO'] ?? '';
$cc_pres    = $row_pres['UNIDAD_CC'] ?? '';
$anio_corto = !empty($anio_pres) ? substr($anio_pres, -2) : '';

// Badge de estado
$estado_badge = '';
$estado_icon  = '';
switch ($row_pres['ESTADO']) {
    case 'borrador':    $estado_badge = 'bg-secondary'; $estado_icon = 'bi-pencil'; break;
    case 'en_revision': $estado_badge = 'bg-warning text-dark'; $estado_icon = 'bi-hourglass-split'; break;
    case 'aprobado':    $estado_badge = 'bg-success'; $estado_icon = 'bi-check-circle'; break;
    case 'rechazado':   $estado_badge = 'bg-danger'; $estado_icon = 'bi-x-circle'; break;
    default:            $estado_badge = 'bg-light text-dark'; $estado_icon = 'bi-question-circle';
}

// ── Cargar partidas agrupadas por ítem de gasto (directo, sin vistas) ────────
$sql_items = "SELECT ig.codigo   AS ITEM_CODIGO,
                     ig.nombre   AS ITEM_NOMBRE,
                     SUM(pa.monto_aprobado)     AS TOTAL_APROBADO,
                     SUM(pa.monto_comprometido) AS TOTAL_COMPROMETIDO,
                     SUM(pa.monto_ejecutado)    AS TOTAL_EJECUTADO,
                     SUM(pa.monto_aprobado - pa.monto_comprometido - pa.monto_ejecutado) AS TOTAL_DISPONIBLE,
                     COUNT(pa.id) AS CANT_PARTIDAS
              FROM ciclo_partida pa
              JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
              WHERE pa.id_presupuesto = '$pres_id'
              GROUP BY ig.codigo, ig.nombre
              ORDER BY ig.codigo;";
$res_items = sql_do_query($sql_items, $db, $usu);

$categorias = [];
$tot_aprobado = 0; $tot_comprometido = 0; $tot_ejecutado = 0; $tot_disponible = 0;
$cant_items_total = 0;

while ($rc = sql_do_array($res_items)) {
    $aprobado     = (float)($rc['TOTAL_APROBADO'] ?? 0);
    $comprometido = (float)($rc['TOTAL_COMPROMETIDO'] ?? 0);
    $ejecutado    = (float)($rc['TOTAL_EJECUTADO'] ?? 0);
    $disponible   = (float)($rc['TOTAL_DISPONIBLE'] ?? 0);

    $tot_aprobado     += $aprobado;
    $tot_comprometido += $comprometido;
    $tot_ejecutado    += $ejecutado;
    $tot_disponible   += $disponible;

    $categorias[] = $rc;
}

// placeholder, recalculated after K2B totals are available
$pct_total = 0;

// ── K2B: Presupuesto autorizado por ítem (desde pres_k2b_presupuestos) ──────
$k2b_ppto_item = [];
if (!empty($anio_corto)) {
    $sql_k2b_ppto = "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(p.campo0, '-', 3), '-', -1) AS item_code,
                            p.importe_planificado AS PPTO_AUTORIZADO
                     FROM pres_k2b_presupuestos p
                     WHERE p.campo0 REGEXP '^[0-9]{2}-[0-9]+-[0-9]+-'
                       AND SUBSTRING_INDEX(p.campo0, '-', 1) = '$anio_corto'
                       AND p.id IN (SELECT MAX(id) FROM pres_k2b_presupuestos GROUP BY campo0)
                     ORDER BY item_code;";
    $res_k2b_ppto = sql_do_query($sql_k2b_ppto, $db, $usu);
    while ($rp = sql_do_array($res_k2b_ppto)) {
        $k2b_ppto_item[$rp['ITEM_CODE']] = (float)($rp['PPTO_AUTORIZADO'] ?? 0);
    }
}

// ── K2B: Total OC por ítem con fallback (patrón k2b_sr.php) ─────────────────
$k2b_por_item = [];
if (!empty($anio_pres) && !empty($cc_pres)) {
    $sql_k2b = "SELECT ig.codigo AS ITEM_CODIGO,
                       COUNT(DISTINCT sr.codigo) AS CANT_SR,
                       COUNT(*) AS CANT_LINEAS,
                       COALESCE(oc_item.total_oc, 0) AS TOTAL_OC
                FROM pres_k2b_sr_logs sr
                INNER JOIN ciclo_item_gasto ig ON sr.esquema_gastoid = ig.codigo
                LEFT JOIN (
                    SELECT esquema_gastoid, SUM(sr_monto) AS total_oc FROM (
                        SELECT esquema_gastoid, codigo,
                               CASE WHEN SUM(monto_linea_oc_usd) > 0 THEN SUM(monto_linea_oc_usd)
                                    ELSE MAX(importe_total) END AS sr_monto
                        FROM pres_k2b_sr_logs
                        WHERE esquema_anio = '$anio_pres' AND esquema_cc = '$cc_pres'
                              AND codigo IS NOT NULL AND codigo != ''
                        GROUP BY esquema_gastoid, codigo
                    ) sr_oc GROUP BY esquema_gastoid
                ) oc_item ON oc_item.esquema_gastoid = ig.codigo
                WHERE sr.esquema_anio = '$anio_pres'
                  AND sr.esquema_cc = '$cc_pres'
                  AND sr.codigo IS NOT NULL AND sr.codigo != ''
                GROUP BY ig.codigo";
    $res_k2b = sql_do_query($sql_k2b, $db, $usu);
    while ($rk = sql_do_array($res_k2b)) {
        $k2b_por_item[$rk['ITEM_CODIGO']] = $rk;
    }
}
$tiene_k2b = !empty($k2b_por_item);

// ── K2B: Detalle de SRs para accordion (misma query que k2b_sr.php) ─────────
$k2b_sr_grupos = [];
if ($tiene_k2b && !empty($cat_sel)) {
    $sql_sr_det = "SELECT sr.codigo, sr.fecha, sr.estado, sr.usuario, sr.descripcion,
                          sr.precio_unitario, sr.cantidad_autorizada,
                          (sr.precio_unitario * sr.cantidad_autorizada) AS IMPORTE_LINEA,
                          sr.importe_total, sr.orden_compra, sr.proveedor, sr.monto_linea_oc_usd
                   FROM pres_k2b_sr_logs sr
                   WHERE sr.esquema_anio = '$anio_pres'
                     AND sr.esquema_cc = '$cc_pres'
                     AND sr.esquema_gastoid = '$cat_sel'
                     AND sr.codigo IS NOT NULL AND sr.codigo != ''
                   ORDER BY sr.fecha DESC, sr.codigo;";
    $res_sr_det = sql_do_query($sql_sr_det, $db, $usu);
    $sr_raw = [];
    while ($rd = sql_do_array($res_sr_det)) { $sr_raw[] = $rd; }

    // Agrupar por SR y calcular total OC con fallback (patrón k2b_sr.php)
    $sr_groups = []; $sr_oc_totals = [];
    foreach ($sr_raw as $fila) {
        $sr_code = $fila['CODIGO'] ?? '';
        if (!isset($sr_groups[$sr_code])) {
            $sr_groups[$sr_code] = [];
            $sr_oc_totals[$sr_code] = 0.0;
        }
        $sr_groups[$sr_code][] = $fila;
        $sr_oc_totals[$sr_code] += (float)($fila['MONTO_LINEA_OC_USD'] ?? 0);
    }
    foreach ($sr_oc_totals as $sr_code => $oc_sum) {
        if ($oc_sum <= 0 && !empty($sr_groups[$sr_code])) {
            $sr_oc_totals[$sr_code] = (float)($sr_groups[$sr_code][0]['IMPORTE_TOTAL'] ?? 0);
        }
    }
    $k2b_sr_grupos = ['sr_groups' => $sr_groups, 'sr_oc_totals' => $sr_oc_totals, 'filas' => $sr_raw];
}

// ── Cargar trail de revisiones ───────────────────────────────────────────────
$sql_rev = "SELECT r.nivel, r.accion, r.observaciones, r.fecha,
                   COALESCE(cu.nombre, CONCAT('Usuario #', r.id_usuario)) AS REVISOR
            FROM ciclo_revision r
            LEFT JOIN ciclo_usuario cu ON r.id_usuario = cu.id
            WHERE r.id_presupuesto = '$pres_id'
            ORDER BY r.nivel, r.fecha;";
$res_rev = sql_do_query($sql_rev, $db, $usu);
$revisiones = [];
while ($rr = sql_do_array($res_rev)) {
    $revisiones[] = $rr;
}

// ── Precargar sub-ítems (categorías de gasto) por ítem ───────────────────────
$items_por_cat = [];
$sql_sub = "SELECT ig.codigo AS ITEM_CODIGO,
                   pa.id AS PARTIDA_ID,
                   cg.codigo AS CAT_CODIGO,
                   cg.nombre AS CAT_NOMBRE,
                   pa.monto_aprobado     AS MONTO_APROBADO,
                   pa.monto_comprometido AS MONTO_COMPROMETIDO,
                   pa.monto_ejecutado    AS MONTO_EJECUTADO,
                   (pa.monto_aprobado - pa.monto_comprometido - pa.monto_ejecutado) AS SALDO_DISPONIBLE
            FROM ciclo_partida pa
            JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
            LEFT JOIN ciclo_categoria_gasto cg ON pa.id_categoria = cg.id
            WHERE pa.id_presupuesto = '$pres_id'
            ORDER BY ig.codigo, cg.codigo;";
$res_sub = sql_do_query($sql_sub, $db, $usu);
while ($ri = sql_do_array($res_sub)) {
    $items_por_cat[$ri['ITEM_CODIGO']][] = $ri;
    $cant_items_total++;
}
?>

<?php echo generarTituloSeccion('Detalle de Presupuesto', 'bi bi-journal-text'); ?>

<!-- CABECERA -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="row align-items-center">
            <div class="col-md-5">
                <div class="d-flex align-items-center gap-3">
                    <div>
                        <div class="fs-3 fw-bold text-primary"><?php echo htmlspecialchars($anio_pres); ?></div>
                        <div class="text-muted small">Período fiscal</div>
                    </div>
                    <div class="vr" style="height:40px;"></div>
                    <div>
                        <div class="fw-bold"><?php echo htmlspecialchars($row_pres['UNIDAD']); ?></div>
                        <div class="text-muted small">
                            CC: <?php echo htmlspecialchars($cc_pres); ?>
                            <?php if (!empty($row_pres['ORIGEN'])): ?>
                                · <span class="badge bg-outline-secondary border small" style="font-size:0.65rem;">
                                    <?php echo strtoupper(htmlspecialchars($row_pres['ORIGEN'])); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 text-center">
                <span class="badge <?php echo $estado_badge; ?> fs-6">
                    <i class="bi <?php echo $estado_icon; ?> me-1"></i><?php echo ucfirst(htmlspecialchars($row_pres['ESTADO'])); ?>
                </span>
                <?php if (!empty($row_pres['FECHA_APROBACION'])): ?>
                    <div class="text-muted small mt-1"><?php echo enmascararfecha($row_pres['FECHA_APROBACION']); ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-5">
                <div class="text-end">
                    <div class="text-muted small">Monto Total Aprobado</div>
                    <div class="fs-3 fw-bold text-primary">$ <?php echo number_format((float)$row_pres['MONTO_TOTAL'], 2, ',', '.'); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SUMMARY CARDS -->
<?php
    // K2B totals for cards
    $gt_k2b_oc = 0; $gt_k2b_sr = 0; $gt_k2b_ppto = 0;
    foreach ($categorias as $rc_c) {
        $ic = $rc_c['ITEM_CODIGO'] ?? '';
        $gt_k2b_ppto += $k2b_ppto_item[$ic] ?? 0;
        $gt_k2b_oc   += (float)(($k2b_por_item[$ic] ?? [])['TOTAL_OC'] ?? 0);
        $gt_k2b_sr   += (int)(($k2b_por_item[$ic] ?? [])['CANT_SR'] ?? 0);
    }
    $gt_k2b_saldo = $gt_k2b_ppto - $gt_k2b_oc;

    // Ejecución efectiva: usar K2B OC cuando hay datos K2B, sino ciclo_partida
    $ejec_efectivo = $tiene_k2b ? $gt_k2b_oc : $tot_ejecutado;
    $disp_efectivo = $tot_aprobado - $tot_comprometido - $ejec_efectivo;
    $pct_total     = ($tot_aprobado > 0) ? round(($ejec_efectivo / $tot_aprobado) * 100, 2) : 0;

    $bar_class = 'bg-success';
    if ($pct_total >= 95) $bar_class = 'bg-danger';
    elseif ($pct_total >= 80) $bar_class = 'bg-warning';
    $bar_pct = min($pct_total, 100);
?>
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-primary">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Aprobado</div>
                        <div class="fs-5 fw-bold text-primary">$ <?php echo number_format($tot_aprobado, 2, ',', '.'); ?></div>
                    </div>
                    <i class="bi bi-clipboard-check text-primary" style="font-size:1.5rem;opacity:0.3;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-warning">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Comprometido</div>
                        <div class="fs-5 fw-bold text-warning">$ <?php echo number_format($tot_comprometido, 2, ',', '.'); ?></div>
                    </div>
                    <i class="bi bi-lock text-warning" style="font-size:1.5rem;opacity:0.3;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-danger">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Ejecutado <?php if ($tiene_k2b): ?><span class="badge bg-info text-dark" style="font-size:0.6rem;">K2B OC</span><?php endif; ?></div>
                        <div class="fs-5 fw-bold text-danger"><?php echo $tiene_k2b ? 'U$D' : '$'; ?> <?php echo number_format($ejec_efectivo, 2, ',', '.'); ?></div>
                    </div>
                    <i class="bi bi-cash-stack text-danger" style="font-size:1.5rem;opacity:0.3;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-success">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Disponible</div>
                        <div class="fs-5 fw-bold <?php echo ($disp_efectivo >= 0) ? 'text-success' : 'text-danger'; ?>">
                            $ <?php echo number_format($disp_efectivo, 2, ',', '.'); ?>
                        </div>
                    </div>
                    <i class="bi bi-wallet2 text-success" style="font-size:1.5rem;opacity:0.3;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BARRA DE PROGRESO GENERAL -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="small fw-semibold text-muted">Ejecución global</span>
            <span class="small fw-bold <?php echo ($pct_total >= 95) ? 'text-danger' : (($pct_total >= 80) ? 'text-warning' : 'text-success'); ?>">
                <?php echo number_format($pct_total, 1); ?>%
            </span>
        </div>
        <div class="progress" style="height:12px;">
            <div class="progress-bar <?php echo $bar_class; ?>" style="width:<?php echo $bar_pct; ?>%;"></div>
        </div>
        <div class="d-flex justify-content-between mt-1">
            <span class="small text-muted"><?php echo count($categorias); ?> ítems · <?php echo $cant_items_total; ?> partidas</span>
            <span class="small text-muted">
                <?php if ($tiene_k2b): ?>
                    <i class="bi bi-box-arrow-in-down-right me-1"></i><?php echo $gt_k2b_sr; ?> SR K2B · U$D <?php echo number_format($gt_k2b_oc, 2, ',', '.'); ?> en OC
                <?php endif; ?>
            </span>
        </div>
    </div>
</div>

<!-- ACCIONES -->
<div class="d-flex gap-2 mb-3">
    <a href="index.php?estado=202" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver al listado
    </a>
    <a href="index.php?estado=204&id=<?php echo $pres_id; ?>" class="btn btn-outline-info btn-sm">
        <i class="bi bi-arrow-left-right me-1"></i>Comparativo
    </a>
    <?php if (!empty($revisiones)): ?>
    <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#colRevision">
        <i class="bi bi-clock-history me-1"></i>Trail de aprobación (<?php echo count($revisiones); ?>)
    </button>
    <?php endif; ?>
</div>

<!-- TRAIL DE REVISIONES (colapsable) -->
<?php if (!empty($revisiones)): ?>
<div class="collapse mb-3" id="colRevision">
    <div class="card shadow-sm">
        <div class="card-header bg-light py-2">
            <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Trail de Aprobación</h6>
        </div>
        <div class="card-body py-2">
            <div class="d-flex flex-wrap gap-3">
                <?php foreach ($revisiones as $rev): ?>
                    <?php
                        $rev_badge = 'bg-secondary';
                        $rev_icon  = 'bi-question-circle';
                        if ($rev['ACCION'] === 'aprobar') { $rev_badge = 'bg-success'; $rev_icon = 'bi-check-circle'; }
                        elseif ($rev['ACCION'] === 'rechazar') { $rev_badge = 'bg-danger'; $rev_icon = 'bi-x-circle'; }
                        elseif ($rev['ACCION'] === 'observar') { $rev_badge = 'bg-warning text-dark'; $rev_icon = 'bi-eye'; }
                    ?>
                    <div class="d-flex align-items-start gap-2 border rounded p-2" style="min-width:220px;">
                        <span class="badge <?php echo $rev_badge; ?> mt-1">
                            <i class="bi <?php echo $rev_icon; ?>"></i> N<?php echo $rev['NIVEL']; ?>
                        </span>
                        <div>
                            <div class="small fw-semibold"><?php echo htmlspecialchars($rev['REVISOR']); ?></div>
                            <div class="small text-muted"><?php echo htmlspecialchars(mb_substr($rev['OBSERVACIONES'] ?? '', 0, 50)); ?></div>
                            <div class="small text-muted"><?php echo enmascararfecha($rev['FECHA'] ?? ''); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- TABLA PRINCIPAL: DESGLOSE POR ÍTEM DE GASTO -->
<?php
$col_count = 7; // base: expand + nombre + apr + comp + ejec + disp + %consumo
if ($tiene_k2b) $col_count += 4; // + Ppto K2B + Total OC + Saldo K2B + SR
?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white py-2">
        <h6 class="mb-0"><i class="bi bi-folder me-2"></i>Desglose por Ítem de Gasto</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:30px;"></th>
                        <th>Ítem de Gasto</th>
                        <th class="text-end">Aprobado</th>
                        <th class="text-end">Comprometido</th>
                        <th class="text-end">Ejecutado</th>
                        <th class="text-end">Disponible</th>
                        <th style="width:150px;">% Consumo</th>
                        <?php if ($tiene_k2b): ?>
                        <th class="text-end">Ppto K2B</th>
                        <th class="text-end">Total OC</th>
                        <th class="text-end">Saldo K2B</th>
                        <th class="text-center">SR</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
<?php
$n = 0;
$gt_oc = 0; $gt_ppto_k2b = 0; $gt_saldo_k2b = 0;
foreach ($categorias as $rc):
    $n++;
    $aprobado     = (float)($rc['TOTAL_APROBADO'] ?? 0);
    $comprometido = (float)($rc['TOTAL_COMPROMETIDO'] ?? 0);
    $ejecutado    = (float)($rc['TOTAL_EJECUTADO'] ?? 0);
    $disponible   = (float)($rc['TOTAL_DISPONIBLE'] ?? 0);
    $pct          = ($aprobado > 0) ? round(($ejecutado / $aprobado) * 100, 2) : 0;

    $item_codigo = $rc['ITEM_CODIGO'] ?? '';
    $es_seleccionada = ($cat_sel === $item_codigo);
    $tiene_items = !empty($items_por_cat[$item_codigo]);
    $k2b_data    = $k2b_por_item[$item_codigo] ?? null;
    $k2b_ppto    = $k2b_ppto_item[$item_codigo] ?? 0;
    $k2b_oc      = (float)($k2b_data['TOTAL_OC'] ?? 0);
    $k2b_saldo   = $k2b_ppto - $k2b_oc;
    $k2b_cant_sr = (int)($k2b_data['CANT_SR'] ?? 0);

    $gt_oc        += $k2b_oc;
    $gt_ppto_k2b  += $k2b_ppto;
    $gt_saldo_k2b += $k2b_saldo;

    // Color por consumo
    $row_class = '';
    if ($pct >= 95)      $row_class = 'table-danger';
    elseif ($pct >= 80)  $row_class = 'table-warning';

    // Progress bar
    $pb_class = 'bg-success';
    if ($pct >= 95) $pb_class = 'bg-danger';
    elseif ($pct >= 80) $pb_class = 'bg-warning';
    $pb_pct = min($pct, 100);
?>
                    <tr class="<?php echo $row_class; ?>" style="cursor:pointer;"
                        onclick="window.location='index.php?estado=<?php echo $estado; ?>&id=<?php echo $pres_id; ?>&cat=<?php echo ($es_seleccionada ? '' : urlencode($item_codigo)); ?>'">
                        <td class="text-center">
                            <i class="bi bi-<?php echo $es_seleccionada ? 'chevron-down' : 'chevron-right'; ?> text-muted"></i>
                        </td>
                        <td>
                            <span class="fw-bold"><?php echo htmlspecialchars($item_codigo); ?></span>
                            <span class="text-muted mx-1">—</span>
                            <?php echo htmlspecialchars($rc['ITEM_NOMBRE'] ?? ''); ?>
                            <?php if ($tiene_items && count($items_por_cat[$item_codigo]) > 1): ?>
                                <span class="badge bg-secondary ms-1" style="font-size:0.65rem;"><?php echo count($items_por_cat[$item_codigo]); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">$ <?php echo number_format($aprobado, 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format($comprometido, 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format($ejecutado, 2, ',', '.'); ?></td>
                        <td class="text-end <?php echo ($disponible < 0) ? 'text-danger fw-bold' : ''; ?>">
                            $ <?php echo number_format($disponible, 2, ',', '.'); ?>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:8px;">
                                    <div class="progress-bar <?php echo $pb_class; ?>" style="width:<?php echo $pb_pct; ?>%;"></div>
                                </div>
                                <span class="small fw-semibold" style="min-width:42px;text-align:right;"><?php echo number_format($pct, 1); ?>%</span>
                            </div>
                        </td>
                        <?php if ($tiene_k2b): ?>
                        <td class="text-end small">$ <?php echo number_format($k2b_ppto, 2, ',', '.'); ?></td>
                        <td class="text-end small fw-semibold">U$D <?php echo number_format($k2b_oc, 2, ',', '.'); ?></td>
                        <td class="text-end small fw-bold" style="color:<?php echo ($k2b_saldo >= 0) ? '#198754' : '#dc3545'; ?>">
                            U$D <?php echo number_format($k2b_saldo, 2, ',', '.'); ?>
                        </td>
                        <td class="text-center">
                            <?php if ($k2b_cant_sr > 0): ?>
                                <span class="badge bg-info text-dark"><?php echo $k2b_cant_sr; ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>

<?php
    // ── EXPANDED: Sub-partidas + K2B SR detail ──────────────────────────
    if ($es_seleccionada):
        // Sub-partidas (categorías de gasto)
        if ($tiene_items && count($items_por_cat[$item_codigo]) > 1):
            $items = $items_por_cat[$item_codigo];
            $sub_tot_apr = 0; $sub_tot_comp = 0; $sub_tot_ejec = 0; $sub_tot_disp = 0;
?>
                    <tr>
                        <td colspan="<?php echo $col_count; ?>" class="p-0">
                            <div class="bg-light border-top border-bottom" style="border-left:4px solid #0d6efd;">
                                <div class="px-3 py-1"><span class="small fw-semibold text-primary"><i class="bi bi-layers me-1"></i>Partidas presupuestarias</span></div>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0" style="background:transparent;">
                                        <thead>
                                            <tr class="small text-muted">
                                                <th style="padding-left:2rem;">Categoría</th>
                                                <th>Descripción</th>
                                                <th class="text-end">Aprobado</th>
                                                <th class="text-end">Comprometido</th>
                                                <th class="text-end">Ejecutado</th>
                                                <th class="text-end">Disponible</th>
                                                <th style="width:150px;">% Consumo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
<?php
            foreach ($items as $ri):
                $i_aprobado     = (float)($ri['MONTO_APROBADO'] ?? 0);
                $i_comprometido = (float)($ri['MONTO_COMPROMETIDO'] ?? 0);
                $i_ejecutado    = (float)($ri['MONTO_EJECUTADO'] ?? 0);
                $i_disponible   = (float)($ri['SALDO_DISPONIBLE'] ?? 0);
                $i_pct          = ($i_aprobado > 0) ? round(($i_ejecutado / $i_aprobado) * 100, 2) : 0;

                $sub_tot_apr  += $i_aprobado;
                $sub_tot_comp += $i_comprometido;
                $sub_tot_ejec += $i_ejecutado;
                $sub_tot_disp += $i_disponible;

                $i_pb_class = 'bg-success';
                if ($i_pct >= 95) $i_pb_class = 'bg-danger';
                elseif ($i_pct >= 80) $i_pb_class = 'bg-warning';

                $i_row_class = '';
                if ($i_pct >= 95) $i_row_class = 'table-danger';
                elseif ($i_pct >= 80) $i_row_class = 'table-warning';
?>
                                            <tr class="<?php echo $i_row_class; ?>">
                                                <td class="fw-semibold small" style="padding-left:2rem;"><?php echo htmlspecialchars($ri['CAT_CODIGO'] ?? '—'); ?></td>
                                                <td class="small"><?php echo htmlspecialchars($ri['CAT_NOMBRE'] ?? 'Partida sin categoría'); ?></td>
                                                <td class="text-end small">$ <?php echo number_format($i_aprobado, 2, ',', '.'); ?></td>
                                                <td class="text-end small">$ <?php echo number_format($i_comprometido, 2, ',', '.'); ?></td>
                                                <td class="text-end small">$ <?php echo number_format($i_ejecutado, 2, ',', '.'); ?></td>
                                                <td class="text-end small <?php echo ($i_disponible < 0) ? 'text-danger fw-bold' : ''; ?>">
                                                    $ <?php echo number_format($i_disponible, 2, ',', '.'); ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-1">
                                                        <div class="progress flex-grow-1" style="height:6px;">
                                                            <div class="progress-bar <?php echo $i_pb_class; ?>" style="width:<?php echo min($i_pct, 100); ?>%;"></div>
                                                        </div>
                                                        <span class="small" style="min-width:36px;text-align:right;"><?php echo number_format($i_pct, 1); ?>%</span>
                                                    </div>
                                                </td>
                                            </tr>
<?php endforeach; ?>
                                            <tr class="fw-bold small" style="background:#e2e6ea;">
                                                <td style="padding-left:2rem;">SUBTOTAL</td>
                                                <td><?php echo count($items); ?> partida<?php echo count($items) > 1 ? 's' : ''; ?></td>
                                                <td class="text-end">$ <?php echo number_format($sub_tot_apr, 2, ',', '.'); ?></td>
                                                <td class="text-end">$ <?php echo number_format($sub_tot_comp, 2, ',', '.'); ?></td>
                                                <td class="text-end">$ <?php echo number_format($sub_tot_ejec, 2, ',', '.'); ?></td>
                                                <td class="text-end">$ <?php echo number_format($sub_tot_disp, 2, ',', '.'); ?></td>
                                                <td></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>
<?php
        endif; // tiene_items > 1

        // ── K2B SR detail accordion (patrón k2b_sr.php) ─────────────────
        if (!empty($k2b_sr_grupos['sr_groups'])):
            $sr_groups = $k2b_sr_grupos['sr_groups'];
            $sr_oc_totals = $k2b_sr_grupos['sr_oc_totals'];
            $sr_total_oc = array_sum($sr_oc_totals);
?>
                    <tr>
                        <td colspan="<?php echo $col_count; ?>" class="p-0">
                            <div class="border-top border-bottom" style="border-left:4px solid #0dcaf0; background:#f0fbff;">
                                <div class="px-3 py-1"><span class="small fw-semibold text-info"><i class="bi bi-box-arrow-in-down-right me-1"></i>Solicitudes K2B — <?php echo count($sr_groups); ?> SR · U$D <?php echo number_format($sr_total_oc, 2, ',', '.'); ?></span></div>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0" style="background:transparent;">
                                        <thead class="table-light">
                                            <tr class="small">
                                                <th style="padding-left:1.5rem;">SR</th>
                                                <th class="text-center">Fecha</th>
                                                <th class="text-center">Estado</th>
                                                <th>Usuario</th>
                                                <th>Descripción</th>
                                                <th class="text-end">P.Unit</th>
                                                <th class="text-end">Cant.Aut</th>
                                                <th>OC</th>
                                                <th>Proveedor</th>
                                                <th class="text-end">OC (USD)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
<?php
            $sr_idx = 0;
            foreach ($sr_groups as $sr_code => $sr_rows):
                $sr_idx++;
                $row_count = count($sr_rows);
                $row_num = 0;
                $sr_bg = ($sr_idx % 2 === 0) ? 'background:#e8f4fd;' : 'background:#f0fbff;';
                $sr_oc_total = $sr_oc_totals[$sr_code] ?? 0.0;

                foreach ($sr_rows as $rd):
                    $row_num++;
                    $est_k2b = $rd['ESTADO'] ?? '';
                    if ($est_k2b === 'FINALIZADA') $est_icon = '2';
                    elseif ($est_k2b === 'RECHAZADA') $est_icon = '5';
                    elseif ($est_k2b === 'CUMPLIDA' || $est_k2b === 'CUMPLIMIENTOSTOCK') $est_icon = '4';
                    else $est_icon = '3';
                    if ($est_icon === '3' && (!empty($rd['ORDEN_COMPRA'] ?? '') || (float)($rd['MONTO_LINEA_OC_USD'] ?? 0) > 0)) $est_icon = '4';
                    $is_first = ($row_num === 1);
?>
                                            <tr style="<?php echo $sr_bg; ?><?php echo $is_first ? 'border-top:2px solid #dee2e6;' : ''; ?>">
                                                <td class="text-nowrap" style="padding-left:1.5rem;<?php echo $is_first ? 'font-weight:600;' : 'padding-left:2.5rem;color:#999;font-size:0.75rem;'; ?>">
                                                    <?php if ($is_first): ?>
                                                        <?php echo htmlspecialchars($rd['CODIGO'] ?? ''); ?>
                                                        <?php if ($row_count > 1): ?>
                                                            <span class="text-muted" style="font-size:0.7rem;font-weight:normal;">(<?php echo $row_count; ?> lín.)</span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        └ línea <?php echo $row_num; ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center small"><?php echo $is_first ? enmascararfecha($rd['FECHA'] ?? '') : ''; ?></td>
                                                <td class="text-center"><?php if ($is_first): ?><img title="<?php echo htmlspecialchars($est_k2b); ?>" src="img/ico/<?php echo $est_icon; ?>.png" width="20" height="20" /><?php endif; ?></td>
                                                <td class="small"><?php echo $is_first ? htmlspecialchars($rd['USUARIO'] ?? '') : ''; ?></td>
                                                <td class="small" title="<?php echo htmlspecialchars($rd['DESCRIPCION'] ?? ''); ?>">
                                                    <?php echo htmlspecialchars(mb_substr($rd['DESCRIPCION'] ?? '', 0, 50)); ?><?php echo (mb_strlen($rd['DESCRIPCION'] ?? '') > 50) ? '…' : ''; ?>
                                                </td>
                                                <td class="text-end small">$ <?php echo number_format((float)($rd['PRECIO_UNITARIO'] ?? 0), 2, ',', '.'); ?></td>
                                                <td class="text-end small"><?php echo number_format((float)($rd['CANTIDAD_AUTORIZADA'] ?? 0), 2, ',', '.'); ?></td>
                                                <td class="small text-nowrap"><?php echo htmlspecialchars($rd['ORDEN_COMPRA'] ?? ''); ?></td>
                                                <td class="small"><?php echo htmlspecialchars(mb_substr($rd['PROVEEDOR'] ?? '', 0, 25)); ?></td>
                                                <td class="text-end small fw-semibold"><?php if ($is_first): ?>U$D <?php echo number_format($sr_oc_total, 2, ',', '.'); ?><?php endif; ?></td>
                                            </tr>
<?php
                endforeach;
            endforeach;
?>
                                            <tr class="fw-bold small" style="background:#d1ecf1;">
                                                <td colspan="9" class="text-end" style="padding-left:1.5rem;">Total OC Ítem</td>
                                                <td class="text-end">U$D <?php echo number_format($sr_total_oc, 2, ',', '.'); ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>
<?php
        endif; // k2b_sr_grupos
    endif; // es_seleccionada
endforeach;
?>

<?php if ($n > 0): ?>
                    <tr class="table-dark fw-bold">
                        <td></td>
                        <td>TOTALES</td>
                        <td class="text-end">$ <?php echo number_format($tot_aprobado, 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format($tot_comprometido, 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format($tot_ejecutado, 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format($tot_disponible, 2, ',', '.'); ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:8px;">
                                    <div class="progress-bar <?php echo $bar_class; ?>" style="width:<?php echo $bar_pct; ?>%;"></div>
                                </div>
                                <span class="small" style="min-width:42px;text-align:right;"><?php echo number_format($pct_total, 1); ?>%</span>
                            </div>
                        </td>
                        <?php if ($tiene_k2b): ?>
                        <td class="text-end">$ <?php echo number_format($gt_ppto_k2b, 2, ',', '.'); ?></td>
                        <td class="text-end">U$D <?php echo number_format($gt_oc, 2, ',', '.'); ?></td>
                        <td class="text-end" style="color:<?php echo ($gt_saldo_k2b >= 0) ? '#198754' : '#dc3545'; ?>">
                            U$D <?php echo number_format($gt_saldo_k2b, 2, ',', '.'); ?>
                        </td>
                        <td class="text-center"><?php echo $gt_k2b_sr; ?></td>
                        <?php endif; ?>
                    </tr>
<?php endif; ?>
<?php if ($n === 0): ?>
                    <tr><td colspan="<?php echo $col_count; ?>" class="text-center text-muted py-4">No hay datos de partidas para este presupuesto.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>