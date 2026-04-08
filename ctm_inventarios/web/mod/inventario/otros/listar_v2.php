<?php
/**
 * ============================================================================
 * Listado de Otros Dispositivos - ctm_inventarios v2
 * ============================================================================
 */

$buscar = Enmascarar_post('buscar');
?>

<?php echo generarTituloSeccion('Otros Dispositivos', 'bi bi-usb-drive'); ?>
<?php echo generarCampoBusqueda($buscar, 'Buscar por código, marca, serie...'); ?>

<div class="table-responsive">
    <table class="table table-pade table-hover table-sm">
        <?php echo generarTablaHeader(['#', 'Código', 'Marca', 'Descripción', 'Nro. Serie', 'Estado', 'Opciones']); ?>
        <tbody>
<?php
$sql = "SELECT a.otroid, a.codigo, a.marca, a.descripcion, a.numeroserie, a.baja, a.estadoid
          FROM inve_otros a
         WHERE a.estadoid = '1'";
if ($buscar) {
    $sql .= " AND (UPPER(a.codigo) LIKE UPPER('%$buscar%')
               OR UPPER(a.marca) LIKE UPPER('%$buscar%')
               OR UPPER(a.numeroserie) LIKE UPPER('%$buscar%')
               OR UPPER(a.descripcion) LIKE UPPER('%$buscar%'))";
}
$sql .= " ORDER BY a.otroid DESC;";
$result = sql_do_query($sql, '1', $usu);
$n = 0;
while ($row = sql_do_array($result)) {
    $n++;
    $estBadge = ($row['BAJA'] == '0' || !$row['BAJA'])
        ? '<span class="badge bg-success">Activo</span>'
        : '<span class="badge bg-danger">Baja</span>';
?>
            <tr>
                <td class="text-center"><?php echo $n; ?></td>
                <td class="text-center fw-semibold"><?php echo htmlspecialchars($row['CODIGO']); ?></td>
                <td><?php echo htmlspecialchars($row['MARCA']); ?></td>
                <td><?php echo htmlspecialchars($row['DESCRIPCION']); ?></td>
                <td><?php echo htmlspecialchars($row['NUMEROSERIE']); ?></td>
                <td class="text-center"><?php echo $estBadge; ?></td>
                <td class="text-center">
                    <?php
                    $variables = array("otroid" => $row['OTROID']);
                    $estados = array("28" => "Resumen");
                    echo impSelectHerramienta($variables, $estados, $n);
                    ?>
                </td>
            </tr>
<?php } ?>
        </tbody>
    </table>
</div>
<?php if ($n == 0): ?>
<div class="alert alert-info text-center"><i class="bi bi-info-circle me-2"></i>No se encontraron dispositivos.</div>
<?php endif; ?>
<div class="text-muted small mt-2"><i class="bi bi-list-ol me-1"></i>Total: <?php echo $n; ?> registro(s)</div>
