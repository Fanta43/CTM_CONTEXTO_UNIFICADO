<?php
/**
 * ABM Tipos de Artículo (articulos_tipos - morebius DB READ-ONLY)
 * Listado de tipos: Celular, Notebook, PC, etc.
 */

$sql = "SELECT tipo_art, detalle FROM articulos_tipos ORDER BY detalle;";
$result = sql_do_query($sql, '2', $usu);
?>

<?php echo generarTituloSeccion('Tipos de Artículo', 'bi bi-list-ul'); ?>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-pade mb-0">
                <?php echo generarTablaHeader(['#', 'ID', 'Tipo de Artículo']); ?>
                <tbody>
                <?php
                $n = 0;
                while ($row = sql_do_array($result)) {
                    $n++;
                ?>
                    <tr class="<?php echo claseFilaAlternada($n); ?>">
                        <td class="text-center"><?php echo $n; ?></td>
                        <td class="text-center"><?php echo $row['TIPO_ART']; ?></td>
                        <td><?php echo htmlspecialchars($row['DETALLE'] ?? ''); ?></td>
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
