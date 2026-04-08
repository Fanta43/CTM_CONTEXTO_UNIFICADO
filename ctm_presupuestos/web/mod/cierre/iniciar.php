<?php
/**
 * ============================================================================
 * Iniciar Cierre de Período
 * ============================================================================
 * ID menú: 601 | Tablas: ciclo_periodo_fiscal, ciclo_solicitud_recurso
 * Paso 1 del cierre: cambia período a 'en_cierre', bloqueando nuevas solicitudes.
 * Solo admin/director puede ejecutar.
 * ============================================================================
 */

$db  = '1';
$msg = '';

$rol_usuario = <?php
/**
 * ============================================================================
 * Iniciar Cierre de Período
 * ============================================================================
 * ID menú: 601 | Tablas: ciclo_periodo_fiscal, ciclo_solicitud_recurso
 * Paso 1 del cierre: cambia período a 'en_cierre', bloqueando nuevas solicitudes.
 * Solo admin/director puede ejecutar.
 * ============================================================================
 */

$db  = '1';
$msg = '';

$rol_usuario = $_SESSION['rol'] ?? '';

// ── Verificar permisos ───────────────────────────────────────────────────────
if (!in_array($rol_usuario, ['admin', 'director'])) {
    echo generarAlertJS('No permitido', 'Solo administradores y directores pueden iniciar el cierre.', 'error', "index.php?estado=1");
    return;
}

// ── Obtener período abierto ──────────────────────────────────────────────────
$row_periodo = sql_do_array(sql_do_query(
    "SELECT * FROM ciclo_periodo_fiscal WHERE estado = 'abierto' LIMIT 1;", $db, $usu));

if (!$row_periodo) {
    echo generarAlertJS('Sin período', 'No hay un período fiscal en estado abierto para iniciar el cierre.', 'info', "index.php?estado=1");
    return;
}
$periodo_id = $row_periodo['ID'];
$anio = $row_periodo['ANIO'];

// ── Solicitudes abiertas ─────────────────────────────────────────────────────
$sql_sol = "SELECT estado AS ESTADO_SOL, COUNT(*) AS CANT
            FROM ciclo_solicitud_recurso
            WHERE id_periodo = '$periodo_id' AND estado IN ('pendiente', 'aprobada')
            GROUP BY estado;";
$res_sol = sql_do_query($sql_sol, $db, $usu);
$sol_pendientes = 0;
$sol_aprobadas  = 0;
while ($rs = sql_do_array($res_sol)) {
    if ($rs['ESTADO_SOL'] === 'pendiente') $sol_pendientes = (int)$rs['CANT'];
    if ($rs['ESTADO_SOL'] === 'aprobada')  $sol_aprobadas  = (int)$rs['CANT'];
}
$total_abiertas = $sol_pendientes + $sol_aprobadas;

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Enmascarar_post('accion_post') === 'iniciar') {
    sql_do_query("UPDATE ciclo_periodo_fiscal SET estado = 'en_cierre', auditoria_usu = '$usu' WHERE id = '$periodo_id';", $db, $usu);
    $msg = generarAlertJS('Cierre iniciado', "El período $anio fue cambiado a estado 'en_cierre'. Ya no se pueden crear nuevas solicitudes.", 'success', "index.php?estado=602");
}

echo $msg;
?>

<?php echo generarTituloSeccion('Iniciar Cierre — Período ' . $anio, 'bi bi-lock'); ?>

<?php echo generarWizardPasos(1, 4, ['Iniciar', 'Validar', 'Consolidar', 'Cerrar']); ?>

<!-- PANEL INFORMATIVO -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-warning text-dark">
        <h6 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Atención</h6>
    </div>
    <div class="card-body">
        <p class="mb-2">Al iniciar el cierre del período <strong><?php echo $anio; ?></strong>:</p>
        <ul class="mb-3">
            <li><strong>No se podrán crear nuevas solicitudes</strong> de recurso para este período.</li>
            <li>Las solicitudes pendientes y aprobadas existentes deben resolverse antes de cerrar definitivamente.</li>
            <li>El período pasará al estado <span class="badge bg-warning text-dark">en_cierre</span>.</li>
        </ul>
    </div>
</div>

<!-- RESUMEN DE SOLICITUDES ABIERTAS -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center shadow-sm <?php echo ($sol_pendientes > 0) ? 'border-warning' : 'border-success'; ?>">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold <?php echo ($sol_pendientes > 0) ? 'text-warning' : 'text-success'; ?>">
                    <?php echo $sol_pendientes; ?>
                </div>
                <small class="text-muted">Solicitudes Pendientes</small>
                <?php if ($sol_pendientes > 0): ?>
                <div class="mt-2"><a href="index.php?estado=403" class="btn btn-outline-warning btn-sm">Ir a aprobar</a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm <?php echo ($sol_aprobadas > 0) ? 'border-info' : 'border-success'; ?>">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold <?php echo ($sol_aprobadas > 0) ? 'text-info' : 'text-success'; ?>">
                    <?php echo $sol_aprobadas; ?>
                </div>
                <small class="text-muted">Solicitudes Aprobadas sin Ejecutar</small>
                <?php if ($sol_aprobadas > 0): ?>
                <div class="mt-2"><a href="index.php?estado=404" class="btn btn-outline-info btn-sm">Ir a ejecutar</a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm border-primary">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-primary"><?php echo $anio; ?></div>
                <small class="text-muted">Período Actual</small>
                <div class="mt-2"><span class="badge bg-success fs-6">Abierto</span></div>
            </div>
        </div>
    </div>
</div>

<?php if ($total_abiertas > 0): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    Hay <strong><?php echo $total_abiertas; ?></strong> solicitud(es) sin resolver.
    Puede iniciar el cierre igualmente, pero deberá resolverlas antes de cerrar el período definitivamente (Paso 2).
</div>
<?php endif; ?>

<!-- BOTÓN INICIAR CIERRE -->
<div class="card shadow-sm">
    <div class="card-body text-center">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <input type="hidden" name="accion_post" value="iniciar">
            <p class="text-muted mb-3">¿Está seguro de iniciar el proceso de cierre del período <?php echo $anio; ?>?</p>
            <button type="submit" class="btn btn-warning btn-lg">
                <i class="bi bi-lock me-2"></i>Iniciar Cierre del Período <?php echo $anio; ?>
            </button>
        </form>
    </div>
</div>
SESSION['ciclo_rol_efectivo'] ?? '';

// ── Verificar permisos ───────────────────────────────────────────────────────
if (!in_array($rol_usuario, ['admin', 'director'])) {
    echo generarAlertJS('No permitido', 'Solo administradores y directores pueden iniciar el cierre.', 'error', "index.php?estado=1");
    return;
}

// ── Obtener período abierto ──────────────────────────────────────────────────
$row_periodo = sql_do_array(sql_do_query(
    "SELECT * FROM ciclo_periodo_fiscal WHERE estado = 'abierto' LIMIT 1;", $db, $usu));

if (!$row_periodo) {
    echo generarAlertJS('Sin período', 'No hay un período fiscal en estado abierto para iniciar el cierre.', 'info', "index.php?estado=1");
    return;
}
$periodo_id = $row_periodo['ID'];
$anio = $row_periodo['ANIO'];

// ── Solicitudes abiertas ─────────────────────────────────────────────────────
$sql_sol = "SELECT estado AS ESTADO_SOL, COUNT(*) AS CANT
            FROM ciclo_solicitud_recurso
            WHERE id_periodo = '$periodo_id' AND estado IN ('pendiente', 'aprobada')
            GROUP BY estado;";
$res_sol = sql_do_query($sql_sol, $db, $usu);
$sol_pendientes = 0;
$sol_aprobadas  = 0;
while ($rs = sql_do_array($res_sol)) {
    if ($rs['ESTADO_SOL'] === 'pendiente') $sol_pendientes = (int)$rs['CANT'];
    if ($rs['ESTADO_SOL'] === 'aprobada')  $sol_aprobadas  = (int)$rs['CANT'];
}
$total_abiertas = $sol_pendientes + $sol_aprobadas;

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Enmascarar_post('accion_post') === 'iniciar') {
    sql_do_query("UPDATE ciclo_periodo_fiscal SET estado = 'en_cierre', auditoria_usu = '$usu' WHERE id = '$periodo_id';", $db, $usu);
    $msg = generarAlertJS('Cierre iniciado', "El período $anio fue cambiado a estado 'en_cierre'. Ya no se pueden crear nuevas solicitudes.", 'success', "index.php?estado=602");
}

echo $msg;
?>

<?php echo generarTituloSeccion('Iniciar Cierre — Período ' . $anio, 'bi bi-lock'); ?>

<?php echo generarWizardPasos(1, 4, ['Iniciar', 'Validar', 'Consolidar', 'Cerrar']); ?>

<!-- PANEL INFORMATIVO -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-warning text-dark">
        <h6 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Atención</h6>
    </div>
    <div class="card-body">
        <p class="mb-2">Al iniciar el cierre del período <strong><?php echo $anio; ?></strong>:</p>
        <ul class="mb-3">
            <li><strong>No se podrán crear nuevas solicitudes</strong> de recurso para este período.</li>
            <li>Las solicitudes pendientes y aprobadas existentes deben resolverse antes de cerrar definitivamente.</li>
            <li>El período pasará al estado <span class="badge bg-warning text-dark">en_cierre</span>.</li>
        </ul>
    </div>
</div>

<!-- RESUMEN DE SOLICITUDES ABIERTAS -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center shadow-sm <?php echo ($sol_pendientes > 0) ? 'border-warning' : 'border-success'; ?>">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold <?php echo ($sol_pendientes > 0) ? 'text-warning' : 'text-success'; ?>">
                    <?php echo $sol_pendientes; ?>
                </div>
                <small class="text-muted">Solicitudes Pendientes</small>
                <?php if ($sol_pendientes > 0): ?>
                <div class="mt-2"><a href="index.php?estado=403" class="btn btn-outline-warning btn-sm">Ir a aprobar</a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm <?php echo ($sol_aprobadas > 0) ? 'border-info' : 'border-success'; ?>">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold <?php echo ($sol_aprobadas > 0) ? 'text-info' : 'text-success'; ?>">
                    <?php echo $sol_aprobadas; ?>
                </div>
                <small class="text-muted">Solicitudes Aprobadas sin Ejecutar</small>
                <?php if ($sol_aprobadas > 0): ?>
                <div class="mt-2"><a href="index.php?estado=404" class="btn btn-outline-info btn-sm">Ir a ejecutar</a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm border-primary">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-primary"><?php echo $anio; ?></div>
                <small class="text-muted">Período Actual</small>
                <div class="mt-2"><span class="badge bg-success fs-6">Abierto</span></div>
            </div>
        </div>
    </div>
</div>

<?php if ($total_abiertas > 0): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    Hay <strong><?php echo $total_abiertas; ?></strong> solicitud(es) sin resolver.
    Puede iniciar el cierre igualmente, pero deberá resolverlas antes de cerrar el período definitivamente (Paso 2).
</div>
<?php endif; ?>

<!-- BOTÓN INICIAR CIERRE -->
<div class="card shadow-sm">
    <div class="card-body text-center">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <input type="hidden" name="accion_post" value="iniciar">
            <p class="text-muted mb-3">¿Está seguro de iniciar el proceso de cierre del período <?php echo $anio; ?>?</p>
            <button type="submit" class="btn btn-warning btn-lg">
                <i class="bi bi-lock me-2"></i>Iniciar Cierre del Período <?php echo $anio; ?>
            </button>
        </form>
    </div>
</div>
SESSION['ciclo_rol_efectivo'] ?? '';

// ── Verificar permisos ───────────────────────────────────────────────────────
if (!in_array($rol_usuario, ['admin', 'director'])) {
    echo generarAlertJS('No permitido', 'Solo administradores y directores pueden iniciar el cierre.', 'error', "index.php?estado=1");
    return;
}

// ── Obtener período abierto ──────────────────────────────────────────────────
$row_periodo = sql_do_array(sql_do_query(
    "SELECT * FROM ciclo_periodo_fiscal WHERE estado = 'abierto' LIMIT 1;", $db, $usu));

if (!$row_periodo) {
    echo generarAlertJS('Sin período', 'No hay un período fiscal en estado abierto para iniciar el cierre.', 'info', "index.php?estado=1");
    return;
}
$periodo_id = $row_periodo['ID'];
$anio = $row_periodo['ANIO'];

// ── Solicitudes abiertas ─────────────────────────────────────────────────────
$sql_sol = "SELECT estado AS ESTADO_SOL, COUNT(*) AS CANT
            FROM ciclo_solicitud_recurso
            WHERE id_periodo = '$periodo_id' AND estado IN ('pendiente', 'aprobada')
            GROUP BY estado;";
$res_sol = sql_do_query($sql_sol, $db, $usu);
$sol_pendientes = 0;
$sol_aprobadas  = 0;
while ($rs = sql_do_array($res_sol)) {
    if ($rs['ESTADO_SOL'] === 'pendiente') $sol_pendientes = (int)$rs['CANT'];
    if ($rs['ESTADO_SOL'] === 'aprobada')  $sol_aprobadas  = (int)$rs['CANT'];
}
$total_abiertas = $sol_pendientes + $sol_aprobadas;

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Enmascarar_post('accion_post') === 'iniciar') {
    sql_do_query("UPDATE ciclo_periodo_fiscal SET estado = 'en_cierre', auditoria_usu = '$usu' WHERE id = '$periodo_id';", $db, $usu);
    $msg = generarAlertJS('Cierre iniciado', "El período $anio fue cambiado a estado 'en_cierre'. Ya no se pueden crear nuevas solicitudes.", 'success', "index.php?estado=602");
}

echo $msg;
?>

<?php echo generarTituloSeccion('Iniciar Cierre — Período ' . $anio, 'bi bi-lock'); ?>

<?php echo generarWizardPasos(1, 4, ['Iniciar', 'Validar', 'Consolidar', 'Cerrar']); ?>

<!-- PANEL INFORMATIVO -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-warning text-dark">
        <h6 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Atención</h6>
    </div>
    <div class="card-body">
        <p class="mb-2">Al iniciar el cierre del período <strong><?php echo $anio; ?></strong>:</p>
        <ul class="mb-3">
            <li><strong>No se podrán crear nuevas solicitudes</strong> de recurso para este período.</li>
            <li>Las solicitudes pendientes y aprobadas existentes deben resolverse antes de cerrar definitivamente.</li>
            <li>El período pasará al estado <span class="badge bg-warning text-dark">en_cierre</span>.</li>
        </ul>
    </div>
</div>

<!-- RESUMEN DE SOLICITUDES ABIERTAS -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center shadow-sm <?php echo ($sol_pendientes > 0) ? 'border-warning' : 'border-success'; ?>">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold <?php echo ($sol_pendientes > 0) ? 'text-warning' : 'text-success'; ?>">
                    <?php echo $sol_pendientes; ?>
                </div>
                <small class="text-muted">Solicitudes Pendientes</small>
                <?php if ($sol_pendientes > 0): ?>
                <div class="mt-2"><a href="index.php?estado=403" class="btn btn-outline-warning btn-sm">Ir a aprobar</a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm <?php echo ($sol_aprobadas > 0) ? 'border-info' : 'border-success'; ?>">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold <?php echo ($sol_aprobadas > 0) ? 'text-info' : 'text-success'; ?>">
                    <?php echo $sol_aprobadas; ?>
                </div>
                <small class="text-muted">Solicitudes Aprobadas sin Ejecutar</small>
                <?php if ($sol_aprobadas > 0): ?>
                <div class="mt-2"><a href="index.php?estado=404" class="btn btn-outline-info btn-sm">Ir a ejecutar</a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm border-primary">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-primary"><?php echo $anio; ?></div>
                <small class="text-muted">Período Actual</small>
                <div class="mt-2"><span class="badge bg-success fs-6">Abierto</span></div>
            </div>
        </div>
    </div>
</div>

<?php if ($total_abiertas > 0): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    Hay <strong><?php echo $total_abiertas; ?></strong> solicitud(es) sin resolver.
    Puede iniciar el cierre igualmente, pero deberá resolverlas antes de cerrar el período definitivamente (Paso 2).
</div>
<?php endif; ?>

<!-- BOTÓN INICIAR CIERRE -->
<div class="card shadow-sm">
    <div class="card-body text-center">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <input type="hidden" name="accion_post" value="iniciar">
            <p class="text-muted mb-3">¿Está seguro de iniciar el proceso de cierre del período <?php echo $anio; ?>?</p>
            <button type="submit" class="btn btn-warning btn-lg">
                <i class="bi bi-lock me-2"></i>Iniciar Cierre del Período <?php echo $anio; ?>
            </button>
        </form>
    </div>
</div>
SESSION['ciclo_rol_efectivo'] ?? '';

// ── Verificar permisos ───────────────────────────────────────────────────────
if (!in_array($rol_usuario, ['admin', 'director'])) {
    echo generarAlertJS('No permitido', 'Solo administradores y directores pueden iniciar el cierre.', 'error', "index.php?estado=1");
    return;
}

// ── Obtener período abierto ──────────────────────────────────────────────────
$row_periodo = sql_do_array(sql_do_query(
    "SELECT * FROM ciclo_periodo_fiscal WHERE estado = 'abierto' LIMIT 1;", $db, $usu));

if (!$row_periodo) {
    echo generarAlertJS('Sin período', 'No hay un período fiscal en estado abierto para iniciar el cierre.', 'info', "index.php?estado=1");
    return;
}
$periodo_id = $row_periodo['ID'];
$anio = $row_periodo['ANIO'];

// ── Solicitudes abiertas ─────────────────────────────────────────────────────
$sql_sol = "SELECT estado AS ESTADO_SOL, COUNT(*) AS CANT
            FROM ciclo_solicitud_recurso
            WHERE id_periodo = '$periodo_id' AND estado IN ('pendiente', 'aprobada')
            GROUP BY estado;";
$res_sol = sql_do_query($sql_sol, $db, $usu);
$sol_pendientes = 0;
$sol_aprobadas  = 0;
while ($rs = sql_do_array($res_sol)) {
    if ($rs['ESTADO_SOL'] === 'pendiente') $sol_pendientes = (int)$rs['CANT'];
    if ($rs['ESTADO_SOL'] === 'aprobada')  $sol_aprobadas  = (int)$rs['CANT'];
}
$total_abiertas = $sol_pendientes + $sol_aprobadas;

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Enmascarar_post('accion_post') === 'iniciar') {
    sql_do_query("UPDATE ciclo_periodo_fiscal SET estado = 'en_cierre', auditoria_usu = '$usu' WHERE id = '$periodo_id';", $db, $usu);
    $msg = generarAlertJS('Cierre iniciado', "El período $anio fue cambiado a estado 'en_cierre'. Ya no se pueden crear nuevas solicitudes.", 'success', "index.php?estado=602");
}

echo $msg;
?>

<?php echo generarTituloSeccion('Iniciar Cierre — Período ' . $anio, 'bi bi-lock'); ?>

<?php echo generarWizardPasos(1, 4, ['Iniciar', 'Validar', 'Consolidar', 'Cerrar']); ?>

<!-- PANEL INFORMATIVO -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-warning text-dark">
        <h6 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Atención</h6>
    </div>
    <div class="card-body">
        <p class="mb-2">Al iniciar el cierre del período <strong><?php echo $anio; ?></strong>:</p>
        <ul class="mb-3">
            <li><strong>No se podrán crear nuevas solicitudes</strong> de recurso para este período.</li>
            <li>Las solicitudes pendientes y aprobadas existentes deben resolverse antes de cerrar definitivamente.</li>
            <li>El período pasará al estado <span class="badge bg-warning text-dark">en_cierre</span>.</li>
        </ul>
    </div>
</div>

<!-- RESUMEN DE SOLICITUDES ABIERTAS -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center shadow-sm <?php echo ($sol_pendientes > 0) ? 'border-warning' : 'border-success'; ?>">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold <?php echo ($sol_pendientes > 0) ? 'text-warning' : 'text-success'; ?>">
                    <?php echo $sol_pendientes; ?>
                </div>
                <small class="text-muted">Solicitudes Pendientes</small>
                <?php if ($sol_pendientes > 0): ?>
                <div class="mt-2"><a href="index.php?estado=403" class="btn btn-outline-warning btn-sm">Ir a aprobar</a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm <?php echo ($sol_aprobadas > 0) ? 'border-info' : 'border-success'; ?>">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold <?php echo ($sol_aprobadas > 0) ? 'text-info' : 'text-success'; ?>">
                    <?php echo $sol_aprobadas; ?>
                </div>
                <small class="text-muted">Solicitudes Aprobadas sin Ejecutar</small>
                <?php if ($sol_aprobadas > 0): ?>
                <div class="mt-2"><a href="index.php?estado=404" class="btn btn-outline-info btn-sm">Ir a ejecutar</a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm border-primary">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-primary"><?php echo $anio; ?></div>
                <small class="text-muted">Período Actual</small>
                <div class="mt-2"><span class="badge bg-success fs-6">Abierto</span></div>
            </div>
        </div>
    </div>
</div>

<?php if ($total_abiertas > 0): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    Hay <strong><?php echo $total_abiertas; ?></strong> solicitud(es) sin resolver.
    Puede iniciar el cierre igualmente, pero deberá resolverlas antes de cerrar el período definitivamente (Paso 2).
</div>
<?php endif; ?>

<!-- BOTÓN INICIAR CIERRE -->
<div class="card shadow-sm">
    <div class="card-body text-center">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <input type="hidden" name="accion_post" value="iniciar">
            <p class="text-muted mb-3">¿Está seguro de iniciar el proceso de cierre del período <?php echo $anio; ?>?</p>
            <button type="submit" class="btn btn-warning btn-lg">
                <i class="bi bi-lock me-2"></i>Iniciar Cierre del Período <?php echo $anio; ?>
            </button>
        </form>
    </div>
</div>
