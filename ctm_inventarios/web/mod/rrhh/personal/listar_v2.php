<?php
/**
 * ============================================================================
 * Listado de Personal - ctm_inventarios v2
 * ============================================================================
 */

$buscar = Enmascarar_post('buscar');
?>

<?php echo generarTituloSeccion('Personal', 'bi bi-people'); ?>
<?php echo generarCampoBusqueda($buscar, 'Buscar por legajo, nombre, cargo, centro de costo...'); ?>

<div class="table-responsive">
    <table class="table table-pade table-hover table-sm">
        <?php echo generarTablaHeader(['', 'Legajo', 'Nombre y Apellido', 'Cargo', 'Centro de Costo', 'Estado', 'Opciones']); ?>
        <tbody>
<?php
$sql = "SELECT a.EmpLegajo, a.EmpNombre, a.EmpCargoNom, a.EmpCentroCostoNom, a.Situacion_de_Revista
          FROM vw_ctm_plantel a
         WHERE 1=1";
if ($buscar) {
    $sql .= " AND (UPPER(a.EmpLegajo) LIKE UPPER('%$buscar%')
               OR UPPER(a.EmpNombre) LIKE UPPER('%$buscar%')
               OR UPPER(a.EmpCentroCostoNom) LIKE UPPER('%$buscar%')
               OR UPPER(a.EmpCargoNom) LIKE UPPER('%$buscar%'))";
}
$sql .= " ORDER BY a.Situacion_de_Revista ASC, a.EmpNombre ASC;";
$result = sql_do_query($sql, '1', $usu);
$n = 0;
while ($row = sql_do_array($result)) {
    $n++;
    $estBadge = ($row['SITUACION_DE_REVISTA'] == 'ACTIVO')
        ? '<span class="badge bg-success">Activo</span>'
        : '<span class="badge bg-secondary">Inactivo</span>';

    $foto_url = 'https://intranet.saltogrande.org/imagenes/img_personal/hi_res/' . rawurlencode($row['EMPLEGAJO']) . '.jpg';
?>
            <tr>
                <td class="text-center" width="40">
                    <img src="<?php echo $foto_url; ?>" alt="" width="36" height="36"
                         class="rounded-circle" style="object-fit:cover;"
                         onerror="this.src='img/rrhh/rrhh_default.png'">
                </td>
                <td class="text-center"><?php echo htmlspecialchars($row['EMPLEGAJO']); ?></td>
                <td><?php echo htmlspecialchars($row['EMPNOMBRE']); ?></td>
                <td><?php echo htmlspecialchars($row['EMPCARGONOM']); ?></td>
                <td><?php echo htmlspecialchars($row['EMPCENTROCOSTONOM']); ?></td>
                <td class="text-center"><?php echo $estBadge; ?></td>
                <td class="text-center">
                    <?php
                    $variables = array("legajo" => $row['EMPLEGAJO']);
                    $estados = array("26" => "Resumen");
                    echo impSelectHerramienta($variables, $estados, $n);
                    ?>
                </td>
            </tr>
<?php } ?>
        </tbody>
    </table>
</div>
<?php if ($n == 0): ?>
<div class="alert alert-info text-center"><i class="bi bi-info-circle me-2"></i>No se encontraron personas.</div>
<?php endif; ?>
<div class="text-muted small mt-2"><i class="bi bi-list-ol me-1"></i>Total: <?php echo $n; ?> registro(s)</div>
