<?php
/**
 * ============================================================================
 * Historial de Cambios
 * ============================================================================
 * ID menú: 304 | Tablas: ciclo_revision, ciclo_revision_detalle, ciclo_usuario, ciclo_item_gasto
 * Línea de tiempo completa con todas las acciones realizadas sobre un presupuesto.
 * Solo lectura.
 * ============================================================================
 */

$db  = '1';
$pres_id = Enmascarar_get('id');

if (empty($pres_id)) {
    echo generarAlertJS('Error', 'No se especificó un presupuesto.', 'error', "index.php?estado=301");
    return;
}

// ── Cargar presupuesto ───────────────────────────────────────────────────────
$sql_pres = "SELECT pr.*, pf.anio, uo.nombre AS UNIDAD
             FROM ciclo_presupuesto pr
             JOIN ciclo_periodo_fiscal pf ON pr.id_periodo = pf.id
             JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
             WHERE pr.id = '$pres_id';";
$row_pres = sql_do_array(sql_do_query($sql_pres, $db, $usu));

if (!$row_pres) {
    echo generarAlertJS('Error', 'Presupuesto no encontrado.', 'error', "index.php?estado=301");
    return;
}

// Badge de estado
$estado_badge = '';
switch ($row_pres['ESTADO']) {
    case 'borrador':    $estado_badge = '<span class="badge bg-secondary">Borrador</span>'; break;
    case 'en_revision': $estado_badge = '<span class="badge bg-warning text-dark">En Revisión</span>'; break;
    case 'aprobado':    $estado_badge = '<span class="badge bg-success">Aprobado</span>'; break;
    case 'rechazado':   $estado_badge = '<span class="badge bg-danger">Rechazado</span>'; break;
    default:            $estado_badge = '<span class="badge bg-light text-dark">' . htmlspecialchars($row_pres['ESTADO']) . '</span>';
}

// ── Cargar revisiones ────────────────────────────────────────────────────────
$sql_revs = "SELECT r.*, u.nombre AS USUARIO_NOMBRE
             FROM ciclo_revision r
             LEFT JOIN ciclo_usuario u ON r.id_usuario = u.id
             WHERE r.id_presupuesto = '$pres_id'
             ORDER BY r.fecha DESC;";
$res_revs = sql_do_query($sql_revs, $db, $usu);

$revisiones = [];
while ($rr = sql_do_array($res_revs)) $revisiones[] = $rr;
?>

<?php echo generarTituloSeccion('Historial de Cambios', 'bi bi-clock-history'); ?>

<!-- CABECERA -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <small class="text-muted">Período</small>
                <div class="fw-bold fs-5"><?php echo htmlspecialchars($row_pres['ANIO']); ?></div>
            </div>
            <div class="col-md-4">
                <small class="text-muted">Unidad</small>
                <div class="fw-bold"><?php echo htmlspecialchars($row_pres['UNIDAD']); ?></div>
            </div>
            <div class="col-md-2">
                <small class="text-muted">Estado Actual</small>
                <div><?php echo $estado_badge; ?></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Monto Total</small>
                <div class="fw-bold text-primary">$ <?php echo number_format((float)$row_pres['MONTO_TOTAL'], 2, ',', '.'); ?></div>
            </div>
        </div>
    </div>
</div>

<div class="mb-3">
    <a href="index.php?estado=301" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver a bandeja
    </a>
    <a href="index.php?estado=203&id=<?php echo $pres_id; ?>" class="btn btn-pade-alt btn-sm">
        <i class="bi bi-eye me-1"></i>Ver Detalle
    </a>
</div>

<!-- TIMELINE -->
<style>
.timeline { position: relative; padding-left: 30px; }
.timeline::before {
    content: '';
    position: absolute;
    left: 14px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}
.timeline-item { position: relative; margin-bottom: 1.5rem; }
.timeline-dot {
    position: absolute;
    left: -24px;
    top: 6px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #dee2e6;
}
.timeline-dot.aprobar   { background: #198754; box-shadow: 0 0 0 2px #198754; }
.timeline-dot.devolver  { background: #dc3545; box-shadow: 0 0 0 2px #dc3545; }
.timeline-dot.modificar { background: #0d6efd; box-shadow: 0 0 0 2px #0d6efd; }
.timeline-dot.rechazar  { background: #6c757d; box-shadow: 0 0 0 2px #6c757d; }
</style>

<?php if (empty($revisiones)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>No hay acciones registradas para este presupuesto.
</div>
<?php else: ?>

<div class="timeline">
<?php foreach ($revisiones as $rev):
    $accion = $rev['ACCION'] ?? 'modificar';
    $icono = 'bi-pencil';
    $badge_class = 'bg-primary';
    switch ($accion) {
        case 'aprobar':  $icono = 'bi-check-circle'; $badge_class = 'bg-success'; break;
        case 'devolver': $icono = 'bi-arrow-counterclockwise'; $badge_class = 'bg-danger'; break;
        case 'rechazar': $icono = 'bi-x-circle'; $badge_class = 'bg-secondary'; break;
        case 'modificar': $icono = 'bi-pencil-square'; $badge_class = 'bg-primary'; break;
    }
?>
    <div class="timeline-item">
        <div class="timeline-dot <?php echo $accion; ?>"></div>
        <div class="card shadow-sm">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge <?php echo $badge_class; ?> me-2">
                        <i class="bi <?php echo $icono; ?> me-1"></i><?php echo ucfirst($accion); ?>
                    </span>
                    <strong><?php echo htmlspecialchars($rev['USUARIO_NOMBRE'] ?? 'Usuario #' . $rev['ID_USUARIO']); ?></strong>
                    <small class="text-muted ms-2">Nivel <?php echo $rev['NIVEL']; ?></small>
                </div>
                <small class="text-muted"><?php echo enmascararfecha($rev['FECHA'] ?? ''); ?></small>
            </div>
            <?php if (!empty($rev['OBSERVACIONES'])): ?>
            <div class="card-body py-2">
                <p class="mb-0 fst-italic text-muted">"<?php echo htmlspecialchars($rev['OBSERVACIONES']); ?>"</p>
            </div>
            <?php endif; ?>

            <?php
            // Cargar detalles de esta revisión
            $rev_id = $rev['ID'];
            $sql_det = "SELECT rd.*, ig.nombre AS ITEM, ig.codigo AS ITEM_CODIGO
                        FROM ciclo_revision_detalle rd
                        JOIN ciclo_partida pa ON rd.id_partida = pa.id
                        JOIN ciclo_item_gasto ig ON pa.id_item = ig.id
                        WHERE rd.id_revision = '$rev_id';";
            $res_det = sql_do_query($sql_det, $db, $usu);
            $detalles = [];
            while ($rd = sql_do_array($res_det)) $detalles[] = $rd;

            if (!empty($detalles)):
            ?>
            <div class="card-body p-0 border-top">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center small">Código</th>
                            <th class="small">Ítem</th>
                            <th class="text-end small">Monto Anterior</th>
                            <th class="text-end small">Monto Nuevo</th>
                            <th class="text-end small">Diferencia</th>
                            <th class="small">Motivo</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($detalles as $d):
                        $ant = (float)$d['MONTO_ANTERIOR'];
                        $nvo = (float)$d['MONTO_NUEVO'];
                        $dif = $nvo - $ant;
                        $dif_class = ($dif > 0) ? 'text-success' : (($dif < 0) ? 'text-danger' : 'text-muted');
                    ?>
                        <tr>
                            <td class="text-center small"><?php echo htmlspecialchars($d['ITEM_CODIGO']); ?></td>
                            <td class="small"><?php echo htmlspecialchars($d['ITEM']); ?></td>
                            <td class="text-end small">$ <?php echo number_format($ant, 2, ',', '.'); ?></td>
                            <td class="text-end small fw-bold">$ <?php echo number_format($nvo, 2, ',', '.'); ?></td>
                            <td class="text-end small <?php echo $dif_class; ?>">
                                <?php echo ($dif >= 0 ? '+' : '') . number_format($dif, 2, ',', '.'); ?>
                            </td>
                            <td class="small fst-italic"><?php echo htmlspecialchars($d['MOTIVO'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php endif; ?>
