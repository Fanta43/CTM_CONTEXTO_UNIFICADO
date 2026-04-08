<?php
/**
 * ============================================================================
 * Listado de Préstamos - ctm_inventarios v2
 * ============================================================================
 */

$buscar = Enmascarar_post('buscar');
?>

<?php echo generarTituloSeccion('Préstamos de Equipos', 'bi bi-arrow-left-right'); ?>
<?php echo generarCampoBusqueda($buscar, 'Buscar por responsable, observaciones...'); ?>

<div class="table-responsive">
    <table class="table table-pade table-hover table-sm">
        <?php echo generarTablaHeader(['#', 'ID', 'Responsable', 'Observaciones', 'PC', 'Impresora', 'Monitor', 'Otro', 'Fecha', 'Devuelto', 'Opciones']); ?>
        <tbody>
<?php
$sql = "SELECT a.prestamoid, a.responsable, a.observaciones, a.id_pc, a.id_impresoras,
               a.id_monitores, a.id_otros, a.fechaprestamo, a.devuelto
          FROM inve_prestamos a WHERE 1=1";
if ($buscar) {
    $sql .= " AND (UPPER(a.responsable) LIKE UPPER('%$buscar%')
               OR UPPER(a.observaciones) LIKE UPPER('%$buscar%'))";
}
$sql .= " ORDER BY a.prestamoid DESC;";
$result = sql_do_query($sql, '1', $usu);
$n = 0;
while ($row = sql_do_array($result)) {
    $n++;
    $devBadge = ($row['DEVUELTO'] == '1')
        ? '<span class="badge bg-success">Sí</span>'
        : '<span class="badge bg-warning text-dark">No</span>';

    // Resolver nombres de equipos
    $pc_nom = $row['ID_PC'] ? (getPc($row['ID_PC'])['CODIGOPC'] ?? '-') : '-';
    $imp_nom = $row['ID_IMPRESORAS'] ? (getImpresoras($row['ID_IMPRESORAS'])['CODIGO'] ?? '-') : '-';
    $mon_nom = $row['ID_MONITORES'] ? (getMonitores($row['ID_MONITORES'])['CODIGO'] ?? '-') : '-';
    $otr_nom = $row['ID_OTROS'] ? (getOtros($row['ID_OTROS'])['CODIGO'] ?? '-') : '-';
?>
            <tr>
                <td class="text-center"><?php echo $n; ?></td>
                <td class="text-center"><?php echo $row['PRESTAMOID']; ?></td>
                <td><?php echo htmlspecialchars($row['RESPONSABLE']); ?></td>
                <td><?php echo htmlspecialchars($row['OBSERVACIONES']); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($pc_nom); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($imp_nom); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($mon_nom); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($otr_nom); ?></td>
                <td class="text-center"><?php echo enmascararFecha($row['FECHAPRESTAMO']); ?></td>
                <td class="text-center"><?php echo $devBadge; ?></td>
                <td class="text-center">
                    <?php
                    $variables = array("prestamoid" => $row['PRESTAMOID']);
                    $estados = array("17" => "Detalle");
                    echo impSelectHerramienta($variables, $estados, $n);
                    ?>
                </td>
            </tr>
<?php } ?>
        </tbody>
    </table>
</div>
<?php if ($n == 0): ?>
<div class="alert alert-info text-center"><i class="bi bi-info-circle me-2"></i>No se encontraron préstamos.</div>
<?php endif; ?>
<div class="text-muted small mt-2"><i class="bi bi-list-ol me-1"></i>Total: <?php echo $n; ?> registro(s)</div>
