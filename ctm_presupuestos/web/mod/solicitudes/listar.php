<?php
/**
 * ============================================================================
 * Listado de Solicitudes de Recurso (SR) — K2B
 * ============================================================================
 * Tabla principal: pres_k2b_sr_logs
 * Muestra todas las SR importadas desde K2B, agrupadas por ítem de gasto.
 * Permite filtrar por período, unidad, estado K2B y configurar qué estados
 * imputan al saldo. Incluye resumen por ítem y detalle con accordion.
 * Acciones: ver detalle individual de SR, notificar por email.
 * ============================================================================
 */

$db = '1';

// ── Sub-página: Notificar SR seleccionadas ──────────────────────────────────
if (isset($_GET['accion']) && $_GET['accion'] === 'notificar' && !empty($_POST['sr_codigos'])) {
    include_once __DIR__ . '/notificar.php';
    return;
}

// ── Sub-página: Detalle de una SR individual ────────────────────────────────
if (isset($_GET['accion']) && $_GET['accion'] === 'detalle' && !empty($_GET['sr'])) {
    include_once __DIR__ . '/detalle_sr.php';
    return;
}

$rol_usuario    = $_SESSION['ciclo_rol_efectivo'] ?? '';
$unidad_usuario = $_SESSION['id_unidad'] ?? '';

// ── Estados disponibles y selección por defecto para cálculo de saldo ───────
$estados_disponibles = [
    'AUTORIZADA'            => 'Autorizada',
    'ENPROCESOAUTORIZACION' => 'En Proceso Autorización',
    'ENPROCESOCOMPRA'       => 'En Proceso Compra',
    'CUMPLIDA'              => 'Cumplida',
    'CUMPLIMIENTOSTOCK'     => 'Cumplimiento Stock',
    'FINALIZADA'            => 'Finalizada',
    'RECHAZADA'             => 'Rechazada',
];
// Por defecto: todos excepto RECHAZADA y FINALIZADA
$estados_saldo_default = ['AUTORIZADA','ENPROCESOAUTORIZACION','ENPROCESOCOMPRA','CUMPLIDA','CUMPLIMIENTOSTOCK'];

// ── Filtros ────────────────────────────────────────────────────────────────────
$filtro_periodo = Enmascarar_get('periodo');
$filtro_unidad  = Enmascarar_get('unidad');
$filtro_estado  = Enmascarar_get('filtro_estado');
$estados_saldo  = $estados_saldo_default;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['filtro_periodo']))  $filtro_periodo = Enmascarar_post('filtro_periodo');
    if (!empty($_POST['filtro_unidad']))   $filtro_unidad  = Enmascarar_post('filtro_unidad');
    if (!empty($_POST['filtro_estado']))   $filtro_estado  = Enmascarar_post('filtro_estado');
    // Capturar checkboxes de estados para saldo
    if (isset($_POST['estados_saldo'])) {
        $estados_saldo = array_intersect($_POST['estados_saldo'], array_keys($estados_disponibles));
    } else {
        $estados_saldo = []; // si se envió el form sin ninguno marcado
    }
}

// Default: año actual
if (empty($filtro_periodo) || $filtro_periodo === 'null') {
    $row_ab = sql_do_array(sql_do_query("SELECT id FROM ciclo_periodo_fiscal WHERE estado = 'abierto' LIMIT 1;", $db, $usu));
    $filtro_periodo = $row_ab['ID'] ?? '';
}

// Obtener año del período seleccionado
$anio_sel = '';
if (!empty($filtro_periodo)) {
    $row_per = sql_do_array(sql_do_query("SELECT anio FROM ciclo_periodo_fiscal WHERE id = '$filtro_periodo';", $db, $usu));
    $anio_sel = $row_per['ANIO'] ?? '';
}

// ── Parsear esquema de registros nuevos ─────────────────────────────────────
if (!empty($anio_sel)) {
    sql_do_query("UPDATE pres_k2b_sr_logs
                  SET esquema_anio = CAST(SUBSTRING_INDEX(esquema, '-', 1) AS UNSIGNED) + 2000,
                      esquema_cc = CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(esquema, '-', 2), '-', -1) AS UNSIGNED),
                      esquema_gastoid = CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(esquema, '-', 3), '-', -1) AS UNSIGNED)
                  WHERE esquema IS NOT NULL AND esquema != '' AND esquema LIKE '%-%'
                        AND esquema_anio IS NULL;", $db, $usu);

    // Resolver FKs
    sql_do_query("UPDATE pres_k2b_sr_logs sr
                  JOIN ciclo_periodo_fiscal pf ON pf.anio = sr.esquema_anio
                  SET sr.id_periodo = pf.id
                  WHERE sr.esquema_anio IS NOT NULL AND sr.id_periodo IS NULL;", $db, $usu);

    sql_do_query("UPDATE pres_k2b_sr_logs sr
                  JOIN ciclo_unidad_organizativa uo ON uo.cc = sr.esquema_cc
                  SET sr.id_unidad = uo.id
                  WHERE sr.esquema_cc IS NOT NULL AND sr.id_unidad IS NULL;", $db, $usu);

    sql_do_query("UPDATE pres_k2b_sr_logs sr
                  JOIN ciclo_item_gasto ig ON ig.codigo = sr.esquema_gastoid
                  SET sr.id_item = ig.id
                  WHERE sr.esquema_gastoid IS NOT NULL AND sr.id_item IS NULL;", $db, $usu);
}

// ── Construir WHERE ──────────────────────────────────────────────────────────
$where = "WHERE sr.esquema_anio = '$anio_sel' AND sr.codigo IS NOT NULL AND sr.codigo != ''";
if (!empty($filtro_unidad) && $filtro_unidad !== 'null') {
    $where .= " AND sr.id_unidad = '$filtro_unidad'";
}
if (!empty($filtro_estado) && $filtro_estado !== 'null') {
    $where .= " AND sr.estado = '$filtro_estado'";
}

// Año corto para filtrar presupuestos K2B (ej: 2025 → '25')
$anio_corto = !empty($anio_sel) ? substr($anio_sel, -2) : '';

// Condición SQL para estados que restan del saldo
$estados_saldo_sql = '';
if (!empty($estados_saldo)) {
    $escaped = array_map(function($e) { return "'" . addslashes($e) . "'"; }, $estados_saldo);
    $estados_saldo_sql = implode(',', $escaped);
}

// ── Resumen por item ─────────────────────────────────────────────────────────
$sql_resumen = "SELECT ig.codigo AS ITEM_CODIGO, ig.nombre AS ITEM_NOMBRE,
                       COUNT(DISTINCT sr.codigo) AS CANT_SR,
                       COUNT(*) AS CANT_LINEAS,
                       COALESCE(oc_item.total_oc, 0) AS TOTAL_OC,
                       " . ($estados_saldo_sql ? "COALESCE(oc_saldo.total_oc_saldo, 0)" : "0") . " AS TOTAL_OC_SALDO,
                       pk.importe_planificado AS PPTO_AUTORIZADO
                FROM pres_k2b_sr_logs sr
                INNER JOIN ciclo_item_gasto ig ON sr.esquema_gastoid = ig.codigo
                LEFT JOIN (
                    SELECT esquema_gastoid, SUM(sr_monto) AS total_oc FROM (
                        SELECT esquema_gastoid, codigo,
                               CASE WHEN SUM(monto_linea_oc_usd) > 0 THEN SUM(monto_linea_oc_usd)
                                    ELSE MAX(importe_total) END AS sr_monto
                        FROM pres_k2b_sr_logs
                        WHERE esquema_anio = '$anio_sel' AND codigo IS NOT NULL AND codigo != ''
                        GROUP BY esquema_gastoid, codigo
                    ) sr_oc GROUP BY esquema_gastoid
                ) oc_item ON oc_item.esquema_gastoid = ig.codigo
                " . ($estados_saldo_sql ? "LEFT JOIN (
                    SELECT esquema_gastoid, SUM(sr_monto) AS total_oc_saldo FROM (
                        SELECT esquema_gastoid, codigo,
                               CASE WHEN SUM(monto_linea_oc_usd) > 0 THEN SUM(monto_linea_oc_usd)
                                    ELSE MAX(importe_total) END AS sr_monto
                        FROM pres_k2b_sr_logs
                        WHERE esquema_anio = '$anio_sel' AND codigo IS NOT NULL AND codigo != ''
                          AND estado IN ($estados_saldo_sql)
                        GROUP BY esquema_gastoid, codigo
                    ) sr_oc2 GROUP BY esquema_gastoid
                ) oc_saldo ON oc_saldo.esquema_gastoid = ig.codigo" : "") . "
                LEFT JOIN (
                    SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(p.campo0, '-', 3), '-', -1) AS item_code,
                           p.importe_planificado
                    FROM pres_k2b_presupuestos p
                    WHERE p.campo0 REGEXP '^[0-9]{2}-[0-9]+-[0-9]+-'
                      AND SUBSTRING_INDEX(p.campo0, '-', 1) = '$anio_corto'
                      AND p.id IN (SELECT MAX(id) FROM pres_k2b_presupuestos GROUP BY campo0)
                ) pk ON pk.item_code = ig.codigo
                $where
                GROUP BY ig.codigo, ig.nombre, ig.orden, pk.importe_planificado, oc_item.total_oc
                         " . ($estados_saldo_sql ? ", oc_saldo.total_oc_saldo" : "") . "
                ORDER BY ig.orden, ig.codigo;";

// ── Detalle de SRs ──────────────────────────────────────────────────────────
$sql_detalle = "SELECT sr.codigo, sr.fecha, sr.estado, sr.usuario, sr.descripcion,
                       sr.matricula, sr.clasificacion_producto, sr.categoria,
                       sr.precio_unitario, sr.cantidad_solicitada, sr.cantidad_autorizada,
                       (sr.precio_unitario * sr.cantidad_autorizada) AS IMPORTE_LINEA,
                       sr.importe_total, sr.esquema,
                       sr.orden_compra, sr.proveedor, sr.monto_linea_oc_usd,
                       sr.orden_stock, sr.pedido, sr.estado_orden_compra,
                       sr.fecha_entrega_sol, sr.fecha_entrega_fin,
                       sr.cantidad_comprada, sr.cantidad_recepcionada,
                       CAST(ig.codigo AS CHAR) AS ITEM_CODIGO, ig.nombre AS ITEM_NOMBRE,
                       uo.nombre AS UNIDAD_NOMBRE
                FROM pres_k2b_sr_logs sr
                INNER JOIN ciclo_item_gasto ig ON sr.esquema_gastoid = ig.codigo
                LEFT JOIN ciclo_unidad_organizativa uo ON sr.esquema_cc = uo.cc
                $where
                ORDER BY ig.orden, ig.codigo, sr.codigo, sr.lin;";

// Combos
$sql_combo_per = "SELECT id AS ID, CONCAT(anio, ' (', estado, ')') AS NOMBRE FROM ciclo_periodo_fiscal ORDER BY anio DESC;";
$sql_combo_uni = "SELECT id AS ID, nombre AS NOMBRE FROM ciclo_unidad_organizativa ORDER BY nombre;";
?>

<?php echo generarTituloSeccion('Solicitudes de Recurso — Período ' . $anio_sel, 'bi bi-box-arrow-in-down-right'); ?>

<!-- FILTROS -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>" id="frmFiltros">
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
                    <label class="form-label small fw-semibold mb-1">Estado K2B</label>
                    <select class="form-select" name="filtro_estado" onchange="this.form.submit()">
                        <option value="null">Todos</option>
<?php foreach ($estados_disponibles as $ek => $el): ?>
                        <option value="<?php echo $ek; ?>" <?php echo ($filtro_estado === $ek) ? 'selected' : ''; ?>><?php echo $el; ?></option>
<?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <a href="index.php?estado=<?php echo $estado; ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-circle me-1"></i>Limpiar
                    </a>
                </div>
            </div>
            <hr class="my-2">
            <div class="row align-items-center">
                <div class="col-auto">
                    <span class="small fw-semibold text-muted"><i class="bi bi-gear me-1"></i>Estados que imputan al saldo:</span>
                </div>
                <?php foreach ($estados_disponibles as $est_key => $est_label): ?>
                <div class="col-auto">
                    <div class="form-check form-check-inline mb-0">
                        <input class="form-check-input" type="checkbox" name="estados_saldo[]"
                               value="<?php echo $est_key; ?>" id="chk_<?php echo $est_key; ?>"
                               <?php echo in_array($est_key, $estados_saldo) ? 'checked' : ''; ?>
                               onchange="document.getElementById('frmFiltros').submit();">
                        <label class="form-check-label small" for="chk_<?php echo $est_key; ?>"><?php echo $est_label; ?></label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </form>
    </div>
</div>

<!-- RESUMEN POR ITEM -->
<?php
$res_resumen = sql_do_query($sql_resumen, $db, $usu);
$gt_sr = 0; $gt_lin = 0; $gt_oc = 0; $gt_oc_saldo = 0; $gt_ppto = 0; $gt_saldo = 0;
?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white py-2">
        <h6 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Resumen por Ítem de Gasto</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Ítem</th>
                        <th>Nombre</th>
                        <th class="text-end">Ppto Autorizado</th>
                        <th class="text-center">SR</th>
                        <th class="text-center">Líneas</th>
                        <th class="text-end">Total OC (USD)</th>
                        <th class="text-end">Imputado (USD)</th>
                        <th class="text-end">Saldo</th>
                    </tr>
                </thead>
                <tbody>
<?php
$n = 0;
while ($rr = sql_do_array($res_resumen)):
    $n++;
    $gt_sr       += (int)$rr['CANT_SR'];
    $gt_lin      += (int)$rr['CANT_LINEAS'];
    $gt_oc       += (float)($rr['TOTAL_OC'] ?? 0);
    $gt_oc_saldo += (float)($rr['TOTAL_OC_SALDO'] ?? 0);
    $gt_ppto     += (float)($rr['PPTO_AUTORIZADO'] ?? 0);
    $oc_saldo = (float)($rr['TOTAL_OC_SALDO'] ?? 0);
    $saldo = (float)($rr['PPTO_AUTORIZADO'] ?? 0) - $oc_saldo;
    $gt_saldo += $saldo;
?>
                    <tr class="<?php echo claseFilaAlternada($n); ?>">
                        <td class="fw-bold"><?php echo htmlspecialchars($rr['ITEM_CODIGO'] ?? 'Sin vincular'); ?></td>
                        <td><?php echo htmlspecialchars($rr['ITEM_NOMBRE'] ?? '—'); ?></td>
                        <td class="text-end">$ <?php echo number_format((float)($rr['PPTO_AUTORIZADO'] ?? 0), 2, ',', '.'); ?></td>
                        <td class="text-center"><span class="badge bg-primary"><?php echo $rr['CANT_SR']; ?></span></td>
                        <td class="text-center"><span class="badge bg-secondary"><?php echo $rr['CANT_LINEAS']; ?></span></td>
                        <td class="text-end">U$D <?php echo number_format((float)($rr['TOTAL_OC'] ?? 0), 2, ',', '.'); ?></td>
                        <td class="text-end fw-semibold">U$D <?php echo number_format($oc_saldo, 2, ',', '.'); ?></td>
                        <td class="text-end fw-bold" style="color:<?php echo ($saldo >= 0) ? '#198754' : '#dc3545'; ?>">
                            U$D <?php echo number_format($saldo, 2, ',', '.'); ?>
                        </td>
                    </tr>
<?php endwhile; ?>
<?php if ($n > 0): ?>
                    <tr class="table-dark fw-bold">
                        <td colspan="2" class="text-end">TOTALES</td>
                        <td class="text-end">$ <?php echo number_format($gt_ppto, 2, ',', '.'); ?></td>
                        <td class="text-center"><?php echo $gt_sr; ?></td>
                        <td class="text-center"><?php echo $gt_lin; ?></td>
                        <td class="text-end">U$D <?php echo number_format($gt_oc, 2, ',', '.'); ?></td>
                        <td class="text-end">U$D <?php echo number_format($gt_oc_saldo, 2, ',', '.'); ?></td>
                        <td class="text-end" style="color:<?php echo ($gt_saldo >= 0) ? '#198754' : '#dc3545'; ?>">
                            U$D <?php echo number_format($gt_saldo, 2, ',', '.'); ?>
                        </td>
                    </tr>
<?php endif; ?>
<?php if ($n === 0): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Sin datos K2B para este período.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- DETALLE DE SRs AGRUPADO POR ITEM -->
<form method="post" action="index.php?estado=<?php echo $estado; ?>&accion=notificar" id="frmNotificar">
<?php
$res_detalle = sql_do_query($sql_detalle, $db, $usu);
// Agrupar filas por item y por SR
$grupos = [];
while ($rd = sql_do_array($res_detalle)) {
    $key = (string)($rd['ITEM_CODIGO'] ?? 'SIN_ITEM');
    if (!isset($grupos[$key])) {
        $grupos[$key] = [
            'codigo' => $rd['ITEM_CODIGO'] ?? '',
            'nombre' => $rd['ITEM_NOMBRE'] ?? '',
            'filas'  => [],
            'total'  => 0.0,
        ];
    }
    $grupos[$key]['filas'][] = $rd;
}

// Agrupar renglones por SR dentro de cada ítem y calcular total OC por SR
foreach ($grupos as &$grupo) {
    $sr_groups = [];
    $sr_oc_totals = [];
    foreach ($grupo['filas'] as $fila) {
        $sr_code = $fila['CODIGO'] ?? '';
        if (!isset($sr_groups[$sr_code])) {
            $sr_groups[$sr_code] = [];
            $sr_oc_totals[$sr_code] = 0.0;
        }
        $sr_groups[$sr_code][] = $fila;
        $sr_oc_totals[$sr_code] += (float)($fila['MONTO_LINEA_OC_USD'] ?? 0);
    }
    // Fallback: si una SR tiene SUM(monto_linea_oc_usd)=0, usar importe_total
    $grupo_total_recalc = 0.0;
    foreach ($sr_oc_totals as $sr_code => $oc_sum) {
        if ($oc_sum <= 0 && !empty($sr_groups[$sr_code])) {
            $sr_oc_totals[$sr_code] = (float)($sr_groups[$sr_code][0]['IMPORTE_TOTAL'] ?? 0);
        }
        $grupo_total_recalc += $sr_oc_totals[$sr_code];
    }
    $grupo['sr_groups'] = $sr_groups;
    $grupo['sr_oc_totals'] = $sr_oc_totals;
    $grupo['total'] = $grupo_total_recalc;
}
unset($grupo);
?>
<?php if (empty($grupos)): ?>
<div class="card shadow-sm">
    <div class="card-header bg-info text-dark">
        <h6 class="mb-0"><i class="bi bi-list-ul me-2"></i>Detalle de Solicitudes de Recurso</h6>
    </div>
    <div class="card-body text-center text-muted py-4">Sin solicitudes de recurso vinculadas a ítems de gasto para los filtros seleccionados.</div>
</div>
<?php else: ?>

<!-- Barra de acciones para SR seleccionadas -->
<div class="card shadow-sm mb-2">
    <div class="card-body py-2 d-flex align-items-center gap-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="chkSelAll"
                   onchange="document.querySelectorAll('.chk-sr').forEach(c => c.checked = this.checked); updSelCount();">
            <label class="form-check-label small fw-semibold" for="chkSelAll">Seleccionar todas</label>
        </div>
        <button type="submit" class="btn btn-pade btn-sm">
            <i class="bi bi-envelope-paper me-1"></i>Notificar seleccionadas
        </button>
        <span class="text-muted small" id="spanSelCount"></span>
    </div>
</div>
<div class="accordion" id="accSR">
<?php $idx = 0; foreach ($grupos as $gkey => $grupo): $idx++; $aid = 'item_' . htmlspecialchars((string)$gkey); ?>
    <div class="accordion-item">
        <h2 class="accordion-header" id="head_<?php echo $aid; ?>">
            <button class="accordion-button <?php echo ($idx > 1) ? 'collapsed' : ''; ?>" type="button"
                    data-bs-toggle="collapse" data-bs-target="#col_<?php echo $aid; ?>">
                <span class="fw-bold me-2"><?php echo htmlspecialchars((string)$gkey); ?></span>
                <span class="me-3"><?php echo htmlspecialchars($grupo['nombre']); ?></span>
                <span class="badge bg-primary me-2"><?php echo count($grupo['sr_groups']); ?> SR</span>
                <span class="badge bg-secondary me-2"><?php echo count($grupo['filas']); ?> líneas</span>
                <span class="small text-muted">Total OC: U$D <?php echo number_format($grupo['total'], 2, ',', '.'); ?></span>
            </button>
        </h2>
        <div id="col_<?php echo $aid; ?>" class="accordion-collapse collapse <?php echo ($idx === 1) ? 'show' : ''; ?>" data-bs-parent="#accSR">
            <div class="accordion-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:30px;"></th>
                                <th>SR</th>
                                <th class="text-center">Fecha</th>
                                <th class="text-center">Estado</th>
                                <th>Usuario</th>
                                <th>Descripción</th>
                                <th class="text-end">P.Unit</th>
                                <th class="text-end">Cant.Aut</th>
                                <th>OC</th>
                                <th>Proveedor</th>
                                <th class="text-end">Total OC (USD)</th>
                            </tr>
                        </thead>
                        <tbody>
<?php
$sr_idx = 0;
foreach ($grupo['sr_groups'] as $sr_code => $sr_rows):
    $sr_idx++;
    $row_count = count($sr_rows);
    $row_num = 0;
    $sr_bg = ($sr_idx % 2 === 0) ? 'background:#f8f9fa;' : 'background:#fff;';
    $sr_oc_total = $grupo['sr_oc_totals'][$sr_code] ?? 0.0;
    $sr_estado = $sr_rows[0]['ESTADO'] ?? '';
    $sr_imputa = in_array($sr_estado, $estados_saldo);

    foreach ($sr_rows as $rd):
        $row_num++;
        $est_k2b = $rd['ESTADO'] ?? '';
        if ($est_k2b === 'FINALIZADA') $est_icon = '2';
        elseif ($est_k2b === 'RECHAZADA') $est_icon = '5';
        elseif ($est_k2b === 'CUMPLIDA' || $est_k2b === 'CUMPLIMIENTOSTOCK') $est_icon = '4';
        else $est_icon = '3';
        if ($est_icon === '3' && (!empty($rd['ORDEN_STOCK'] ?? '') || !empty($rd['ORDEN_COMPRA'] ?? '') || (float)($rd['MONTO_LINEA_OC_USD'] ?? 0) > 0)) $est_icon = '4';
        $is_first = ($row_num === 1);
?>
                            <tr style="<?php echo $sr_bg; ?><?php echo $is_first ? 'border-top:2px solid #dee2e6;' : ''; ?>">
                                <td class="text-center" style="width:30px;<?php echo $sr_bg; ?>">
                                    <?php if ($is_first): ?>
                                        <input type="checkbox" class="form-check-input chk-sr" name="sr_codigos[]" value="<?php echo htmlspecialchars($rd['CODIGO'] ?? ''); ?>" onchange="updSelCount();">
                                    <?php endif; ?>
                                </td>
                                <td class="text-nowrap" style="<?php echo $is_first ? 'font-weight:600;' : 'padding-left:2rem;color:#999;font-size:0.75rem;'; ?>">
                                    <?php if ($is_first): ?>
                                        <a href="index.php?estado=<?php echo $estado; ?>&accion=detalle&sr=<?php echo urlencode($rd['CODIGO'] ?? ''); ?>"
                                           class="text-decoration-none" title="Ver detalle de SR <?php echo htmlspecialchars($rd['CODIGO'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($rd['CODIGO'] ?? ''); ?>
                                        </a>
                                        <?php if ($row_count > 1): ?>
                                            <span class="text-muted" style="font-size:0.7rem;font-weight:normal;">(<?php echo $row_count; ?> lín.)</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        └ línea <?php echo $row_num; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center small"><?php echo $is_first ? enmascararfecha($rd['FECHA'] ?? '') : ''; ?></td>
                                <td class="text-center"><?php if ($is_first): ?><img title="<?php echo htmlspecialchars($est_k2b); ?>" src="img/ico/<?php echo $est_icon; ?>.png" width="24" height="24" /><?php endif; ?></td>
                                <td class="small"><?php echo $is_first ? htmlspecialchars($rd['USUARIO'] ?? '') : ''; ?></td>
                                <td class="small" title="<?php echo htmlspecialchars($rd['DESCRIPCION'] ?? ''); ?>">
                                    <?php echo htmlspecialchars(mb_substr($rd['DESCRIPCION'] ?? '', 0, 60)); ?><?php echo (mb_strlen($rd['DESCRIPCION'] ?? '') > 60) ? '...' : ''; ?>
                                </td>
                                <td class="text-end small">$ <?php echo number_format((float)($rd['PRECIO_UNITARIO'] ?? 0), 2, ',', '.'); ?></td>
                                <td class="text-end small"><?php echo number_format((float)($rd['CANTIDAD_AUTORIZADA'] ?? 0), 2, ',', '.'); ?></td>
                                <td class="small text-nowrap"><?php echo htmlspecialchars($rd['ORDEN_COMPRA'] ?? ''); ?></td>
                                <td class="small"><?php echo htmlspecialchars(mb_substr($rd['PROVEEDOR'] ?? '', 0, 30)); ?></td>
                                <td class="text-end small <?php echo $sr_imputa ? 'fw-semibold' : ''; ?>" <?php if (!$sr_imputa): ?>style="text-decoration:line-through;color:#999;" title="No imputa al saldo (<?php echo htmlspecialchars($sr_estado); ?>)"<?php endif; ?>><?php if ($is_first): ?>U$D <?php echo number_format($sr_oc_total, 2, ',', '.'); ?><?php endif; ?></td>
                            </tr>
<?php
    endforeach;
endforeach;
?>
                            <tr class="table-light fw-bold">
                                <td colspan="10" class="text-end">Subtotal Ítem</td>
                                <td class="text-end">U$D <?php echo number_format($grupo['total'], 2, ',', '.'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</form>

<script>
function updSelCount() {
    var n = document.querySelectorAll('.chk-sr:checked').length;
    var el = document.getElementById('spanSelCount');
    if (el) el.textContent = n > 0 ? n + ' SR seleccionada' + (n > 1 ? 's' : '') : '';
}
</script>
