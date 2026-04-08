<?php
/**
 * Resumen / Detalle de un equipo del inventario (ctm_inventario)
 * Muestra datos del equipo, historial de asignaciones y proveedor.
 */

$codigo_equipo = Enmascarar_post('codigo') ?: Enmascarar_get('codigo');
$equipo_id     = Enmascarar_post('equipo_id') ?: Enmascarar_get('equipo_id');

if (!$codigo_equipo && !$equipo_id) {
    echo generarAlertJS('Sin equipo', 'No se especificó un equipo para consultar.', 'warning', 'index.php?estado=1');
    return;
}

// Buscar por código o por ID
$where = $codigo_equipo ? "i.codigo = '$codigo_equipo'" : "i.id = '$equipo_id'";

$sql = "SELECT i.*, p.EmpNombre, p.EmpCargoNom, p.EmpCentroCostoNom, p.EmpEmail
          FROM ctm_inventario i
          LEFT JOIN ctm_plantel p ON i.legajo = p.EmpLegajo
         WHERE $where
         LIMIT 1;";
$row = sql_do_array(sql_do_query($sql, '2', $usu));

if (!$row) {
    echo generarAlertJS('No encontrado', 'El equipo solicitado no existe.', 'error', 'index.php?estado=1');
    return;
}

$garantia_vencida = false;
if ($row['FECHAVTOGARANTIA']) {
    $garantia_vencida = (strtotime($row['FECHAVTOGARANTIA']) < time());
}
?>

<?php echo generarTituloSeccion('Detalle del Equipo: ' . ($row['CODIGO'] ?? ''), 'bi bi-pc-display'); ?>

<div class="row g-3 fade-in">
    <!-- Info principal -->
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Información del Equipo</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th style="width:35%">Código</th><td class="fw-semibold"><?php echo htmlspecialchars($row['CODIGO'] ?? ''); ?></td></tr>
                    <tr><th>Producto</th><td><?php echo htmlspecialchars($row['PRODUCTO'] ?? ''); ?></td></tr>
                    <tr><th>Tipo</th><td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($row['TIPO'] ?? ''); ?></span></td></tr>
                    <tr><th>Detalle</th><td><?php echo htmlspecialchars($row['DETALLE'] ?? ''); ?></td></tr>
                    <tr><th>Marca</th><td><?php echo htmlspecialchars($row['MARCA'] ?? ''); ?></td></tr>
                    <tr><th>Modelo</th><td><?php echo htmlspecialchars($row['MODELO'] ?? ''); ?></td></tr>
                    <tr><th>Nro. Serie</th><td><code><?php echo htmlspecialchars($row['NUMEROSERIE'] ?? ''); ?></code></td></tr>
                    <tr><th>OC</th><td><?php echo htmlspecialchars($row['OC'] ?? ''); ?></td></tr>
                    <tr><th>SR</th><td><?php echo htmlspecialchars($row['SR'] ?? ''); ?></td></tr>
                    <tr><th>PCC</th><td><?php echo htmlspecialchars($row['PCC'] ?? ''); ?></td></tr>
                    <tr><th>Fecha Compra</th><td><?php echo enmascararfecha($row['FECHACOMPRA']); ?></td></tr>
                    <tr><th>Garantía Vto.</th><td>
                        <?php if ($garantia_vencida): ?>
                            <span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i><?php echo enmascararfecha($row['FECHAVTOGARANTIA']); ?> (Vencida)</span>
                        <?php elseif ($row['FECHAVTOGARANTIA']): ?>
                            <span class="text-success"><i class="bi bi-shield-check me-1"></i><?php echo enmascararfecha($row['FECHAVTOGARANTIA']); ?></span>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td></tr>
                    <tr><th>Estado</th><td><?php echo generarBadgeEstado($row['BAJA']); ?></td></tr>
                    <?php if ($row['BAJA'] == 1 && $row['FCH_BAJA']): ?>
                    <tr><th>Fecha Baja</th><td class="text-danger"><?php echo enmascararfecha($row['FCH_BAJA']); ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Asignación actual -->
    <div class="col-lg-6">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="bi bi-person-badge me-2"></i>Asignación Actual</h6>
            </div>
            <div class="card-body">
                <?php if ($row['LEGAJO'] && $row['EMPNOMBRE']): ?>
                <div class="text-center mb-3">
                    <a href="index.php?estado=44&legajo=<?php echo $row['LEGAJO']; ?>">
                        <?php echo fotoEmpleado($row['LEGAJO'], $row['EMPNOMBRE'], $servidor_img, 64); ?>
                    </a>
                </div>
                <table class="table table-sm table-borderless mb-0">
                    <tr><th style="width:35%">Legajo</th><td>
                        <a href="index.php?estado=44&legajo=<?php echo $row['LEGAJO']; ?>" class="text-decoration-none"><?php echo $row['LEGAJO']; ?></a>
                    </td></tr>
                    <tr><th>Nombre</th><td class="fw-semibold">
                        <a href="index.php?estado=44&legajo=<?php echo $row['LEGAJO']; ?>" class="text-decoration-none text-dark" data-bs-toggle="tooltip" title="Ver todos los equipos de esta persona">
                            <?php echo htmlspecialchars($row['EMPNOMBRE']); ?>
                        </a>
                    </td></tr>
                    <tr><th>Cargo</th><td><?php echo htmlspecialchars($row['EMPCARGONOM'] ?? ''); ?></td></tr>
                    <tr><th>Centro Costo</th><td><?php echo htmlspecialchars($row['EMPCENTROCOSTONOM'] ?? ''); ?></td></tr>
                    <tr><th>Email</th><td><?php echo htmlspecialchars($row['EMPEMAIL'] ?? ''); ?></td></tr>
                </table>
                <?php else: ?>
                <div class="text-center text-muted py-3">
                    <i class="bi bi-person-x fs-1 d-block mb-2"></i>
                    Sin asignación actual
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Proveedor -->
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0"><i class="bi bi-building me-2"></i>Proveedor</h6>
            </div>
            <div class="card-body">
                <?php if ($row['PROVEEDOR_NOMBRE']): ?>
                <table class="table table-sm table-borderless mb-0">
                    <tr><th style="width:35%">Nombre</th><td><?php echo htmlspecialchars($row['PROVEEDOR_NOMBRE']); ?></td></tr>
                    <tr><th>Dirección</th><td><?php echo htmlspecialchars($row['PROVEEDOR_DIRECCION'] ?? ''); ?></td></tr>
                    <tr><th>Teléfono</th><td><?php echo htmlspecialchars($row['PROVEEDOR_TELEFONO'] ?? ''); ?></td></tr>
                    <tr><th>Email</th><td><?php echo htmlspecialchars($row['PROVEEDOR_EMAIL1'] ?? ''); ?></td></tr>
                </table>
                <?php else: ?>
                <div class="text-center text-muted py-3">Sin datos de proveedor</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Botón volver -->
<div class="mt-3">
    <a href="javascript:history.back();" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
</div>
