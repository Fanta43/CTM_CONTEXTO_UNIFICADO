<?php
/**
 * ============================================================================
 * Saldos por Ítem de Gasto — SR K2B
 * ============================================================================
 * Tabla: pres_k2b_sr_logs, pres_k2b_presupuestos, ciclo_item_gasto
 * Vista de saldo presupuestario: presupuesto K2B autorizado vs OC comprometidas
 * por ítem de gasto. Incluye semáforo de colores y detalle expandible.
 * ============================================================================
 */

$db = '1';

$rol_usuario    = $_SESSION['ciclo_rol_efectivo'] ?? '';
$unidad_usuario = $_SESSION['id_unidad'] ?? '';

// ── Filtros ────────────────────────────────────────────────────────────────────
$filtro_periodo = Enmascarar_get('periodo');
$filtro_unidad  = Enmascarar_get('unidad');
$item_sel       = Enmascarar_get('item'); // para expandir un ítem

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['filtro_periodo'])) $filtro_periodo = Enmascarar_post('filtro_periodo');
    if (!empty($_POST['filtro_unidad']))  $filtro_unidad  = Enmascarar_post('filtro_unidad');
}

// Default: período abierto
if (empty($filtro_periodo) || $filtro_periodo === 'null') {
    $row_ab = sql_do_array(sql_do_query("SELECT id FROM ciclo_periodo_fiscal WHERE estado = 'abierto' LIMIT 1;", $db, $usu));
    $filtro_periodo = $row_ab['ID'] ?? '';
}

// Obtener año
$anio_sel = '';
if (!empty($filtro_periodo)) {
    $row_per = sql_do_array(sql_do_query("SELECT anio FROM ciclo_periodo_fiscal WHERE id = '$filtro_periodo';", $db, $usu));
    $anio_sel = $row_per['ANIO'] ?? '';
}
$anio_corto = !empty($anio_sel) ? substr($anio_sel, -2) : '';

// ── WHERE para SR ───────────────────────────────────────────────────────────
$where_sr = "WHERE sr.esquema_anio = '$anio_sel' AND sr.codigo IS NOT NULL AND sr.codigo != ''";
if (!empty($filtro_unidad) && $filtro_unidad !== 'null') {
    $where_sr .= " AND sr.esquema_cc = (SELECT cc FROM ciclo_unidad_organizativa WHERE id = '$filtro_unidad')";
}

// Estados que imputan al saldo (todos excepto RECHAZADA y FINALIZADA)
$estados_imputan = "'AUTORIZADA','ENPROCESOAUTORIZACION','ENPROCESOCOMPRA','CUMPLIDA','CUMPLIMIENTOSTOCK'";

// ── Saldos por ítem ─────────────────────────────────────────────────────────
$sql_saldos = "SELECT ig.codigo AS ITEM_CODIGO, ig.nombre AS ITEM_NOMBRE,
                      COALESCE(pk.importe_planificado, 0) AS PPTO_K2B,
                      COALESCE(oc_all.total_oc, 0) AS OC_TOTAL,
                      COALESCE(oc_imp.total_oc_imp, 0) AS OC_IMPUTADO,
                      COUNT(DISTINCT sr.codigo) AS CANT_SR,
                      COUNT(*) AS CANT_LINEAS
               FROM pres_k2b_sr_logs sr
               INNER JOIN ciclo_item_gasto ig ON sr.esquema_gastoid = ig.codigo
               LEFT JOIN (
                   SELECT esquema_gastoid, SUM(sr_monto) AS total_oc FROM (
                       SELECT esquema_gastoid, codigo,
                              CASE WHEN SUM(monto_linea_oc_usd) > 0 THEN SUM(monto_linea_oc_usd)
                                   ELSE MAX(importe_total) END AS sr_monto
                       FROM pres_k2b_sr_logs
                       $where_sr
                       GROUP BY esquema_gastoid, codigo
                   ) sr_oc GROUP BY esquema_gastoid
               ) oc_all ON oc_all.esquema_gastoid = ig.codigo
               LEFT JOIN (
                   SELECT esquema_gastoid, SUM(sr_monto) AS total_oc_imp FROM (
                       SELECT esquema_gastoid, codigo,
                              CASE WHEN SUM(monto_linea_oc_usd) > 0 THEN SUM(monto_linea_oc_usd)
                                   ELSE MAX(importe_total) END AS sr_monto
                       FROM pres_k2b_sr_logs
                       $where_sr AND estado IN ($estados_imputan)
                       GROUP BY esquema_gastoid, codigo
                   ) sr_oc2 GROUP BY esquema_gastoid
               ) oc_imp ON oc_imp.esquema_gastoid = ig.codigo
               LEFT JOIN (
                   SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(p.campo0, '-', 3), '-', -1) AS item_code,
                          p.importe_planificado
                   FROM pres_k2b_presupuestos p
                   WHERE p.campo0 REGEXP '^[0-9]{2}-[0-9]+-[0-9]+-'
                     AND SUBSTRING_INDEX(p.campo0, '-', 1) = '$anio_corto'
                     AND p.id IN (SELECT MAX(id) FROM pres_k2b_presupuestos GROUP BY campo0)
               ) pk ON pk.item_code = ig.codigo
               $where_sr
               GROUP BY ig.codigo, ig.nombre, ig.orden, pk.importe_planificado,
                        oc_all.total_oc, oc_imp.total_oc_imp
               ORDER BY ig.orden, ig.codigo;";

// ── Detalle de SRs para ítem expandido ──────────────────────────────────────
$detalle_item = [];
if (!empty($item_sel)) {
    $sql_det = "SELECT sr.codigo, sr.fecha, sr.estado, sr.usuario, sr.descripcion,
                       sr.precio_unitario, sr.cantidad_autorizada,
                       (sr.precio_unitario * sr.cantidad_autorizada) AS IMPORTE_LINEA,
                       sr.importe_total, sr.orden_compra, sr.proveedor, sr.monto_linea_oc_usd,
                       sr.orden_stock
                FROM pres_k2b_sr_logs sr
                $where_sr AND sr.esquema_gastoid = '$item_sel'
                ORDER BY sr.codigo, sr.lin;";
    $res_det = sql_do_query($sql_det, $db, $usu);
    while ($rd = sql_do_array($res_det)) {
        $detalle_item[] = $rd;
    }
}

// Combos
$sql_combo_per = "SELECT id AS ID, CONCAT(anio, ' (', estado, ')') AS NOMBRE FROM ciclo_periodo_fiscal ORDER BY anio DESC;";
$sql_combo_uni = "SELECT id AS ID, nombre AS NOMBRE FROM ciclo_unidad_organizativa ORDER BY nombre;";
?>

<?php echo generarTituloSeccion('Saldos por Ítem de Gasto — ' . $anio_sel, 'bi bi-wallet2'); ?>

<!-- FILTROS -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Período</label>
                    <?php echo imp_combo_w_oc('filtro_periodo', $sql_combo_per, $db, $usu, $filtro_periodo); ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Unidad</label>
                    <?php echo imp_combo_w_oc('filtro_unidad', $sql_combo_uni, $db, $usu, $filtro_unidad); ?>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-pade btn-sm">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                    <a href="index.php?estado=<?php echo $estado; ?>" class="btn btn-outline-secondary btn-sm ms-1">
                        <i class="bi bi-x-circle me-1"></i>Limpiar
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- TABLA DE SALDOS -->
<?php
$res_saldos = sql_do_query($sql_saldos, $db, $usu);
$gt_ppto = 0; $gt_oc = 0; $gt_imp = 0; $gt_saldo = 0; $gt_sr = 0;
?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white py-2">
        <h6 class="mb-0"><i class="bi bi-table me-2"></i>Saldos Presupuestarios por Ítem</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:30px;"></th>
                        <th>Ítem</th>
                        <th>Nombre</th>
                        <th class="text-end">Ppto K2B</th>
                        <th class="text-end">Total OC</th>
                        <th class="text-end">Imputado</th>
                        <th class="text-end">Saldo</th>
                        <th>% Consumo</th>
                        <th class="text-center">SR</th>
                    </tr>
                </thead>
                <tbody>
<?php
$n = 0;
while ($r = sql_do_array($res_saldos)):
    $n++;
    $ppto  = (float)($r['PPTO_K2B'] ?? 0);
    $oc    = (float)($r['OC_TOTAL'] ?? 0);
    $imp   = (float)($r['OC_IMPUTADO'] ?? 0);
    $saldo = $ppto - $imp;
    $pct   = ($ppto > 0) ? round(($imp / $ppto) * 100, 1) : ($imp > 0 ? 100 : 0);
    $sr_n  = (int)($r['CANT_SR'] ?? 0);
    $item_codigo = $r['ITEM_CODIGO'] ?? '';
    $es_expandido = ($item_sel === $item_codigo);

    $gt_ppto  += $ppto;
    $gt_oc    += $oc;
    $gt_imp   += $imp;
    $gt_saldo += $saldo;
    $gt_sr    += $sr_n;

    // Semáforo
    $row_class = '';
    $pb_class = 'bg-success';
    if ($pct >= 95) { $row_class = 'table-danger'; $pb_class = 'bg-danger'; }
    elseif ($pct >= 80) { $row_class = 'table-warning'; $pb_class = 'bg-warning'; }
    $pb_pct = min($pct, 100);
?>
                    <tr class="<?php echo $row_class; ?>" style="cursor:pointer;"
                        onclick="window.location='index.php?estado=<?php echo $estado; ?>&item=<?php echo ($es_expandido ? '' : urlencode($item_codigo)); ?>'">
                        <td class="text-center">
                            <i class="bi bi-<?php echo $es_expandido ? 'chevron-down' : 'chevron-right'; ?> text-muted"></i>
                        </td>
                        <td class="fw-bold"><?php echo htmlspecialchars($item_codigo); ?></td>
                        <td><?php echo htmlspecialchars($r['ITEM_NOMBRE'] ?? '—'); ?></td>
                        <td class="text-end">$ <?php echo number_format($ppto, 2, ',', '.'); ?></td>
                        <td class="text-end">U$D <?php echo number_format($oc, 2, ',', '.'); ?></td>
                        <td class="text-end fw-semibold">U$D <?php echo number_format($imp, 2, ',', '.'); ?></td>
                        <td class="text-end fw-bold" style="color:<?php echo ($saldo >= 0) ? '#198754' : '#dc3545'; ?>">
                            U$D <?php echo number_format($saldo, 2, ',', '.'); ?>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:8px;">
                                    <div class="progress-bar <?php echo $pb_class; ?>" style="width:<?php echo $pb_pct; ?>%;"></div>
                                </div>
                                <span class="small fw-semibold" style="min-width:42px;text-align:right;"><?php echo number_format($pct, 1); ?>%</span>
                            </div>
                        </td>
                        <td class="text-center"><span class="badge bg-primary"><?php echo $sr_n; ?></span></td>
                    </tr>
<?php
    // ── Detalle expandido ────────────────────────────────────────────────
    if ($es_expandido && !empty($detalle_item)):
        // Agrupar por SR
        $sr_groups = []; $sr_oc_totals = [];
        foreach ($detalle_item as $fila) {
            $sc = $fila['CODIGO'] ?? '';
            if (!isset($sr_groups[$sc])) { $sr_groups[$sc] = []; $sr_oc_totals[$sc] = 0.0; }
            $sr_groups[$sc][] = $fila;
            $sr_oc_totals[$sc] += (float)($fila['MONTO_LINEA_OC_USD'] ?? 0);
        }
        foreach ($sr_oc_totals as $sc => $ocs) {
            if ($ocs <= 0 && !empty($sr_groups[$sc])) {
                $sr_oc_totals[$sc] = (float)($sr_groups[$sc][0]['IMPORTE_TOTAL'] ?? 0);
            }
        }
?>
                    <tr>
                        <td colspan="9" class="p-0">
                            <div class="border-top border-bottom" style="border-left:4px solid #0dcaf0; background:#f0fbff;">
                                <div class="px-3 py-1"><span class="small fw-semibold text-info"><i class="bi bi-box-arrow-in-down-right me-1"></i>Solicitudes del ítem <?php echo htmlspecialchars($item_codigo); ?> — <?php echo count($sr_groups); ?> SR</span></div>
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
                                                <th class="text-end">OC (USD)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
<?php
        $sr_idx = 0;
        foreach ($sr_groups as $sr_code => $sr_rows):
            $sr_idx++;
            $row_num = 0;
            $sr_bg = ($sr_idx % 2 === 0) ? 'background:#e8f4fd;' : 'background:#f0fbff;';
            $sr_oc_total = $sr_oc_totals[$sr_code] ?? 0.0;
            foreach ($sr_rows as $rd):
                $row_num++;
                $is_first = ($row_num === 1);
                $row_count = count($sr_rows);
                $est_k2b = $rd['ESTADO'] ?? '';
                if ($est_k2b === 'FINALIZADA') $est_icon = '2';
                elseif ($est_k2b === 'RECHAZADA') $est_icon = '5';
                elseif ($est_k2b === 'CUMPLIDA' || $est_k2b === 'CUMPLIMIENTOSTOCK') $est_icon = '4';
                else $est_icon = '3';
                if ($est_icon === '3' && (!empty($rd['ORDEN_COMPRA'] ?? '') || (float)($rd['MONTO_LINEA_OC_USD'] ?? 0) > 0)) $est_icon = '4';
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
                                                <td class="text-end small fw-semibold"><?php if ($is_first): ?>U$D <?php echo number_format($sr_oc_total, 2, ',', '.'); ?><?php endif; ?></td>
                                            </tr>
<?php
            endforeach;
        endforeach;
?>
                                            <tr class="fw-bold small" style="background:#d1ecf1;">
                                                <td colspan="8" class="text-end" style="padding-left:1.5rem;">Total OC Ítem</td>
                                                <td class="text-end">U$D <?php echo number_format(array_sum($sr_oc_totals), 2, ',', '.'); ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>
<?php
    endif; // es_expandido
endwhile;
?>
<?php if ($n > 0): ?>
                    <tr class="table-dark fw-bold">
                        <td></td>
                        <td colspan="2" class="text-end">TOTALES</td>
                        <td class="text-end">$ <?php echo number_format($gt_ppto, 2, ',', '.'); ?></td>
                        <td class="text-end">U$D <?php echo number_format($gt_oc, 2, ',', '.'); ?></td>
                        <td class="text-end">U$D <?php echo number_format($gt_imp, 2, ',', '.'); ?></td>
                        <td class="text-end" style="color:<?php echo ($gt_saldo >= 0) ? '#198754' : '#dc3545'; ?>">
                            U$D <?php echo number_format($gt_saldo, 2, ',', '.'); ?>
                        </td>
                        <td></td>
                        <td class="text-center"><?php echo $gt_sr; ?></td>
                    </tr>
<?php endif; ?>
<?php if ($n === 0): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Sin datos de saldos K2B para este período.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
