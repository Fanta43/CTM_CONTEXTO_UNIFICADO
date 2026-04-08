<?php
/**
 * ============================================================================
 * Reporte: Comparativo Anual
 * ============================================================================
 * ID menú: 502 | Tablas: ciclo_partida, ciclo_presupuesto, ciclo_periodo_fiscal
 * Varios períodos lado a lado con variación porcentual.
 * Identifica tendencias de gasto. Exportable a CSV. Solo lectura.
 * ============================================================================
 */

$db = '1';

// ── Filtros ──────────────────────────────────────────────────────────────────
$anio_desde = Enmascarar_get('desde');
$anio_hasta = Enmascarar_get('hasta');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['anio_desde'])) $anio_desde = Enmascarar_post('anio_desde');
    if (!empty($_POST['anio_hasta'])) $anio_hasta = Enmascarar_post('anio_hasta');
}

// Default: últimos 3 años
$anio_actual = (int)date('Y');
if (empty($anio_desde)) $anio_desde = $anio_actual - 2;
if (empty($anio_hasta)) $anio_hasta = $anio_actual;
$anio_desde = (int)$anio_desde;
$anio_hasta = (int)$anio_hasta;
if ($anio_desde > $anio_hasta) { $tmp = $anio_desde; $anio_desde = $anio_hasta; $anio_hasta = $tmp; }

// ── Consulta principal ───────────────────────────────────────────────────────
$sql_data = "SELECT pf.anio AS ANIO, cg.nombre AS CATEGORIA, cg.orden AS CAT_ORDEN,
                    ig.nombre AS ITEM, ig.codigo AS ITEM_CODIGO,
                    SUM(pa.monto_aprobado) AS APROBADO,
                    SUM(pa.monto_ejecutado) AS EJECUTADO
             FROM ciclo_partida pa
             JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
             JOIN ciclo_periodo_fiscal pf ON pr.id_periodo = pf.id
             JOIN ciclo_categoria_gasto cg ON pa.id_categoria = cg.id
             JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
             WHERE pf.anio BETWEEN '$anio_desde' AND '$anio_hasta'
             GROUP BY pf.anio, cg.nombre, cg.orden, ig.nombre, ig.codigo
             ORDER BY cg.orden, ig.codigo, pf.anio;";
$res_data = sql_do_query($sql_data, $db, $usu);

// Organizar datos en estructura [categoria/item][anio]
$datos = [];
$anios = [];
while ($rd = sql_do_array($res_data)) {
    $key = $rd['CATEGORIA'] . '|' . $rd['ITEM_CODIGO'] . '|' . $rd['ITEM'];
    $a = (int)$rd['ANIO'];
    $anios[$a] = true;
    $datos[$key][$a] = [
        'aprobado'  => (float)$rd['APROBADO'],
        'ejecutado' => (float)$rd['EJECUTADO'],
    ];
    $datos[$key]['_cat'] = $rd['CATEGORIA'];
    $datos[$key]['_item_codigo'] = $rd['ITEM_CODIGO'];
    $datos[$key]['_item'] = $rd['ITEM'];
}
ksort($anios);
$anios_arr = array_keys($anios);

// ── Exportar CSV ─────────────────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="comparativo_' . $anio_desde . '_' . $anio_hasta . '.csv"');
    $out = fopen('php://output', 'w');
    $header = ['Categoría', 'Código', 'Ítem'];
    foreach ($anios_arr as $a) { $header[] = "Aprobado $a"; $header[] = "Ejecutado $a"; }
    $header[] = 'Variación %';
    fputcsv($out, $header, ';');
    foreach ($datos as $key => $d) {
        $row_csv = [$d['_cat'], $d['_item_codigo'], $d['_item']];
        foreach ($anios_arr as $a) {
            $row_csv[] = number_format($d[$a]['aprobado'] ?? 0, 2, ',', '.');
            $row_csv[] = number_format($d[$a]['ejecutado'] ?? 0, 2, ',', '.');
        }
        $ultimo = end($anios_arr);
        $penultimo = prev($anios_arr);
        $ejec_ult = $d[$ultimo]['ejecutado'] ?? 0;
        $ejec_pen = $d[$penultimo]['ejecutado'] ?? 0;
        $var = ($ejec_pen > 0) ? round(($ejec_ult - $ejec_pen) / $ejec_pen * 100, 2) : 0;
        $row_csv[] = number_format($var, 2, ',', '.') . '%';
        fputcsv($out, $row_csv, ';');
    }
    fclose($out);
    exit;
}
?>

<?php echo generarTituloSeccion('Comparativo Anual', 'bi bi-graph-up'); ?>

<!-- FILTROS -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Desde año</label>
                <input type="number" class="form-control form-control-sm" name="anio_desde"
                       value="<?php echo $anio_desde; ?>" min="2020" max="2050">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Hasta año</label>
                <input type="number" class="form-control form-control-sm" name="anio_hasta"
                       value="<?php echo $anio_hasta; ?>" min="2020" max="2050">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-pade btn-sm"><i class="bi bi-search me-1"></i>Consultar</button>
            </div>
            <div class="col-md-3">
                <a href="index.php?estado=<?php echo $estado; ?>&desde=<?php echo $anio_desde; ?>&hasta=<?php echo $anio_hasta; ?>&export=csv"
                   class="btn btn-outline-success btn-sm">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>Exportar CSV
                </a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($datos)): ?>
<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Sin datos para el rango seleccionado.</div>
<?php else: ?>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-pade table-sm mb-0">
                <thead class="table-primary">
                    <tr>
                        <th>Categoría</th>
                        <th>Código</th>
                        <th>Ítem</th>
                        <?php foreach ($anios_arr as $a): ?>
                        <th class="text-end" colspan="2"><?php echo $a; ?></th>
                        <?php endforeach; ?>
                        <th class="text-center">Var %</th>
                    </tr>
                    <tr class="table-light small">
                        <th colspan="3"></th>
                        <?php foreach ($anios_arr as $a): ?>
                        <th class="text-end">Aprob.</th>
                        <th class="text-end">Ejec.</th>
                        <?php endforeach; ?>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
<?php
$cat_anterior = '';
$n = 0;
foreach ($datos as $key => $d):
    $n++;
    $mostrar_cat = ($d['_cat'] !== $cat_anterior);
    $cat_anterior = $d['_cat'];

    // Calcular variación último vs penúltimo
    $ultimo = end($anios_arr);
    $penultimo = prev($anios_arr);
    $ejec_ult = $d[$ultimo]['ejecutado'] ?? 0;
    $ejec_pen = $penultimo ? ($d[$penultimo]['ejecutado'] ?? 0) : 0;
    $variacion = ($ejec_pen > 0) ? round(($ejec_ult - $ejec_pen) / $ejec_pen * 100, 2) : 0;

    // Color de variación
    $var_class = '';
    $abs_v = abs($variacion);
    if ($abs_v > 50)      $var_class = 'text-danger fw-bold';
    elseif ($abs_v > 20)  $var_class = 'text-warning fw-bold';
    elseif ($variacion < -10) $var_class = 'text-info';
    elseif ($variacion > 10)  $var_class = 'text-success';
?>
                    <tr class="<?php echo claseFilaAlternada($n); ?>">
                        <td class="fw-bold"><?php echo $mostrar_cat ? htmlspecialchars($d['_cat']) : ''; ?></td>
                        <td class="text-center small"><?php echo htmlspecialchars($d['_item_codigo']); ?></td>
                        <td class="small"><?php echo htmlspecialchars($d['_item']); ?></td>
                        <?php foreach ($anios_arr as $a):
                            $apro = $d[$a]['aprobado'] ?? 0;
                            $ejec = $d[$a]['ejecutado'] ?? 0;
                        ?>
                        <td class="text-end small">$ <?php echo number_format($apro, 2, ',', '.'); ?></td>
                        <td class="text-end small">$ <?php echo number_format($ejec, 2, ',', '.'); ?></td>
                        <?php endforeach; ?>
                        <td class="text-center">
                            <span class="<?php echo $var_class; ?>"><?php echo ($variacion >= 0 ? '+' : '') . number_format($variacion, 1); ?>%</span>
                        </td>
                    </tr>
<?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
