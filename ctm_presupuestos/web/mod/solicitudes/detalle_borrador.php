<?php
/**
 * ============================================================================
 * Detalle de un Borrador de SR
 * ============================================================================
 * Tabla: pres_sr_borrador, pres_k2b_sr_logs
 * Muestra la información del borrador y resuelve las matrículas referenciadas
 * contra pres_k2b_sr_logs para mostrar el estado real de cada una.
 * Se accede desde borradores.php con ?accion=detalle_borrador&id=ID
 * ============================================================================
 */

$borrador_id = Enmascarar_get('id');
if (empty($borrador_id)) {
    echo '<div class="alert alert-warning m-3"><i class="bi bi-exclamation-triangle me-2"></i>No se especificó ID de borrador.</div>';
    return;
}

// ── POST: Guardar EETT del borrador ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_eett_borrador']) && $_POST['accion_eett_borrador'] === 'guardar') {
    $bid = addslashes($borrador_id);
    $eett_fields = [
        'eett_denominacion', 'eett_caracteristicas', 'eett_representacion_grafica',
        'eett_normas_fabricacion', 'eett_ensayo_recepcion', 'eett_responsable_recepcion',
        'eett_marcas', 'eett_aplicacion', 'eett_forma_entrega', 'eett_observaciones',
        'eett_proveedores'
    ];
    $cols = ['id_borrador'];
    $vals = ["'$bid'"];
    $sets = [];
    foreach ($eett_fields as $f) {
        $v = addslashes(trim($_POST[$f] ?? ''));
        $cols[] = $f;
        $vals[] = "'$v'";
        $sets[] = "$f = '$v'";
    }
    $justif = addslashes(trim($_POST['justificacion_compra'] ?? ''));
    $cols[] = 'justificacion_compra';
    $vals[] = "'$justif'";
    $sets[] = "justificacion_compra = '$justif'";
    $cols[] = 'auditoria_usu';
    $vals[] = "'$usu'";
    $sets[] = "auditoria_usu = '$usu'";

    $cols_sql = implode(', ', $cols);
    $vals_sql = implode(', ', $vals);
    $sets_sql = implode(', ', $sets);

    $sql_upsert = "INSERT INTO pres_sr_borrador_eett ($cols_sql)
                   VALUES ($vals_sql)
                   ON DUPLICATE KEY UPDATE $sets_sql;";
    sql_do_query($sql_upsert, $db, $usu);
    echo generarAlertJS('Guardado', 'EETT y justificación del borrador actualizados.', 'success');
}

// ── Cargar borrador ─────────────────────────────────────────────────────────
$sql_b = "SELECT b.*,
                 ig.codigo AS ITEM_CODIGO, ig.nombre AS ITEM_NOMBRE,
                 uo.nombre AS UNIDAD_NOMBRE, uo.cc AS CC,
                 pf.anio AS ANIO_DESTINO, pf.estado AS ESTADO_PERIODO
          FROM pres_sr_borrador b
          LEFT JOIN ciclo_item_gasto ig ON ig.codigo = b.esquema_gastoid
          LEFT JOIN ciclo_unidad_organizativa uo ON uo.cc = b.esquema_cc
          LEFT JOIN ciclo_periodo_fiscal pf ON pf.id = b.id_periodo_destino
          WHERE b.id = '$borrador_id'";
$res_b = sql_do_query($sql_b, $db, $usu);
$borr = sql_do_array($res_b);

if (!$borr) {
    echo '<div class="alert alert-warning m-3"><i class="bi bi-exclamation-triangle me-2"></i>No se encontró el borrador #' . htmlspecialchars($borrador_id) . '.</div>';
    return;
}

// ── Parsear matrículas del borrador ─────────────────────────────────────────
$mat_raw = $borr['MATRICULA'] ?? '';
// El campo puede tener formato: "421702, 322791, 322850... +3"
$mat_clean = preg_replace('/\.\.\.\s*\+\d+$/', '', $mat_raw);
$matriculas_lista = array_filter(array_map('trim', explode(',', $mat_clean)));

// ── Parsear SRs de origen ───────────────────────────────────────────────────
$sr_orig_raw = $borr['SR_ORIGEN_CODIGO'] ?? '';
$sr_codigos_lista = array_filter(array_map('trim', explode(',', $sr_orig_raw)));

// ── Cargar datos reales de las matrículas desde pres_k2b_sr_logs ────────────
// Buscar las líneas más recientes por matrícula+item del período origen
$lineas_sr = [];
if (!empty($matriculas_lista)) {
    $mat_escaped = array_map(function($m) { return "'" . addslashes($m) . "'"; }, $matriculas_lista);
    $mat_in = implode(',', $mat_escaped);
    $item_code = $borr['ESQUEMA_GASTOID'];

    $sql_sr = "SELECT sr.*,
                      ig.codigo AS ITEM_CODIGO, ig.nombre AS ITEM_NOMBRE,
                      uo.nombre AS UNIDAD_NOMBRE,
                      (sr.precio_unitario * sr.cantidad_autorizada) AS IMPORTE_LINEA,
                      cg.nombre AS CAT_NOMBRE
               FROM pres_k2b_sr_logs sr
               LEFT JOIN ciclo_item_gasto ig ON sr.esquema_gastoid = ig.codigo
               LEFT JOIN ciclo_unidad_organizativa uo ON sr.esquema_cc = uo.cc
               LEFT JOIN pres_k2b_matricula_categoria mc ON mc.matricula = sr.matricula
               LEFT JOIN ciclo_categoria_gasto cg ON cg.id = mc.id_categoria
               WHERE sr.matricula IN ($mat_in)
                 AND sr.esquema_gastoid = '$item_code'
               ORDER BY sr.matricula, sr.fecha DESC, sr.codigo DESC";
    $res_sr = sql_do_query($sql_sr, $db, $usu);

    // Deduplicar: tomar la línea más reciente por matrícula
    $seen = [];
    while ($r = sql_do_array($res_sr)) {
        $mat_key = $r['MATRICULA'];
        if (!isset($seen[$mat_key])) {
            $seen[$mat_key] = true;
            $lineas_sr[] = $r;
        }
    }
}

// ── Totales de las líneas resueltas ─────────────────────────────────────
$total_importe = 0;
foreach ($lineas_sr as $l) {
    $total_importe += (float)($l['IMPORTE_LINEA'] ?? 0);
}

// Badge estado borrador
$est_borr = $borr['ESTADO'] ?? 'borrador';
switch ($est_borr) {
    case 'borrador':   $badge_borr = '<span class="badge bg-secondary fs-6">Borrador</span>'; break;
    case 'enviada':    $badge_borr = '<span class="badge bg-success fs-6">Enviada</span>'; break;
    case 'descartada': $badge_borr = '<span class="badge bg-danger fs-6">Descartada</span>'; break;
    default:           $badge_borr = '<span class="badge bg-light text-dark fs-6">' . htmlspecialchars($est_borr) . '</span>';
}

// ── Cargar EETT del borrador (tabla paralela) ────────────────────────────────
$bid_esc = addslashes($borrador_id);
$sql_eett_borr = "SELECT * FROM pres_sr_borrador_eett WHERE id_borrador = '$bid_esc'";
$res_eett_borr = sql_do_query($sql_eett_borr, $db, $usu);
$eett_borr = sql_do_array($res_eett_borr);

// Si no hay EETT del borrador, pre-cargar desde la matrícula principal
$eett_source = 'borrador'; // 'borrador' o 'matricula'
if (!$eett_borr) {
    $eett_borr = [];
    $eett_source = 'matricula';
    // Buscar EETT de la primera matrícula que tenga datos
    if (!empty($matriculas_lista)) {
        $mat_esc_list = array_map(function($m) { return "'" . addslashes($m) . "'"; }, $matriculas_lista);
        $mat_in_eett = implode(',', $mat_esc_list);
        $sql_eett_mat = "SELECT * FROM pres_k2b_matricula_categoria
                         WHERE matricula IN ($mat_in_eett)
                           AND eett_denominacion IS NOT NULL AND eett_denominacion != ''
                         LIMIT 1;";
        $res_eett_mat = sql_do_query($sql_eett_mat, $db, $usu);
        $eett_borr = sql_do_array($res_eett_mat) ?: [];
    }
}

$eett_campos = [
    'eett_denominacion'           => ['label' => 'Denominación del Material',    'type' => 'textarea', 'rows' => 2],
    'eett_caracteristicas'        => ['label' => 'Características Nominales',    'type' => 'textarea', 'rows' => 3],
    'eett_representacion_grafica' => ['label' => 'Representación Gráfica',       'type' => 'textarea', 'rows' => 2],
    'eett_normas_fabricacion'     => ['label' => 'Normas de Fabricación',        'type' => 'textarea', 'rows' => 2],
    'eett_ensayo_recepcion'       => ['label' => 'Ensayo para la Recepción',     'type' => 'textarea', 'rows' => 2],
    'eett_responsable_recepcion'  => ['label' => 'Responsable de la Recepción',  'type' => 'input',    'rows' => 1],
    'eett_marcas'                 => ['label' => 'Marcas',                       'type' => 'textarea', 'rows' => 2],
    'eett_aplicacion'             => ['label' => 'Aplicación',                   'type' => 'textarea', 'rows' => 2],
    'eett_forma_entrega'          => ['label' => 'Forma de Entrega',             'type' => 'textarea', 'rows' => 2],
    'eett_observaciones'          => ['label' => 'Observaciones',                'type' => 'textarea', 'rows' => 2],
    'eett_proveedores'            => ['label' => 'Proveedores',                  'type' => 'textarea', 'rows' => 2],
];

$tiene_eett_borr = false;
foreach ($eett_campos as $k => $v) {
    if (!empty($eett_borr[strtoupper($k)] ?? '')) { $tiene_eett_borr = true; break; }
}
$tiene_justif_borr = !empty($eett_borr['JUSTIFICACION_COMPRA'] ?? '');
?>

<!-- Botón volver -->
<div class="mb-3">
    <a href="index.php?estado=<?php echo $estado; ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver a Borradores
    </a>
</div>

<?php echo generarTituloSeccion('Borrador SR #' . htmlspecialchars($borrador_id), 'bi bi-pencil-square'); ?>

<!-- CABECERA DEL BORRADOR -->
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-dark bg-opacity-75 text-white py-2">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Información del Borrador</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="small text-muted">ID Borrador</div>
                        <div class="fw-bold fs-5">#<?php echo htmlspecialchars($borrador_id); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Estado</div>
                        <div><?php echo $badge_borr; ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Período Destino</div>
                        <div class="fw-semibold"><?php echo htmlspecialchars($borr['ANIO_DESTINO'] ?? ''); ?>
                            <span class="badge bg-info text-dark ms-1" style="font-size:0.65rem;"><?php echo htmlspecialchars($borr['ESTADO_PERIODO'] ?? ''); ?></span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Ítem de Gasto</div>
                        <div>
                            <span class="fw-bold"><?php echo htmlspecialchars($borr['ITEM_CODIGO'] ?? $borr['ESQUEMA_GASTOID']); ?></span>
                            <span class="text-muted mx-1">—</span>
                            <?php echo htmlspecialchars($borr['ITEM_NOMBRE'] ?? ''); ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Centro de Costos</div>
                        <div class="fw-semibold"><?php echo htmlspecialchars($borr['CC'] ?? $borr['ESQUEMA_CC']); ?> — <?php echo htmlspecialchars($borr['UNIDAD_NOMBRE'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Descripción</div>
                        <div class="small"><?php echo htmlspecialchars($borr['DESCRIPCION'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">SR Origen</div>
                        <div class="small">
                            <?php foreach ($sr_codigos_lista as $src): ?>
                                <a href="index.php?estado=401&accion=detalle&sr=<?php echo urlencode(trim($src)); ?>" class="badge bg-outline-primary text-primary border me-1" title="Ver SR <?php echo htmlspecialchars(trim($src)); ?>">
                                    <?php echo htmlspecialchars(trim($src)); ?>
                                </a>
                            <?php endforeach; ?>
                            <?php if (empty($sr_codigos_lista)): ?><span class="text-muted">—</span><?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Creado</div>
                        <div class="small"><?php echo enmascararfecha($borr['AUDITORIA_FECHA'] ?? ''); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen -->
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-info text-dark py-2">
                <h6 class="mb-0"><i class="bi bi-calculator me-2"></i>Resumen Proyección</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Matrículas</span>
                    <span class="fw-bold"><?php echo count($matriculas_lista); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">P. Unitario Ref.</span>
                    <span class="fw-semibold">U$D <?php echo number_format((float)($borr['PRECIO_UNITARIO_REF'] ?? 0), 2, ',', '.'); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Factor Aplicado</span>
                    <span class="fw-semibold">×<?php echo number_format((float)($borr['FACTOR_APLICADO'] ?? 1), 4); ?></span>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Monto Proyectado</span>
                    <span class="fw-bold text-primary fs-5">U$D <?php echo number_format((float)($borr['MONTO_ESTIMADO'] ?? 0), 2, ',', '.'); ?></span>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between">
                    <span class="text-muted small">Σ Importe SR origen</span>
                    <span class="fw-semibold">U$D <?php echo number_format($total_importe, 2, ',', '.'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MATRÍCULAS REFERENCIADAS (datos reales de SR origen) -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary bg-opacity-75 text-white py-2">
        <h6 class="mb-0"><i class="bi bi-list-ol me-2"></i>Matrículas del Borrador — <?php echo count($lineas_sr); ?> encontrada<?php echo count($lineas_sr) !== 1 ? 's' : ''; ?> de <?php echo count($matriculas_lista); ?></h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr class="table-light">
                        <th class="text-center" style="width:30px;">#</th>
                        <th>Matrícula</th>
                        <th>Descripción</th>
                        <th>Categoría</th>
                        <th class="text-center">SR Origen</th>
                        <th class="text-center">Estado SR</th>
                        <th class="text-end">P. Unit.</th>
                        <th class="text-end">Importe</th>
                    </tr>
                </thead>
                <tbody>
<?php if (empty($lineas_sr)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-3">
                            <i class="bi bi-inbox d-block fs-4 mb-1"></i>
                            No se encontraron datos de SR para las matrículas referenciadas.
                        </td>
                    </tr>
<?php else:
    $n = 0;
    foreach ($lineas_sr as $l):
        $n++;
        $imp = (float)($l['IMPORTE_LINEA'] ?? 0);
        $est = $l['ESTADO'] ?? '';
        $bc = 'bg-primary';
        if ($est === 'RECHAZADA') $bc = 'bg-danger';
        elseif ($est === 'FINALIZADA') $bc = 'bg-secondary';
        elseif ($est === 'CUMPLIDA' || $est === 'CUMPLIMIENTOSTOCK') $bc = 'bg-success';
        elseif ($est === 'ENPROCESOCOMPRA' || $est === 'ENPROCESOAUTORIZACION') $bc = 'bg-warning text-dark';
?>
                    <tr>
                        <td class="text-center fw-bold"><?php echo $n; ?></td>
                        <td class="small fw-semibold text-nowrap"><?php echo htmlspecialchars($l['MATRICULA'] ?? ''); ?></td>
                        <td class="small" title="<?php echo htmlspecialchars($l['DESCRIPCION'] ?? ''); ?>">
                            <?php echo htmlspecialchars(mb_substr($l['DESCRIPCION'] ?? '', 0, 60)); ?><?php echo (mb_strlen($l['DESCRIPCION'] ?? '') > 60) ? '…' : ''; ?>
                        </td>
                        <td class="small"><?php echo htmlspecialchars($l['CAT_NOMBRE'] ?? ''); ?></td>
                        <td class="text-center">
                            <a href="index.php?estado=401&accion=detalle&sr=<?php echo urlencode($l['CODIGO'] ?? ''); ?>" class="text-decoration-none" title="Ver SR">
                                <small><?php echo htmlspecialchars($l['CODIGO'] ?? ''); ?></small>
                            </a>
                        </td>
                        <td class="text-center">
                            <span class="badge <?php echo $bc; ?>" style="font-size:0.65rem;"><?php echo htmlspecialchars($est); ?></span>
                        </td>
                        <td class="text-end small">U$D <?php echo number_format((float)($l['PRECIO_UNITARIO'] ?? 0), 2, ',', '.'); ?></td>
                        <td class="text-end small fw-semibold">U$D <?php echo number_format($imp, 2, ',', '.'); ?></td>
                    </tr>
<?php endforeach; ?>
                    <tr class="table-dark fw-bold">
                        <td colspan="7" class="text-end">TOTALES</td>
                        <td class="text-end">U$D <?php echo number_format($total_importe, 2, ',', '.'); ?></td>
                    </tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MATRÍCULAS NO ENCONTRADAS (si las hay) -->
<?php
$mat_encontradas = array_column($lineas_sr, 'MATRICULA');
$mat_faltantes = array_diff($matriculas_lista, $mat_encontradas);
if (!empty($mat_faltantes)):
?>
<div class="card shadow-sm mb-4 border-warning">
    <div class="card-header bg-warning bg-opacity-25 py-2">
        <h6 class="mb-0"><i class="bi bi-exclamation-triangle me-2 text-warning"></i>Matrículas sin datos en SR — <?php echo count($mat_faltantes); ?></h6>
    </div>
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-1">
            <?php foreach ($mat_faltantes as $mf): ?>
                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($mf); ?></span>
            <?php endforeach; ?>
        </div>
        <small class="text-muted d-block mt-2">Estas matrículas figuran en el borrador pero no se encontraron en pres_k2b_sr_logs para el ítem <?php echo htmlspecialchars($borr['ITEM_CODIGO'] ?? $borr['ESQUEMA_GASTOID']); ?>.</small>
    </div>
</div>
<?php endif; ?>

<!-- ESPECIFICACIONES TÉCNICAS (EETT) Y JUSTIFICACIÓN DEL BORRADOR -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark bg-opacity-75 text-white py-2 d-flex justify-content-between align-items-center" role="button" data-bs-toggle="collapse" data-bs-target="#collapseEETTBorr">
        <h6 class="mb-0">
            <i class="bi bi-file-earmark-ruled me-2"></i>Especificaciones Técnicas (EETT) y Justificación
            <?php if ($tiene_eett_borr): ?><span class="badge bg-success ms-2" style="font-size:0.6rem;">Completado</span><?php endif; ?>
            <?php if ($eett_source === 'matricula' && $tiene_eett_borr): ?><span class="badge bg-info ms-1" style="font-size:0.6rem;">Pre-cargado de matrícula</span><?php endif; ?>
        </h6>
        <i class="bi bi-chevron-down"></i>
    </div>
    <div class="collapse <?php echo ($tiene_eett_borr || $tiene_justif_borr) ? 'show' : ''; ?>" id="collapseEETTBorr">
        <div class="card-body">
            <?php if ($eett_source === 'matricula' && $tiene_eett_borr): ?>
            <div class="alert alert-info small py-2 mb-3">
                <i class="bi bi-info-circle me-1"></i>Los campos fueron pre-cargados desde la matrícula <strong><?php echo htmlspecialchars($matriculas_lista[0] ?? ''); ?></strong>.
                Puede editarlos y guardarlos para este borrador específico.
            </div>
            <?php endif; ?>

            <form method="post" action="index.php?estado=<?php echo $estado; ?>&accion=detalle_borrador&id=<?php echo urlencode($borrador_id); ?>">
                <input type="hidden" name="accion_eett_borrador" value="guardar">

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0"><i class="bi bi-file-earmark-ruled me-1"></i>Campos EETT</h6>
                    <div class="d-flex gap-1">
                        <?php if ($tiene_eett_borr && $eett_source === 'borrador'): ?>
                        <a href="web/mod/solicitudes/imprimir_eett.php?tipo=borrador&id=<?php echo urlencode($borrador_id); ?>"
                           target="_blank" class="btn btn-outline-dark btn-sm">
                            <i class="bi bi-printer me-1"></i>Imprimir
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($matriculas_lista)): ?>
                        <button type="button" class="btn btn-outline-info btn-sm" id="btnIaEETTBorr" onclick="iaCompletarEETTBorr()">
                            <i class="bi bi-robot me-1"></i>Auto-completar con IA
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div id="iaEettBorrMsg" class="d-none mb-2"></div>
                <div class="row g-3">
                    <?php foreach ($eett_campos as $campo_key => $campo_cfg):
                        $val = htmlspecialchars($eett_borr[strtoupper($campo_key)] ?? '');
                    ?>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small mb-1"><?php echo $campo_cfg['label']; ?></label>
                        <?php if ($campo_cfg['type'] === 'input'): ?>
                            <input type="text" class="form-control form-control-sm" name="<?php echo $campo_key; ?>"
                                   value="<?php echo $val; ?>" placeholder="<?php echo $campo_cfg['label']; ?>">
                        <?php else: ?>
                            <textarea class="form-control form-control-sm" name="<?php echo $campo_key; ?>"
                                      rows="<?php echo $campo_cfg['rows']; ?>" placeholder="<?php echo $campo_cfg['label']; ?>"><?php echo $val; ?></textarea>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <hr class="my-3">

                <h6 class="fw-bold mb-2"><i class="bi bi-justify-left me-1"></i>Justificación de Compra</h6>
                <div class="mb-3">
                    <textarea class="form-control" name="justificacion_compra" rows="4"
                              placeholder="Describa la justificación de compra para este borrador..."><?php echo htmlspecialchars($eett_borr['JUSTIFICACION_COMPRA'] ?? ''); ?></textarea>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-floppy me-1"></i>Guardar EETT y Justificación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (!empty($matriculas_lista)): ?>
<script>
function iaCompletarEETTBorr() {
    var btn = document.getElementById('btnIaEETTBorr');
    var msgDiv = document.getElementById('iaEettBorrMsg');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Consultando IA...';

    msgDiv.className = 'alert alert-info small mb-2';
    msgDiv.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Consultando al modelo <strong><?php echo defined("OLLAMA_MODEL") ? OLLAMA_MODEL : "qwen3.5:9b"; ?></strong>... Esto puede tardar varios segundos.';

    var formData = new FormData();
    formData.append('matricula', '<?php echo addslashes($matriculas_lista[0] ?? ''); ?>');
    formData.append('descripcion_renglon', '<?php echo addslashes($borrador['DESCRIPCION'] ?? ''); ?>');

    fetch('web/mod/configuracion/matricula_eett_ia_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(function(resp) { return resp.json(); })
    .then(function(data) {
        if (!data.ok) {
            msgDiv.className = 'alert alert-danger small mb-2';
            msgDiv.innerHTML = '<i class="bi bi-x-circle me-1"></i>' + (data.error || 'Error desconocido');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-robot me-1"></i>Reintentar IA';
            return;
        }

        var campos = data.campos;
        var rellenados = 0;
        for (var key in campos) {
            var el = document.querySelector('[name="' + key + '"]');
            if (el && campos[key]) {
                if (!el.value || el.value.trim() === '') {
                    el.value = campos[key];
                    el.style.borderColor = '#0dcaf0';
                    rellenados++;
                } else {
                    el.setAttribute('data-ia-sugerido', campos[key]);
                    el.style.borderColor = '#fd7e14';
                    el.title = 'IA sugiere: ' + campos[key];
                }
            }
        }

        msgDiv.className = 'alert alert-success small mb-2';
        msgDiv.innerHTML = '<i class="bi bi-check-circle me-1"></i>IA completó <strong>' + rellenados + '</strong> campo(s) vacío(s).' +
            ' Los campos con borde naranja ya tenían valor — pase el mouse para ver la sugerencia.' +
            '<br><small class="text-muted">Modelo: ' + (data.modelo || '?') + '. Revise y ajuste antes de guardar.</small>';

        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-robot me-1"></i>Reintentar IA';
    })
    .catch(function(err) {
        msgDiv.className = 'alert alert-danger small mb-2';
        msgDiv.innerHTML = '<i class="bi bi-x-circle me-1"></i>Error de conexión: ' + err.message +
            '<br><small>Verifique que Ollama esté corriendo en <?php echo defined("OLLAMA_HOST") ? OLLAMA_HOST : "http://localhost:11434"; ?></small>';
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-robot me-1"></i>Reintentar IA';
    });
}
</script>
<?php endif; ?>
