<?php
/**
 * Equipos por Unidad Organizativa
 * Muestra los equipos del inventario agrupados por centro de costo.
 */

$cc_filtro = Enmascarar_post('cc') ?: Enmascarar_get('cc');

// Obtener centros de costo con equipos
$sql_cc = "SELECT DISTINCT p.EmpCentroCostoCod AS ID, p.EmpCentroCostoNom AS NOMBRE
             FROM ctm_plantel p
             INNER JOIN articulos_con a ON a.legajo_art = p.EmpLegajo
             WHERE (a.DE_BAJA <> 1 OR a.DE_BAJA IS NULL) AND (a.devuelto = 0 OR a.devuelto IS NULL)
             ORDER BY p.EmpCentroCostoNom;";

// Si hay filtro, traer equipos de ese CC
$result_equipos = null;
if ($cc_filtro && $cc_filtro != 'null') {
    $sql_eq = "SELECT a.nro_art, a.detalle, a.tipo_art, a.MARCA, a.MODELO, a.NSERIE,
                      a.legajo_art AS legajo_art, a.devuelto, a.DE_BAJA,
                      t.detalle AS tipo_nombre,
                      m.detmarca AS marca_nombre,
                      p.EmpNombre, p.EmpCargoNom
                 FROM articulos_con a
                 LEFT JOIN articulos_tipos t ON a.tipo_art = t.tipo_art
                 LEFT JOIN artimarcas m ON a.MARCA = m.marcaid
                 INNER JOIN ctm_plantel p ON a.legajo_art = p.EmpLegajo
                WHERE p.EmpCentroCostoCod = '$cc_filtro'
                  AND (a.DE_BAJA <> 1 OR a.DE_BAJA IS NULL)
                  AND (a.devuelto = 0 OR a.devuelto IS NULL)
                ORDER BY p.EmpNombre, t.detalle;";
    $result_equipos = sql_do_query($sql_eq, '2', $usu);
}

// Resumen por CC
$sql_resumen = "SELECT p.EmpCentroCostoNom AS CC, COUNT(*) AS CANT
                  FROM articulos_con a
                  INNER JOIN ctm_plantel p ON a.legajo_art = p.EmpLegajo
                 WHERE (a.DE_BAJA <> 1 OR a.DE_BAJA IS NULL) AND (a.devuelto = 0 OR a.devuelto IS NULL)
                 GROUP BY p.EmpCentroCostoNom
                 ORDER BY CANT DESC
                 LIMIT 15;";
$result_resumen = sql_do_query($sql_resumen, '2', $usu);
?>

<?php echo generarTituloSeccion('Equipos por Unidad', 'bi bi-building'); ?>

<div class="row g-3">
    <!-- Filtro + Resumen -->
    <div class="col-lg-4">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Seleccionar Unidad</h6>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <?php echo imp_combo_w_oc('cc', $sql_cc, '2', $usu, $cc_filtro); ?>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Top 15 Unidades</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th class="ps-3">Centro de Costo</th><th class="text-center">Equipos</th></tr></thead>
                    <tbody>
                    <?php while ($rr = sql_do_array($result_resumen)): ?>
                        <tr>
                            <td class="ps-3"><small><?php echo htmlspecialchars($rr['CC']); ?></small></td>
                            <td class="text-center"><span class="badge bg-primary"><?php echo $rr['CANT']; ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Detalle -->
    <div class="col-lg-8">
        <?php if ($result_equipos): ?>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-pade mb-0">
                        <?php echo generarTablaHeader(['#', 'Art.', 'Tipo', 'Detalle', 'Marca / Modelo', 'Persona', 'Cargo']); ?>
                        <tbody>
                        <?php
                        $n = 0;
                        while ($row = sql_do_array($result_equipos)) {
                            $n++;
                        ?>
                            <tr class="<?php echo claseFilaAlternada($n); ?>">
                                <td class="text-center"><?php echo $n; ?></td>
                                <td class="text-center"><?php echo $row['NRO_ART']; ?></td>
                                <td class="text-center"><span class="badge bg-info text-dark"><?php echo htmlspecialchars($row['TIPO_NOMBRE'] ?? ''); ?></span></td>
                                <td><?php echo htmlspecialchars(mb_strimwidth($row['DETALLE'] ?? '', 0, 35, '...')); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($row['MARCA_NOMBRE'] ?? ''); ?>
                                    <?php if ($row['MODELO']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($row['MODELO']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="index.php?estado=44&legajo=<?php echo $row['LEGAJO_ART']; ?>" class="text-decoration-none text-dark d-flex align-items-center gap-2" data-bs-toggle="tooltip" title="Ver equipos de esta persona">
                                        <?php echo fotoEmpleado($row['LEGAJO_ART'], $row['EMPNOMBRE'] ?? '', $servidor_img, 28); ?>
                                        <span><?php echo htmlspecialchars($row['EMPNOMBRE'] ?? ''); ?></span>
                                    </a>
                                </td>
                                <td><small><?php echo htmlspecialchars($row['EMPCARGONOM'] ?? ''); ?></small></td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white text-end">
                <small class="text-muted"><?php echo $n; ?> equipo(s) asignado(s)</small>
            </div>
        </div>
        <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-arrow-left-circle fs-1 d-block mb-2"></i>
                Seleccione una unidad para ver sus equipos asignados
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
