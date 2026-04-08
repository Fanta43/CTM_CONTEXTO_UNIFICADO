<?php
/**
 * ============================================================================
 * Bandeja de Revisión
 * ============================================================================
 * ID menú: 301 | Tablas: ciclo_presupuesto, ciclo_periodo_fiscal, ciclo_unidad_organizativa
 * Lista presupuestos pendientes de revisión según el nivel jerárquico del usuario.
 * jefe_unidad → nivel 1 | gerente → nivel 2 | director/admin → nivel 3
 * ============================================================================
 */

$db  = '1';
$msg = '';

// ── Determinar nivel de revisión del usuario ─────────────────────────────────
$rol_usuario    = <?php
/**
 * ============================================================================
 * Bandeja de Revisión
 * ============================================================================
 * ID menú: 301 | Tablas: ciclo_presupuesto, ciclo_periodo_fiscal, ciclo_unidad_organizativa
 * Lista presupuestos pendientes de revisión según el nivel jerárquico del usuario.
 * jefe_unidad → nivel 1 | gerente → nivel 2 | director/admin → nivel 3
 * ============================================================================
 */

$db  = '1';
$msg = '';

// ── Determinar nivel de revisión del usuario ─────────────────────────────────
$rol_usuario    = $_SESSION['rol'] ?? '';
$unidad_usuario = $_SESSION['id_unidad'] ?? '';

// Obtener nivel_aprobacion de la unidad del usuario
$nivel_revision = 0;
if (!empty($unidad_usuario)) {
    $sql_niv = "SELECT nivel_aprobacion FROM ciclo_unidad_organizativa WHERE id = '$unidad_usuario';";
    $row_niv = sql_do_array(sql_do_query($sql_niv, $db, $usu));
    $nivel_revision = (int)($row_niv['NIVEL_APROBACION'] ?? 0);
}

// Override por rol para admin/director
if ($rol_usuario === 'admin' || $rol_usuario === 'director') $nivel_revision = 3;
elseif ($rol_usuario === 'gerente')    $nivel_revision = 2;
elseif ($rol_usuario === 'jefe_unidad') $nivel_revision = 1;

// ── Filtro de unidades según rol ─────────────────────────────────────────────
$where_rol = '';
if ($rol_usuario === 'jefe_unidad' && !empty($unidad_usuario)) {
    $where_rol = "AND pr.id_unidad = '$unidad_usuario'";
} elseif ($rol_usuario === 'gerente' && !empty($unidad_usuario)) {
    $where_rol = "AND (pr.id_unidad = '$unidad_usuario'
                  OR pr.id_unidad IN (SELECT id FROM ciclo_unidad_organizativa WHERE id_padre = '$unidad_usuario'))";
}
// director y admin ven todo

// ── Consultar presupuestos en revisión ───────────────────────────────────────
$sql_list = "SELECT pr.*, pf.anio, uo.nombre AS UNIDAD,
                    DATEDIFF(NOW(), pr.fecha_creacion) AS DIAS_PENDIENTE
             FROM ciclo_presupuesto pr
             JOIN ciclo_periodo_fiscal pf ON pr.id_periodo = pf.id
             JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
             WHERE pr.estado = 'en_revision'
             $where_rol
             ORDER BY pr.fecha_creacion ASC;";
$result_list = sql_do_query($sql_list, $db, $usu);
?>

<?php echo generarTituloSeccion('Bandeja de Revisión', 'bi bi-inbox'); ?>

<div class="alert alert-info mb-3">
    <i class="bi bi-info-circle me-2"></i>
    Nivel de revisión actual: <strong><?php echo $nivel_revision; ?></strong>
    (<?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $rol_usuario))); ?>)
    — Se muestran presupuestos pendientes para su nivel jerárquico.
</div>

<div class="table-responsive">
    <table class="table table-hover table-pade">
        <?php echo generarTablaHeader(['Período', 'Unidad', 'Monto Total', 'Fecha Envío', 'Días Pendiente', 'Acciones']); ?>
        <tbody>
<?php
$n = 0;
while ($row = sql_do_array($result_list)):
    $n++;
    $dias = (int)($row['DIAS_PENDIENTE'] ?? 0);
    $dias_class = '';
    if ($dias > 14)     $dias_class = 'text-danger fw-bold';
    elseif ($dias > 7)  $dias_class = 'text-warning fw-bold';
?>
            <tr class="<?php echo claseFilaAlternada($n); ?>">
                <td class="text-center fw-bold"><?php echo htmlspecialchars($row['ANIO']); ?></td>
                <td><?php echo htmlspecialchars($row['UNIDAD']); ?></td>
                <td class="text-end">$ <?php echo number_format((float)$row['MONTO_TOTAL'], 2, ',', '.'); ?></td>
                <td class="text-center small"><?php echo enmascararfecha($row['FECHA_CREACION'] ?? ''); ?></td>
                <td class="text-center">
                    <span class="<?php echo $dias_class; ?>"><?php echo $dias; ?> días</span>
                </td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                        <a href="index.php?estado=302&id=<?php echo $row['ID']; ?>"
                           class="btn btn-pade btn-sm" title="Revisar partidas">
                            <i class="bi bi-pencil-square me-1"></i>Revisar
                        </a>
                        <a href="index.php?estado=304&id=<?php echo $row['ID']; ?>"
                           class="btn btn-outline-secondary btn-sm" title="Ver historial">
                            <i class="bi bi-clock-history"></i>
                        </a>
                    </div>
                </td>
            </tr>
<?php endwhile; ?>
<?php if ($n === 0): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">
                <i class="bi bi-check-circle me-2"></i>No hay presupuestos pendientes de revisión.
            </td></tr>
<?php endif; ?>
        </tbody>
    </table>
</div>
SESSION['ciclo_rol_efectivo'] ?? '';
$unidad_usuario = $_SESSION['id_unidad'] ?? '';

// Obtener nivel_aprobacion de la unidad del usuario
$nivel_revision = 0;
if (!empty($unidad_usuario)) {
    $sql_niv = "SELECT nivel_aprobacion FROM ciclo_unidad_organizativa WHERE id = '$unidad_usuario';";
    $row_niv = sql_do_array(sql_do_query($sql_niv, $db, $usu));
    $nivel_revision = (int)($row_niv['NIVEL_APROBACION'] ?? 0);
}

// Override por rol para admin/director
if ($rol_usuario === 'admin' || $rol_usuario === 'director') $nivel_revision = 3;
elseif ($rol_usuario === 'gerente')    $nivel_revision = 2;
elseif ($rol_usuario === 'jefe_unidad') $nivel_revision = 1;

// ── Filtro de unidades según rol ─────────────────────────────────────────────
$where_rol = '';
if ($rol_usuario === 'jefe_unidad' && !empty($unidad_usuario)) {
    $where_rol = "AND pr.id_unidad = '$unidad_usuario'";
} elseif ($rol_usuario === 'gerente' && !empty($unidad_usuario)) {
    $where_rol = "AND (pr.id_unidad = '$unidad_usuario'
                  OR pr.id_unidad IN (SELECT id FROM ciclo_unidad_organizativa WHERE id_padre = '$unidad_usuario'))";
}
// director y admin ven todo

// ── Consultar presupuestos en revisión ───────────────────────────────────────
$sql_list = "SELECT pr.*, pf.anio, uo.nombre AS UNIDAD,
                    DATEDIFF(NOW(), pr.fecha_creacion) AS DIAS_PENDIENTE
             FROM ciclo_presupuesto pr
             JOIN ciclo_periodo_fiscal pf ON pr.id_periodo = pf.id
             JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
             WHERE pr.estado = 'en_revision'
             $where_rol
             ORDER BY pr.fecha_creacion ASC;";
$result_list = sql_do_query($sql_list, $db, $usu);
?>

<?php echo generarTituloSeccion('Bandeja de Revisión', 'bi bi-inbox'); ?>

<div class="alert alert-info mb-3">
    <i class="bi bi-info-circle me-2"></i>
    Nivel de revisión actual: <strong><?php echo $nivel_revision; ?></strong>
    (<?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $rol_usuario))); ?>)
    — Se muestran presupuestos pendientes para su nivel jerárquico.
</div>

<div class="table-responsive">
    <table class="table table-hover table-pade">
        <?php echo generarTablaHeader(['Período', 'Unidad', 'Monto Total', 'Fecha Envío', 'Días Pendiente', 'Acciones']); ?>
        <tbody>
<?php
$n = 0;
while ($row = sql_do_array($result_list)):
    $n++;
    $dias = (int)($row['DIAS_PENDIENTE'] ?? 0);
    $dias_class = '';
    if ($dias > 14)     $dias_class = 'text-danger fw-bold';
    elseif ($dias > 7)  $dias_class = 'text-warning fw-bold';
?>
            <tr class="<?php echo claseFilaAlternada($n); ?>">
                <td class="text-center fw-bold"><?php echo htmlspecialchars($row['ANIO']); ?></td>
                <td><?php echo htmlspecialchars($row['UNIDAD']); ?></td>
                <td class="text-end">$ <?php echo number_format((float)$row['MONTO_TOTAL'], 2, ',', '.'); ?></td>
                <td class="text-center small"><?php echo enmascararfecha($row['FECHA_CREACION'] ?? ''); ?></td>
                <td class="text-center">
                    <span class="<?php echo $dias_class; ?>"><?php echo $dias; ?> días</span>
                </td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                        <a href="index.php?estado=302&id=<?php echo $row['ID']; ?>"
                           class="btn btn-pade btn-sm" title="Revisar partidas">
                            <i class="bi bi-pencil-square me-1"></i>Revisar
                        </a>
                        <a href="index.php?estado=304&id=<?php echo $row['ID']; ?>"
                           class="btn btn-outline-secondary btn-sm" title="Ver historial">
                            <i class="bi bi-clock-history"></i>
                        </a>
                    </div>
                </td>
            </tr>
<?php endwhile; ?>
<?php if ($n === 0): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">
                <i class="bi bi-check-circle me-2"></i>No hay presupuestos pendientes de revisión.
            </td></tr>
<?php endif; ?>
        </tbody>
    </table>
</div>
SESSION['ciclo_rol_efectivo'] ?? '';
$unidad_usuario = $_SESSION['id_unidad'] ?? '';

// Obtener nivel_aprobacion de la unidad del usuario
$nivel_revision = 0;
if (!empty($unidad_usuario)) {
    $sql_niv = "SELECT nivel_aprobacion FROM ciclo_unidad_organizativa WHERE id = '$unidad_usuario';";
    $row_niv = sql_do_array(sql_do_query($sql_niv, $db, $usu));
    $nivel_revision = (int)($row_niv['NIVEL_APROBACION'] ?? 0);
}

// Override por rol para admin/director
if ($rol_usuario === 'admin' || $rol_usuario === 'director') $nivel_revision = 3;
elseif ($rol_usuario === 'gerente')    $nivel_revision = 2;
elseif ($rol_usuario === 'jefe_unidad') $nivel_revision = 1;

// ── Filtro de unidades según rol ─────────────────────────────────────────────
$where_rol = '';
if ($rol_usuario === 'jefe_unidad' && !empty($unidad_usuario)) {
    $where_rol = "AND pr.id_unidad = '$unidad_usuario'";
} elseif ($rol_usuario === 'gerente' && !empty($unidad_usuario)) {
    $where_rol = "AND (pr.id_unidad = '$unidad_usuario'
                  OR pr.id_unidad IN (SELECT id FROM ciclo_unidad_organizativa WHERE id_padre = '$unidad_usuario'))";
}
// director y admin ven todo

// ── Consultar presupuestos en revisión ───────────────────────────────────────
$sql_list = "SELECT pr.*, pf.anio, uo.nombre AS UNIDAD,
                    DATEDIFF(NOW(), pr.fecha_creacion) AS DIAS_PENDIENTE
             FROM ciclo_presupuesto pr
             JOIN ciclo_periodo_fiscal pf ON pr.id_periodo = pf.id
             JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
             WHERE pr.estado = 'en_revision'
             $where_rol
             ORDER BY pr.fecha_creacion ASC;";
$result_list = sql_do_query($sql_list, $db, $usu);
?>

<?php echo generarTituloSeccion('Bandeja de Revisión', 'bi bi-inbox'); ?>

<div class="alert alert-info mb-3">
    <i class="bi bi-info-circle me-2"></i>
    Nivel de revisión actual: <strong><?php echo $nivel_revision; ?></strong>
    (<?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $rol_usuario))); ?>)
    — Se muestran presupuestos pendientes para su nivel jerárquico.
</div>

<div class="table-responsive">
    <table class="table table-hover table-pade">
        <?php echo generarTablaHeader(['Período', 'Unidad', 'Monto Total', 'Fecha Envío', 'Días Pendiente', 'Acciones']); ?>
        <tbody>
<?php
$n = 0;
while ($row = sql_do_array($result_list)):
    $n++;
    $dias = (int)($row['DIAS_PENDIENTE'] ?? 0);
    $dias_class = '';
    if ($dias > 14)     $dias_class = 'text-danger fw-bold';
    elseif ($dias > 7)  $dias_class = 'text-warning fw-bold';
?>
            <tr class="<?php echo claseFilaAlternada($n); ?>">
                <td class="text-center fw-bold"><?php echo htmlspecialchars($row['ANIO']); ?></td>
                <td><?php echo htmlspecialchars($row['UNIDAD']); ?></td>
                <td class="text-end">$ <?php echo number_format((float)$row['MONTO_TOTAL'], 2, ',', '.'); ?></td>
                <td class="text-center small"><?php echo enmascararfecha($row['FECHA_CREACION'] ?? ''); ?></td>
                <td class="text-center">
                    <span class="<?php echo $dias_class; ?>"><?php echo $dias; ?> días</span>
                </td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                        <a href="index.php?estado=302&id=<?php echo $row['ID']; ?>"
                           class="btn btn-pade btn-sm" title="Revisar partidas">
                            <i class="bi bi-pencil-square me-1"></i>Revisar
                        </a>
                        <a href="index.php?estado=304&id=<?php echo $row['ID']; ?>"
                           class="btn btn-outline-secondary btn-sm" title="Ver historial">
                            <i class="bi bi-clock-history"></i>
                        </a>
                    </div>
                </td>
            </tr>
<?php endwhile; ?>
<?php if ($n === 0): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">
                <i class="bi bi-check-circle me-2"></i>No hay presupuestos pendientes de revisión.
            </td></tr>
<?php endif; ?>
        </tbody>
    </table>
</div>
SESSION['ciclo_rol_efectivo'] ?? '';
$unidad_usuario = $_SESSION['id_unidad'] ?? '';

// Obtener nivel_aprobacion de la unidad del usuario
$nivel_revision = 0;
if (!empty($unidad_usuario)) {
    $sql_niv = "SELECT nivel_aprobacion FROM ciclo_unidad_organizativa WHERE id = '$unidad_usuario';";
    $row_niv = sql_do_array(sql_do_query($sql_niv, $db, $usu));
    $nivel_revision = (int)($row_niv['NIVEL_APROBACION'] ?? 0);
}

// Override por rol para admin/director
if ($rol_usuario === 'admin' || $rol_usuario === 'director') $nivel_revision = 3;
elseif ($rol_usuario === 'gerente')    $nivel_revision = 2;
elseif ($rol_usuario === 'jefe_unidad') $nivel_revision = 1;

// ── Filtro de unidades según rol ─────────────────────────────────────────────
$where_rol = '';
if ($rol_usuario === 'jefe_unidad' && !empty($unidad_usuario)) {
    $where_rol = "AND pr.id_unidad = '$unidad_usuario'";
} elseif ($rol_usuario === 'gerente' && !empty($unidad_usuario)) {
    $where_rol = "AND (pr.id_unidad = '$unidad_usuario'
                  OR pr.id_unidad IN (SELECT id FROM ciclo_unidad_organizativa WHERE id_padre = '$unidad_usuario'))";
}
// director y admin ven todo

// ── Consultar presupuestos en revisión ───────────────────────────────────────
$sql_list = "SELECT pr.*, pf.anio, uo.nombre AS UNIDAD,
                    DATEDIFF(NOW(), pr.fecha_creacion) AS DIAS_PENDIENTE
             FROM ciclo_presupuesto pr
             JOIN ciclo_periodo_fiscal pf ON pr.id_periodo = pf.id
             JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
             WHERE pr.estado = 'en_revision'
             $where_rol
             ORDER BY pr.fecha_creacion ASC;";
$result_list = sql_do_query($sql_list, $db, $usu);
?>

<?php echo generarTituloSeccion('Bandeja de Revisión', 'bi bi-inbox'); ?>

<div class="alert alert-info mb-3">
    <i class="bi bi-info-circle me-2"></i>
    Nivel de revisión actual: <strong><?php echo $nivel_revision; ?></strong>
    (<?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $rol_usuario))); ?>)
    — Se muestran presupuestos pendientes para su nivel jerárquico.
</div>

<div class="table-responsive">
    <table class="table table-hover table-pade">
        <?php echo generarTablaHeader(['Período', 'Unidad', 'Monto Total', 'Fecha Envío', 'Días Pendiente', 'Acciones']); ?>
        <tbody>
<?php
$n = 0;
while ($row = sql_do_array($result_list)):
    $n++;
    $dias = (int)($row['DIAS_PENDIENTE'] ?? 0);
    $dias_class = '';
    if ($dias > 14)     $dias_class = 'text-danger fw-bold';
    elseif ($dias > 7)  $dias_class = 'text-warning fw-bold';
?>
            <tr class="<?php echo claseFilaAlternada($n); ?>">
                <td class="text-center fw-bold"><?php echo htmlspecialchars($row['ANIO']); ?></td>
                <td><?php echo htmlspecialchars($row['UNIDAD']); ?></td>
                <td class="text-end">$ <?php echo number_format((float)$row['MONTO_TOTAL'], 2, ',', '.'); ?></td>
                <td class="text-center small"><?php echo enmascararfecha($row['FECHA_CREACION'] ?? ''); ?></td>
                <td class="text-center">
                    <span class="<?php echo $dias_class; ?>"><?php echo $dias; ?> días</span>
                </td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                        <a href="index.php?estado=302&id=<?php echo $row['ID']; ?>"
                           class="btn btn-pade btn-sm" title="Revisar partidas">
                            <i class="bi bi-pencil-square me-1"></i>Revisar
                        </a>
                        <a href="index.php?estado=304&id=<?php echo $row['ID']; ?>"
                           class="btn btn-outline-secondary btn-sm" title="Ver historial">
                            <i class="bi bi-clock-history"></i>
                        </a>
                    </div>
                </td>
            </tr>
<?php endwhile; ?>
<?php if ($n === 0): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">
                <i class="bi bi-check-circle me-2"></i>No hay presupuestos pendientes de revisión.
            </td></tr>
<?php endif; ?>
        </tbody>
    </table>
</div>
