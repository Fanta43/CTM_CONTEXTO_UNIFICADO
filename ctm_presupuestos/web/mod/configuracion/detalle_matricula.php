<?php
/**
 * ============================================================================
 * Detalle de Matrícula K2B
 * ============================================================================
 * Tabla: pres_k2b_sr_logs, pres_k2b_matricula_categoria
 * Muestra un encabezado con datos resumidos de la matrícula y el historial
 * completo de SRs en las que aparece.
 * Se accede desde matriculas.php con ?accion=detalle_matricula&mat=XXXXX
 * ============================================================================
 */

$matricula = Enmascarar_get('mat');
if (empty($matricula)) {
    echo '<div class="alert alert-warning m-3"><i class="bi bi-exclamation-triangle me-2"></i>No se especificó matrícula.</div>';
    return;
}

$mat_esc = addslashes($matricula);
$msg_eett = '';

// ── Procesar POST: Guardar EETT y Justificación ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['accion_eett']) && $_POST['accion_eett'] === 'guardar') {
    $campos_eett = [
        'eett_denominacion', 'eett_caracteristicas', 'eett_representacion_grafica',
        'eett_normas_fabricacion', 'eett_ensayo_recepcion', 'eett_responsable_recepcion',
        'eett_marcas', 'eett_aplicacion', 'eett_forma_entrega', 'eett_observaciones',
        'eett_proveedores', 'justificacion_compra'
    ];
    $sets = [];
    $cols = [];
    $vals = [];
    foreach ($campos_eett as $campo) {
        $val = addslashes($_POST[$campo] ?? '');
        $sets[] = "$campo = '$val'";
        $cols[] = $campo;
        $vals[] = "'$val'";
    }
    // Categoría
    $id_cat_post = Enmascarar_post('id_categoria');
    if (empty($id_cat_post) || $id_cat_post === 'null') {
        $sets[] = "id_categoria = NULL";
        $cat_col = "id_categoria";
        $cat_val = "NULL";
    } else {
        $sets[] = "id_categoria = '$id_cat_post'";
        $cat_col = "id_categoria";
        $cat_val = "'$id_cat_post'";
    }

    $sets[] = "auditoria_usu = '$usu'";
    $sets_sql = implode(', ', $sets);
    $cols_sql = implode(', ', $cols);
    $vals_sql = implode(', ', $vals);

    // Upsert: si ya existe el registro lo actualiza, si no lo crea
    $sql_upsert = "INSERT INTO pres_k2b_matricula_categoria (matricula, $cols_sql, $cat_col, auditoria_usu)
                   VALUES ('$mat_esc', $vals_sql, $cat_val, '$usu')
                   ON DUPLICATE KEY UPDATE $sets_sql;";
    sql_do_query($sql_upsert, $db, $usu);
    $msg_eett = generarAlertJS('Guardado', 'Especificaciones técnicas y justificación actualizadas.', 'success');
}
echo $msg_eett;

// ── Datos de cabecera (resumen agrupado) ────────────────────────────────────
$sql_cab = "SELECT sr.matricula AS MATRICULA,
                   MIN(sr.descripcion) AS DESCRIPCION,
                   ig.codigo AS ITEM_CODIGO,
                   ig.nombre AS ITEM_NOMBRE,
                   COUNT(DISTINCT sr.codigo) AS CANT_SR,
                   COUNT(*) AS CANT_LINEAS,
                   SUM(sr.monto_linea_oc_usd) AS TOTAL_USD,
                   MAX(sr.fecha) AS ULTIMA_COMPRA,
                   MIN(sr.fecha) AS PRIMERA_COMPRA,
                   MIN(sr.matricula_link) AS MATRICULA_LINK,
                   mc.id_categoria AS CAT_ID,
                   cg.nombre AS CAT_NOMBRE,
                   cg.codigo AS CAT_CODIGO,
                   (SELECT ult.precio_unitario
                    FROM pres_k2b_sr_logs ult
                    WHERE ult.matricula = sr.matricula
                      AND ult.esquema_gastoid = ig.codigo
                      AND ult.monto_linea_oc_usd > 0
                    ORDER BY ult.fecha DESC, ult.codigo DESC LIMIT 1) AS ULTIMO_PU_OC,
                   (SELECT ult2.precio_unitario
                    FROM pres_k2b_sr_logs ult2
                    WHERE ult2.matricula = sr.matricula
                      AND ult2.esquema_gastoid = ig.codigo
                      AND ult2.precio_unitario > 0
                    ORDER BY ult2.fecha DESC, ult2.codigo DESC LIMIT 1) AS ULTIMO_PU_SOL
            FROM pres_k2b_sr_logs sr
            INNER JOIN ciclo_item_gasto ig ON sr.esquema_gastoid = ig.codigo
            LEFT JOIN pres_k2b_matricula_categoria mc ON mc.matricula = sr.matricula
            LEFT JOIN ciclo_categoria_gasto cg ON cg.id = mc.id_categoria
            WHERE sr.matricula = '$mat_esc'
            GROUP BY sr.matricula, ig.codigo, ig.nombre, mc.id_categoria, cg.nombre, cg.codigo
            LIMIT 1;";
$res_cab = sql_do_query($sql_cab, $db, $usu);
$cab = sql_do_array($res_cab);

if (!$cab) {
    echo '<div class="alert alert-warning m-3"><i class="bi bi-exclamation-triangle me-2"></i>No se encontraron datos para la matrícula <strong>' . htmlspecialchars($matricula) . '</strong>.</div>';
    return;
}

// ── Datos EETT, categoría y justificación existentes ────────────────────────
$sql_eett = "SELECT mc.id_categoria, mc.eett_denominacion, mc.eett_caracteristicas,
                    mc.eett_representacion_grafica, mc.eett_normas_fabricacion,
                    mc.eett_ensayo_recepcion, mc.eett_responsable_recepcion,
                    mc.eett_marcas, mc.eett_aplicacion, mc.eett_forma_entrega,
                    mc.eett_observaciones, mc.eett_proveedores, mc.justificacion_compra,
                    cg.id_item AS CAT_ITEM_ID
             FROM pres_k2b_matricula_categoria mc
             LEFT JOIN ciclo_categoria_gasto cg ON cg.id = mc.id_categoria
             WHERE mc.matricula = '$mat_esc'";
$res_eett = sql_do_query($sql_eett, $db, $usu);
$eett = sql_do_array($res_eett);
if (!$eett) $eett = [];

$cat_id_actual = $eett['ID_CATEGORIA'] ?? ($cab['CAT_ID'] ?? '');

// ── Cargar ítems y categorías para combos ───────────────────────────────────
$sql_items = "SELECT id, codigo, nombre FROM ciclo_item_gasto ORDER BY codigo;";
$res_items = sql_do_query($sql_items, $db, $usu);
$items_combo = [];
while ($ri = sql_do_array($res_items)) { $items_combo[] = $ri; }

$sql_cats = "SELECT cg.id, cg.id_item, cg.codigo, cg.nombre, ig.codigo AS item_codigo
             FROM ciclo_categoria_gasto cg
             JOIN ciclo_item_gasto ig ON cg.id_item = ig.id
             WHERE cg.activo = 1
             ORDER BY ig.codigo, cg.codigo;";
$res_cats = sql_do_query($sql_cats, $db, $usu);
$categorias_all = [];
while ($rc = sql_do_array($res_cats)) { $categorias_all[] = $rc; }

// Agrupar categorías por item_id para JSON
$cats_por_item_id = [];
foreach ($categorias_all as $c) {
    $cats_por_item_id[$c['ID_ITEM']][] = ['id' => $c['ID'], 'codigo' => $c['CODIGO'], 'nombre' => $c['NOMBRE']];
}

// Determinar el item_id actual (del que viene la categoría o de la SR)
$item_id_actual = $eett['CAT_ITEM_ID'] ?? ($cab['ITEM_ID'] ?? '');
// Si no hay item_id desde categoría, buscar por código de item
if (empty($item_id_actual) && !empty($cab['ITEM_CODIGO'])) {
    foreach ($items_combo as $ic) {
        if ($ic['CODIGO'] == $cab['ITEM_CODIGO']) { $item_id_actual = $ic['ID']; break; }
    }
}

// ── Historial de SRs ────────────────────────────────────────────────────────
$sql_hist = "SELECT sr.codigo AS CODIGO, sr.lin AS LIN,
                    sr.descripcion AS DESCRIPCION,
                    sr.estado AS ESTADO, sr.fecha AS FECHA,
                    sr.precio_unitario AS PRECIO_UNITARIO,
                    sr.cantidad_solicitada AS CANT_SOL,
                    sr.cantidad_autorizada AS CANT_AUT,
                    sr.cantidad_comprada AS CANT_COMP,
                    sr.cantidad_recepcionada AS CANT_REC,
                    sr.monto_linea_oc_usd AS MONTO_OC_USD,
                    sr.orden_compra AS ORDEN_COMPRA,
                    sr.proveedor AS PROVEEDOR,
                    sr.usuario AS USUARIO,
                    sr.gestor AS GESTOR,
                    sr.fecha_entrega_sol AS FECHA_ENTREGA_SOL,
                    sr.fecha_entrega_fin AS FECHA_ENTREGA_FIN,
                    sr.esquema AS ESQUEMA,
                    uo.nombre AS UNIDAD_NOMBRE
             FROM pres_k2b_sr_logs sr
             LEFT JOIN ciclo_unidad_organizativa uo ON sr.esquema_cc = uo.cc
             WHERE sr.matricula = '$mat_esc'
             ORDER BY sr.fecha DESC, sr.codigo DESC;";
$res_hist = sql_do_query($sql_hist, $db, $usu);
$historial = [];
while ($rh = sql_do_array($res_hist)) {
    $historial[] = $rh;
}

// Precio referencia para mostrar
$pu_display = 0;
$pu_label = '';
if (!empty($cab['ULTIMO_PU_OC']) && (float)$cab['ULTIMO_PU_OC'] > 0) {
    $pu_display = (float)$cab['ULTIMO_PU_OC'];
    $pu_label = 'Último P.U. (OC)';
} elseif (!empty($cab['ULTIMO_PU_SOL']) && (float)$cab['ULTIMO_PU_SOL'] > 0) {
    $pu_display = (float)$cab['ULTIMO_PU_SOL'];
    $pu_label = 'Último P.U. (Sol.)';
}
?>

<!-- Botón volver -->
<div class="mb-3">
    <a href="index.php?estado=<?php echo $estado; ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver a Matrículas
    </a>
</div>

<?php echo generarTituloSeccion('Matrícula ' . htmlspecialchars($matricula), 'bi bi-box-seam'); ?>

<!-- ENCABEZADO DE LA MATRÍCULA -->
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white py-2">
                <h6 class="mb-0"><i class="bi bi-box-seam me-2"></i>Información de la Matrícula</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="small text-muted">Matrícula</div>
                        <div class="fw-bold fs-5">
                            <?php if (!empty($cab['MATRICULA_LINK']) && $cab['MATRICULA_LINK'] !== 'NULL'): ?>
                                <a href="<?php echo htmlspecialchars($cab['MATRICULA_LINK']); ?>" target="_blank" class="text-decoration-none">
                                    <?php echo htmlspecialchars($matricula); ?>
                                    <i class="bi bi-box-arrow-up-right" style="font-size:0.7rem;"></i>
                                </a>
                            <?php else: ?>
                                <?php echo htmlspecialchars($matricula); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="small text-muted">Descripción</div>
                        <div class="fw-semibold"><?php echo htmlspecialchars($cab['DESCRIPCION'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Ítem de Gasto</div>
                        <div>
                            <span class="badge bg-info text-dark me-1"><?php echo htmlspecialchars($cab['ITEM_CODIGO']); ?></span>
                            <?php echo htmlspecialchars($cab['ITEM_NOMBRE'] ?? ''); ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Categoría</div>
                        <div>
                            <?php if (!empty($cab['CAT_NOMBRE'])): ?>
                                <span class="badge bg-success me-1"><?php echo htmlspecialchars($cab['CAT_CODIGO'] ?? ''); ?></span>
                                <?php echo htmlspecialchars($cab['CAT_NOMBRE']); ?>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Sin categoría</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Primera / Última SR</div>
                        <div class="small">
                            <?php echo enmascararfecha($cab['PRIMERA_COMPRA'] ?? ''); ?>
                            <span class="text-muted mx-1">→</span>
                            <?php echo enmascararfecha($cab['ULTIMA_COMPRA'] ?? ''); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen numérico -->
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-info text-dark py-2">
                <h6 class="mb-0"><i class="bi bi-calculator me-2"></i>Resumen</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">SR vinculadas</span>
                    <span class="fw-bold"><?php echo (int)$cab['CANT_SR']; ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Líneas totales</span>
                    <span class="fw-bold"><?php echo (int)$cab['CANT_LINEAS']; ?></span>
                </div>
                <hr class="my-2">
                <?php if ($pu_display > 0): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small"><?php echo $pu_label; ?></span>
                    <span class="fw-bold">U$D <?php echo number_format($pu_display, 2, ',', '.'); ?></span>
                </div>
                <?php endif; ?>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Total OC (USD)</span>
                    <span class="fw-bold text-success">U$D <?php echo number_format((float)($cab['TOTAL_USD'] ?? 0), 2, ',', '.'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- HISTORIAL DE SRs -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark bg-opacity-75 text-white py-2">
        <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Historial de SR — <?php echo count($historial); ?> registro<?php echo count($historial) !== 1 ? 's' : ''; ?></h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr class="table-light">
                        <th class="text-center">SR</th>
                        <th class="text-center">Lín.</th>
                        <th>Descripción</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Fecha</th>
                        <th class="text-end">P. Unit.</th>
                        <th class="text-center">Cant. Sol.</th>
                        <th class="text-center">Cant. Aut.</th>
                        <th style="border-left:2px solid #6ec9a0;">OC</th>
                        <th class="text-end">Monto OC</th>
                        <th>Proveedor</th>
                        <th>Unidad</th>
                    </tr>
                </thead>
                <tbody>
<?php if (empty($historial)): ?>
                    <tr>
                        <td colspan="12" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            No se encontraron registros de SR para esta matrícula.
                        </td>
                    </tr>
<?php else:
    $gt_monto_oc = 0;
    foreach ($historial as $h):
        $moc = (float)($h['MONTO_OC_USD'] ?? 0);
        $gt_monto_oc += $moc;
        $est = $h['ESTADO'] ?? '';
        $bc = 'bg-primary';
        if ($est === 'RECHAZADA') $bc = 'bg-danger';
        elseif ($est === 'FINALIZADA') $bc = 'bg-secondary';
        elseif ($est === 'CUMPLIDA' || $est === 'CUMPLIMIENTOSTOCK') $bc = 'bg-success';
        elseif ($est === 'ENPROCESOCOMPRA' || $est === 'ENPROCESOAUTORIZACION') $bc = 'bg-warning text-dark';
?>
                    <tr>
                        <td class="text-center">
                            <a href="index.php?estado=401&accion=detalle&sr=<?php echo urlencode($h['CODIGO']); ?>" class="text-decoration-none fw-semibold" title="Ver SR <?php echo htmlspecialchars($h['CODIGO']); ?>">
                                <?php echo htmlspecialchars($h['CODIGO']); ?>
                            </a>
                        </td>
                        <td class="text-center"><?php echo htmlspecialchars($h['LIN'] ?? ''); ?></td>
                        <td class="small" title="<?php echo htmlspecialchars($h['DESCRIPCION'] ?? ''); ?>">
                            <?php echo htmlspecialchars(mb_substr($h['DESCRIPCION'] ?? '', 0, 50)); ?><?php echo (mb_strlen($h['DESCRIPCION'] ?? '') > 50) ? '…' : ''; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge <?php echo $bc; ?>" style="font-size:0.65rem;"><?php echo htmlspecialchars($est); ?></span>
                        </td>
                        <td class="text-center small"><?php echo enmascararfecha($h['FECHA'] ?? ''); ?></td>
                        <td class="text-end small">U$D <?php echo number_format((float)($h['PRECIO_UNITARIO'] ?? 0), 2, ',', '.'); ?></td>
                        <td class="text-center small"><?php echo number_format((float)($h['CANT_SOL'] ?? 0), 0); ?></td>
                        <td class="text-center small"><?php echo number_format((float)($h['CANT_AUT'] ?? 0), 0); ?></td>
                        <td class="small text-nowrap" style="border-left:2px solid #6ec9a0;"><?php echo htmlspecialchars($h['ORDEN_COMPRA'] ?? ''); ?></td>
                        <td class="text-end small fw-semibold"><?php echo ($moc > 0) ? 'U$D ' . number_format($moc, 2, ',', '.') : '—'; ?></td>
                        <td class="small"><?php echo htmlspecialchars(mb_substr($h['PROVEEDOR'] ?? '', 0, 30)); ?></td>
                        <td class="small"><?php echo htmlspecialchars($h['UNIDAD_NOMBRE'] ?? ''); ?></td>
                    </tr>
<?php endforeach; ?>
                    <tr class="table-dark fw-bold">
                        <td colspan="9" class="text-end">TOTAL OC</td>
                        <td class="text-end">U$D <?php echo number_format($gt_monto_oc, 2, ',', '.'); ?></td>
                        <td colspan="2"></td>
                    </tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ESPECIFICACIONES TÉCNICAS (EETT) Y JUSTIFICACIÓN -->
<?php
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

$tiene_eett = false;
foreach ($eett_campos as $k => $v) {
    if (!empty($eett[strtoupper($k)] ?? '')) { $tiene_eett = true; break; }
}
$tiene_justif = !empty($eett['JUSTIFICACION_COMPRA'] ?? '');
?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark bg-opacity-75 text-white py-2 d-flex justify-content-between align-items-center" role="button" data-bs-toggle="collapse" data-bs-target="#collapseEETT">
        <h6 class="mb-0">
            <i class="bi bi-file-earmark-ruled me-2"></i>Especificaciones Técnicas (EETT)
            <?php if ($tiene_eett): ?><span class="badge bg-success ms-2" style="font-size:0.6rem;">Completado</span><?php endif; ?>
        </h6>
        <i class="bi bi-chevron-down"></i>
    </div>
    <div class="collapse <?php echo ($tiene_eett || $tiene_justif) ? 'show' : ''; ?>" id="collapseEETT">
        <div class="card-body">
            <form method="post" action="index.php?estado=<?php echo $estado; ?>&accion=detalle_matricula&mat=<?php echo urlencode($matricula); ?>">
                <input type="hidden" name="accion_eett" value="guardar">

                <!-- Ítem de Gasto y Categoría -->
                <h6 class="fw-bold mb-2"><i class="bi bi-tags me-1"></i>Clasificación</h6>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small mb-1">Ítem de Gasto</label>
                        <select class="form-select form-select-sm" id="sel_item" onchange="actualizarCategorias()">
                            <option value="">— Seleccionar ítem —</option>
                            <?php foreach ($items_combo as $it): ?>
                                <option value="<?php echo $it['ID']; ?>" <?php echo ($it['ID'] == $item_id_actual) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($it['CODIGO'] . ' - ' . $it['NOMBRE']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small mb-1">Categoría</label>
                        <select class="form-select form-select-sm <?php echo empty($cat_id_actual) ? 'border-warning' : 'border-success'; ?>"
                                name="id_categoria" id="sel_categoria">
                            <option value="null">— Sin categoría —</option>
                        </select>
                    </div>
                </div>

                <hr class="my-3">

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0"><i class="bi bi-file-earmark-ruled me-1"></i>Campos EETT</h6>
                    <button type="button" class="btn btn-outline-info btn-sm" id="btnIaEETT" onclick="iaCompletarEETT()">
                        <i class="bi bi-robot me-1"></i>Auto-completar con IA
                    </button>
                </div>
                <div id="iaEettMsg" class="d-none mb-2"></div>
                <div class="row g-3">
                    <?php foreach ($eett_campos as $campo_key => $campo_cfg):
                        $val = htmlspecialchars($eett[strtoupper($campo_key)] ?? '');
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
                              placeholder="Describa la justificación de compra para esta matrícula..."><?php echo htmlspecialchars($eett['JUSTIFICACION_COMPRA'] ?? ''); ?></textarea>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-floppy me-1"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var catsPorItem = <?php echo json_encode($cats_por_item_id, JSON_UNESCAPED_UNICODE); ?>;
var catIdActual = '<?php echo addslashes($cat_id_actual); ?>';

function actualizarCategorias() {
    var itemId = document.getElementById('sel_item').value;
    var sel = document.getElementById('sel_categoria');
    sel.innerHTML = '<option value="null">— Sin categoría —</option>';
    if (itemId && catsPorItem[itemId]) {
        catsPorItem[itemId].forEach(function(c) {
            var opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.codigo + ' - ' + c.nombre;
            if (c.id == catIdActual) opt.selected = true;
            sel.appendChild(opt);
        });
    }
    sel.className = sel.value && sel.value !== 'null'
        ? 'form-select form-select-sm border-success'
        : 'form-select form-select-sm border-warning';
}
// Inicializar al cargar
actualizarCategorias();

function iaCompletarEETT() {
    var btn = document.getElementById('btnIaEETT');
    var msgDiv = document.getElementById('iaEettMsg');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Consultando IA...';

    msgDiv.className = 'alert alert-info small mb-2';
    msgDiv.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Consultando al modelo <strong><?php echo defined("OLLAMA_MODEL") ? OLLAMA_MODEL : "qwen3.5:9b"; ?></strong>... Esto puede tardar varios segundos.';

    var formData = new FormData();
    formData.append('matricula', '<?php echo addslashes($matricula); ?>');

    fetch('web/mod/configuracion/matricula_eett_ia_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(function(resp) { return resp.json(); })
    .then(function(data) {
        if (!data.ok) {
            msgDiv.className = 'alert alert-danger small mb-2';
            msgDiv.innerHTML = '<i class="bi bi-x-circle me-1"></i>' + (data.error || 'Error desconocido');
            if (data.raw) msgDiv.innerHTML += '<br><small class="text-muted">Respuesta cruda: ' + data.raw.substring(0, 200) + '</small>';
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-robot me-1"></i>Reintentar IA';
            return;
        }

        // Llenar los campos del formulario con la respuesta
        var campos = data.campos;
        var rellenados = 0;
        for (var key in campos) {
            var el = document.querySelector('[name="' + key + '"]');
            if (el && campos[key]) {
                // Solo rellenar si está vacío o preguntar
                if (!el.value || el.value.trim() === '') {
                    el.value = campos[key];
                    el.style.borderColor = '#0dcaf0';
                    rellenados++;
                } else {
                    // Si ya tiene valor, marcar con borde naranja para revisión
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
