<?php
/**
 * Listado de Personal (ctm_plantel - morebius DB)
 * Muestra empleados con sus datos, cargo, centro de costo y equipos asignados.
 */

$buscar = Enmascarar_post('buscar');

$filtro_sql = '';
if ($buscar) {
    $filtro_sql = " AND (UPPER(p.EmpNombre) LIKE UPPER('%$buscar%')
                    OR UPPER(p.EmpLegajo) LIKE UPPER('%$buscar%')
                    OR UPPER(p.EmpCargoNom) LIKE UPPER('%$buscar%')
                    OR UPPER(p.EmpCentroCostoNom) LIKE UPPER('%$buscar%')
                    OR UPPER(p.EmpEmail) LIKE UPPER('%$buscar%')
                    OR UPPER(p.EmpDocDNI) LIKE UPPER('%$buscar%'))";
}

$sql = "SELECT p.EmpLegajo, p.EmpNombre, p.EmpCargoNom, p.EmpCentroCostoNom,
               p.EmpEmail, p.EmpGerenciaNom, p.EmpSexo, p.EmpDocDNI,
               p.EmpFchAlta, p.Situacion_de_Revista,
               (SELECT COUNT(*) FROM articulos_con a
                 WHERE a.legajo_art = p.EmpLegajo
                   AND (a.DE_BAJA <> 1 OR a.DE_BAJA IS NULL)
                   AND (a.devuelto = 0 OR a.devuelto IS NULL)) AS cant_equipos
          FROM ctm_plantel p
         WHERE 1=1 $filtro_sql
         ORDER BY p.EmpNombre ASC;";
$result = sql_do_query($sql, '2', $usu);
?>

<?php echo generarTituloSeccion('Personal - Plantel', 'bi bi-people'); ?>

<?php echo generarCampoBusqueda($buscar, 'Buscar por nombre, legajo, cargo, centro de costo, email, DNI...'); ?>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-pade mb-0">
                <?php echo generarTablaHeader(['#', 'Foto', 'Legajo', 'Nombre', 'Cargo', 'Centro de Costo', 'Gerencia', 'Email', 'Equipos', 'Alta']); ?>
                <tbody>
                <?php
                $n = 0;
                while ($row = sql_do_array($result)) {
                    $n++;
                ?>
                    <tr class="<?php echo claseFilaAlternada($n); ?>">
                        <td class="text-center"><?php echo $n; ?></td>
                        <td class="text-center">
                            <a href="index.php?estado=44&legajo=<?php echo $row['EMPLEGAJO']; ?>">
                                <?php echo fotoEmpleado($row['EMPLEGAJO'], $row['EMPNOMBRE'] ?? '', $servidor_img); ?>
                            </a>
                        </td>
                        <td class="text-center fw-semibold">
                            <a href="index.php?estado=44&legajo=<?php echo $row['EMPLEGAJO']; ?>" class="text-decoration-none" data-bs-toggle="tooltip" title="Ver detalle">
                                <?php echo $row['EMPLEGAJO']; ?>
                            </a>
                        </td>
                        <td>
                            <a href="index.php?estado=44&legajo=<?php echo $row['EMPLEGAJO']; ?>" class="text-decoration-none text-dark">
                                <?php echo htmlspecialchars($row['EMPNOMBRE'] ?? ''); ?>
                            </a>
                            <?php if ($row['SITUACION_DE_REVISTA']): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($row['SITUACION_DE_REVISTA']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['EMPCARGONOM'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['EMPCENTROCOSTONOM'] ?? ''); ?></td>
                        <td><small><?php echo htmlspecialchars($row['EMPGERENCIANOM'] ?? ''); ?></small></td>
                        <td><small><?php echo htmlspecialchars($row['EMPEMAIL'] ?? ''); ?></small></td>
                        <td class="text-center">
                            <?php if ($row['CANT_EQUIPOS'] > 0): ?>
                                <a href="index.php?estado=44&legajo=<?php echo $row['EMPLEGAJO']; ?>" class="text-decoration-none" data-bs-toggle="tooltip" title="Ver equipos asignados">
                                    <span class="badge bg-primary"><?php echo $row['CANT_EQUIPOS']; ?></span>
                                </a>
                            <?php else: ?>
                                <span class="badge bg-light text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><small><?php echo enmascararfecha($row['EMPFCHALTA']); ?></small></td>
                    </tr>
                <?php } ?>
                <?php if ($n == 0): ?>
                    <tr><td colspan="10" class="text-center text-muted py-4"><i class="bi bi-inbox me-2"></i>No se encontró personal</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white text-end">
        <small class="text-muted"><?php echo $n; ?> persona(s) encontrada(s)</small>
    </div>
</div>
