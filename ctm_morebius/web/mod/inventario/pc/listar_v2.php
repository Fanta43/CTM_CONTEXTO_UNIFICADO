<?php
/**
 * Listado de Equipos del Inventario (ctm_inventario - morebius DB)
 * Reingeniería: Bootstrap 5, tabla moderna, búsqueda, badges de estado.
 */

$buscar = Enmascarar_post('buscar');

// Filtro de búsqueda
$filtro_sql = '';
if ($buscar) {
    $filtro_sql = " AND (UPPER(i.codigo) LIKE UPPER('%$buscar%')
                    OR UPPER(i.detalle) LIKE UPPER('%$buscar%')
                    OR UPPER(i.marca) LIKE UPPER('%$buscar%')
                    OR UPPER(i.modelo) LIKE UPPER('%$buscar%')
                    OR UPPER(i.numeroserie) LIKE UPPER('%$buscar%')
                    OR UPPER(i.tipo) LIKE UPPER('%$buscar%')
                    OR UPPER(i.legajo) LIKE UPPER('%$buscar%'))";
}

// Solo activos si no es admin
$filtro_baja = '';
if ($nivel < 9) {
    $filtro_baja = " AND (i.baja = 0 OR i.baja IS NULL)";
}

$sql = "SELECT i.id, i.codigo, i.producto, i.tipo, i.detalle, i.marca, i.modelo,
               i.numeroserie, i.legajo, i.baja, i.fechacompra, i.fechavtogarantia,
               i.oc, i.sr, i.pcc,
               p.EmpNombre
          FROM ctm_inventario i
          LEFT JOIN ctm_plantel p ON i.legajo = p.EmpLegajo
         WHERE 1=1 $filtro_baja $filtro_sql
         ORDER BY i.id DESC;";
$result = sql_do_query($sql, '2', $usu);
?>

<?php echo generarTituloSeccion('Inventario de Equipos', 'bi bi-pc-display'); ?>

<?php echo generarCampoBusqueda($buscar, 'Buscar por código, marca, modelo, serie, tipo, legajo...'); ?>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-pade mb-0">
                <?php echo generarTablaHeader(['#', 'Código', 'Tipo', 'Detalle', 'Marca / Modelo', 'Nro. Serie', 'Asignado a', 'Estado', 'Opciones']); ?>
                <tbody>
                <?php
                $n = 0;
                while ($row = sql_do_array($result)) {
                    $n++;
                    $garantia_vencida = false;
                    if ($row['FECHAVTOGARANTIA']) {
                        $garantia_vencida = (strtotime($row['FECHAVTOGARANTIA']) < time());
                    }
                ?>
                    <tr class="<?php echo claseFilaAlternada($n); ?>">
                        <td class="text-center"><?php echo $n; ?></td>
                        <td class="text-center fw-semibold">
                            <a href="index.php?estado=24&equipo_id=<?php echo $row['ID']; ?>" class="text-decoration-none" data-bs-toggle="tooltip" title="Ver detalle del equipo">
                                <?php echo htmlspecialchars($row['CODIGO'] ?? ''); ?>
                            </a>
                        </td>
                        <td class="text-center"><span class="badge bg-info text-dark"><?php echo htmlspecialchars($row['TIPO'] ?? ''); ?></span></td>
                        <td><?php echo htmlspecialchars(mb_strimwidth($row['DETALLE'] ?? '', 0, 40, '...')); ?></td>
                        <td>
                            <span class="fw-semibold"><?php echo htmlspecialchars($row['MARCA'] ?? ''); ?></span>
                            <?php if ($row['MODELO']): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($row['MODELO']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><small><?php echo htmlspecialchars($row['NUMEROSERIE'] ?? ''); ?></small></td>
                        <td>
                            <?php if ($row['EMPNOMBRE']): ?>
                                <a href="index.php?estado=44&legajo=<?php echo $row['LEGAJO']; ?>" class="text-decoration-none text-dark d-flex align-items-center gap-2" data-bs-toggle="tooltip" title="Ver equipos de esta persona">
                                    <?php echo fotoEmpleado($row['LEGAJO'], $row['EMPNOMBRE'], $servidor_img, 28); ?>
                                    <span><?php echo htmlspecialchars($row['EMPNOMBRE']); ?></span>
                                </a>
                                <small class="text-muted ps-5">Leg: <?php echo $row['LEGAJO']; ?></small>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php echo generarBadgeEstado($row['BAJA']); ?>
                            <?php if ($garantia_vencida): ?>
                                <br><small class="text-danger"><i class="bi bi-exclamation-triangle"></i> Gtía vencida</small>
                            <?php elseif ($row['FECHAVTOGARANTIA']): ?>
                                <br><small class="text-success"><i class="bi bi-shield-check"></i> Gtía <?php echo enmascararfecha($row['FECHAVTOGARANTIA']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <?php
                                $variables = array("equipo_id" => $row['ID'], "codigo" => $row['CODIGO']);
                                $estados = array("24" => "Resumen");
                                echo impSelectHerramienta($variables, $estados, $n);
                                ?>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
                <?php if ($n == 0): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4"><i class="bi bi-inbox me-2"></i>No se encontraron equipos</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white text-end">
        <small class="text-muted"><?php echo $n; ?> equipo(s) encontrado(s)</small>
    </div>
</div>
