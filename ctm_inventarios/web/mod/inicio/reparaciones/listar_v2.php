<?php
/**
 * ============================================================================
 * Listado de Reparaciones - ctm_inventarios v2
 * ============================================================================
 */

$buscar = Enmascarar_post('buscar');
?>

<?php echo generarTituloSeccion('Reparaciones', 'bi bi-tools'); ?>
<?php echo generarCampoBusqueda($buscar, 'Buscar por problema, observaciones...'); ?>

<div class="table-responsive">
    <table class="table table-pade table-hover table-sm">
        <?php echo generarTablaHeader(['#', 'ID', 'Problema', 'Observaciones', 'PC', 'Impresora', 'Monitor', 'Otro', 'Fecha Envío', 'Reparado', 'Opciones']); ?>
        <tbody>
<?php
$sql = "SELECT a.reparacionid, a.problema, a.observaciones, a.id_pc, a.id_impresoras,
               a.id_monitores, a.id_otros, a.fecha_envio, a.reparado
          FROM inve_reparaciones a WHERE 1=1";
if ($buscar) {
    $sql .= " AND (UPPER(a.problema) LIKE UPPER('%$buscar%')
               OR UPPER(a.observaciones) LIKE UPPER('%$buscar%'))";
}
$sql .= " ORDER BY a.reparacionid DESC;";
$result = sql_do_query($sql, '1', $usu);
$n = 0;
while ($row = sql_do_array($result)) {
    $n++;
    $repBadge = ($row['REPARADO'] == '1')
        ? '<span class="badge bg-success">Sí</span>'
        : '<span class="badge bg-danger">No</span>';

    $pc_nom  = $row['ID_PC'] ? (getPc($row['ID_PC'])['CODIGOPC'] ?? '-') : '-';
    $imp_nom = $row['ID_IMPRESORAS'] ? (getImpresoras($row['ID_IMPRESORAS'])['CODIGO'] ?? '-') : '-';
    $mon_nom = $row['ID_MONITORES'] ? (getMonitores($row['ID_MONITORES'])['CODIGO'] ?? '-') : '-';
    $otr_nom = $row['ID_OTROS'] ? (getOtros($row['ID_OTROS'])['CODIGO'] ?? '-') : '-';
?>
            <tr>
                <td class="text-center"><?php echo $n; ?></td>
                <td class="text-center"><?php echo $row['REPARACIONID']; ?></td>
                <td><?php echo htmlspecialchars($row['PROBLEMA']); ?></td>
                <td><?php echo htmlspecialchars($row['OBSERVACIONES']); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($pc_nom); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($imp_nom); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($mon_nom); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($otr_nom); ?></td>
                <td class="text-center"><?php echo enmascararFecha($row['FECHA_ENVIO']); ?></td>
                <td class="text-center"><?php echo $repBadge; ?></td>
                <td class="text-center">
                    <?php
                    $variables = array("reparacionid" => $row['REPARACIONID']);
                    $estados = array("12" => "Detalle");
                    echo impSelectHerramienta($variables, $estados, $n);
                    ?>
                </td>
            </tr>
<?php } ?>
        </tbody>
    </table>
</div>
<?php if ($n == 0): ?>
<div class="alert alert-info text-center"><i class="bi bi-info-circle me-2"></i>No se encontraron reparaciones.</div>
<?php endif; ?>
<div class="text-muted small mt-2"><i class="bi bi-list-ol me-1"></i>Total: <?php echo $n; ?> registro(s)</div>
