<?php
/**
 * Dashboard - Resumen general del inventario IT
 * Muestra estadísticas rápidas y accesos directos.
 */

// ── Datos cross-module ──────────────────────────────────────────────────────
$integ_resumen = integ_dashboard_resumen('1', $usu);
$integ_alertas = integ_dashboard_alertas('1', $usu);

// ── Estadísticas desde morebius (DB '2') ─────────────────────────────────────
$sql_total_pc = "SELECT COUNT(*) AS TOTAL FROM ctm_inventario WHERE baja = 0 OR baja IS NULL;";
$row_pc = sql_do_array(sql_do_query($sql_total_pc, '2', $usu));
$total_equipos = $row_pc['TOTAL'] ?? 0;

$sql_total_art = "SELECT COUNT(*) AS TOTAL FROM articulos_con WHERE DE_BAJA <> 1 OR DE_BAJA IS NULL;";
$row_art = sql_do_array(sql_do_query($sql_total_art, '2', $usu));
$total_articulos = $row_art['TOTAL'] ?? 0;

$sql_total_pers = "SELECT COUNT(*) AS TOTAL FROM ctm_plantel;";
$row_pers = sql_do_array(sql_do_query($sql_total_pers, '2', $usu));
$total_personal = $row_pers['TOTAL'] ?? 0;

$sql_total_asig = "SELECT COUNT(*) AS TOTAL FROM articulos_con WHERE devuelto = 0 AND (DE_BAJA <> 1 OR DE_BAJA IS NULL);";
$row_asig = sql_do_array(sql_do_query($sql_total_asig, '2', $usu));
$total_asignados = $row_asig['TOTAL'] ?? 0;

// ── Últimos movimientos ──────────────────────────────────────────────────────
$sql_ultimos = "SELECT a.nro_art, a.detalle, a.fecha, a.legajo_art,
                       t.detalle AS tipo_nombre,
                       p.EmpNombre
                  FROM articulos_con a
                  LEFT JOIN articulos_tipos t ON a.tipo_art = t.tipo_art
                  LEFT JOIN ctm_plantel p ON a.legajo_art = p.EmpLegajo
                 WHERE a.fecha IS NOT NULL
                 ORDER BY a.fecha DESC
                 LIMIT 10;";
$result_ultimos = sql_do_query($sql_ultimos, '2', $usu);

// ── Distribución por tipo ────────────────────────────────────────────────────
$sql_por_tipo = "SELECT t.detalle AS TIPO, COUNT(*) AS CANT
                   FROM articulos_con a
                   JOIN articulos_tipos t ON a.tipo_art = t.tipo_art
                  WHERE a.DE_BAJA <> 1 OR a.DE_BAJA IS NULL
                  GROUP BY t.detalle
                  ORDER BY CANT DESC;";
$result_por_tipo = sql_do_query($sql_por_tipo, '2', $usu);
?>

<?php echo generarTituloSeccion('Dashboard - Inventario IT', 'bi bi-speedometer2'); ?>

<!-- Stat cards -->
<div class="row g-3 mb-4 fade-in">
    <div class="col-lg-3 col-md-6">
        <a href="index.php?estado=11" class="text-decoration-none">
            <div class="stat-card" style="background: linear-gradient(135deg, #418CBA, #2d6a8f); cursor:pointer;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?php echo number_format($total_equipos); ?></div>
                        <div class="stat-label">Equipos en Inventario</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-pc-display"></i></div>
                </div>
                <div class="text-end mt-2"><small class="text-white-50">Ver listado <i class="bi bi-arrow-right"></i></small></div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 col-md-6">
        <a href="index.php?estado=2" class="text-decoration-none">
            <div class="stat-card" style="background: linear-gradient(135deg, #28a745, #1e7e34); cursor:pointer;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?php echo number_format($total_articulos); ?></div>
                        <div class="stat-label">Artículos Activos</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
                </div>
                <div class="text-end mt-2"><small class="text-white-50">Ver listado <i class="bi bi-arrow-right"></i></small></div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 col-md-6">
        <a href="index.php?estado=2" class="text-decoration-none">
            <div class="stat-card" style="background: linear-gradient(135deg, #ffc107, #e0a800); cursor:pointer;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?php echo number_format($total_asignados); ?></div>
                        <div class="stat-label">Asignaciones Vigentes</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-person-badge"></i></div>
                </div>
                <div class="text-end mt-2"><small class="text-white-50">Ver artículos <i class="bi bi-arrow-right"></i></small></div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 col-md-6">
        <a href="index.php?estado=4" class="text-decoration-none">
            <div class="stat-card" style="background: linear-gradient(135deg, #6f42c1, #5a32a3); cursor:pointer;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?php echo number_format($total_personal); ?></div>
                        <div class="stat-label">Personal en Plantel</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-people"></i></div>
                </div>
                <div class="text-end mt-2"><small class="text-white-50">Ver plantel <i class="bi bi-arrow-right"></i></small></div>
            </div>
        </a>
    </div>
</div>

<!-- ═══ SECCION CROSS-MODULE ═══ -->
<h6 class="text-muted mb-3"><i class="bi bi-link-45deg me-2"></i>Vision Global (datos de otros modulos)</h6>
<?php echo integ_render_cards_resumen($integ_resumen); ?>
<?php echo integ_render_alertas($integ_alertas); ?>
<?php echo integ_render_eventos_recientes('1', $usu, 5); ?>

<div class="row g-3 fade-in">
    <!-- Últimos movimientos -->
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Últimos Movimientos</h6>
                <a href="index.php?estado=2" class="btn btn-sm btn-outline-primary">Ver todos <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="card-body p-0">
                <table class="table table-pade mb-0">
                    <?php echo generarTablaHeader(['#', 'Detalle', 'Tipo', 'Asignado a', 'Fecha']); ?>
                    <tbody>
                    <?php
                    $n = 0;
                    while ($row = sql_do_array($result_ultimos)) {
                        $n++;
                    ?>
                        <tr class="<?php echo claseFilaAlternada($n); ?>">
                            <td class="text-center"><?php echo $row['NRO_ART']; ?></td>
                            <td><?php echo htmlspecialchars(mb_strimwidth($row['DETALLE'] ?? '', 0, 50, '...')); ?></td>
                            <td class="text-center"><span class="badge bg-info text-dark"><?php echo htmlspecialchars($row['TIPO_NOMBRE'] ?? ''); ?></span></td>
                            <td>
                                <?php if ($row['LEGAJO_ART']): ?>
                                    <a href="index.php?estado=44&legajo=<?php echo $row['LEGAJO_ART']; ?>" class="text-decoration-none text-dark d-flex align-items-center gap-2" data-bs-toggle="tooltip" title="Ver equipos de esta persona">
                                        <?php echo fotoEmpleado($row['LEGAJO_ART'], $row['EMPNOMBRE'] ?? '', $servidor_img, 24); ?>
                                        <span><?php echo htmlspecialchars($row['EMPNOMBRE'] ?? '-'); ?></span>
                                    </a>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($row['EMPNOMBRE'] ?? '-'); ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?php echo enmascararfecha($row['FECHA']); ?></td>
                        </tr>
                    <?php } ?>
                    <?php if ($n == 0): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Sin movimientos recientes</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Distribución por tipo -->
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Distribución por Tipo</h6>
                <a href="index.php?estado=2" class="btn btn-sm btn-outline-secondary">Artículos <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="card-body">
                <?php
                $colores = ['#418CBA', '#28a745', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14', '#20c997'];
                $ci = 0;
                while ($row_t = sql_do_array($result_por_tipo)) {
                    $color = $colores[$ci % count($colores)];
                    $ci++;
                ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:12px; height:12px; border-radius:3px; background:<?php echo $color; ?>;"></div>
                        <span class="small"><?php echo htmlspecialchars($row_t['TIPO']); ?></span>
                    </div>
                    <span class="badge bg-secondary"><?php echo $row_t['CANT']; ?></span>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
