<?php
/**
 * ============================================================================
 * Saldos por Partida
 * ============================================================================
 * ID menú: 405 | Tablas: v_saldo_partida, v_resumen_categoria, ciclo_presupuesto
 * Consulta rápida del estado financiero de cada partida.
 * Agrupado por categoría, con semáforo de colores según consumo.
 * Solo lectura.
 * ============================================================================
 */

$db = '1';

$unidad_usuario = $_SESSION['id_unidad'] ?? '';
$rol_usuario    = $_SESSION['rol'] ?? '';

// ── Selección de período ─────────────────────────────────────────────────────
$periodo_sel = Enmascarar_get('periodo');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['filtro_periodo'])) {
    $periodo_sel = Enmascarar_post('filtro_periodo');
}

// Default: período abierto
if (empty($periodo_sel) || $periodo_sel === 'null') {
    $row_ab = sql_do_array(sql_do_query("SELECT id FROM ciclo_periodo_fiscal WHERE estado = 'abierto' LIMIT 1;", $db, $usu));
    $periodo_sel = $row_ab['ID'] ?? '';
}

// ── Determinar unidad a consultar ────────────────────────────────────────────
$unidad_filtro = $unidad_usuario;
if (in_array($rol_usuario, ['director', 'admin'])) {
    $unidad_filtro = Enmascarar_get('unidad');
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['filtro_unidad'])) {
        $unidad_filtro = Enmascarar_post('filtro_unidad');
    }
    if (empty($unidad_filtro) || $unidad_filtro === 'null') $unidad_filtro = '';
}

// ── Buscar presupuesto ───────────────────────────────────────────────────────
$presupuesto_id = '';
if (!empty($periodo_sel) && !empty($unidad_filtro)) {
    $sql_pr = "SELECT id FROM ciclo_presupuesto
               WHERE id_periodo = '$periodo_sel' AND id_unidad = '$unidad_filtro'
               AND estado IN ('aprobado', 'en_revision', 'borrador')
               ORDER BY FIELD(estado, 'aprobado', 'en_revision', 'borrador') LIMIT 1;";
    $row_pr = sql_do_array(sql_do_query($sql_pr, $db, $usu));
    $presupuesto_id = $row_pr['ID'] ?? '';
}

// Combos
$sql_combo_per = "SELECT id AS ID, CONCAT(anio, ' (', estado, ')') AS NOMBRE FROM ciclo_periodo_fiscal ORDER BY anio DESC;";
$sql_combo_uni = "SELECT id AS ID, nombre AS NOMBRE FROM ciclo_unidad_organizativa WHERE 1=1 ORDER BY nombre;";
?>

<?php echo generarTituloSeccion('Saldos por Partida', 'bi bi-piggy-bank'); ?>

<!-- FILTROS -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Período</label>
                <?php echo imp_combo_w_oc('filtro_periodo', $sql_combo_per, $db, $usu, $periodo_sel); ?>
            </div>
            <?php if (in_array($rol_usuario, ['director', 'admin'])): ?>
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Unidad</label>
                <?php echo imp_combo_w_oc('filtro_unidad', $sql_combo_uni, $db, $usu, $unidad_filtro); ?>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if (empty($presupuesto_id)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>No se encontró un presupuesto para la unidad y período seleccionados.
</div>
<?php else: ?>

<!-- SECCIÓN 1: RESUMEN POR CATEGORÍA -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h6 class="mb-0"><i class="bi bi-folder me-2"></i>Resumen por Categoría</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-pade mb-0">
                <?php echo generarTablaHeader(['Categoría', 'Aprobado', 'Comprometido', 'Ejecutado', 'Disponible', '% Consumo']); ?>
                <tbody>
<?php
$sql_cats = "SELECT * FROM v_resumen_categoria WHERE presupuesto_id = '$presupuesto_id';";
$res_cats = sql_do_query($sql_cats, $db, $usu);
$n = 0;
$t_apro = 0; $t_comp = 0; $t_ejec = 0; $t_disp = 0;

while ($rc = sql_do_array($res_cats)):
    $n++;
    $apro = (float)($rc['APROBADO'] ?? 0);
    $comp = (float)($rc['COMPROMETIDO'] ?? 0);
    $ejec = (float)($rc['EJECUTADO'] ?? 0);
    $disp = (float)($rc['DISPONIBLE'] ?? 0);
    $pct  = ($apro > 0) ? round(($ejec / $apro) * 100, 2) : 0;

    $t_apro += $apro; $t_comp += $comp; $t_ejec += $ejec; $t_disp += $disp;

    $row_class = '';
    if ($pct >= 95)      $row_class = 'table-danger';
    elseif ($pct >= 80)  $row_class = 'table-warning';

    $pct_class = 'text-success';
    if ($pct >= 95)      $pct_class = 'text-danger fw-bold';
    elseif ($pct >= 80)  $pct_class = 'text-warning fw-bold';
?>
                    <tr class="<?php echo $row_class; ?>">
                        <td class="fw-bold">
                            <small class="text-muted"><?php echo htmlspecialchars($rc['CAT_CODIGO'] ?? ''); ?></small>
                            <?php echo htmlspecialchars($rc['CATEGORIA'] ?? ''); ?>
                        </td>
                        <td class="text-end">$ <?php echo number_format($apro, 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format($comp, 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format($ejec, 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format($disp, 2, ',', '.'); ?></td>
                        <td class="text-center"><span class="<?php echo $pct_class; ?>"><?php echo number_format($pct, 1); ?>%</span></td>
                    </tr>
<?php endwhile; ?>
<?php if ($n > 0):
    $pct_total = ($t_apro > 0) ? round(($t_ejec / $t_apro) * 100, 2) : 0;
?>
                    <tr class="table-dark fw-bold">
                        <td>TOTALES</td>
                        <td class="text-end">$ <?php echo number_format($t_apro, 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format($t_comp, 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format($t_ejec, 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format($t_disp, 2, ',', '.'); ?></td>
                        <td class="text-center"><?php echo number_format($pct_total, 1); ?>%</td>
                    </tr>
<?php endif; ?>
<?php if ($n === 0): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Sin datos de categorías.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- SECCIÓN 2: DETALLE POR ÍTEM -->
<div class="card shadow-sm">
    <div class="card-header bg-info text-dark">
        <h6 class="mb-0"><i class="bi bi-tag me-2"></i>Detalle por Ítem</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-pade mb-0">
                <?php echo generarTablaHeader(['Categoría', 'Código', 'Ítem', 'Aprobado', 'Comprometido', 'Ejecutado', 'Disponible', '% Consumo']); ?>
                <tbody>
<?php
$sql_items = "SELECT * FROM v_saldo_partida
              WHERE presupuesto_id = '$presupuesto_id'
              ORDER BY cat_codigo, item_codigo;";
$res_items = sql_do_query($sql_items, $db, $usu);
$ni = 0;
$cat_ant = '';

while ($ri = sql_do_array($res_items)):
    $ni++;
    $i_apro = (float)($ri['APROBADO'] ?? 0);
    $i_comp = (float)($ri['COMPROMETIDO'] ?? 0);
    $i_ejec = (float)($ri['EJECUTADO'] ?? 0);
    $i_disp = (float)($ri['DISPONIBLE'] ?? $ri['SALDO_DISPONIBLE'] ?? 0);
    $i_pct  = (float)($ri['PORCENTAJE_CONSUMO'] ?? (($i_apro > 0) ? round(($i_ejec / $i_apro) * 100, 2) : 0));

    $i_row_class = '';
    if ($i_pct >= 95)      $i_row_class = 'table-danger';
    elseif ($i_pct >= 80)  $i_row_class = 'table-warning';

    $i_pct_class = 'text-success';
    if ($i_pct >= 95)      $i_pct_class = 'text-danger fw-bold';
    elseif ($i_pct >= 80)  $i_pct_class = 'text-warning fw-bold';

    $mostrar_cat = (($ri['CAT_CODIGO'] ?? '') !== $cat_ant);
    $cat_ant = $ri['CAT_CODIGO'] ?? '';
?>
                    <tr class="<?php echo $i_row_class; ?>">
                        <td class="fw-bold"><?php echo $mostrar_cat ? htmlspecialchars($ri['CATEGORIA'] ?? $cat_ant) : ''; ?></td>
                        <td class="text-center small"><?php echo htmlspecialchars($ri['ITEM_CODIGO'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($ri['ITEM_NOMBRE'] ?? ''); ?></td>
                        <td class="text-end">$ <?php echo number_format($i_apro, 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format($i_comp, 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format($i_ejec, 2, ',', '.'); ?></td>
                        <td class="text-end">$ <?php echo number_format($i_disp, 2, ',', '.'); ?></td>
                        <td class="text-center"><span class="<?php echo $i_pct_class; ?>"><?php echo number_format($i_pct, 1); ?>%</span></td>
                    </tr>
<?php endwhile; ?>
<?php if ($ni === 0): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Sin datos de ítems.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php endif; ?>
