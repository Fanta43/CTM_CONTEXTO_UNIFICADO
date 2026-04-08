<?php
/**
 * ABM Marcas (artimarcas - morebius DB READ-ONLY)
 * Listado de marcas de equipos.
 * NOTA: morebius es intocable, se muestra solo consulta.
 */

$buscar = Enmascarar_post('buscar');

$filtro_sql = '';
if ($buscar) {
    $filtro_sql = " WHERE UPPER(detmarca) LIKE UPPER('%$buscar%')";
}

$sql = "SELECT marcaid, detmarca FROM artimarcas $filtro_sql ORDER BY detmarca;";
$result = sql_do_query($sql, '2', $usu);
?>

<?php echo generarTituloSeccion('Marcas de Equipos', 'bi bi-tag'); ?>
<?php echo generarCampoBusqueda($buscar, 'Buscar marca...'); ?>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-pade mb-0">
                <?php echo generarTablaHeader(['#', 'ID', 'Marca']); ?>
                <tbody>
                <?php
                $n = 0;
                while ($row = sql_do_array($result)) {
                    $n++;
                ?>
                    <tr class="<?php echo claseFilaAlternada($n); ?>">
                        <td class="text-center"><?php echo $n; ?></td>
                        <td class="text-center"><?php echo $row['MARCAID']; ?></td>
                        <td><?php echo htmlspecialchars($row['DETMARCA'] ?? ''); ?></td>
                    </tr>
                <?php } ?>
                <?php if ($n == 0): ?>
                    <tr><td colspan="3" class="text-center text-muted py-4">No se encontraron marcas</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white text-end">
        <small class="text-muted"><?php echo $n; ?> marca(s)</small>
    </div>
</div>
