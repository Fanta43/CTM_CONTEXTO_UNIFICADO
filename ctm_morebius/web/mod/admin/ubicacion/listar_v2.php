<?php
/**
 * ABM Ubicaciones (artcelubi - morebius DB READ-ONLY)
 * Listado de ubicaciones de artículos.
 */

$buscar = Enmascarar_post('buscar');

$filtro_sql = '';
if ($buscar) {
    $filtro_sql = " AND UPPER(UbicaDet) LIKE UPPER('%$buscar%')";
}

$sql = "SELECT UbicaId, UbicaDet, Ubica_Activa FROM artcelubi WHERE 1=1 $filtro_sql ORDER BY UbicaDet;";
$result = sql_do_query($sql, '2', $usu);
?>

<?php echo generarTituloSeccion('Ubicaciones', 'bi bi-geo-alt'); ?>
<?php echo generarCampoBusqueda($buscar, 'Buscar ubicación...'); ?>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-pade mb-0">
                <?php echo generarTablaHeader(['#', 'ID', 'Ubicación', 'Estado']); ?>
                <tbody>
                <?php
                $n = 0;
                while ($row = sql_do_array($result)) {
                    $n++;
                ?>
                    <tr class="<?php echo claseFilaAlternada($n); ?>">
                        <td class="text-center"><?php echo $n; ?></td>
                        <td class="text-center"><?php echo $row['UBICAID']; ?></td>
                        <td><?php echo htmlspecialchars($row['UBICADET'] ?? ''); ?></td>
                        <td class="text-center">
                            <?php if ($row['UBICA_ACTIVA'] == 1): ?>
                                <span class="badge bg-success">Activa</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactiva</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php } ?>
                <?php if ($n == 0): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">No se encontraron ubicaciones</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white text-end">
        <small class="text-muted"><?php echo $n; ?> ubicación(es)</small>
    </div>
</div>
