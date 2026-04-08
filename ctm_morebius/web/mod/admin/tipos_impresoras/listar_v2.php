<?php
/**
 * Tipos de Celular / Dispositivo (artipcel - morebius DB READ-ONLY)
 */

$sql = "SELECT tipcel, tipceldet, tipcel_activo FROM artipcel ORDER BY tipceldet;";
$result = sql_do_query($sql, '2', $usu);
?>

<?php echo generarTituloSeccion('Tipos de Dispositivo', 'bi bi-phone'); ?>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-pade mb-0">
                <?php echo generarTablaHeader(['#', 'ID', 'Tipo', 'Estado']); ?>
                <tbody>
                <?php
                $n = 0;
                while ($row = sql_do_array($result)) {
                    $n++;
                ?>
                    <tr class="<?php echo claseFilaAlternada($n); ?>">
                        <td class="text-center"><?php echo $n; ?></td>
                        <td class="text-center"><?php echo $row['TIPCEL']; ?></td>
                        <td><?php echo htmlspecialchars($row['TIPCELDET'] ?? ''); ?></td>
                        <td class="text-center">
                            <?php if ($row['TIPCEL_ACTIVO'] == 1): ?>
                                <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactivo</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white text-end">
        <small class="text-muted"><?php echo $n; ?> tipo(s)</small>
    </div>
</div>
