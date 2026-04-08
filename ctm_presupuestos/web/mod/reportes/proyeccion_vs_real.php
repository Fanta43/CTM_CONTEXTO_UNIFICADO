<?php
/**
 * ============================================================================
 * Reporte: Proyección vs. Real
 * ============================================================================
 * ID menú: 503
 * Compara las proyecciones (borradores) vs. la ejecución real (OC K2B)
 * para evaluar la calidad de los factores de proyección aplicados.
 *
 * Fuentes de datos:
 *   - pres_sr_borrador        → Montos proyectados por ítem
 *   - pres_k2b_sr_logs        → Ejecución real (OC)
 *   - pres_k2b_presupuestos   → Presupuesto K2B aprobado
 *   - ciclo_config_proyeccion → Factores configurados
 *   - ciclo_partida           → Presupuesto ciclo (aprobado/ejecutado)
 * ============================================================================
 */

$db = '1';

// ── Filtros ──────────────────────────────────────────────────────────────────
$periodo_sel = Enmascarar_get('periodo');
$filtro_cc   = Enmascarar_get('cc');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['filtro_periodo'])) $periodo_sel = Enmascarar_post('filtro_periodo');
    if (!empty($_POST['filtro_cc']))      $filtro_cc   = Enmascarar_post('filtro_cc');
}
if (empty($periodo_sel) || $periodo_sel === 'null') {
    $row_ab = sql_do_array(sql_do_query("SELECT id FROM ciclo_periodo_fiscal WHERE estado = 'abierto' LIMIT 1;", $db, $usu));
    $periodo_sel = $row_ab['ID'] ?? '';
}
if (empty($filtro_cc) || $filtro_cc === 'null') $filtro_cc = '880';

// Info del período
$row_per = null;
$anio_sel = '';
if (!empty($periodo_sel)) {
    $row_per = sql_do_array(sql_do_query("SELECT * FROM ciclo_periodo_fiscal WHERE id = '$periodo_sel';", $db, $usu));
    $anio_sel = $row_per['ANIO'] ?? '';
}


// Combos
$sql_combo_per = "SELECT id AS ID, CONCAT(anio, ' (', estado, ')') AS NOMBRE FROM ciclo_periodo_fiscal ORDER BY anio DESC;";
$sql_combo_cc  = "SELECT cc AS ID, CONCAT(cc, ' — ', nombre) AS NOMBRE FROM ciclo_unidad_organizativa ORDER BY nombre;";

// ── Cargar datos ─────────────────────────────────────────────────────────────
$filas = [];
$t_borr_proy = 0; $t_oc_real = 0; $t_planif = 0; $t_causado = 0;

if (!empty($anio_sel) && !empty($filtro_cc)) {

    // 1) Borradores proyectados para este período+cc, agrupados por ítem
    $sql_borr = "SELECT b.esquema_gastoid AS ITEM_CODIGO,
                        ig.nombre AS ITEM_NOMBRE,
                        COUNT(*) AS CANT_BORR,
                        SUM(b.monto_estimado) AS TOTAL_PROYECTADO,
                        AVG(b.factor_aplicado) AS FACTOR_PROM,
                        SUM(b.precio_unitario_ref) AS TOTAL_REF
                 FROM pres_sr_borrador b
                 LEFT JOIN ciclo_item_gasto ig ON ig.codigo = b.esquema_gastoid
                 WHERE b.id_periodo_destino = '$periodo_sel'
                   AND b.esquema_cc = '$filtro_cc'
                   AND b.estado != 'descartada'
                 GROUP BY b.esquema_gastoid, ig.nombre
                 ORDER BY b.esquema_gastoid;";
    $res_borr = sql_do_query($sql_borr, $db, $usu);
    $borr_data = [];
    while ($rb = sql_do_array($res_borr)) {
        $borr_data[$rb['ITEM_CODIGO']] = $rb;
    }

    // 2) OC real K2B para este año+cc (mismo patrón que k2b_sr.php / detalle.php)
    $sql_oc = "SELECT esquema_gastoid, SUM(sr_monto) AS TOTAL_OC, COUNT(DISTINCT codigo) AS CANT_SR FROM (
                    SELECT sr.esquema_gastoid, sr.codigo,
                           CASE WHEN SUM(sr.monto_linea_oc_usd) > 0 THEN SUM(sr.monto_linea_oc_usd)
                                ELSE MAX(sr.importe_total) END AS sr_monto
                    FROM pres_k2b_sr_logs sr
                    INNER JOIN ciclo_item_gasto ig ON sr.esquema_gastoid = ig.codigo
                    WHERE sr.esquema_anio = '$anio_sel' AND sr.esquema_cc = '$filtro_cc'
                          AND sr.codigo IS NOT NULL AND sr.codigo != ''
                    GROUP BY sr.esquema_gastoid, sr.codigo
                ) sr_oc GROUP BY esquema_gastoid";
    $res_oc = sql_do_query($sql_oc, $db, $usu);
    $oc_data = [];
    while ($ro = sql_do_array($res_oc)) {
        $oc_data[$ro['ESQUEMA_GASTOID']] = $ro;
    }

    // 3) Presupuesto K2B del año en curso (planilla completa como origen)
    $anio_corto = substr($anio_sel, -2);
    $sql_ppto = "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(p.campo0, '-', 3), '-', -1) AS ITEM_CODE,
                        p.importe_planificado   AS PLANIFICADO,
                        p.importe_precomprometido AS PRECOMPROMETIDO,
                        p.importe_definitivo    AS DEFINITIVO,
                        p.importe_causado       AS CAUSADO,
                        p.pagado                AS PAGADO,
                        p.importe_disponible    AS DISPONIBLE
                 FROM pres_k2b_presupuestos p
                 INNER JOIN ciclo_item_gasto ig ON SUBSTRING_INDEX(SUBSTRING_INDEX(p.campo0, '-', 3), '-', -1) = ig.codigo
                 WHERE p.campo0 REGEXP '^[0-9]{2}-[0-9]+-[0-9]+-'
                   AND SUBSTRING_INDEX(p.campo0, '-', 1) = '$anio_corto'
                   AND p.id IN (SELECT MAX(id) FROM pres_k2b_presupuestos GROUP BY campo0)";
    $res_ppto = sql_do_query($sql_ppto, $db, $usu);
    $ppto_data = [];
    while ($rp = sql_do_array($res_ppto)) {
        $ppto_data[$rp['ITEM_CODE']] = $rp;
    }

    // 5) Ciclo partida (aprobado/ejecutado del ciclo interno)
    $sql_ciclo = "SELECT ig.codigo AS ITEM_CODIGO,
                         SUM(pa.monto_aprobado) AS CICLO_APROBADO,
                         SUM(pa.monto_ejecutado) AS CICLO_EJECUTADO
                  FROM ciclo_partida pa
                  JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                  JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
                  WHERE pr.id_periodo = '$periodo_sel'
                  GROUP BY ig.codigo";
    $res_ciclo = sql_do_query($sql_ciclo, $db, $usu);
    $ciclo_data = [];
    while ($rc = sql_do_array($res_ciclo)) {
        $ciclo_data[$rc['ITEM_CODIGO']] = $rc;
    }

    // 6) Factores configurados para este período destino
    $factores_config = [];
    $res_fact = sql_do_query(
        "SELECT cp.*, ig.codigo AS ITEM_CODIGO, cg.id AS CAT_FK_ID
         FROM ciclo_config_proyeccion cp
         LEFT JOIN ciclo_item_gasto ig ON cp.id_item = ig.id
         LEFT JOIN ciclo_categoria_gasto cg ON cp.id_categoria = cg.id
         WHERE cp.id_periodo_destino = '$periodo_sel'
         ORDER BY CASE WHEN cp.id_item IS NOT NULL THEN 1 WHEN cp.id_categoria IS NOT NULL THEN 2 ELSE 3 END;", $db, $usu);
    while ($rf = sql_do_array($res_fact)) $factores_config[] = $rf;
    $factor_global = null;
    foreach ($factores_config as $fc) {
        if (empty($fc['ID_ITEM']) && empty($fc['ID_CATEGORIA'])) { $factor_global = (float)$fc['FACTOR_AJUSTE']; break; }
    }

    // Consolidar: unión de todos los ítems que aparecen en cualquier fuente
    $all_items = array_unique(array_merge(
        array_keys($borr_data), array_keys($oc_data), array_keys($ppto_data)
    ));
    sort($all_items);

    foreach ($all_items as $item_code) {
        $bd = $borr_data[$item_code] ?? null;
        $od = $oc_data[$item_code] ?? null;
        $cd = $ciclo_data[$item_code] ?? null;

        $proy     = $bd ? (float)$bd['TOTAL_PROYECTADO'] : 0;
        $oc_real  = $od ? (float)$od['TOTAL_OC'] : 0;
        $pd       = $ppto_data[$item_code] ?? null;
        $planif   = $pd ? (float)$pd['PLANIFICADO'] : 0;
        $definitivo = $pd ? (float)$pd['DEFINITIVO'] : 0;
        $causado  = $pd ? (float)$pd['CAUSADO'] : 0;
        $pagado   = $pd ? (float)$pd['PAGADO'] : 0;
        $disponible = $pd ? (float)$pd['DISPONIBLE'] : 0;
        $ciclo_ap = $cd ? (float)$cd['CICLO_APROBADO'] : 0;
        $factor   = $bd ? (float)$bd['FACTOR_PROM'] : null;
        $cant_sr  = $od ? (int)$od['CANT_SR'] : 0;
        $cant_borr = $bd ? (int)$bd['CANT_BORR'] : 0;

        // Desviación proyección vs causado
        $desv = ($proy > 0) ? round(($causado - $proy) / $proy * 100, 2) : null;

        // % ejecución sobre planificado
        $pct_ejec = ($planif > 0) ? round($causado / $planif * 100, 1) : null;

        // Nombre e info del item
        $item_nombre = $bd['ITEM_NOMBRE'] ?? '';
        $cat_nombre  = '';
        $cat_codigo  = '';
        if (empty($item_nombre)) {
            $ri = sql_do_array(sql_do_query(
                "SELECT nombre FROM ciclo_item_gasto WHERE codigo = '$item_code' LIMIT 1;", $db, $usu));
            $item_nombre = $ri['NOMBRE'] ?? "Item $item_code";
        }
        // Categoría: buscar via ciclo_partida (que sí tiene id_categoria)
        if (empty($cat_nombre) && !empty($periodo_sel)) {
            $rc = sql_do_array(sql_do_query(
                "SELECT cg.nombre AS CAT_NOMBRE, cg.codigo AS CAT_CODIGO
                 FROM ciclo_partida pa
                 JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                 JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
                 JOIN ciclo_categoria_gasto cg ON pa.id_categoria = cg.id
                 WHERE pr.id_periodo = '$periodo_sel' AND ig.codigo = '$item_code'
                 LIMIT 1;", $db, $usu));
            $cat_nombre = $rc['CAT_NOMBRE'] ?? '';
            $cat_codigo = $rc['CAT_CODIGO'] ?? '';
        }
        $cat_orden = $cat_codigo ?: 'ZZZ';

        $filas[] = [
            'item_code'    => $item_code,
            'item_nombre'  => $item_nombre,
            'cat_nombre'   => $cat_nombre,
            'cat_codigo'   => $cat_codigo,
            'cat_orden'    => $cat_orden,
            'proy'         => $proy,
            'oc_real'      => $oc_real,
            'planif'       => $planif,
            'definitivo'   => $definitivo,
            'causado'      => $causado,
            'pagado'       => $pagado,
            'disponible'   => $disponible,
            'factor_config'=> $factor,
            'desv'         => $desv,
            'pct_ejec'     => $pct_ejec,
            'cant_sr'      => $cant_sr,
            'cant_borr'    => $cant_borr,
        ];

        $t_borr_proy  += $proy;
        $t_oc_real    += $oc_real;
        $t_planif     += $planif;
        $t_causado    += $causado;
    }

    // Ordenar por categoría → item
    usort($filas, function($a, $b) {
        $cmp = $a['cat_orden'] <=> $b['cat_orden'];
        return $cmp !== 0 ? $cmp : strcmp($a['item_code'], $b['item_code']);
    });
}

// Nombre CC
$nombre_cc = '';
if (!empty($filtro_cc)) {
    $rcc = sql_do_array(sql_do_query("SELECT nombre FROM ciclo_unidad_organizativa WHERE cc = '$filtro_cc' LIMIT 1;", $db, $usu));
    $nombre_cc = $rcc['NOMBRE'] ?? '';
}
?>

<?php echo generarTituloSeccion('Proyección vs. Real — ' . htmlspecialchars($anio_sel), 'bi bi-bullseye'); ?>

<!-- FILTROS -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Período destino</label>
                <?php echo imp_combo_w_oc('filtro_periodo', $sql_combo_per, $db, $usu, $periodo_sel); ?>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Centro de Costos</label>
                <?php echo imp_combo_w_oc('filtro_cc', $sql_combo_cc, $db, $usu, $filtro_cc); ?>
            </div>
            <div class="col-md-4">
                <div class="small text-muted mt-1">
                    <?php if (!empty($anio_sel)): ?>
                        <i class="bi bi-clipboard-data me-1"></i>Planilla K2B <strong><?php echo $anio_sel; ?></strong> vs. Proyección Borradores
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (empty($periodo_sel) || empty($filtro_cc)): ?>
<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Seleccione un período y centro de costos.</div>
<?php else: ?>

<!-- RESUMEN -->
<?php
    $gt_desv = ($t_borr_proy > 0) ? round(($t_causado - $t_borr_proy) / $t_borr_proy * 100, 2) : 0;
    $gt_pct_ejec = ($t_planif > 0) ? round($t_causado / $t_planif * 100, 1) : 0;
    $items_ok = 0; $items_warn = 0; $items_bad = 0;
    foreach ($filas as $f) {
        if ($f['desv'] === null) continue;
        if (abs($f['desv']) <= 15) $items_ok++;
        elseif (abs($f['desv']) <= 30) $items_warn++;
        else $items_bad++;
    }
?>
<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="card text-center shadow-sm border-start border-info border-4">
            <div class="card-body py-2">
                <small class="text-muted d-block">Planificado K2B</small>
                <div class="fw-bold text-info">U$D <?php echo number_format($t_planif, 0, ',', '.'); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center shadow-sm border-start border-primary border-4">
            <div class="card-body py-2">
                <small class="text-muted d-block">Proyectado (Borr.)</small>
                <div class="fw-bold text-primary">U$D <?php echo number_format($t_borr_proy, 0, ',', '.'); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center shadow-sm border-start border-success border-4">
            <div class="card-body py-2">
                <small class="text-muted d-block">Causado K2B</small>
                <div class="fw-bold text-success">U$D <?php echo number_format($t_causado, 0, ',', '.'); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center shadow-sm border-start border-4" style="border-color: <?php echo abs($gt_desv) > 30 ? '#dc3545' : (abs($gt_desv) > 15 ? '#ffc107' : '#198754'); ?> !important;">
            <div class="card-body py-2">
                <small class="text-muted d-block">Desv. Proy. vs Caus.</small>
                <div class="fw-bold" style="color: <?php echo abs($gt_desv) > 30 ? '#dc3545' : (abs($gt_desv) > 15 ? '#ffc107' : '#198754'); ?>;">
                    <?php echo ($gt_desv >= 0 ? '+' : '') . number_format($gt_desv, 1); ?>%
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center shadow-sm border-start border-secondary border-4">
            <div class="card-body py-2">
                <small class="text-muted d-block">% Ejecución Global</small>
                <div class="fw-bold"><?php echo number_format($gt_pct_ejec, 1); ?>%</div>
                <small class="text-muted">causado / planif.</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center shadow-sm border-start border-warning border-4">
            <div class="card-body py-2">
                <small class="text-muted d-block">Precisión Proy.</small>
                <div>
                    <span class="badge bg-success"><?php echo $items_ok; ?> OK</span>
                    <span class="badge bg-warning text-dark"><?php echo $items_warn; ?></span>
                    <span class="badge bg-danger"><?php echo $items_bad; ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TABLA DETALLE -->
<div class="card shadow-sm">
    <div class="card-header bg-primary bg-opacity-75 text-white py-2 d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-table me-2"></i>Detalle por Ítem — <?php echo htmlspecialchars($nombre_cc); ?> — <?php echo htmlspecialchars($anio_sel); ?></h6>
        <span class="badge bg-light text-dark"><?php echo count($filas); ?> ítems</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0" style="font-size: 0.82rem;">
                <thead class="table-primary">
                    <tr>
                        <th>Categoría</th>
                        <th class="text-center">Cód.</th>
                        <th>Ítem de Gasto</th>
                        <th class="text-end">Planificado<br><small class="fw-normal">K2B</small></th>
                        <th class="text-end">Definitivo<br><small class="fw-normal">K2B</small></th>
                        <th class="text-end">Proyectado<br><small class="fw-normal">borrador</small></th>
                        <th class="text-end">Causado<br><small class="fw-normal">K2B</small></th>
                        <th class="text-center">% Ejec.</th>
                        <th class="text-center">Desv.<br><small class="fw-normal">proy/caus</small></th>
                        <th class="text-center">SR</th>
                        <th class="text-center">Sugerencia</th>
                    </tr>
                </thead>
                <tbody>
<?php
$cat_anterior = '';
$n = 0;
foreach ($filas as $f):
    $n++;
    $mostrar_cat = ($f['cat_nombre'] !== $cat_anterior);
    $cat_anterior = $f['cat_nombre'];

    $desv = $f['desv'];
    $desv_class = 'text-success';
    if ($desv !== null) {
        if (abs($desv) > 30) $desv_class = 'text-danger fw-bold';
        elseif (abs($desv) > 15) $desv_class = 'text-warning fw-bold';
    }

    $pct = $f['pct_ejec'];
    $pct_class = 'text-success';
    if ($pct !== null) {
        if ($pct > 95) $pct_class = 'text-danger fw-bold';
        elseif ($pct > 80) $pct_class = 'text-warning fw-bold';
    }

    // Sugerencia
    $sug = '';
    if ($desv !== null) {
        if (abs($desv) > 30) {
            $sug = '<span class="badge bg-danger">Ajustar factor</span>';
        } elseif (abs($desv) > 15) {
            $sug = '<span class="badge bg-warning text-dark">Revisar</span>';
        } else {
            $sug = '<span class="badge bg-success">OK</span>';
        }
    } elseif ($f['causado'] > 0 && $f['proy'] == 0) {
        $sug = '<span class="badge bg-info text-dark">Sin proyección</span>';
    } elseif ($f['proy'] > 0 && $f['causado'] == 0) {
        $sug = '<span class="badge bg-secondary">Sin ejecución</span>';
    }

    // Row highlight
    $row_bg = '';
    if ($desv !== null && abs($desv) > 30) $row_bg = 'style="background:#fff3cd22;"';
?>
                    <tr <?php echo $row_bg; ?>>
                        <td class="fw-semibold"><?php echo $mostrar_cat ? htmlspecialchars($f['cat_nombre']) : ''; ?></td>
                        <td class="text-center text-muted"><?php echo htmlspecialchars($f['item_code']); ?></td>
                        <td><?php echo htmlspecialchars($f['item_nombre']); ?></td>
                        <td class="text-end"><?php echo $f['planif'] > 0 ? number_format($f['planif'], 0, ',', '.') : '—'; ?></td>
                        <td class="text-end"><?php echo $f['definitivo'] > 0 ? number_format($f['definitivo'], 0, ',', '.') : '—'; ?></td>
                        <td class="text-end <?php echo $f['proy'] > 0 ? 'text-primary' : 'text-muted'; ?>"><?php echo $f['proy'] > 0 ? number_format($f['proy'], 0, ',', '.') : '—'; ?></td>
                        <td class="text-end fw-bold"><?php echo $f['causado'] > 0 ? number_format($f['causado'], 0, ',', '.') : '—'; ?></td>
                        <td class="text-center"><span class="<?php echo $pct_class; ?>"><?php echo $pct !== null ? number_format($pct, 1) . '%' : '—'; ?></span></td>
                        <td class="text-center"><span class="<?php echo $desv_class; ?>"><?php echo $desv !== null ? (($desv >= 0 ? '+' : '') . number_format($desv, 1) . '%') : '—'; ?></span></td>
                        <td class="text-center"><?php
                            if ($f['cant_borr'] > 0) echo '<span class="badge bg-outline-secondary border" title="Borradores">' . $f['cant_borr'] . 'B</span> ';
                            if ($f['cant_sr'] > 0)   echo '<span class="badge bg-dark" title="SR reales">' . $f['cant_sr'] . '</span>';
                            if ($f['cant_borr'] == 0 && $f['cant_sr'] == 0) echo '—';
                        ?></td>
                        <td class="text-center"><?php echo $sug; ?></td>
                    </tr>
<?php endforeach; ?>

<?php if (!empty($filas)):
    $gt_desv_fmt = ($t_borr_proy > 0) ? (($gt_desv >= 0 ? '+' : '') . number_format($gt_desv, 1) . '%') : '—';
?>
                    <tr class="table-dark fw-bold" style="font-size:0.85rem;">
                        <td colspan="3" class="text-end">TOTALES</td>
                        <td class="text-end">U$D <?php echo number_format($t_planif, 0, ',', '.'); ?></td>
                        <td class="text-end">—</td>
                        <td class="text-end">U$D <?php echo number_format($t_borr_proy, 0, ',', '.'); ?></td>
                        <td class="text-end">U$D <?php echo number_format($t_causado, 0, ',', '.'); ?></td>
                        <td class="text-center"><?php echo number_format($gt_pct_ejec, 1); ?>%</td>
                        <td class="text-center"><?php echo $gt_desv_fmt; ?></td>
                        <td colspan="2"></td>
                    </tr>
<?php endif; ?>
<?php if (empty($filas)): ?>
                    <tr><td colspan="11" class="text-center text-muted py-4"><i class="bi bi-inbox me-2"></i>Sin datos de proyección ni ejecución para este período y centro de costos.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (!empty($filas)): ?>
<!-- LEYENDA -->
<div class="mt-3 small text-muted">
    <i class="bi bi-info-circle me-1"></i>
    <strong>Origen</strong>: Planilla K2B <?php echo $anio_sel; ?>.
    <strong>% Ejec.</strong> = Causado / Planificado.
    <strong>Desviación</strong> = (Causado − Proyectado) / Proyectado:
    <span class="text-success">Verde ≤15%</span> |
    <span class="text-warning">Amarillo ≤30%</span> |
    <span class="text-danger">Rojo &gt;30%</span>.
</div>
<?php endif; ?>

<?php endif; ?>
