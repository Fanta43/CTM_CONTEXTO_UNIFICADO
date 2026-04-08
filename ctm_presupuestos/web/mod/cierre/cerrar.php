<?php
/**
 * ============================================================================
 * Cerrar Período
 * ============================================================================
 * ID menú: 604 | Tablas: ciclo_periodo_fiscal, ciclo_presupuesto, ciclo_partida
 * Paso 4 (final): marca período como cerrado definitivamente.
 * Acción irreversible. Datos quedan como referencia histórica para generar
 * el borrador del año siguiente.
 * ============================================================================
 */

$db  = '1';
$msg = '';

$rol_usuario = <?php
/**
 * ============================================================================
 * Cerrar Período
 * ============================================================================
 * ID menú: 604 | Tablas: ciclo_periodo_fiscal, ciclo_presupuesto, ciclo_partida
 * Paso 4 (final): marca período como cerrado definitivamente.
 * Acción irreversible. Datos quedan como referencia histórica para generar
 * el borrador del año siguiente.
 * ============================================================================
 */

$db  = '1';
$msg = '';

$rol_usuario = $_SESSION['rol'] ?? '';

// ── Verificar permisos ───────────────────────────────────────────────────────
if (!in_array($rol_usuario, ['admin', 'director'])) {
    echo generarAlertJS('No permitido', 'Solo administradores y directores pueden cerrar un período.', 'error', "index.php?estado=1");
    return;
}

// ── Obtener período en cierre ────────────────────────────────────────────────
$row_periodo = sql_do_array(sql_do_query(
    "SELECT * FROM ciclo_periodo_fiscal WHERE estado = 'en_cierre' LIMIT 1;", $db, $usu));

if (!$row_periodo) {
    echo generarAlertJS('Sin período', 'No hay un período en estado en_cierre. Complete los pasos previos.', 'warning', "index.php?estado=601");
    return;
}
$periodo_id = $row_periodo['ID'];
$anio = $row_periodo['ANIO'];

// ── Validar que no haya solicitudes pendientes ───────────────────────────────
$sql_pend = "SELECT COUNT(*) AS CANT FROM ciclo_solicitud_recurso
             WHERE id_periodo = '$periodo_id' AND estado IN ('pendiente', 'aprobada');";
$row_pend = sql_do_array(sql_do_query($sql_pend, $db, $usu));
if ((int)($row_pend['CANT'] ?? 0) > 0) {
    echo generarAlertJS('Paso incompleto', 'Aún hay solicitudes sin resolver. Complete el Paso 2.', 'warning', "index.php?estado=602");
    return;
}

// ── Resumen final ────────────────────────────────────────────────────────────
$sql_resumen = "SELECT
                  SUM(pa.monto_aprobado) AS TOTAL_APROBADO,
                  SUM(pa.monto_ejecutado) AS TOTAL_EJECUTADO,
                  ROUND(SUM(pa.monto_ejecutado) / NULLIF(SUM(pa.monto_aprobado), 0) * 100, 2) AS PCT_EJECUCION
                FROM ciclo_partida pa
                JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                WHERE pr.id_periodo = '$periodo_id';";
$row_res = sql_do_array(sql_do_query($sql_resumen, $db, $usu));

$total_aprobado = (float)($row_res['TOTAL_APROBADO'] ?? 0);
$total_ejecutado = (float)($row_res['TOTAL_EJECUTADO'] ?? 0);
$pct_ejecucion = (float)($row_res['PCT_EJECUCION'] ?? 0);

$sql_cant_sr = "SELECT COUNT(*) AS CANT FROM ciclo_solicitud_recurso
                WHERE id_periodo = '$periodo_id' AND estado = 'ejecutada';";
$row_sr = sql_do_array(sql_do_query($sql_cant_sr, $db, $usu));
$cant_sr = (int)($row_sr['CANT'] ?? 0);

$sql_cant_pres = "SELECT COUNT(*) AS CANT FROM ciclo_presupuesto WHERE id_periodo = '$periodo_id';";
$row_cp = sql_do_array(sql_do_query($sql_cant_pres, $db, $usu));
$cant_pres = (int)($row_cp['CANT'] ?? 0);

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST — CERRAR
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Enmascarar_post('accion_post') === 'cerrar') {
    $confirmacion = Enmascarar_post('confirmacion');
    if ($confirmacion !== 'SI') {
        $msg = generarAlertJS('Falta confirmación', 'Debe marcar la casilla de confirmación para cerrar el período.', 'error');
    } else {
        sql_do_query("UPDATE ciclo_periodo_fiscal SET estado = 'cerrado', auditoria_usu = '$usu' WHERE id = '$periodo_id';", $db, $usu);
        $msg = generarAlertJS('Período cerrado',
            "El período $anio fue cerrado definitivamente. Los datos quedan como referencia histórica. Puede generar el borrador del próximo año desde el módulo de Presupuestos.",
            'success', "index.php?estado=201");
    }
}

echo $msg;
?>

<?php echo generarTituloSeccion('Cerrar Período — ' . $anio, 'bi bi-lock-fill'); ?>

<?php echo generarWizardPasos(4, 4, ['Iniciar', 'Validar', 'Consolidar', 'Cerrar']); ?>

<!-- RESUMEN FINAL -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-center shadow-sm border-primary">
            <div class="card-body py-3">
                <small class="text-muted">Total Aprobado</small>
                <div class="fw-bold text-primary fs-4">$ <?php echo number_format($total_aprobado, 2, ',', '.'); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm border-success">
            <div class="card-body py-3">
                <small class="text-muted">Total Ejecutado</small>
                <div class="fw-bold text-success fs-4">$ <?php echo number_format($total_ejecutado, 2, ',', '.'); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body py-3">
                <small class="text-muted">% Ejecución Global</small>
                <?php
                $pct_class = 'text-success';
                if ($pct_ejecucion >= 95) $pct_class = 'text-danger';
                elseif ($pct_ejecucion >= 80) $pct_class = 'text-warning';
                ?>
                <div class="fw-bold fs-4 <?php echo $pct_class; ?>"><?php echo number_format($pct_ejecucion, 1); ?>%</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body py-3">
                <small class="text-muted">Solicitudes Ejecutadas</small>
                <div class="fw-bold fs-4"><?php echo $cant_sr; ?></div>
                <small class="text-muted"><?php echo $cant_pres; ?> presupuesto(s)</small>
            </div>
        </div>
    </div>
</div>

<!-- ADVERTENCIA -->
<div class="alert alert-danger mb-4">
    <h6 class="alert-heading"><i class="bi bi-exclamation-octagon me-2"></i>Acción Irreversible</h6>
    <p class="mb-0">Al cerrar el período <strong><?php echo $anio; ?></strong>, los datos quedarán como referencia histórica.
    <strong>No se podrá reabrir.</strong> Los presupuestos aprobados servirán como base para generar el borrador del próximo año.</p>
</div>

<!-- FORMULARIO DE CIERRE -->
<div class="card shadow-sm">
    <div class="card-header bg-danger text-white">
        <h6 class="mb-0"><i class="bi bi-lock-fill me-2"></i>Confirmar Cierre Definitivo</h6>
    </div>
    <div class="card-body text-center">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <input type="hidden" name="accion_post" value="cerrar">

            <div class="form-check d-inline-flex align-items-center gap-2 mb-4">
                <input class="form-check-input" type="checkbox" id="chk_confirmar"
                       onchange="document.getElementById('btn_cerrar').disabled = !this.checked;">
                <input type="hidden" name="confirmacion" id="confirmacion_val" value="NO">
                <label class="form-check-label fw-semibold" for="chk_confirmar">
                    Confirmo que los datos consolidados son correctos y deseo cerrar el período <?php echo $anio; ?> definitivamente.
                </label>
            </div>

            <div class="d-flex justify-content-between">
                <a href="index.php?estado=603" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Volver a Consolidación
                </a>
                <button type="submit" class="btn btn-danger btn-lg" id="btn_cerrar" disabled
                        onclick="document.getElementById('confirmacion_val').value='SI';">
                    <i class="bi bi-lock-fill me-2"></i>Cerrar Período <?php echo $anio; ?> Definitivamente
                </button>
            </div>
        </form>
    </div>
</div>
SESSION['ciclo_rol_efectivo'] ?? '';

// ── Verificar permisos ───────────────────────────────────────────────────────
if (!in_array($rol_usuario, ['admin', 'director'])) {
    echo generarAlertJS('No permitido', 'Solo administradores y directores pueden cerrar un período.', 'error', "index.php?estado=1");
    return;
}

// ── Obtener período en cierre ────────────────────────────────────────────────
$row_periodo = sql_do_array(sql_do_query(
    "SELECT * FROM ciclo_periodo_fiscal WHERE estado = 'en_cierre' LIMIT 1;", $db, $usu));

if (!$row_periodo) {
    echo generarAlertJS('Sin período', 'No hay un período en estado en_cierre. Complete los pasos previos.', 'warning', "index.php?estado=601");
    return;
}
$periodo_id = $row_periodo['ID'];
$anio = $row_periodo['ANIO'];

// ── Validar que no haya solicitudes pendientes ───────────────────────────────
$sql_pend = "SELECT COUNT(*) AS CANT FROM ciclo_solicitud_recurso
             WHERE id_periodo = '$periodo_id' AND estado IN ('pendiente', 'aprobada');";
$row_pend = sql_do_array(sql_do_query($sql_pend, $db, $usu));
if ((int)($row_pend['CANT'] ?? 0) > 0) {
    echo generarAlertJS('Paso incompleto', 'Aún hay solicitudes sin resolver. Complete el Paso 2.', 'warning', "index.php?estado=602");
    return;
}

// ── Resumen final ────────────────────────────────────────────────────────────
$sql_resumen = "SELECT
                  SUM(pa.monto_aprobado) AS TOTAL_APROBADO,
                  SUM(pa.monto_ejecutado) AS TOTAL_EJECUTADO,
                  ROUND(SUM(pa.monto_ejecutado) / NULLIF(SUM(pa.monto_aprobado), 0) * 100, 2) AS PCT_EJECUCION
                FROM ciclo_partida pa
                JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                WHERE pr.id_periodo = '$periodo_id';";
$row_res = sql_do_array(sql_do_query($sql_resumen, $db, $usu));

$total_aprobado = (float)($row_res['TOTAL_APROBADO'] ?? 0);
$total_ejecutado = (float)($row_res['TOTAL_EJECUTADO'] ?? 0);
$pct_ejecucion = (float)($row_res['PCT_EJECUCION'] ?? 0);

$sql_cant_sr = "SELECT COUNT(*) AS CANT FROM ciclo_solicitud_recurso
                WHERE id_periodo = '$periodo_id' AND estado = 'ejecutada';";
$row_sr = sql_do_array(sql_do_query($sql_cant_sr, $db, $usu));
$cant_sr = (int)($row_sr['CANT'] ?? 0);

$sql_cant_pres = "SELECT COUNT(*) AS CANT FROM ciclo_presupuesto WHERE id_periodo = '$periodo_id';";
$row_cp = sql_do_array(sql_do_query($sql_cant_pres, $db, $usu));
$cant_pres = (int)($row_cp['CANT'] ?? 0);

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST — CERRAR
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Enmascarar_post('accion_post') === 'cerrar') {
    $confirmacion = Enmascarar_post('confirmacion');
    if ($confirmacion !== 'SI') {
        $msg = generarAlertJS('Falta confirmación', 'Debe marcar la casilla de confirmación para cerrar el período.', 'error');
    } else {
        sql_do_query("UPDATE ciclo_periodo_fiscal SET estado = 'cerrado', auditoria_usu = '$usu' WHERE id = '$periodo_id';", $db, $usu);
        $msg = generarAlertJS('Período cerrado',
            "El período $anio fue cerrado definitivamente. Los datos quedan como referencia histórica. Puede generar el borrador del próximo año desde el módulo de Presupuestos.",
            'success', "index.php?estado=201");
    }
}

echo $msg;
?>

<?php echo generarTituloSeccion('Cerrar Período — ' . $anio, 'bi bi-lock-fill'); ?>

<?php echo generarWizardPasos(4, 4, ['Iniciar', 'Validar', 'Consolidar', 'Cerrar']); ?>

<!-- RESUMEN FINAL -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-center shadow-sm border-primary">
            <div class="card-body py-3">
                <small class="text-muted">Total Aprobado</small>
                <div class="fw-bold text-primary fs-4">$ <?php echo number_format($total_aprobado, 2, ',', '.'); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm border-success">
            <div class="card-body py-3">
                <small class="text-muted">Total Ejecutado</small>
                <div class="fw-bold text-success fs-4">$ <?php echo number_format($total_ejecutado, 2, ',', '.'); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body py-3">
                <small class="text-muted">% Ejecución Global</small>
                <?php
                $pct_class = 'text-success';
                if ($pct_ejecucion >= 95) $pct_class = 'text-danger';
                elseif ($pct_ejecucion >= 80) $pct_class = 'text-warning';
                ?>
                <div class="fw-bold fs-4 <?php echo $pct_class; ?>"><?php echo number_format($pct_ejecucion, 1); ?>%</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body py-3">
                <small class="text-muted">Solicitudes Ejecutadas</small>
                <div class="fw-bold fs-4"><?php echo $cant_sr; ?></div>
                <small class="text-muted"><?php echo $cant_pres; ?> presupuesto(s)</small>
            </div>
        </div>
    </div>
</div>

<!-- ADVERTENCIA -->
<div class="alert alert-danger mb-4">
    <h6 class="alert-heading"><i class="bi bi-exclamation-octagon me-2"></i>Acción Irreversible</h6>
    <p class="mb-0">Al cerrar el período <strong><?php echo $anio; ?></strong>, los datos quedarán como referencia histórica.
    <strong>No se podrá reabrir.</strong> Los presupuestos aprobados servirán como base para generar el borrador del próximo año.</p>
</div>

<!-- FORMULARIO DE CIERRE -->
<div class="card shadow-sm">
    <div class="card-header bg-danger text-white">
        <h6 class="mb-0"><i class="bi bi-lock-fill me-2"></i>Confirmar Cierre Definitivo</h6>
    </div>
    <div class="card-body text-center">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <input type="hidden" name="accion_post" value="cerrar">

            <div class="form-check d-inline-flex align-items-center gap-2 mb-4">
                <input class="form-check-input" type="checkbox" id="chk_confirmar"
                       onchange="document.getElementById('btn_cerrar').disabled = !this.checked;">
                <input type="hidden" name="confirmacion" id="confirmacion_val" value="NO">
                <label class="form-check-label fw-semibold" for="chk_confirmar">
                    Confirmo que los datos consolidados son correctos y deseo cerrar el período <?php echo $anio; ?> definitivamente.
                </label>
            </div>

            <div class="d-flex justify-content-between">
                <a href="index.php?estado=603" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Volver a Consolidación
                </a>
                <button type="submit" class="btn btn-danger btn-lg" id="btn_cerrar" disabled
                        onclick="document.getElementById('confirmacion_val').value='SI';">
                    <i class="bi bi-lock-fill me-2"></i>Cerrar Período <?php echo $anio; ?> Definitivamente
                </button>
            </div>
        </form>
    </div>
</div>
SESSION['ciclo_rol_efectivo'] ?? '';

// ── Verificar permisos ───────────────────────────────────────────────────────
if (!in_array($rol_usuario, ['admin', 'director'])) {
    echo generarAlertJS('No permitido', 'Solo administradores y directores pueden cerrar un período.', 'error', "index.php?estado=1");
    return;
}

// ── Obtener período en cierre ────────────────────────────────────────────────
$row_periodo = sql_do_array(sql_do_query(
    "SELECT * FROM ciclo_periodo_fiscal WHERE estado = 'en_cierre' LIMIT 1;", $db, $usu));

if (!$row_periodo) {
    echo generarAlertJS('Sin período', 'No hay un período en estado en_cierre. Complete los pasos previos.', 'warning', "index.php?estado=601");
    return;
}
$periodo_id = $row_periodo['ID'];
$anio = $row_periodo['ANIO'];

// ── Validar que no haya solicitudes pendientes ───────────────────────────────
$sql_pend = "SELECT COUNT(*) AS CANT FROM ciclo_solicitud_recurso
             WHERE id_periodo = '$periodo_id' AND estado IN ('pendiente', 'aprobada');";
$row_pend = sql_do_array(sql_do_query($sql_pend, $db, $usu));
if ((int)($row_pend['CANT'] ?? 0) > 0) {
    echo generarAlertJS('Paso incompleto', 'Aún hay solicitudes sin resolver. Complete el Paso 2.', 'warning', "index.php?estado=602");
    return;
}

// ── Resumen final ────────────────────────────────────────────────────────────
$sql_resumen = "SELECT
                  SUM(pa.monto_aprobado) AS TOTAL_APROBADO,
                  SUM(pa.monto_ejecutado) AS TOTAL_EJECUTADO,
                  ROUND(SUM(pa.monto_ejecutado) / NULLIF(SUM(pa.monto_aprobado), 0) * 100, 2) AS PCT_EJECUCION
                FROM ciclo_partida pa
                JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                WHERE pr.id_periodo = '$periodo_id';";
$row_res = sql_do_array(sql_do_query($sql_resumen, $db, $usu));

$total_aprobado = (float)($row_res['TOTAL_APROBADO'] ?? 0);
$total_ejecutado = (float)($row_res['TOTAL_EJECUTADO'] ?? 0);
$pct_ejecucion = (float)($row_res['PCT_EJECUCION'] ?? 0);

$sql_cant_sr = "SELECT COUNT(*) AS CANT FROM ciclo_solicitud_recurso
                WHERE id_periodo = '$periodo_id' AND estado = 'ejecutada';";
$row_sr = sql_do_array(sql_do_query($sql_cant_sr, $db, $usu));
$cant_sr = (int)($row_sr['CANT'] ?? 0);

$sql_cant_pres = "SELECT COUNT(*) AS CANT FROM ciclo_presupuesto WHERE id_periodo = '$periodo_id';";
$row_cp = sql_do_array(sql_do_query($sql_cant_pres, $db, $usu));
$cant_pres = (int)($row_cp['CANT'] ?? 0);

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST — CERRAR
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Enmascarar_post('accion_post') === 'cerrar') {
    $confirmacion = Enmascarar_post('confirmacion');
    if ($confirmacion !== 'SI') {
        $msg = generarAlertJS('Falta confirmación', 'Debe marcar la casilla de confirmación para cerrar el período.', 'error');
    } else {
        sql_do_query("UPDATE ciclo_periodo_fiscal SET estado = 'cerrado', auditoria_usu = '$usu' WHERE id = '$periodo_id';", $db, $usu);
        $msg = generarAlertJS('Período cerrado',
            "El período $anio fue cerrado definitivamente. Los datos quedan como referencia histórica. Puede generar el borrador del próximo año desde el módulo de Presupuestos.",
            'success', "index.php?estado=201");
    }
}

echo $msg;
?>

<?php echo generarTituloSeccion('Cerrar Período — ' . $anio, 'bi bi-lock-fill'); ?>

<?php echo generarWizardPasos(4, 4, ['Iniciar', 'Validar', 'Consolidar', 'Cerrar']); ?>

<!-- RESUMEN FINAL -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-center shadow-sm border-primary">
            <div class="card-body py-3">
                <small class="text-muted">Total Aprobado</small>
                <div class="fw-bold text-primary fs-4">$ <?php echo number_format($total_aprobado, 2, ',', '.'); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm border-success">
            <div class="card-body py-3">
                <small class="text-muted">Total Ejecutado</small>
                <div class="fw-bold text-success fs-4">$ <?php echo number_format($total_ejecutado, 2, ',', '.'); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body py-3">
                <small class="text-muted">% Ejecución Global</small>
                <?php
                $pct_class = 'text-success';
                if ($pct_ejecucion >= 95) $pct_class = 'text-danger';
                elseif ($pct_ejecucion >= 80) $pct_class = 'text-warning';
                ?>
                <div class="fw-bold fs-4 <?php echo $pct_class; ?>"><?php echo number_format($pct_ejecucion, 1); ?>%</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body py-3">
                <small class="text-muted">Solicitudes Ejecutadas</small>
                <div class="fw-bold fs-4"><?php echo $cant_sr; ?></div>
                <small class="text-muted"><?php echo $cant_pres; ?> presupuesto(s)</small>
            </div>
        </div>
    </div>
</div>

<!-- ADVERTENCIA -->
<div class="alert alert-danger mb-4">
    <h6 class="alert-heading"><i class="bi bi-exclamation-octagon me-2"></i>Acción Irreversible</h6>
    <p class="mb-0">Al cerrar el período <strong><?php echo $anio; ?></strong>, los datos quedarán como referencia histórica.
    <strong>No se podrá reabrir.</strong> Los presupuestos aprobados servirán como base para generar el borrador del próximo año.</p>
</div>

<!-- FORMULARIO DE CIERRE -->
<div class="card shadow-sm">
    <div class="card-header bg-danger text-white">
        <h6 class="mb-0"><i class="bi bi-lock-fill me-2"></i>Confirmar Cierre Definitivo</h6>
    </div>
    <div class="card-body text-center">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <input type="hidden" name="accion_post" value="cerrar">

            <div class="form-check d-inline-flex align-items-center gap-2 mb-4">
                <input class="form-check-input" type="checkbox" id="chk_confirmar"
                       onchange="document.getElementById('btn_cerrar').disabled = !this.checked;">
                <input type="hidden" name="confirmacion" id="confirmacion_val" value="NO">
                <label class="form-check-label fw-semibold" for="chk_confirmar">
                    Confirmo que los datos consolidados son correctos y deseo cerrar el período <?php echo $anio; ?> definitivamente.
                </label>
            </div>

            <div class="d-flex justify-content-between">
                <a href="index.php?estado=603" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Volver a Consolidación
                </a>
                <button type="submit" class="btn btn-danger btn-lg" id="btn_cerrar" disabled
                        onclick="document.getElementById('confirmacion_val').value='SI';">
                    <i class="bi bi-lock-fill me-2"></i>Cerrar Período <?php echo $anio; ?> Definitivamente
                </button>
            </div>
        </form>
    </div>
</div>
SESSION['ciclo_rol_efectivo'] ?? '';

// ── Verificar permisos ───────────────────────────────────────────────────────
if (!in_array($rol_usuario, ['admin', 'director'])) {
    echo generarAlertJS('No permitido', 'Solo administradores y directores pueden cerrar un período.', 'error', "index.php?estado=1");
    return;
}

// ── Obtener período en cierre ────────────────────────────────────────────────
$row_periodo = sql_do_array(sql_do_query(
    "SELECT * FROM ciclo_periodo_fiscal WHERE estado = 'en_cierre' LIMIT 1;", $db, $usu));

if (!$row_periodo) {
    echo generarAlertJS('Sin período', 'No hay un período en estado en_cierre. Complete los pasos previos.', 'warning', "index.php?estado=601");
    return;
}
$periodo_id = $row_periodo['ID'];
$anio = $row_periodo['ANIO'];

// ── Validar que no haya solicitudes pendientes ───────────────────────────────
$sql_pend = "SELECT COUNT(*) AS CANT FROM ciclo_solicitud_recurso
             WHERE id_periodo = '$periodo_id' AND estado IN ('pendiente', 'aprobada');";
$row_pend = sql_do_array(sql_do_query($sql_pend, $db, $usu));
if ((int)($row_pend['CANT'] ?? 0) > 0) {
    echo generarAlertJS('Paso incompleto', 'Aún hay solicitudes sin resolver. Complete el Paso 2.', 'warning', "index.php?estado=602");
    return;
}

// ── Resumen final ────────────────────────────────────────────────────────────
$sql_resumen = "SELECT
                  SUM(pa.monto_aprobado) AS TOTAL_APROBADO,
                  SUM(pa.monto_ejecutado) AS TOTAL_EJECUTADO,
                  ROUND(SUM(pa.monto_ejecutado) / NULLIF(SUM(pa.monto_aprobado), 0) * 100, 2) AS PCT_EJECUCION
                FROM ciclo_partida pa
                JOIN ciclo_presupuesto pr ON pa.id_presupuesto = pr.id
                WHERE pr.id_periodo = '$periodo_id';";
$row_res = sql_do_array(sql_do_query($sql_resumen, $db, $usu));

$total_aprobado = (float)($row_res['TOTAL_APROBADO'] ?? 0);
$total_ejecutado = (float)($row_res['TOTAL_EJECUTADO'] ?? 0);
$pct_ejecucion = (float)($row_res['PCT_EJECUCION'] ?? 0);

$sql_cant_sr = "SELECT COUNT(*) AS CANT FROM ciclo_solicitud_recurso
                WHERE id_periodo = '$periodo_id' AND estado = 'ejecutada';";
$row_sr = sql_do_array(sql_do_query($sql_cant_sr, $db, $usu));
$cant_sr = (int)($row_sr['CANT'] ?? 0);

$sql_cant_pres = "SELECT COUNT(*) AS CANT FROM ciclo_presupuesto WHERE id_periodo = '$periodo_id';";
$row_cp = sql_do_array(sql_do_query($sql_cant_pres, $db, $usu));
$cant_pres = (int)($row_cp['CANT'] ?? 0);

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST — CERRAR
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Enmascarar_post('accion_post') === 'cerrar') {
    $confirmacion = Enmascarar_post('confirmacion');
    if ($confirmacion !== 'SI') {
        $msg = generarAlertJS('Falta confirmación', 'Debe marcar la casilla de confirmación para cerrar el período.', 'error');
    } else {
        sql_do_query("UPDATE ciclo_periodo_fiscal SET estado = 'cerrado', auditoria_usu = '$usu' WHERE id = '$periodo_id';", $db, $usu);
        $msg = generarAlertJS('Período cerrado',
            "El período $anio fue cerrado definitivamente. Los datos quedan como referencia histórica. Puede generar el borrador del próximo año desde el módulo de Presupuestos.",
            'success', "index.php?estado=201");
    }
}

echo $msg;
?>

<?php echo generarTituloSeccion('Cerrar Período — ' . $anio, 'bi bi-lock-fill'); ?>

<?php echo generarWizardPasos(4, 4, ['Iniciar', 'Validar', 'Consolidar', 'Cerrar']); ?>

<!-- RESUMEN FINAL -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-center shadow-sm border-primary">
            <div class="card-body py-3">
                <small class="text-muted">Total Aprobado</small>
                <div class="fw-bold text-primary fs-4">$ <?php echo number_format($total_aprobado, 2, ',', '.'); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm border-success">
            <div class="card-body py-3">
                <small class="text-muted">Total Ejecutado</small>
                <div class="fw-bold text-success fs-4">$ <?php echo number_format($total_ejecutado, 2, ',', '.'); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body py-3">
                <small class="text-muted">% Ejecución Global</small>
                <?php
                $pct_class = 'text-success';
                if ($pct_ejecucion >= 95) $pct_class = 'text-danger';
                elseif ($pct_ejecucion >= 80) $pct_class = 'text-warning';
                ?>
                <div class="fw-bold fs-4 <?php echo $pct_class; ?>"><?php echo number_format($pct_ejecucion, 1); ?>%</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body py-3">
                <small class="text-muted">Solicitudes Ejecutadas</small>
                <div class="fw-bold fs-4"><?php echo $cant_sr; ?></div>
                <small class="text-muted"><?php echo $cant_pres; ?> presupuesto(s)</small>
            </div>
        </div>
    </div>
</div>

<!-- ADVERTENCIA -->
<div class="alert alert-danger mb-4">
    <h6 class="alert-heading"><i class="bi bi-exclamation-octagon me-2"></i>Acción Irreversible</h6>
    <p class="mb-0">Al cerrar el período <strong><?php echo $anio; ?></strong>, los datos quedarán como referencia histórica.
    <strong>No se podrá reabrir.</strong> Los presupuestos aprobados servirán como base para generar el borrador del próximo año.</p>
</div>

<!-- FORMULARIO DE CIERRE -->
<div class="card shadow-sm">
    <div class="card-header bg-danger text-white">
        <h6 class="mb-0"><i class="bi bi-lock-fill me-2"></i>Confirmar Cierre Definitivo</h6>
    </div>
    <div class="card-body text-center">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>">
            <input type="hidden" name="accion_post" value="cerrar">

            <div class="form-check d-inline-flex align-items-center gap-2 mb-4">
                <input class="form-check-input" type="checkbox" id="chk_confirmar"
                       onchange="document.getElementById('btn_cerrar').disabled = !this.checked;">
                <input type="hidden" name="confirmacion" id="confirmacion_val" value="NO">
                <label class="form-check-label fw-semibold" for="chk_confirmar">
                    Confirmo que los datos consolidados son correctos y deseo cerrar el período <?php echo $anio; ?> definitivamente.
                </label>
            </div>

            <div class="d-flex justify-content-between">
                <a href="index.php?estado=603" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Volver a Consolidación
                </a>
                <button type="submit" class="btn btn-danger btn-lg" id="btn_cerrar" disabled
                        onclick="document.getElementById('confirmacion_val').value='SI';">
                    <i class="bi bi-lock-fill me-2"></i>Cerrar Período <?php echo $anio; ?> Definitivamente
                </button>
            </div>
        </form>
    </div>
</div>
