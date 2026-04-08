<?php
/**
 * ============================================================================
 * Aprobar / Devolver Presupuesto
 * ============================================================================
 * ID menú: 303 | Tablas: ciclo_presupuesto, ciclo_revision, ciclo_unidad_organizativa
 * Acciones finales del revisor: aprobar (siguiente nivel o final) o devolver a borrador.
 * Nivel máximo = 3 (dirección) → aprobado final.
 * ============================================================================
 */

$db  = '1';
$msg = '';
$pres_id = Enmascarar_get('id');

if (empty($pres_id)) {
    echo generarAlertJS('Error', 'No se especificó un presupuesto.', 'error', "index.php?estado=301");
    return;
}

// ── Cargar presupuesto ───────────────────────────────────────────────────────
$sql_pres = "SELECT pr.*, pf.anio, uo.nombre AS UNIDAD, uo.nivel_aprobacion AS NIVEL_UNIDAD
             FROM ciclo_presupuesto pr
             JOIN ciclo_periodo_fiscal pf ON pr.id_periodo = pf.id
             JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
             WHERE pr.id = '$pres_id';";
$row_pres = sql_do_array(sql_do_query($sql_pres, $db, $usu));

if (!$row_pres) {
    echo generarAlertJS('Error', 'Presupuesto no encontrado.', 'error', "index.php?estado=301");
    return;
}

if ($row_pres['ESTADO'] !== 'en_revision') {
    echo generarAlertJS('Error', 'Este presupuesto no está en estado de revisión.', 'warning', "index.php?estado=301");
    return;
}

// ── Nivel del revisor ────────────────────────────────────────────────────────
$rol_usuario = <?php
/**
 * ============================================================================
 * Aprobar / Devolver Presupuesto
 * ============================================================================
 * ID menú: 303 | Tablas: ciclo_presupuesto, ciclo_revision, ciclo_unidad_organizativa
 * Acciones finales del revisor: aprobar (siguiente nivel o final) o devolver a borrador.
 * Nivel máximo = 3 (dirección) → aprobado final.
 * ============================================================================
 */

$db  = '1';
$msg = '';
$pres_id = Enmascarar_get('id');

if (empty($pres_id)) {
    echo generarAlertJS('Error', 'No se especificó un presupuesto.', 'error', "index.php?estado=301");
    return;
}

// ── Cargar presupuesto ───────────────────────────────────────────────────────
$sql_pres = "SELECT pr.*, pf.anio, uo.nombre AS UNIDAD, uo.nivel_aprobacion AS NIVEL_UNIDAD
             FROM ciclo_presupuesto pr
             JOIN ciclo_periodo_fiscal pf ON pr.id_periodo = pf.id
             JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
             WHERE pr.id = '$pres_id';";
$row_pres = sql_do_array(sql_do_query($sql_pres, $db, $usu));

if (!$row_pres) {
    echo generarAlertJS('Error', 'Presupuesto no encontrado.', 'error', "index.php?estado=301");
    return;
}

if ($row_pres['ESTADO'] !== 'en_revision') {
    echo generarAlertJS('Error', 'Este presupuesto no está en estado de revisión.', 'warning', "index.php?estado=301");
    return;
}

// ── Nivel del revisor ────────────────────────────────────────────────────────
$rol_usuario = $_SESSION['rol'] ?? '';
$nivel_revisor = 1;
if ($rol_usuario === 'admin' || $rol_usuario === 'director') $nivel_revisor = 3;
elseif ($rol_usuario === 'gerente') $nivel_revisor = 2;

$nivel_maximo = 3; // Dirección

// ── Última revisión ──────────────────────────────────────────────────────────
$sql_ult = "SELECT r.*, u.nombre AS USUARIO_NOMBRE
            FROM ciclo_revision r
            LEFT JOIN ciclo_usuario u ON r.id_usuario = u.id
            WHERE r.id_presupuesto = '$pres_id'
            ORDER BY r.fecha DESC LIMIT 1;";
$row_ult = sql_do_array(sql_do_query($sql_ult, $db, $usu));

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion_post   = Enmascarar_post('accion_post');
    $observaciones = Enmascarar_post('observaciones');

    // ── APROBAR ──────────────────────────────────────────────────────────────
    if ($accion_post === 'aprobar') {
        // Determinar si es aprobación final o pasa al siguiente nivel
        $es_final = ($nivel_revisor >= $nivel_maximo);
        $nuevo_estado = $es_final ? 'aprobado' : 'en_revision';
        $accion_txt = $es_final ? 'Aprobación final' : 'Aprobado nivel ' . $nivel_revisor;

        // Registrar revisión
        $sql_rev = "INSERT INTO ciclo_revision (id_presupuesto, id_usuario, nivel, accion, observaciones, fecha)
                    VALUES ('$pres_id', '$usu', '$nivel_revisor', 'aprobar', '$observaciones', NOW());";
        sql_do_query($sql_rev, $db, $usu);

        // Actualizar presupuesto
        if ($es_final) {
            sql_do_query("UPDATE ciclo_presupuesto SET estado = 'aprobado', fecha_aprobacion = NOW(), auditoria_usu = '$usu' WHERE id = '$pres_id';", $db, $usu);
        } else {
            sql_do_query("UPDATE ciclo_presupuesto SET auditoria_usu = '$usu' WHERE id = '$pres_id';", $db, $usu);
        }

        $msg_txt = $es_final
            ? 'El presupuesto fue APROBADO definitivamente.'
            : 'Aprobado en nivel ' . $nivel_revisor . '. Pasa al siguiente nivel de revisión.';
        $msg = generarAlertJS('Aprobado', $msg_txt, 'success', "index.php?estado=301");
    }

    // ── DEVOLVER ─────────────────────────────────────────────────────────────
    if ($accion_post === 'devolver') {
        if (empty($observaciones)) {
            $msg = generarAlertJS('Error', 'Las observaciones son obligatorias al devolver un presupuesto.', 'error');
        } else {
            // Registrar revisión
            $sql_rev = "INSERT INTO ciclo_revision (id_presupuesto, id_usuario, nivel, accion, observaciones, fecha)
                        VALUES ('$pres_id', '$usu', '$nivel_revisor', 'devolver', '$observaciones', NOW());";
            sql_do_query($sql_rev, $db, $usu);

            // Volver a borrador
            sql_do_query("UPDATE ciclo_presupuesto SET estado = 'borrador', auditoria_usu = '$usu' WHERE id = '$pres_id';", $db, $usu);

            $msg = generarAlertJS('Devuelto', 'El presupuesto fue devuelto a estado borrador.', 'info', "index.php?estado=301");
        }
    }
}

echo $msg;

// ── Badge de estado ──────────────────────────────────────────────────────────
$estado_badge = '<span class="badge bg-warning text-dark fs-6">En Revisión</span>';
?>

<?php echo generarTituloSeccion('Aprobar / Devolver Presupuesto', 'bi bi-check2-square'); ?>

<!-- CABECERA -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-2">
                <small class="text-muted">Período</small>
                <div class="fw-bold fs-5"><?php echo htmlspecialchars($row_pres['ANIO']); ?></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Unidad</small>
                <div class="fw-bold"><?php echo htmlspecialchars($row_pres['UNIDAD']); ?></div>
            </div>
            <div class="col-md-2">
                <small class="text-muted">Estado</small>
                <div><?php echo $estado_badge; ?></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Monto Total</small>
                <div class="fw-bold text-primary fs-5">$ <?php echo number_format((float)$row_pres['MONTO_TOTAL'], 2, ',', '.'); ?></div>
            </div>
            <div class="col-md-2">
                <small class="text-muted">Su nivel</small>
                <div><span class="badge bg-primary fs-6">Nivel <?php echo $nivel_revisor; ?></span></div>
            </div>
        </div>
    </div>
</div>

<?php if ($row_ult): ?>
<div class="alert alert-secondary mb-4">
    <h6 class="alert-heading"><i class="bi bi-clock-history me-2"></i>Última acción registrada</h6>
    <p class="mb-1">
        <strong><?php echo htmlspecialchars($row_ult['USUARIO_NOMBRE'] ?? 'Usuario #' . $row_ult['ID_USUARIO']); ?></strong>
        — <?php echo htmlspecialchars(ucfirst($row_ult['ACCION'] ?? '')); ?>
        (Nivel <?php echo $row_ult['NIVEL']; ?>)
        — <?php echo enmascararfecha($row_ult['FECHA'] ?? ''); ?>
    </p>
    <?php if (!empty($row_ult['OBSERVACIONES'])): ?>
    <p class="mb-0 small fst-italic">"<?php echo htmlspecialchars($row_ult['OBSERVACIONES']); ?>"</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="mb-3">
    <a href="index.php?estado=301" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver a bandeja
    </a>
    <a href="index.php?estado=302&id=<?php echo $pres_id; ?>" class="btn btn-pade-alt btn-sm">
        <i class="bi bi-pencil-square me-1"></i>Revisar partidas
    </a>
    <a href="index.php?estado=304&id=<?php echo $pres_id; ?>" class="btn btn-outline-info btn-sm">
        <i class="bi bi-clock-history me-1"></i>Historial completo
    </a>
</div>

<!-- ACCIONES -->
<div class="row g-4">
    <!-- APROBAR -->
    <div class="col-md-6">
        <div class="card shadow-sm border-success">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="bi bi-check-circle me-2"></i>Aprobar</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    <?php if ($nivel_revisor >= $nivel_maximo): ?>
                        <strong>Aprobación FINAL.</strong> El presupuesto quedará en estado definitivo 'aprobado'.
                    <?php else: ?>
                        Aprueba en nivel <?php echo $nivel_revisor; ?> y pasa al siguiente nivel de revisión.
                    <?php endif; ?>
                </p>
                <form method="post" action="index.php?estado=<?php echo $estado; ?>&id=<?php echo $pres_id; ?>">
                    <input type="hidden" name="accion_post" value="aprobar">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Observaciones (opcional)</label>
                        <textarea class="form-control" name="observaciones" rows="3"
                                  placeholder="Comentarios sobre la aprobación..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-check2-all me-1"></i>
                        <?php echo ($nivel_revisor >= $nivel_maximo) ? 'Aprobar Definitivamente' : 'Aprobar Nivel ' . $nivel_revisor; ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- DEVOLVER -->
    <div class="col-md-6">
        <div class="card shadow-sm border-danger">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0"><i class="bi bi-arrow-counterclockwise me-2"></i>Devolver a Borrador</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    El presupuesto volverá a estado 'borrador' para que el responsable realice correcciones.
                    <strong>Las observaciones son obligatorias.</strong>
                </p>
                <form method="post" action="index.php?estado=<?php echo $estado; ?>&id=<?php echo $pres_id; ?>">
                    <input type="hidden" name="accion_post" value="devolver">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Observaciones <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="observaciones" rows="3" required
                                  placeholder="Indique qué debe corregir el responsable..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Devolver a Borrador
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
SESSION['ciclo_rol_efectivo'] ?? '';
$nivel_revisor = 1;
if ($rol_usuario === 'admin' || $rol_usuario === 'director') $nivel_revisor = 3;
elseif ($rol_usuario === 'gerente') $nivel_revisor = 2;

$nivel_maximo = 3; // Dirección

// ── Última revisión ──────────────────────────────────────────────────────────
$sql_ult = "SELECT r.*, u.nombre AS USUARIO_NOMBRE
            FROM ciclo_revision r
            LEFT JOIN ciclo_usuario u ON r.id_usuario = u.id
            WHERE r.id_presupuesto = '$pres_id'
            ORDER BY r.fecha DESC LIMIT 1;";
$row_ult = sql_do_array(sql_do_query($sql_ult, $db, $usu));

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion_post   = Enmascarar_post('accion_post');
    $observaciones = Enmascarar_post('observaciones');

    // ── APROBAR ──────────────────────────────────────────────────────────────
    if ($accion_post === 'aprobar') {
        // Determinar si es aprobación final o pasa al siguiente nivel
        $es_final = ($nivel_revisor >= $nivel_maximo);
        $nuevo_estado = $es_final ? 'aprobado' : 'en_revision';
        $accion_txt = $es_final ? 'Aprobación final' : 'Aprobado nivel ' . $nivel_revisor;

        // Registrar revisión
        $sql_rev = "INSERT INTO ciclo_revision (id_presupuesto, id_usuario, nivel, accion, observaciones, fecha)
                    VALUES ('$pres_id', '$usu', '$nivel_revisor', 'aprobar', '$observaciones', NOW());";
        sql_do_query($sql_rev, $db, $usu);

        // Actualizar presupuesto
        if ($es_final) {
            sql_do_query("UPDATE ciclo_presupuesto SET estado = 'aprobado', fecha_aprobacion = NOW(), auditoria_usu = '$usu' WHERE id = '$pres_id';", $db, $usu);
        } else {
            sql_do_query("UPDATE ciclo_presupuesto SET auditoria_usu = '$usu' WHERE id = '$pres_id';", $db, $usu);
        }

        $msg_txt = $es_final
            ? 'El presupuesto fue APROBADO definitivamente.'
            : 'Aprobado en nivel ' . $nivel_revisor . '. Pasa al siguiente nivel de revisión.';
        $msg = generarAlertJS('Aprobado', $msg_txt, 'success', "index.php?estado=301");
    }

    // ── DEVOLVER ─────────────────────────────────────────────────────────────
    if ($accion_post === 'devolver') {
        if (empty($observaciones)) {
            $msg = generarAlertJS('Error', 'Las observaciones son obligatorias al devolver un presupuesto.', 'error');
        } else {
            // Registrar revisión
            $sql_rev = "INSERT INTO ciclo_revision (id_presupuesto, id_usuario, nivel, accion, observaciones, fecha)
                        VALUES ('$pres_id', '$usu', '$nivel_revisor', 'devolver', '$observaciones', NOW());";
            sql_do_query($sql_rev, $db, $usu);

            // Volver a borrador
            sql_do_query("UPDATE ciclo_presupuesto SET estado = 'borrador', auditoria_usu = '$usu' WHERE id = '$pres_id';", $db, $usu);

            $msg = generarAlertJS('Devuelto', 'El presupuesto fue devuelto a estado borrador.', 'info', "index.php?estado=301");
        }
    }
}

echo $msg;

// ── Badge de estado ──────────────────────────────────────────────────────────
$estado_badge = '<span class="badge bg-warning text-dark fs-6">En Revisión</span>';
?>

<?php echo generarTituloSeccion('Aprobar / Devolver Presupuesto', 'bi bi-check2-square'); ?>

<!-- CABECERA -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-2">
                <small class="text-muted">Período</small>
                <div class="fw-bold fs-5"><?php echo htmlspecialchars($row_pres['ANIO']); ?></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Unidad</small>
                <div class="fw-bold"><?php echo htmlspecialchars($row_pres['UNIDAD']); ?></div>
            </div>
            <div class="col-md-2">
                <small class="text-muted">Estado</small>
                <div><?php echo $estado_badge; ?></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Monto Total</small>
                <div class="fw-bold text-primary fs-5">$ <?php echo number_format((float)$row_pres['MONTO_TOTAL'], 2, ',', '.'); ?></div>
            </div>
            <div class="col-md-2">
                <small class="text-muted">Su nivel</small>
                <div><span class="badge bg-primary fs-6">Nivel <?php echo $nivel_revisor; ?></span></div>
            </div>
        </div>
    </div>
</div>

<?php if ($row_ult): ?>
<div class="alert alert-secondary mb-4">
    <h6 class="alert-heading"><i class="bi bi-clock-history me-2"></i>Última acción registrada</h6>
    <p class="mb-1">
        <strong><?php echo htmlspecialchars($row_ult['USUARIO_NOMBRE'] ?? 'Usuario #' . $row_ult['ID_USUARIO']); ?></strong>
        — <?php echo htmlspecialchars(ucfirst($row_ult['ACCION'] ?? '')); ?>
        (Nivel <?php echo $row_ult['NIVEL']; ?>)
        — <?php echo enmascararfecha($row_ult['FECHA'] ?? ''); ?>
    </p>
    <?php if (!empty($row_ult['OBSERVACIONES'])): ?>
    <p class="mb-0 small fst-italic">"<?php echo htmlspecialchars($row_ult['OBSERVACIONES']); ?>"</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="mb-3">
    <a href="index.php?estado=301" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver a bandeja
    </a>
    <a href="index.php?estado=302&id=<?php echo $pres_id; ?>" class="btn btn-pade-alt btn-sm">
        <i class="bi bi-pencil-square me-1"></i>Revisar partidas
    </a>
    <a href="index.php?estado=304&id=<?php echo $pres_id; ?>" class="btn btn-outline-info btn-sm">
        <i class="bi bi-clock-history me-1"></i>Historial completo
    </a>
</div>

<!-- ACCIONES -->
<div class="row g-4">
    <!-- APROBAR -->
    <div class="col-md-6">
        <div class="card shadow-sm border-success">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="bi bi-check-circle me-2"></i>Aprobar</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    <?php if ($nivel_revisor >= $nivel_maximo): ?>
                        <strong>Aprobación FINAL.</strong> El presupuesto quedará en estado definitivo 'aprobado'.
                    <?php else: ?>
                        Aprueba en nivel <?php echo $nivel_revisor; ?> y pasa al siguiente nivel de revisión.
                    <?php endif; ?>
                </p>
                <form method="post" action="index.php?estado=<?php echo $estado; ?>&id=<?php echo $pres_id; ?>">
                    <input type="hidden" name="accion_post" value="aprobar">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Observaciones (opcional)</label>
                        <textarea class="form-control" name="observaciones" rows="3"
                                  placeholder="Comentarios sobre la aprobación..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-check2-all me-1"></i>
                        <?php echo ($nivel_revisor >= $nivel_maximo) ? 'Aprobar Definitivamente' : 'Aprobar Nivel ' . $nivel_revisor; ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- DEVOLVER -->
    <div class="col-md-6">
        <div class="card shadow-sm border-danger">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0"><i class="bi bi-arrow-counterclockwise me-2"></i>Devolver a Borrador</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    El presupuesto volverá a estado 'borrador' para que el responsable realice correcciones.
                    <strong>Las observaciones son obligatorias.</strong>
                </p>
                <form method="post" action="index.php?estado=<?php echo $estado; ?>&id=<?php echo $pres_id; ?>">
                    <input type="hidden" name="accion_post" value="devolver">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Observaciones <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="observaciones" rows="3" required
                                  placeholder="Indique qué debe corregir el responsable..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Devolver a Borrador
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
SESSION['ciclo_rol_efectivo'] ?? '';
$nivel_revisor = 1;
if ($rol_usuario === 'admin' || $rol_usuario === 'director') $nivel_revisor = 3;
elseif ($rol_usuario === 'gerente') $nivel_revisor = 2;

$nivel_maximo = 3; // Dirección

// ── Última revisión ──────────────────────────────────────────────────────────
$sql_ult = "SELECT r.*, u.nombre AS USUARIO_NOMBRE
            FROM ciclo_revision r
            LEFT JOIN ciclo_usuario u ON r.id_usuario = u.id
            WHERE r.id_presupuesto = '$pres_id'
            ORDER BY r.fecha DESC LIMIT 1;";
$row_ult = sql_do_array(sql_do_query($sql_ult, $db, $usu));

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion_post   = Enmascarar_post('accion_post');
    $observaciones = Enmascarar_post('observaciones');

    // ── APROBAR ──────────────────────────────────────────────────────────────
    if ($accion_post === 'aprobar') {
        // Determinar si es aprobación final o pasa al siguiente nivel
        $es_final = ($nivel_revisor >= $nivel_maximo);
        $nuevo_estado = $es_final ? 'aprobado' : 'en_revision';
        $accion_txt = $es_final ? 'Aprobación final' : 'Aprobado nivel ' . $nivel_revisor;

        // Registrar revisión
        $sql_rev = "INSERT INTO ciclo_revision (id_presupuesto, id_usuario, nivel, accion, observaciones, fecha)
                    VALUES ('$pres_id', '$usu', '$nivel_revisor', 'aprobar', '$observaciones', NOW());";
        sql_do_query($sql_rev, $db, $usu);

        // Actualizar presupuesto
        if ($es_final) {
            sql_do_query("UPDATE ciclo_presupuesto SET estado = 'aprobado', fecha_aprobacion = NOW(), auditoria_usu = '$usu' WHERE id = '$pres_id';", $db, $usu);
        } else {
            sql_do_query("UPDATE ciclo_presupuesto SET auditoria_usu = '$usu' WHERE id = '$pres_id';", $db, $usu);
        }

        $msg_txt = $es_final
            ? 'El presupuesto fue APROBADO definitivamente.'
            : 'Aprobado en nivel ' . $nivel_revisor . '. Pasa al siguiente nivel de revisión.';
        $msg = generarAlertJS('Aprobado', $msg_txt, 'success', "index.php?estado=301");
    }

    // ── DEVOLVER ─────────────────────────────────────────────────────────────
    if ($accion_post === 'devolver') {
        if (empty($observaciones)) {
            $msg = generarAlertJS('Error', 'Las observaciones son obligatorias al devolver un presupuesto.', 'error');
        } else {
            // Registrar revisión
            $sql_rev = "INSERT INTO ciclo_revision (id_presupuesto, id_usuario, nivel, accion, observaciones, fecha)
                        VALUES ('$pres_id', '$usu', '$nivel_revisor', 'devolver', '$observaciones', NOW());";
            sql_do_query($sql_rev, $db, $usu);

            // Volver a borrador
            sql_do_query("UPDATE ciclo_presupuesto SET estado = 'borrador', auditoria_usu = '$usu' WHERE id = '$pres_id';", $db, $usu);

            $msg = generarAlertJS('Devuelto', 'El presupuesto fue devuelto a estado borrador.', 'info', "index.php?estado=301");
        }
    }
}

echo $msg;

// ── Badge de estado ──────────────────────────────────────────────────────────
$estado_badge = '<span class="badge bg-warning text-dark fs-6">En Revisión</span>';
?>

<?php echo generarTituloSeccion('Aprobar / Devolver Presupuesto', 'bi bi-check2-square'); ?>

<!-- CABECERA -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-2">
                <small class="text-muted">Período</small>
                <div class="fw-bold fs-5"><?php echo htmlspecialchars($row_pres['ANIO']); ?></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Unidad</small>
                <div class="fw-bold"><?php echo htmlspecialchars($row_pres['UNIDAD']); ?></div>
            </div>
            <div class="col-md-2">
                <small class="text-muted">Estado</small>
                <div><?php echo $estado_badge; ?></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Monto Total</small>
                <div class="fw-bold text-primary fs-5">$ <?php echo number_format((float)$row_pres['MONTO_TOTAL'], 2, ',', '.'); ?></div>
            </div>
            <div class="col-md-2">
                <small class="text-muted">Su nivel</small>
                <div><span class="badge bg-primary fs-6">Nivel <?php echo $nivel_revisor; ?></span></div>
            </div>
        </div>
    </div>
</div>

<?php if ($row_ult): ?>
<div class="alert alert-secondary mb-4">
    <h6 class="alert-heading"><i class="bi bi-clock-history me-2"></i>Última acción registrada</h6>
    <p class="mb-1">
        <strong><?php echo htmlspecialchars($row_ult['USUARIO_NOMBRE'] ?? 'Usuario #' . $row_ult['ID_USUARIO']); ?></strong>
        — <?php echo htmlspecialchars(ucfirst($row_ult['ACCION'] ?? '')); ?>
        (Nivel <?php echo $row_ult['NIVEL']; ?>)
        — <?php echo enmascararfecha($row_ult['FECHA'] ?? ''); ?>
    </p>
    <?php if (!empty($row_ult['OBSERVACIONES'])): ?>
    <p class="mb-0 small fst-italic">"<?php echo htmlspecialchars($row_ult['OBSERVACIONES']); ?>"</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="mb-3">
    <a href="index.php?estado=301" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver a bandeja
    </a>
    <a href="index.php?estado=302&id=<?php echo $pres_id; ?>" class="btn btn-pade-alt btn-sm">
        <i class="bi bi-pencil-square me-1"></i>Revisar partidas
    </a>
    <a href="index.php?estado=304&id=<?php echo $pres_id; ?>" class="btn btn-outline-info btn-sm">
        <i class="bi bi-clock-history me-1"></i>Historial completo
    </a>
</div>

<!-- ACCIONES -->
<div class="row g-4">
    <!-- APROBAR -->
    <div class="col-md-6">
        <div class="card shadow-sm border-success">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="bi bi-check-circle me-2"></i>Aprobar</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    <?php if ($nivel_revisor >= $nivel_maximo): ?>
                        <strong>Aprobación FINAL.</strong> El presupuesto quedará en estado definitivo 'aprobado'.
                    <?php else: ?>
                        Aprueba en nivel <?php echo $nivel_revisor; ?> y pasa al siguiente nivel de revisión.
                    <?php endif; ?>
                </p>
                <form method="post" action="index.php?estado=<?php echo $estado; ?>&id=<?php echo $pres_id; ?>">
                    <input type="hidden" name="accion_post" value="aprobar">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Observaciones (opcional)</label>
                        <textarea class="form-control" name="observaciones" rows="3"
                                  placeholder="Comentarios sobre la aprobación..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-check2-all me-1"></i>
                        <?php echo ($nivel_revisor >= $nivel_maximo) ? 'Aprobar Definitivamente' : 'Aprobar Nivel ' . $nivel_revisor; ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- DEVOLVER -->
    <div class="col-md-6">
        <div class="card shadow-sm border-danger">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0"><i class="bi bi-arrow-counterclockwise me-2"></i>Devolver a Borrador</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    El presupuesto volverá a estado 'borrador' para que el responsable realice correcciones.
                    <strong>Las observaciones son obligatorias.</strong>
                </p>
                <form method="post" action="index.php?estado=<?php echo $estado; ?>&id=<?php echo $pres_id; ?>">
                    <input type="hidden" name="accion_post" value="devolver">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Observaciones <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="observaciones" rows="3" required
                                  placeholder="Indique qué debe corregir el responsable..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Devolver a Borrador
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
SESSION['ciclo_rol_efectivo'] ?? '';
$nivel_revisor = 1;
if ($rol_usuario === 'admin' || $rol_usuario === 'director') $nivel_revisor = 3;
elseif ($rol_usuario === 'gerente') $nivel_revisor = 2;

$nivel_maximo = 3; // Dirección

// ── Última revisión ──────────────────────────────────────────────────────────
$sql_ult = "SELECT r.*, u.nombre AS USUARIO_NOMBRE
            FROM ciclo_revision r
            LEFT JOIN ciclo_usuario u ON r.id_usuario = u.id
            WHERE r.id_presupuesto = '$pres_id'
            ORDER BY r.fecha DESC LIMIT 1;";
$row_ult = sql_do_array(sql_do_query($sql_ult, $db, $usu));

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion_post   = Enmascarar_post('accion_post');
    $observaciones = Enmascarar_post('observaciones');

    // ── APROBAR ──────────────────────────────────────────────────────────────
    if ($accion_post === 'aprobar') {
        // Determinar si es aprobación final o pasa al siguiente nivel
        $es_final = ($nivel_revisor >= $nivel_maximo);
        $nuevo_estado = $es_final ? 'aprobado' : 'en_revision';
        $accion_txt = $es_final ? 'Aprobación final' : 'Aprobado nivel ' . $nivel_revisor;

        // Registrar revisión
        $sql_rev = "INSERT INTO ciclo_revision (id_presupuesto, id_usuario, nivel, accion, observaciones, fecha)
                    VALUES ('$pres_id', '$usu', '$nivel_revisor', 'aprobar', '$observaciones', NOW());";
        sql_do_query($sql_rev, $db, $usu);

        // Actualizar presupuesto
        if ($es_final) {
            sql_do_query("UPDATE ciclo_presupuesto SET estado = 'aprobado', fecha_aprobacion = NOW(), auditoria_usu = '$usu' WHERE id = '$pres_id';", $db, $usu);
        } else {
            sql_do_query("UPDATE ciclo_presupuesto SET auditoria_usu = '$usu' WHERE id = '$pres_id';", $db, $usu);
        }

        $msg_txt = $es_final
            ? 'El presupuesto fue APROBADO definitivamente.'
            : 'Aprobado en nivel ' . $nivel_revisor . '. Pasa al siguiente nivel de revisión.';
        $msg = generarAlertJS('Aprobado', $msg_txt, 'success', "index.php?estado=301");
    }

    // ── DEVOLVER ─────────────────────────────────────────────────────────────
    if ($accion_post === 'devolver') {
        if (empty($observaciones)) {
            $msg = generarAlertJS('Error', 'Las observaciones son obligatorias al devolver un presupuesto.', 'error');
        } else {
            // Registrar revisión
            $sql_rev = "INSERT INTO ciclo_revision (id_presupuesto, id_usuario, nivel, accion, observaciones, fecha)
                        VALUES ('$pres_id', '$usu', '$nivel_revisor', 'devolver', '$observaciones', NOW());";
            sql_do_query($sql_rev, $db, $usu);

            // Volver a borrador
            sql_do_query("UPDATE ciclo_presupuesto SET estado = 'borrador', auditoria_usu = '$usu' WHERE id = '$pres_id';", $db, $usu);

            $msg = generarAlertJS('Devuelto', 'El presupuesto fue devuelto a estado borrador.', 'info', "index.php?estado=301");
        }
    }
}

echo $msg;

// ── Badge de estado ──────────────────────────────────────────────────────────
$estado_badge = '<span class="badge bg-warning text-dark fs-6">En Revisión</span>';
?>

<?php echo generarTituloSeccion('Aprobar / Devolver Presupuesto', 'bi bi-check2-square'); ?>

<!-- CABECERA -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-2">
                <small class="text-muted">Período</small>
                <div class="fw-bold fs-5"><?php echo htmlspecialchars($row_pres['ANIO']); ?></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Unidad</small>
                <div class="fw-bold"><?php echo htmlspecialchars($row_pres['UNIDAD']); ?></div>
            </div>
            <div class="col-md-2">
                <small class="text-muted">Estado</small>
                <div><?php echo $estado_badge; ?></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Monto Total</small>
                <div class="fw-bold text-primary fs-5">$ <?php echo number_format((float)$row_pres['MONTO_TOTAL'], 2, ',', '.'); ?></div>
            </div>
            <div class="col-md-2">
                <small class="text-muted">Su nivel</small>
                <div><span class="badge bg-primary fs-6">Nivel <?php echo $nivel_revisor; ?></span></div>
            </div>
        </div>
    </div>
</div>

<?php if ($row_ult): ?>
<div class="alert alert-secondary mb-4">
    <h6 class="alert-heading"><i class="bi bi-clock-history me-2"></i>Última acción registrada</h6>
    <p class="mb-1">
        <strong><?php echo htmlspecialchars($row_ult['USUARIO_NOMBRE'] ?? 'Usuario #' . $row_ult['ID_USUARIO']); ?></strong>
        — <?php echo htmlspecialchars(ucfirst($row_ult['ACCION'] ?? '')); ?>
        (Nivel <?php echo $row_ult['NIVEL']; ?>)
        — <?php echo enmascararfecha($row_ult['FECHA'] ?? ''); ?>
    </p>
    <?php if (!empty($row_ult['OBSERVACIONES'])): ?>
    <p class="mb-0 small fst-italic">"<?php echo htmlspecialchars($row_ult['OBSERVACIONES']); ?>"</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="mb-3">
    <a href="index.php?estado=301" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver a bandeja
    </a>
    <a href="index.php?estado=302&id=<?php echo $pres_id; ?>" class="btn btn-pade-alt btn-sm">
        <i class="bi bi-pencil-square me-1"></i>Revisar partidas
    </a>
    <a href="index.php?estado=304&id=<?php echo $pres_id; ?>" class="btn btn-outline-info btn-sm">
        <i class="bi bi-clock-history me-1"></i>Historial completo
    </a>
</div>

<!-- ACCIONES -->
<div class="row g-4">
    <!-- APROBAR -->
    <div class="col-md-6">
        <div class="card shadow-sm border-success">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="bi bi-check-circle me-2"></i>Aprobar</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    <?php if ($nivel_revisor >= $nivel_maximo): ?>
                        <strong>Aprobación FINAL.</strong> El presupuesto quedará en estado definitivo 'aprobado'.
                    <?php else: ?>
                        Aprueba en nivel <?php echo $nivel_revisor; ?> y pasa al siguiente nivel de revisión.
                    <?php endif; ?>
                </p>
                <form method="post" action="index.php?estado=<?php echo $estado; ?>&id=<?php echo $pres_id; ?>">
                    <input type="hidden" name="accion_post" value="aprobar">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Observaciones (opcional)</label>
                        <textarea class="form-control" name="observaciones" rows="3"
                                  placeholder="Comentarios sobre la aprobación..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-check2-all me-1"></i>
                        <?php echo ($nivel_revisor >= $nivel_maximo) ? 'Aprobar Definitivamente' : 'Aprobar Nivel ' . $nivel_revisor; ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- DEVOLVER -->
    <div class="col-md-6">
        <div class="card shadow-sm border-danger">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0"><i class="bi bi-arrow-counterclockwise me-2"></i>Devolver a Borrador</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    El presupuesto volverá a estado 'borrador' para que el responsable realice correcciones.
                    <strong>Las observaciones son obligatorias.</strong>
                </p>
                <form method="post" action="index.php?estado=<?php echo $estado; ?>&id=<?php echo $pres_id; ?>">
                    <input type="hidden" name="accion_post" value="devolver">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Observaciones <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="observaciones" rows="3" required
                                  placeholder="Indique qué debe corregir el responsable..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Devolver a Borrador
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
