<?php
/**
 * ============================================================================
 * Validar Cierre
 * ============================================================================
 * ID menú: 602 | Tablas: ciclo_solicitud_recurso, ciclo_periodo_fiscal
 * Paso 2: verifica que no queden solicitudes sin resolver.
 * No permite avanzar al paso 3 hasta que estén todas cerradas.
 * ============================================================================
 */

$db  = '1';
$msg = '';

$rol_usuario = <?php
/**
 * ============================================================================
 * Validar Cierre
 * ============================================================================
 * ID menú: 602 | Tablas: ciclo_solicitud_recurso, ciclo_periodo_fiscal
 * Paso 2: verifica que no queden solicitudes sin resolver.
 * No permite avanzar al paso 3 hasta que estén todas cerradas.
 * ============================================================================
 */

$db  = '1';
$msg = '';

$rol_usuario = $_SESSION['rol'] ?? '';

// ── Obtener período en cierre ────────────────────────────────────────────────
$row_periodo = sql_do_array(sql_do_query(
    "SELECT * FROM ciclo_periodo_fiscal WHERE estado = 'en_cierre' LIMIT 1;", $db, $usu));

if (!$row_periodo) {
    echo generarAlertJS('Sin período', 'No hay un período en estado en_cierre. Debe ejecutar el Paso 1 primero.', 'warning', "index.php?estado=601");
    return;
}
$periodo_id = $row_periodo['ID'];
$anio = $row_periodo['ANIO'];

// ── Solicitudes sin resolver ─────────────────────────────────────────────────
$sql_pend = "SELECT sr.id, sr.descripcion, sr.monto_total, sr.estado AS ESTADO_SOL,
                    u.nombre AS SOLICITANTE, sr.fecha_solicitud
             FROM ciclo_solicitud_recurso sr
             JOIN ciclo_usuario u ON sr.id_usuario_solicita = u.id
             WHERE sr.id_periodo = '$periodo_id' AND sr.estado IN ('pendiente', 'aprobada')
             ORDER BY sr.estado, sr.fecha_solicitud;";
$res_pend = sql_do_query($sql_pend, $db, $usu);

$pendientes = [];
while ($rp = sql_do_array($res_pend)) $pendientes[] = $rp;
$hay_pendientes = !empty($pendientes);
?>

<?php echo generarTituloSeccion('Validar Cierre — Período ' . $anio, 'bi bi-clipboard-check'); ?>

<?php echo generarWizardPasos(2, 4, ['Iniciar', 'Validar', 'Consolidar', 'Cerrar']); ?>

<?php if ($hay_pendientes): ?>

<div class="alert alert-danger mb-4">
    <h6 class="alert-heading"><i class="bi bi-exclamation-octagon me-2"></i>Solicitudes sin resolver</h6>
    <p class="mb-0">Existen <strong><?php echo count($pendientes); ?></strong> solicitud(es) que deben resolverse antes de continuar con la consolidación.</p>
</div>

<div class="table-responsive">
    <table class="table table-hover table-pade">
        <?php echo generarTablaHeader(['Nro', 'Solicitante', 'Descripción', 'Monto', 'Estado', 'Fecha', 'Ir a resolver']); ?>
        <tbody>
<?php
$n = 0;
foreach ($pendientes as $p):
    $n++;
    $estado_badge = ($p['ESTADO_SOL'] === 'pendiente')
        ? '<span class="badge bg-warning text-dark">Pendiente</span>'
        : '<span class="badge bg-info">Aprobada</span>';
    $link_resolver = ($p['ESTADO_SOL'] === 'pendiente') ? '403' : '404';
?>
            <tr class="<?php echo claseFilaAlternada($n); ?>">
                <td class="text-center fw-bold">#<?php echo $p['ID']; ?></td>
                <td><?php echo htmlspecialchars($p['SOLICITANTE']); ?></td>
                <td><?php echo htmlspecialchars($p['DESCRIPCION'] ?? ''); ?></td>
                <td class="text-end">$ <?php echo number_format((float)$p['MONTO_TOTAL'], 2, ',', '.'); ?></td>
                <td class="text-center"><?php echo $estado_badge; ?></td>
                <td class="text-center small"><?php echo enmascararfecha($p['FECHA_SOLICITUD'] ?? ''); ?></td>
                <td class="text-center">
                    <a href="index.php?estado=<?php echo $link_resolver; ?>" class="btn btn-pade btn-sm">
                        <i class="bi bi-arrow-right me-1"></i>Resolver
                    </a>
                </td>
            </tr>
<?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php else: ?>

<div class="alert alert-success mb-4">
    <h6 class="alert-heading"><i class="bi bi-check-circle me-2"></i>Validación OK</h6>
    <p class="mb-0">No hay solicitudes pendientes ni aprobadas sin ejecutar. Puede continuar con la consolidación.</p>
</div>

<div class="card shadow-sm">
    <div class="card-body text-center">
        <p class="text-muted mb-3">Todas las solicitudes del período <?php echo $anio; ?> están resueltas.</p>
        <a href="index.php?estado=603" class="btn btn-success btn-lg">
            <i class="bi bi-arrow-right me-2"></i>Continuar a Consolidación (Paso 3)
        </a>
    </div>
</div>

<?php endif; ?>
SESSION['ciclo_rol_efectivo'] ?? '';

// ── Obtener período en cierre ────────────────────────────────────────────────
$row_periodo = sql_do_array(sql_do_query(
    "SELECT * FROM ciclo_periodo_fiscal WHERE estado = 'en_cierre' LIMIT 1;", $db, $usu));

if (!$row_periodo) {
    echo generarAlertJS('Sin período', 'No hay un período en estado en_cierre. Debe ejecutar el Paso 1 primero.', 'warning', "index.php?estado=601");
    return;
}
$periodo_id = $row_periodo['ID'];
$anio = $row_periodo['ANIO'];

// ── Solicitudes sin resolver ─────────────────────────────────────────────────
$sql_pend = "SELECT sr.id, sr.descripcion, sr.monto_total, sr.estado AS ESTADO_SOL,
                    u.nombre AS SOLICITANTE, sr.fecha_solicitud
             FROM ciclo_solicitud_recurso sr
             JOIN ciclo_usuario u ON sr.id_usuario_solicita = u.id
             WHERE sr.id_periodo = '$periodo_id' AND sr.estado IN ('pendiente', 'aprobada')
             ORDER BY sr.estado, sr.fecha_solicitud;";
$res_pend = sql_do_query($sql_pend, $db, $usu);

$pendientes = [];
while ($rp = sql_do_array($res_pend)) $pendientes[] = $rp;
$hay_pendientes = !empty($pendientes);
?>

<?php echo generarTituloSeccion('Validar Cierre — Período ' . $anio, 'bi bi-clipboard-check'); ?>

<?php echo generarWizardPasos(2, 4, ['Iniciar', 'Validar', 'Consolidar', 'Cerrar']); ?>

<?php if ($hay_pendientes): ?>

<div class="alert alert-danger mb-4">
    <h6 class="alert-heading"><i class="bi bi-exclamation-octagon me-2"></i>Solicitudes sin resolver</h6>
    <p class="mb-0">Existen <strong><?php echo count($pendientes); ?></strong> solicitud(es) que deben resolverse antes de continuar con la consolidación.</p>
</div>

<div class="table-responsive">
    <table class="table table-hover table-pade">
        <?php echo generarTablaHeader(['Nro', 'Solicitante', 'Descripción', 'Monto', 'Estado', 'Fecha', 'Ir a resolver']); ?>
        <tbody>
<?php
$n = 0;
foreach ($pendientes as $p):
    $n++;
    $estado_badge = ($p['ESTADO_SOL'] === 'pendiente')
        ? '<span class="badge bg-warning text-dark">Pendiente</span>'
        : '<span class="badge bg-info">Aprobada</span>';
    $link_resolver = ($p['ESTADO_SOL'] === 'pendiente') ? '403' : '404';
?>
            <tr class="<?php echo claseFilaAlternada($n); ?>">
                <td class="text-center fw-bold">#<?php echo $p['ID']; ?></td>
                <td><?php echo htmlspecialchars($p['SOLICITANTE']); ?></td>
                <td><?php echo htmlspecialchars($p['DESCRIPCION'] ?? ''); ?></td>
                <td class="text-end">$ <?php echo number_format((float)$p['MONTO_TOTAL'], 2, ',', '.'); ?></td>
                <td class="text-center"><?php echo $estado_badge; ?></td>
                <td class="text-center small"><?php echo enmascararfecha($p['FECHA_SOLICITUD'] ?? ''); ?></td>
                <td class="text-center">
                    <a href="index.php?estado=<?php echo $link_resolver; ?>" class="btn btn-pade btn-sm">
                        <i class="bi bi-arrow-right me-1"></i>Resolver
                    </a>
                </td>
            </tr>
<?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php else: ?>

<div class="alert alert-success mb-4">
    <h6 class="alert-heading"><i class="bi bi-check-circle me-2"></i>Validación OK</h6>
    <p class="mb-0">No hay solicitudes pendientes ni aprobadas sin ejecutar. Puede continuar con la consolidación.</p>
</div>

<div class="card shadow-sm">
    <div class="card-body text-center">
        <p class="text-muted mb-3">Todas las solicitudes del período <?php echo $anio; ?> están resueltas.</p>
        <a href="index.php?estado=603" class="btn btn-success btn-lg">
            <i class="bi bi-arrow-right me-2"></i>Continuar a Consolidación (Paso 3)
        </a>
    </div>
</div>

<?php endif; ?>
SESSION['ciclo_rol_efectivo'] ?? '';

// ── Obtener período en cierre ────────────────────────────────────────────────
$row_periodo = sql_do_array(sql_do_query(
    "SELECT * FROM ciclo_periodo_fiscal WHERE estado = 'en_cierre' LIMIT 1;", $db, $usu));

if (!$row_periodo) {
    echo generarAlertJS('Sin período', 'No hay un período en estado en_cierre. Debe ejecutar el Paso 1 primero.', 'warning', "index.php?estado=601");
    return;
}
$periodo_id = $row_periodo['ID'];
$anio = $row_periodo['ANIO'];

// ── Solicitudes sin resolver ─────────────────────────────────────────────────
$sql_pend = "SELECT sr.id, sr.descripcion, sr.monto_total, sr.estado AS ESTADO_SOL,
                    u.nombre AS SOLICITANTE, sr.fecha_solicitud
             FROM ciclo_solicitud_recurso sr
             JOIN ciclo_usuario u ON sr.id_usuario_solicita = u.id
             WHERE sr.id_periodo = '$periodo_id' AND sr.estado IN ('pendiente', 'aprobada')
             ORDER BY sr.estado, sr.fecha_solicitud;";
$res_pend = sql_do_query($sql_pend, $db, $usu);

$pendientes = [];
while ($rp = sql_do_array($res_pend)) $pendientes[] = $rp;
$hay_pendientes = !empty($pendientes);
?>

<?php echo generarTituloSeccion('Validar Cierre — Período ' . $anio, 'bi bi-clipboard-check'); ?>

<?php echo generarWizardPasos(2, 4, ['Iniciar', 'Validar', 'Consolidar', 'Cerrar']); ?>

<?php if ($hay_pendientes): ?>

<div class="alert alert-danger mb-4">
    <h6 class="alert-heading"><i class="bi bi-exclamation-octagon me-2"></i>Solicitudes sin resolver</h6>
    <p class="mb-0">Existen <strong><?php echo count($pendientes); ?></strong> solicitud(es) que deben resolverse antes de continuar con la consolidación.</p>
</div>

<div class="table-responsive">
    <table class="table table-hover table-pade">
        <?php echo generarTablaHeader(['Nro', 'Solicitante', 'Descripción', 'Monto', 'Estado', 'Fecha', 'Ir a resolver']); ?>
        <tbody>
<?php
$n = 0;
foreach ($pendientes as $p):
    $n++;
    $estado_badge = ($p['ESTADO_SOL'] === 'pendiente')
        ? '<span class="badge bg-warning text-dark">Pendiente</span>'
        : '<span class="badge bg-info">Aprobada</span>';
    $link_resolver = ($p['ESTADO_SOL'] === 'pendiente') ? '403' : '404';
?>
            <tr class="<?php echo claseFilaAlternada($n); ?>">
                <td class="text-center fw-bold">#<?php echo $p['ID']; ?></td>
                <td><?php echo htmlspecialchars($p['SOLICITANTE']); ?></td>
                <td><?php echo htmlspecialchars($p['DESCRIPCION'] ?? ''); ?></td>
                <td class="text-end">$ <?php echo number_format((float)$p['MONTO_TOTAL'], 2, ',', '.'); ?></td>
                <td class="text-center"><?php echo $estado_badge; ?></td>
                <td class="text-center small"><?php echo enmascararfecha($p['FECHA_SOLICITUD'] ?? ''); ?></td>
                <td class="text-center">
                    <a href="index.php?estado=<?php echo $link_resolver; ?>" class="btn btn-pade btn-sm">
                        <i class="bi bi-arrow-right me-1"></i>Resolver
                    </a>
                </td>
            </tr>
<?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php else: ?>

<div class="alert alert-success mb-4">
    <h6 class="alert-heading"><i class="bi bi-check-circle me-2"></i>Validación OK</h6>
    <p class="mb-0">No hay solicitudes pendientes ni aprobadas sin ejecutar. Puede continuar con la consolidación.</p>
</div>

<div class="card shadow-sm">
    <div class="card-body text-center">
        <p class="text-muted mb-3">Todas las solicitudes del período <?php echo $anio; ?> están resueltas.</p>
        <a href="index.php?estado=603" class="btn btn-success btn-lg">
            <i class="bi bi-arrow-right me-2"></i>Continuar a Consolidación (Paso 3)
        </a>
    </div>
</div>

<?php endif; ?>
SESSION['ciclo_rol_efectivo'] ?? '';

// ── Obtener período en cierre ────────────────────────────────────────────────
$row_periodo = sql_do_array(sql_do_query(
    "SELECT * FROM ciclo_periodo_fiscal WHERE estado = 'en_cierre' LIMIT 1;", $db, $usu));

if (!$row_periodo) {
    echo generarAlertJS('Sin período', 'No hay un período en estado en_cierre. Debe ejecutar el Paso 1 primero.', 'warning', "index.php?estado=601");
    return;
}
$periodo_id = $row_periodo['ID'];
$anio = $row_periodo['ANIO'];

// ── Solicitudes sin resolver ─────────────────────────────────────────────────
$sql_pend = "SELECT sr.id, sr.descripcion, sr.monto_total, sr.estado AS ESTADO_SOL,
                    u.nombre AS SOLICITANTE, sr.fecha_solicitud
             FROM ciclo_solicitud_recurso sr
             JOIN ciclo_usuario u ON sr.id_usuario_solicita = u.id
             WHERE sr.id_periodo = '$periodo_id' AND sr.estado IN ('pendiente', 'aprobada')
             ORDER BY sr.estado, sr.fecha_solicitud;";
$res_pend = sql_do_query($sql_pend, $db, $usu);

$pendientes = [];
while ($rp = sql_do_array($res_pend)) $pendientes[] = $rp;
$hay_pendientes = !empty($pendientes);
?>

<?php echo generarTituloSeccion('Validar Cierre — Período ' . $anio, 'bi bi-clipboard-check'); ?>

<?php echo generarWizardPasos(2, 4, ['Iniciar', 'Validar', 'Consolidar', 'Cerrar']); ?>

<?php if ($hay_pendientes): ?>

<div class="alert alert-danger mb-4">
    <h6 class="alert-heading"><i class="bi bi-exclamation-octagon me-2"></i>Solicitudes sin resolver</h6>
    <p class="mb-0">Existen <strong><?php echo count($pendientes); ?></strong> solicitud(es) que deben resolverse antes de continuar con la consolidación.</p>
</div>

<div class="table-responsive">
    <table class="table table-hover table-pade">
        <?php echo generarTablaHeader(['Nro', 'Solicitante', 'Descripción', 'Monto', 'Estado', 'Fecha', 'Ir a resolver']); ?>
        <tbody>
<?php
$n = 0;
foreach ($pendientes as $p):
    $n++;
    $estado_badge = ($p['ESTADO_SOL'] === 'pendiente')
        ? '<span class="badge bg-warning text-dark">Pendiente</span>'
        : '<span class="badge bg-info">Aprobada</span>';
    $link_resolver = ($p['ESTADO_SOL'] === 'pendiente') ? '403' : '404';
?>
            <tr class="<?php echo claseFilaAlternada($n); ?>">
                <td class="text-center fw-bold">#<?php echo $p['ID']; ?></td>
                <td><?php echo htmlspecialchars($p['SOLICITANTE']); ?></td>
                <td><?php echo htmlspecialchars($p['DESCRIPCION'] ?? ''); ?></td>
                <td class="text-end">$ <?php echo number_format((float)$p['MONTO_TOTAL'], 2, ',', '.'); ?></td>
                <td class="text-center"><?php echo $estado_badge; ?></td>
                <td class="text-center small"><?php echo enmascararfecha($p['FECHA_SOLICITUD'] ?? ''); ?></td>
                <td class="text-center">
                    <a href="index.php?estado=<?php echo $link_resolver; ?>" class="btn btn-pade btn-sm">
                        <i class="bi bi-arrow-right me-1"></i>Resolver
                    </a>
                </td>
            </tr>
<?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php else: ?>

<div class="alert alert-success mb-4">
    <h6 class="alert-heading"><i class="bi bi-check-circle me-2"></i>Validación OK</h6>
    <p class="mb-0">No hay solicitudes pendientes ni aprobadas sin ejecutar. Puede continuar con la consolidación.</p>
</div>

<div class="card shadow-sm">
    <div class="card-body text-center">
        <p class="text-muted mb-3">Todas las solicitudes del período <?php echo $anio; ?> están resueltas.</p>
        <a href="index.php?estado=603" class="btn btn-success btn-lg">
            <i class="bi bi-arrow-right me-2"></i>Continuar a Consolidación (Paso 3)
        </a>
    </div>
</div>

<?php endif; ?>
