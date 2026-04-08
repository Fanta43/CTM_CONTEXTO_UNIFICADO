<?php
/**
 * ============================================================================
 * Borradores de SR — Gestión de SR proyectadas
 * ============================================================================
 * Tabla: pres_sr_borrador
 * Muestra los borradores de SR generados por generar_borrador.php,
 * agrupados por período destino y centro de costos.
 * Permite ver, descartar o marcar como enviadas las SR borrador.
 * ============================================================================
 */

$db = '1';

// ── Sub-página: Detalle de un borrador individual ───────────────────────────
if (isset($_GET['accion']) && $_GET['accion'] === 'detalle_borrador' && !empty($_GET['id'])) {
    include_once __DIR__ . '/detalle_borrador.php';
    return;
}

// ── Acciones POST ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion_borrador = Enmascarar_post('accion_borrador');

    // Cambiar estado de borradores seleccionados
    if (in_array($accion_borrador, ['descartar', 'enviada', 'borrador']) && !empty($_POST['ids'])) {
        $ids = array_map('intval', $_POST['ids']);
        $ids_sql = implode(',', $ids);
        $nuevo_estado = ($accion_borrador === 'descartar') ? 'descartada' : (($accion_borrador === 'enviada') ? 'enviada' : 'borrador');
        sql_do_query("UPDATE pres_sr_borrador SET estado = '$nuevo_estado', auditoria_usu = '$usu', auditoria_fecha = NOW()
                      WHERE id IN ($ids_sql);", $db, $usu);
        $msg = generarAlertJS('Actualizado', count($ids) . ' borrador(es) marcados como ' . $nuevo_estado . '.', 'success');
        echo $msg;
    }

    // Eliminar borradores descartados
    if ($accion_borrador === 'eliminar_descartadas') {
        $filtro_per = Enmascarar_post('filtro_periodo');
        $filtro_cc  = Enmascarar_post('filtro_cc');
        $del_where = "WHERE estado = 'descartada'";
        if (!empty($filtro_per) && $filtro_per !== 'null') $del_where .= " AND id_periodo_destino = '$filtro_per'";
        if (!empty($filtro_cc) && $filtro_cc !== 'null')   $del_where .= " AND esquema_cc = '$filtro_cc'";
        sql_do_query("DELETE FROM pres_sr_borrador $del_where;", $db, $usu);
        $msg = generarAlertJS('Eliminados', 'Se eliminaron los borradores descartados.', 'info');
        echo $msg;
    }
}

// ── Filtros ────────────────────────────────────────────────────────────────
$filtro_periodo = Enmascarar_get('periodo');
$filtro_cc      = Enmascarar_get('cc');
$filtro_estado  = Enmascarar_get('filtro_estado');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['filtro_periodo'])) $filtro_periodo = Enmascarar_post('filtro_periodo');
    if (!empty($_POST['filtro_cc']))      $filtro_cc      = Enmascarar_post('filtro_cc');
    if (!empty($_POST['filtro_estado']))  $filtro_estado  = Enmascarar_post('filtro_estado');
}

// Default: período abierto
if (empty($filtro_periodo) || $filtro_periodo === 'null') {
    $row_ab = sql_do_array(sql_do_query("SELECT id FROM ciclo_periodo_fiscal WHERE estado = 'abierto' LIMIT 1;", $db, $usu));
    $filtro_periodo = $row_ab['ID'] ?? '';
}

// Obtener año del período
$anio_sel = '';
if (!empty($filtro_periodo)) {
    $row_per = sql_do_array(sql_do_query("SELECT anio FROM ciclo_periodo_fiscal WHERE id = '$filtro_periodo';", $db, $usu));
    $anio_sel = $row_per['ANIO'] ?? '';
}

// ── WHERE ──────────────────────────────────────────────────────────────────
$where = "WHERE 1=1";
if (!empty($filtro_periodo) && $filtro_periodo !== 'null') {
    $where .= " AND b.id_periodo_destino = '$filtro_periodo'";
}
if (!empty($filtro_cc) && $filtro_cc !== 'null') {
    $where .= " AND b.esquema_cc = '$filtro_cc'";
}
if (!empty($filtro_estado) && $filtro_estado !== 'null') {
    $where .= " AND b.estado = '$filtro_estado'";
}

// ── Resumen ────────────────────────────────────────────────────────────────
$sql_resumen = "SELECT b.estado, COUNT(*) AS CANT, SUM(b.monto_estimado) AS MONTO
                FROM pres_sr_borrador b
                $where
                GROUP BY b.estado";
$res_resumen = sql_do_query($sql_resumen, $db, $usu);
$resumen = ['borrador' => ['cant' => 0, 'monto' => 0], 'enviada' => ['cant' => 0, 'monto' => 0], 'descartada' => ['cant' => 0, 'monto' => 0]];
while ($rr = sql_do_array($res_resumen)) {
    $e = strtolower($rr['ESTADO']);
    $resumen[$e] = ['cant' => (int)$rr['CANT'], 'monto' => (float)$rr['MONTO']];
}
$total_borr = $resumen['borrador']['cant'] + $resumen['enviada']['cant'] + $resumen['descartada']['cant'];
$total_monto = $resumen['borrador']['monto'] + $resumen['enviada']['monto'] + $resumen['descartada']['monto'];

// ── Resumen EETT ──────────────────────────────────────────────────────────
$sql_eett_res = "SELECT
                    COUNT(*) AS TOTAL,
                    SUM(CASE WHEN be.eett_denominacion IS NOT NULL AND be.eett_denominacion != '' THEN 1 ELSE 0 END) AS CON_EETT,
                    SUM(CASE WHEN be.justificacion_compra IS NOT NULL AND be.justificacion_compra != '' THEN 1 ELSE 0 END) AS CON_JUSTIF
                 FROM pres_sr_borrador b
                 LEFT JOIN pres_sr_borrador_eett be ON be.id_borrador = b.id
                 $where";
$row_eett_res = sql_do_array(sql_do_query($sql_eett_res, $db, $usu));
$eett_total = (int)($row_eett_res['TOTAL'] ?? 0);
$eett_con = (int)($row_eett_res['CON_EETT'] ?? 0);
$eett_sin = $eett_total - $eett_con;
$justif_con = (int)($row_eett_res['CON_JUSTIF'] ?? 0);

// ── Listado ────────────────────────────────────────────────────────────────
$sql_list = "SELECT b.*,
                    ig.codigo AS ITEM_CODIGO, ig.nombre AS ITEM_NOMBRE,
                    uo.nombre AS UNIDAD_NOMBRE, uo.cc AS CC,
                    pf.anio AS ANIO_DESTINO,
                    CASE WHEN be.eett_denominacion IS NOT NULL AND be.eett_denominacion != '' THEN 1 ELSE 0 END AS TIENE_EETT,
                    CASE WHEN be.justificacion_compra IS NOT NULL AND be.justificacion_compra != '' THEN 1 ELSE 0 END AS TIENE_JUSTIF
             FROM pres_sr_borrador b
             LEFT JOIN ciclo_item_gasto ig ON ig.codigo = b.esquema_gastoid
             LEFT JOIN ciclo_unidad_organizativa uo ON uo.cc = b.esquema_cc
             LEFT JOIN ciclo_periodo_fiscal pf ON pf.id = b.id_periodo_destino
             LEFT JOIN pres_sr_borrador_eett be ON be.id_borrador = b.id
             $where
             ORDER BY b.esquema_gastoid, b.estado, b.id";
$res_list = sql_do_query($sql_list, $db, $usu);
$borradores = [];
while ($rb = sql_do_array($res_list)) {
    $borradores[] = $rb;
}

// ── Combos ─────────────────────────────────────────────────────────────────
$sql_periodos = "SELECT id AS ID, CONCAT(anio, ' (', estado, ')') AS NOMBRE FROM ciclo_periodo_fiscal ORDER BY anio DESC;";
$sql_cc = "SELECT DISTINCT uo.cc AS ID, CONCAT(uo.cc, ' - ', uo.nombre) AS NOMBRE
           FROM ciclo_unidad_organizativa uo
           WHERE uo.cc IS NOT NULL AND uo.cc != '' ORDER BY uo.cc;";
?>

<?php echo generarTituloSeccion('Borradores de SR — Proyecciones', 'bi bi-pencil-square'); ?>

<!-- RESUMEN CARDS -->
<div class="row g-3 mb-3">
    <div class="col-md-2">
        <div class="card shadow-sm border-start border-4 border-secondary">
            <div class="card-body py-2">
                <div class="text-muted small">Borradores</div>
                <div class="fs-5 fw-bold"><?php echo $resumen['borrador']['cant']; ?></div>
                <small class="text-muted">U$D <?php echo number_format($resumen['borrador']['monto'], 2, ',', '.'); ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card shadow-sm border-start border-4 border-success">
            <div class="card-body py-2">
                <div class="text-muted small">Enviadas a K2B</div>
                <div class="fs-5 fw-bold text-success"><?php echo $resumen['enviada']['cant']; ?></div>
                <small class="text-muted">U$D <?php echo number_format($resumen['enviada']['monto'], 2, ',', '.'); ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card shadow-sm border-start border-4 border-danger">
            <div class="card-body py-2">
                <div class="text-muted small">Descartadas</div>
                <div class="fs-5 fw-bold text-danger"><?php echo $resumen['descartada']['cant']; ?></div>
                <small class="text-muted">U$D <?php echo number_format($resumen['descartada']['monto'], 2, ',', '.'); ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card shadow-sm border-start border-4 border-primary">
            <div class="card-body py-2">
                <div class="text-muted small">Total</div>
                <div class="fs-5 fw-bold"><?php echo $total_borr; ?></div>
                <small class="text-muted">U$D <?php echo number_format($total_monto, 2, ',', '.'); ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card shadow-sm border-start border-4 border-info">
            <div class="card-body py-2">
                <div class="text-muted small">Con EETT</div>
                <div class="fs-5 fw-bold text-info"><?php echo $eett_con; ?> <small class="text-muted fs-6">/ <?php echo $eett_total; ?></small></div>
                <small class="text-muted"><?php echo $eett_sin; ?> sin completar</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card shadow-sm border-start border-4 border-warning">
            <div class="card-body py-2">
                <div class="text-muted small">Con Justif.</div>
                <div class="fs-5 fw-bold text-warning"><?php echo $justif_con; ?> <small class="text-muted fs-6">/ <?php echo $eett_total; ?></small></div>
                <small class="text-muted"><?php echo ($eett_total - $justif_con); ?> sin completar</small>
            </div>
        </div>
    </div>
</div>

<!-- FILTROS -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Período destino</label>
                <?php echo imp_combo_w('filtro_periodo', $sql_periodos, $db, $usu, $filtro_periodo); ?>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Centro de Costos</label>
                <?php echo imp_combo_w('filtro_cc', $sql_cc, $db, $usu, $filtro_cc); ?>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Estado</label>
                <select class="form-select form-select-sm" name="filtro_estado" onchange="this.form.submit()">
                    <option value="null">Todos</option>
                    <option value="borrador" <?php echo ($filtro_estado === 'borrador') ? 'selected' : ''; ?>>Borrador</option>
                    <option value="enviada" <?php echo ($filtro_estado === 'enviada') ? 'selected' : ''; ?>>Enviada</option>
                    <option value="descartada" <?php echo ($filtro_estado === 'descartada') ? 'selected' : ''; ?>>Descartada</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-pade btn-sm w-100">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </button>
            </div>
            <div class="col-md-2">
                <a href="index.php?estado=<?php echo $estado; ?>" class="btn btn-outline-secondary btn-sm w-100">
                    <i class="bi bi-x-circle me-1"></i>Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- TABLA DE BORRADORES -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark bg-opacity-75 text-white py-2">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-pencil-square me-2"></i>SR Borrador — <?php echo count($borradores); ?> registro<?php echo count($borradores) !== 1 ? 's' : ''; ?></h6>
            <div class="d-flex gap-1">
                <?php if ($eett_sin > 0): ?>
                <button type="button" class="btn btn-sm btn-info" onclick="iaEettMasivoBorr()" id="btnIaMasivoBorr">
                    <i class="bi bi-robot me-1"></i>Auto-completar EETT con IA (<?php echo $eett_sin; ?>)
                </button>
                <?php endif; ?>
                <button type="button" class="btn btn-sm btn-outline-light" onclick="seleccionarTodos(true)"><i class="bi bi-check-all"></i></button>
                <button type="button" class="btn btn-sm btn-outline-light" onclick="seleccionarTodos(false)"><i class="bi bi-x-lg"></i></button>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>" id="formBorradores">
            <input type="hidden" name="accion_borrador" id="accion_borrador" value="">
            <input type="hidden" name="filtro_periodo" value="<?php echo htmlspecialchars($filtro_periodo); ?>">
            <input type="hidden" name="filtro_cc" value="<?php echo htmlspecialchars($filtro_cc); ?>">
            <input type="hidden" name="filtro_estado" value="<?php echo htmlspecialchars($filtro_estado); ?>">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr class="table-light">
                            <th style="width:30px;"><input type="checkbox" id="chkAll" onclick="seleccionarTodos(this.checked)"></th>
                            <th class="text-center">Período</th>
                            <th>Ítem de Gasto</th>
                            <th>CC</th>
                            <th>Matrícula</th>
                            <th>Descripción</th>
                            <th class="text-end">Monto Proy.</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">EETT</th>
                            <th class="text-center">Justif.</th>
                            <th style="width:60px;"></th>
                        </tr>
                    </thead>
                    <tbody>
<?php if (empty($borradores)): ?>
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                No hay borradores de SR para los filtros seleccionados.
                                <br><small>Genere borradores desde <strong>Presupuestos → Generar Borrador SR</strong>.</small>
                            </td>
                        </tr>
<?php else:
    $item_ant = '';
    $gt_ref = 0; $gt_proy = 0;
    foreach ($borradores as $b):
        $item_code = $b['ITEM_CODIGO'] ?? $b['ESQUEMA_GASTOID'];
        $item_name = $b['ITEM_NOMBRE'] ?? '';
        $es_nuevo_item = ($item_code !== $item_ant);
        $item_ant = $item_code;

        $gt_ref  += (float)($b['PRECIO_UNITARIO_REF'] ?? 0);
        $gt_proy += (float)($b['MONTO_ESTIMADO'] ?? 0);

        // Badge estado
        $est_b = $b['ESTADO'] ?? 'borrador';
        switch ($est_b) {
            case 'borrador':   $badge_b = '<span class="badge bg-secondary">Borrador</span>'; break;
            case 'enviada':    $badge_b = '<span class="badge bg-success">Enviada</span>'; break;
            case 'descartada': $badge_b = '<span class="badge bg-danger">Descartada</span>'; break;
            default:           $badge_b = '<span class="badge bg-light text-dark">' . htmlspecialchars($est_b) . '</span>';
        }
?>
<?php if ($es_nuevo_item): ?>
                        <tr class="table-warning" style="font-size:0.78rem;">
                            <td></td>
                            <td colspan="10" class="fw-bold">
                                <i class="bi bi-folder2 me-1"></i>
                                <?php echo htmlspecialchars($item_code); ?> — <?php echo htmlspecialchars($item_name); ?>
                            </td>
                        </tr>
<?php endif; ?>
                        <tr id="borrRow_<?php echo $b['ID']; ?>">
                            <td class="text-center"><input type="checkbox" name="ids[]" value="<?php echo $b['ID']; ?>" class="chk-borrador"></td>
                            <td class="text-center small"><?php echo htmlspecialchars($b['ANIO_DESTINO'] ?? ''); ?></td>
                            <td class="small text-nowrap"><?php echo htmlspecialchars($item_code); ?></td>
                            <td class="small"><?php echo htmlspecialchars($b['CC'] ?? $b['ESQUEMA_CC']); ?></td>
                            <td class="small fw-semibold text-nowrap"><?php echo htmlspecialchars($b['MATRICULA'] ?? ''); ?></td>
                            <td class="small" title="<?php echo htmlspecialchars($b['DESCRIPCION'] ?? ''); ?>">
                                <?php echo htmlspecialchars(mb_substr($b['DESCRIPCION'] ?? '', 0, 50)); ?>
                                <?php echo mb_strlen($b['DESCRIPCION'] ?? '') > 50 ? '…' : ''; ?>
                            </td>
                            <td class="text-end small fw-semibold">U$D <?php echo number_format((float)($b['MONTO_ESTIMADO'] ?? 0), 2, ',', '.'); ?></td>
                            <td class="text-center"><?php echo $badge_b; ?></td>
                            <td class="text-center" id="eettCell_<?php echo $b['ID']; ?>">
                                <?php if ((int)($b['TIENE_EETT'] ?? 0)): ?>
                                    <a href="index.php?estado=<?php echo $estado; ?>&accion=detalle_borrador&id=<?php echo $b['ID']; ?>#collapseEETTBorr" class="badge bg-success text-decoration-none" style="font-size:0.6rem;" title="EETT completadas — clic para editar"><i class="bi bi-check-lg"></i> EETT</a>
                                <?php else: ?>
                                    <a href="index.php?estado=<?php echo $estado; ?>&accion=detalle_borrador&id=<?php echo $b['ID']; ?>#collapseEETTBorr" class="badge bg-light text-danger border text-decoration-none" style="font-size:0.6rem;" title="Sin EETT — clic para completar"><i class="bi bi-exclamation-triangle"></i> Falta</a>
                                <?php endif; ?>
                            </td>
                            <td class="text-center" id="justifCell_<?php echo $b['ID']; ?>">
                                <?php if ((int)($b['TIENE_JUSTIF'] ?? 0)): ?>
                                    <span class="badge bg-success" style="font-size:0.6rem;" title="Justificación completada"><i class="bi bi-check-lg"></i></span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border" style="font-size:0.6rem;" title="Sin justificación"><i class="bi bi-dash"></i></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="index.php?estado=<?php echo $estado; ?>&accion=detalle_borrador&id=<?php echo $b['ID']; ?>" class="btn btn-sm btn-outline-primary py-0 px-1" title="Ver detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
<?php endforeach; ?>
                        <tr class="table-dark fw-bold">
                            <td></td>
                            <td colspan="5" class="text-end">TOTALES</td>
                            <td class="text-end">U$D <?php echo number_format($gt_proy, 2, ',', '.'); ?></td>
                            <td colspan="4"></td>
                        </tr>
<?php endif; ?>
                    </tbody>
                </table>
            </div>

<?php if (!empty($borradores)): ?>
            <!-- ACCIONES -->
            <div class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <i class="bi bi-info-circle me-1"></i>Seleccione borradores y elija una acción.
                </small>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-success btn-sm" onclick="accionBorrador('enviada')">
                        <i class="bi bi-send me-1"></i>Marcar como Enviada
                    </button>
                    <button type="button" class="btn btn-warning btn-sm" onclick="accionBorrador('borrador')">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Volver a Borrador
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="accionBorrador('descartar')">
                        <i class="bi bi-trash me-1"></i>Descartar
                    </button>
                    <?php if ($resumen['descartada']['cant'] > 0): ?>
                    <button type="button" class="btn btn-danger btn-sm" onclick="accionBorrador('eliminar_descartadas')" title="Elimina permanentemente los borradores descartados">
                        <i class="bi bi-x-octagon me-1"></i>Purgar descartadas (<?php echo $resumen['descartada']['cant']; ?>)
                    </button>
                    <?php endif; ?>
                </div>
            </div>
<?php endif; ?>
        </form>
    </div>
</div>

<script>
function seleccionarTodos(checked) {
    document.querySelectorAll('.chk-borrador').forEach(function(cb) { cb.checked = checked; });
    document.getElementById('chkAll').checked = checked;
}

function accionBorrador(accion) {
    var checks = document.querySelectorAll('.chk-borrador:checked');
    if (accion !== 'eliminar_descartadas' && checks.length === 0) {
        alert('Seleccione al menos un borrador.');
        return;
    }
    var msgs = {
        'enviada': '¿Marcar los borradores seleccionados como ENVIADOS a K2B?',
        'borrador': '¿Devolver los seleccionados al estado BORRADOR?',
        'descartar': '¿DESCARTAR los borradores seleccionados?',
        'eliminar_descartadas': '¿ELIMINAR PERMANENTEMENTE todos los borradores descartados?'
    };
    if (confirm(msgs[accion] || '¿Confirma la acción?')) {
        document.getElementById('accion_borrador').value = accion;
        document.getElementById('formBorradores').submit();
    }
}

function iaEettMasivoBorr() {
    var btn = document.getElementById('btnIaMasivoBorr');
    if (!btn) return;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Obteniendo pendientes...';

    var fd = new FormData();
    fd.append('filtro_periodo', '<?php echo addslashes($filtro_periodo ?? ''); ?>');

    fetch('web/mod/solicitudes/borrador_eett_ia_masivo_ajax.php', {
        method: 'POST',
        body: fd
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data.ok || !data.pendientes || data.pendientes.length === 0) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-robot me-1"></i>No hay pendientes';
            if (!data.ok) alert(data.error || 'Error al obtener pendientes.');
            return;
        }

        var pendientes = data.pendientes;
        var total = pendientes.length;
        var completadas = 0;
        var errores = 0;

        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>0 / ' + total;

        function procesarSiguiente(idx) {
            if (idx >= total) {
                btn.disabled = false;
                btn.className = 'btn btn-sm btn-success';
                btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Listo: ' + completadas + ' OK, ' + errores + ' errores';
                return;
            }

            var item = pendientes[idx];
            var row = document.getElementById('borrRow_' + item.id_borrador);
            var eettCell = document.getElementById('eettCell_' + item.id_borrador);
            if (eettCell) {
                eettCell.innerHTML = '<span class="spinner-border spinner-border-sm text-info" style="width:12px;height:12px;"></span>';
            }

            var fd2 = new FormData();
            fd2.append('id_borrador', item.id_borrador);
            fd2.append('matricula', item.matricula);
            fd2.append('descripcion', item.descripcion || '');

            fetch('web/mod/solicitudes/borrador_eett_ia_ajax.php', {
                method: 'POST',
                body: fd2
            })
            .then(function(r2) { return r2.json(); })
            .then(function(res) {
                if (res.ok) {
                    completadas++;
                    if (eettCell) {
                        eettCell.innerHTML = '<span class="badge bg-success" style="font-size:0.6rem;"><i class="bi bi-check-lg"></i> EETT</span>';
                    }
                } else {
                    errores++;
                    if (eettCell) {
                        eettCell.innerHTML = '<span class="badge bg-danger" style="font-size:0.6rem;" title="' + (res.error || 'Error') + '"><i class="bi bi-x-lg"></i></span>';
                    }
                }
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + (completadas + errores) + ' / ' + total;
                procesarSiguiente(idx + 1);
            })
            .catch(function(err) {
                errores++;
                if (eettCell) {
                    eettCell.innerHTML = '<span class="badge bg-danger" style="font-size:0.6rem;" title="' + err.message + '"><i class="bi bi-x-lg"></i></span>';
                }
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + (completadas + errores) + ' / ' + total;
                procesarSiguiente(idx + 1);
            });
        }

        procesarSiguiente(0);
    })
    .catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-robot me-1"></i>Error: ' + err.message;
    });
}
</script>
