<?php
/**
 * Centros de Costo / Sectores (ctm_centroscosto - morebius DB READ-ONLY)
 */

$buscar = Enmascarar_post('buscar');

$filtro_sql = '';
if ($buscar) {
    $filtro_sql = " WHERE UPPER(CentroCostoNom) LIKE UPPER('%$buscar%') OR CAST(CentroCosto AS CHAR) LIKE '%$buscar%'";
}

$sql = "SELECT CentroCostoCod, CentroCostoNom, CentroCosto FROM ctm_centroscosto $filtro_sql ORDER BY CentroCostoNom;";
$result = sql_do_query($sql, '2', $usu);
?>

<?php echo generarTituloSeccion('Centros de Costo / Sectores', 'bi bi-building'); ?>
<?php echo generarCampoBusqueda($buscar, 'Buscar sector...'); ?>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-pade mb-0">
                <?php echo generarTablaHeader(['#', 'Código', 'Centro de Costo', 'CC Nro.']); ?>
                <tbody>
                <?php
                $n = 0;
                while ($row = sql_do_array($result)) {
                    $n++;
                ?>
                    <tr class="<?php echo claseFilaAlternada($n); ?>">
                        <td class="text-center"><?php echo $n; ?></td>
                        <td class="text-center"><?php echo $row['CENTROCOSTOCOD']; ?></td>
                        <td><?php echo htmlspecialchars($row['CENTROCOSTONOM'] ?? ''); ?></td>
                        <td class="text-center"><span class="badge bg-secondary"><?php echo $row['CENTROCOSTO']; ?></span></td>
                    </tr>
                <?php } ?>
                <?php if ($n == 0): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">No se encontraron sectores</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white text-end">
        <small class="text-muted"><?php echo $n; ?> sector(es)</small>
    </div>
</div>
