<?php
/**
 * Detalle de Persona - Equipos asignados
 * Muestra datos del empleado y listado completo de artículos/equipos asignados.
 */

$legajo = Enmascarar_post('legajo') ?: Enmascarar_get('legajo');

if (!$legajo) {
    echo generarAlertJS('Sin persona', 'No se especificó un legajo para consultar.', 'warning', 'index.php?estado=4');
    return;
}

// Datos de la persona
$sql_per = "SELECT p.EmpLegajo, p.EmpNombre, p.EmpCargoNom, p.EmpCentroCostoNom,
                   p.EmpGerenciaNom, p.EmpEmail, p.EmpDocDNI, p.EmpSexo,
                   p.EmpFchAlta, p.Situacion_de_Revista, p.EmpCentroCostoCod
              FROM ctm_plantel p
             WHERE p.EmpLegajo = '$legajo'
             LIMIT 1;";
$persona = sql_do_array(sql_do_query($sql_per, '2', $usu));

if (!$persona) {
    echo generarAlertJS('No encontrado', 'El legajo solicitado no existe en el plantel.', 'error', 'index.php?estado=4');
    return;
}

// Artículos asignados (articulos_con)
$sql_art = "SELECT a.nro_art, a.fecha, a.tipo_art, a.detalle, a.nota,
                   a.devuelto, a.DE_BAJA, a.Nro_Linea, a.MARCA, a.MODELO, a.NSERIE, a.IMEI,
                   t.detalle AS tipo_nombre,
                   m.detmarca AS marca_nombre
              FROM articulos_con a
              LEFT JOIN articulos_tipos t ON a.tipo_art = t.tipo_art
              LEFT JOIN artimarcas m ON a.MARCA = m.marcaid
             WHERE a.legajo_art = '$legajo'
             ORDER BY a.DE_BAJA ASC, a.devuelto ASC, a.fecha DESC;";
$result_art = sql_do_query($sql_art, '2', $usu);

// Equipos del inventario (ctm_inventario)
$sql_inv = "SELECT i.id, i.codigo, i.tipo, i.detalle, i.marca, i.modelo,
                   i.numeroserie, i.baja, i.fechacompra, i.fechavtogarantia
              FROM ctm_inventario i
             WHERE i.legajo = '$legajo'
             ORDER BY i.baja ASC, i.id DESC;";
$result_inv = sql_do_query($sql_inv, '2', $usu);

$cant_art = registros($result_art);
$cant_inv = registros($result_inv);
?>

<?php echo generarTituloSeccion('Detalle: ' . htmlspecialchars($persona['EMPNOMBRE'] ?? ''), 'bi bi-person-badge'); ?>

<div class="row g-3 fade-in">
    <!-- Datos personales -->
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-person-circle me-2"></i>Datos del Empleado</h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <?php echo fotoEmpleado($persona['EMPLEGAJO'], $persona['EMPNOMBRE'] ?? '', $servidor_img, 80); ?>
                    <div class="mt-2 fw-semibold"><?php echo htmlspecialchars($persona['EMPNOMBRE']); ?></div>
                </div>
                <table class="table table-sm table-borderless mb-0">
                    <tr><th style="width:40%">Legajo</th><td class="fw-semibold"><?php echo $persona['EMPLEGAJO']; ?></td></tr>
                    <tr><th>DNI</th><td><?php echo htmlspecialchars($persona['EMPDOCDNI'] ?? ''); ?></td></tr>
                    <tr><th>Cargo</th><td><?php echo htmlspecialchars($persona['EMPCARGONOM'] ?? ''); ?></td></tr>
                    <tr><th>Centro Costo</th><td><?php echo htmlspecialchars($persona['EMPCENTROCOSTONOM'] ?? ''); ?></td></tr>
                    <tr><th>Gerencia</th><td><?php echo htmlspecialchars($persona['EMPGERENCIANOM'] ?? ''); ?></td></tr>
                    <tr><th>Email</th><td><small><?php echo htmlspecialchars($persona['EMPEMAIL'] ?? ''); ?></small></td></tr>
                    <tr><th>Situación</th><td><?php echo htmlspecialchars($persona['SITUACION_DE_REVISTA'] ?? ''); ?></td></tr>
                    <tr><th>Alta</th><td><?php echo enmascararfecha($persona['EMPFCHALTA']); ?></td></tr>
                </table>
            </div>
        </div>

        <!-- Resumen rápido -->
        <div class="card shadow-sm mt-3">
            <div class="card-body text-center">
                <div class="row g-2">
                    <div class="col-6">
                        <div class="stat-card py-2" style="background: linear-gradient(135deg, #28a745, #1e7e34); border-radius:8px;">
                            <div class="stat-number fs-4"><?php echo $cant_art; ?></div>
                            <div class="stat-label">Artículos</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card py-2" style="background: linear-gradient(135deg, #418CBA, #2d6a8f); border-radius:8px;">
                            <div class="stat-number fs-4"><?php echo $cant_inv; ?></div>
                            <div class="stat-label">Equipos Inv.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Equipos asignados -->
    <div class="col-lg-8">
        <!-- Artículos (articulos_con) -->
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-box-seam me-2"></i>Artículos Asignados</h6>
                <span class="badge bg-primary"><?php echo $cant_art; ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-pade mb-0">
                        <?php echo generarTablaHeader(['Art.', 'Tipo', 'Detalle', 'Marca / Modelo', 'Serie / IMEI', 'Fecha', 'Estado']); ?>
                        <tbody>
                        <?php
                        $n = 0;
                        while ($row = sql_do_array($result_art)) {
                            $n++;
                        ?>
                            <tr class="<?php echo claseFilaAlternada($n); ?>">
                                <td class="text-center fw-semibold"><?php echo $row['NRO_ART']; ?></td>
                                <td class="text-center"><span class="badge bg-info text-dark"><?php echo htmlspecialchars($row['TIPO_NOMBRE'] ?? ''); ?></span></td>
                                <td><?php echo htmlspecialchars(mb_strimwidth($row['DETALLE'] ?? '', 0, 30, '...')); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($row['MARCA_NOMBRE'] ?? ''); ?>
                                    <?php if ($row['MODELO']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($row['MODELO']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <small>
                                        <?php echo htmlspecialchars($row['NSERIE'] ?? ''); ?>
                                        <?php if ($row['IMEI']): ?>
                                            <br>IMEI: <?php echo htmlspecialchars($row['IMEI']); ?>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td class="text-center"><small><?php echo enmascararfecha($row['FECHA']); ?></small></td>
                                <td class="text-center">
                                    <?php echo generarBadgeEstado($row['DE_BAJA']); ?>
                                    <?php echo generarBadgeDevuelto($row['DEVUELTO']); ?>
                                </td>
                            </tr>
                        <?php } ?>
                        <?php if ($n == 0): ?>
                            <tr><td colspan="7" class="text-center text-muted py-3">Sin artículos asignados</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Equipos inventario (ctm_inventario) -->
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-pc-display me-2"></i>Equipos en Inventario</h6>
                <span class="badge bg-primary"><?php echo $cant_inv; ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-pade mb-0">
                        <?php echo generarTablaHeader(['Código', 'Tipo', 'Detalle', 'Marca / Modelo', 'Nro. Serie', 'Compra', 'Garantía', 'Estado']); ?>
                        <tbody>
                        <?php
                        $n2 = 0;
                        while ($row2 = sql_do_array($result_inv)) {
                            $n2++;
                            $gvenc = ($row2['FECHAVTOGARANTIA'] && strtotime($row2['FECHAVTOGARANTIA']) < time());
                        ?>
                            <tr class="<?php echo claseFilaAlternada($n2); ?>">
                                <td class="text-center">
                                    <a href="index.php?estado=24&equipo_id=<?php echo $row2['ID']; ?>" class="fw-semibold text-decoration-none" data-bs-toggle="tooltip" title="Ver detalle del equipo">
                                        <?php echo htmlspecialchars($row2['CODIGO'] ?? ''); ?>
                                    </a>
                                </td>
                                <td class="text-center"><span class="badge bg-info text-dark"><?php echo htmlspecialchars($row2['TIPO'] ?? ''); ?></span></td>
                                <td><?php echo htmlspecialchars(mb_strimwidth($row2['DETALLE'] ?? '', 0, 30, '...')); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($row2['MARCA'] ?? ''); ?>
                                    <?php if ($row2['MODELO']): ?><br><small class="text-muted"><?php echo htmlspecialchars($row2['MODELO']); ?></small><?php endif; ?>
                                </td>
                                <td class="text-center"><small><?php echo htmlspecialchars($row2['NUMEROSERIE'] ?? ''); ?></small></td>
                                <td class="text-center"><small><?php echo enmascararfecha($row2['FECHACOMPRA']); ?></small></td>
                                <td class="text-center">
                                    <?php if ($gvenc): ?>
                                        <small class="text-danger"><i class="bi bi-exclamation-triangle"></i> Vencida</small>
                                    <?php elseif ($row2['FECHAVTOGARANTIA']): ?>
                                        <small class="text-success"><i class="bi bi-shield-check"></i> <?php echo enmascararfecha($row2['FECHAVTOGARANTIA']); ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?php echo generarBadgeEstado($row2['BAJA']); ?></td>
                            </tr>
                        <?php } ?>
                        <?php if ($n2 == 0): ?>
                            <tr><td colspan="8" class="text-center text-muted py-3">Sin equipos en inventario</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Volver -->
<div class="mt-3">
    <a href="index.php?estado=4" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver al listado
    </a>
</div>
