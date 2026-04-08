<?php
/**
 * ============================================================================
 * Dashboard - ctm_inventarios v2
 * ============================================================================
 */

// ── Contadores ───────────────────────────────────────────────────────────────
$sql_pcs      = "SELECT COUNT(*) AS TOTAL FROM inve_pc WHERE estadoid='1';";
$sql_imp      = "SELECT COUNT(*) AS TOTAL FROM inve_impresoras WHERE estadoid='1';";
$sql_mon      = "SELECT COUNT(*) AS TOTAL FROM inve_monitores WHERE estadoid='1';";
$sql_otros    = "SELECT COUNT(*) AS TOTAL FROM inve_otros WHERE estadoid='1';";
$sql_prest    = "SELECT COUNT(*) AS TOTAL FROM inve_prestamos WHERE devuelto='0';";
$sql_rep      = "SELECT COUNT(*) AS TOTAL FROM inve_reparaciones WHERE reparado='0';";

// ── Datos cross-module ──────────────────────────────────────────────────────
$integ_resumen = integ_dashboard_resumen('1', $usu);
$integ_alertas = integ_dashboard_alertas('1', $usu);

$total_pcs   = sql_do_array(sql_do_query($sql_pcs, '1', $usu))['TOTAL'] ?? 0;
$total_imp   = sql_do_array(sql_do_query($sql_imp, '1', $usu))['TOTAL'] ?? 0;
$total_mon   = sql_do_array(sql_do_query($sql_mon, '1', $usu))['TOTAL'] ?? 0;
$total_otros = sql_do_array(sql_do_query($sql_otros, '1', $usu))['TOTAL'] ?? 0;
$total_prest = sql_do_array(sql_do_query($sql_prest, '1', $usu))['TOTAL'] ?? 0;
$total_rep   = sql_do_array(sql_do_query($sql_rep, '1', $usu))['TOTAL'] ?? 0;
?>

<div class="row g-3 mb-4">
    <!-- PCs -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <a href="index.php?estado=20" class="stat-card bg-grad-blue d-block">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number"><?php echo $total_pcs; ?></div>
                    <div class="stat-label">PCs / Equipos</div>
                </div>
                <div class="stat-icon"><i class="bi bi-pc-display"></i></div>
            </div>
            <div class="mt-2 small opacity-75">Ver listado <i class="bi bi-arrow-right"></i></div>
        </a>
    </div>
    <!-- Impresoras -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <a href="index.php?estado=21" class="stat-card bg-grad-green d-block">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number"><?php echo $total_imp; ?></div>
                    <div class="stat-label">Impresoras</div>
                </div>
                <div class="stat-icon"><i class="bi bi-printer"></i></div>
            </div>
            <div class="mt-2 small opacity-75">Ver listado <i class="bi bi-arrow-right"></i></div>
        </a>
    </div>
    <!-- Monitores -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <a href="index.php?estado=22" class="stat-card bg-grad-orange d-block">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number"><?php echo $total_mon; ?></div>
                    <div class="stat-label">Monitores</div>
                </div>
                <div class="stat-icon"><i class="bi bi-display"></i></div>
            </div>
            <div class="mt-2 small opacity-75">Ver listado <i class="bi bi-arrow-right"></i></div>
        </a>
    </div>
    <!-- Otros -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <a href="index.php?estado=23" class="stat-card bg-grad-purple d-block">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number"><?php echo $total_otros; ?></div>
                    <div class="stat-label">Otros Disp.</div>
                </div>
                <div class="stat-icon"><i class="bi bi-usb-drive"></i></div>
            </div>
            <div class="mt-2 small opacity-75">Ver listado <i class="bi bi-arrow-right"></i></div>
        </a>
    </div>
    <!-- Préstamos activos -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <a href="index.php?estado=10" class="stat-card bg-grad-red d-block">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number"><?php echo $total_prest; ?></div>
                    <div class="stat-label">Préstamos</div>
                </div>
                <div class="stat-icon"><i class="bi bi-arrow-left-right"></i></div>
            </div>
            <div class="mt-2 small opacity-75">Ver activos <i class="bi bi-arrow-right"></i></div>
        </a>
    </div>
    <!-- Reparaciones activas -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <a href="index.php?estado=11" class="stat-card bg-grad-teal d-block">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-number"><?php echo $total_rep; ?></div>
                    <div class="stat-label">Reparaciones</div>
                </div>
                <div class="stat-icon"><i class="bi bi-tools"></i></div>
            </div>
            <div class="mt-2 small opacity-75">Ver activas <i class="bi bi-arrow-right"></i></div>
        </a>
    </div>
</div>

<!-- ═══ SECCION CROSS-MODULE ═══ -->
<h6 class="text-muted mb-3 mt-2"><i class="bi bi-link-45deg me-2"></i>Vision Global (datos de otros modulos)</h6>
<?php echo integ_render_cards_resumen($integ_resumen); ?>
<?php echo integ_render_alertas($integ_alertas); ?>
<?php echo integ_render_eventos_recientes('1', $usu, 5); ?>

<!-- Últimos equipos registrados -->
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Últimos equipos registrados</h6>
                <a href="index.php?estado=20" class="btn btn-sm btn-outline-light">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center">Código</th>
                            <th>Marca</th>
                            <th>IP</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
<?php
$sql_ult = "SELECT pcid, codigopc, marca, ip, baja FROM inve_pc WHERE estadoid='1' ORDER BY pcid DESC LIMIT 8;";
$r_ult = sql_do_query($sql_ult, '1', $usu);
while ($ru = sql_do_array($r_ult)) {
    $est = ($ru['BAJA'] == '0' || !$ru['BAJA']) ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Baja</span>';
?>
                        <tr>
                            <td class="text-center"><a href="index.php?estado=24&codigopc=<?php echo urlencode($ru['CODIGOPC']); ?>"><?php echo htmlspecialchars($ru['CODIGOPC']); ?></a></td>
                            <td><?php echo htmlspecialchars($ru['MARCA']); ?></td>
                            <td><?php echo htmlspecialchars($ru['IP']); ?></td>
                            <td class="text-center"><?php echo $est; ?></td>
                        </tr>
<?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-arrow-left-right me-2"></i>Préstamos recientes</h6>
                <a href="index.php?estado=10" class="btn btn-sm btn-outline-light">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center">#</th>
                            <th>Responsable</th>
                            <th class="text-center">Fecha</th>
                            <th class="text-center">Devuelto</th>
                        </tr>
                    </thead>
                    <tbody>
<?php
$sql_pr = "SELECT prestamoid, responsable, fechaprestamo, devuelto FROM inve_prestamos ORDER BY prestamoid DESC LIMIT 8;";
$r_pr = sql_do_query($sql_pr, '1', $usu);
while ($rp = sql_do_array($r_pr)) {
    $dev = ($rp['DEVUELTO'] == '1') ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-warning text-dark">No</span>';
?>
                        <tr>
                            <td class="text-center"><?php echo $rp['PRESTAMOID']; ?></td>
                            <td><?php echo htmlspecialchars($rp['RESPONSABLE']); ?></td>
                            <td class="text-center"><?php echo enmascararFecha($rp['FECHAPRESTAMO']); ?></td>
                            <td class="text-center"><?php echo $dev; ?></td>
                        </tr>
<?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
