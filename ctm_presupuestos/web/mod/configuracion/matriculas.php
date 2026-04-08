<?php
/**
 * ============================================================================
 * Matrículas K2B - Consulta y Asignación de Categoría
 * ============================================================================
 * Muestra las matrículas (productos) utilizadas en compras registradas en
 * pres_k2b_sr_logs, agrupadas por ítem de gasto (ciclo_item_gasto).
 * Permite asignar a cada matrícula una categoría de gasto
 * (ciclo_categoria_gasto) dentro de su ítem correspondiente.
 * Tabla de mapeo: pres_k2b_matricula_categoria
 * ============================================================================
 */

$db = '1';
$msg = '';

// ── Sub-página: Detalle de una matrícula individual ──────────────────────────
if (isset($_GET['accion']) && $_GET['accion'] === 'detalle_matricula' && !empty($_GET['mat'])) {
    include_once __DIR__ . '/detalle_matricula.php';
    return;
}

// Filtro por ítem de gasto
$filtro_item = Enmascarar_get('item');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['filtro_item'])) {
    $filtro_item = Enmascarar_post('filtro_item');
}

// Filtro por búsqueda de texto
$buscar = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buscar'])) {
    $buscar = Enmascarar_post('buscar');
}

// ═══════════════════════════════════════════════════════════════════════════════
// PROCESAR POST: Asignar categoría a una matrícula
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['accion_post']) && $_POST['accion_post'] === 'asignar_categoria') {
    $mat = addslashes(Enmascarar_post('matricula'));
    $id_cat = Enmascarar_post('id_categoria');

    if (empty($mat)) {
        $msg = generarAlertJS('Error', 'Matrícula no válida.', 'error');
    } else {
        if (empty($id_cat) || $id_cat === 'null') {
            // Quitar asignación
            sql_do_query("DELETE FROM pres_k2b_matricula_categoria WHERE matricula = '$mat';", $db, $usu);
            $msg = generarAlertJS('Categoría quitada', "Se quitó la categoría de la matrícula $mat.", 'info');
        } else {
            // Upsert
            $sql_upsert = "INSERT INTO pres_k2b_matricula_categoria (matricula, id_categoria, auditoria_usu)
                           VALUES ('$mat', '$id_cat', '$usu')
                           ON DUPLICATE KEY UPDATE id_categoria = '$id_cat', auditoria_usu = '$usu';";
            sql_do_query($sql_upsert, $db, $usu);
            $msg = generarAlertJS('Categoría asignada', "Categoría actualizada para la matrícula $mat.", 'success');
        }
    }
}

echo $msg;

// ═══════════════════════════════════════════════════════════════════════════════
// CONSULTA PRINCIPAL
// ═══════════════════════════════════════════════════════════════════════════════

$where_parts = ["sr.matricula IS NOT NULL", "sr.matricula != ''"];

if (!empty($filtro_item) && $filtro_item !== 'null') {
    $where_parts[] = "ig.codigo = '$filtro_item'";
}
if (!empty($buscar)) {
    $buscar_esc = addslashes($buscar);
    $where_parts[] = "(sr.matricula LIKE '%$buscar_esc%' OR sr.descripcion LIKE '%$buscar_esc%')";
}

$where_sql = implode(' AND ', $where_parts);

$sql_matriculas = "SELECT sr.matricula AS MATRICULA,
                          MIN(sr.descripcion) AS DESCRIPCION,
                          ig.codigo AS ITEM_CODIGO,
                          ig.nombre AS ITEM_NOMBRE,
                          ig.id AS ITEM_ID,
                          COUNT(DISTINCT sr.codigo) AS CANT_SR,
                          SUM(sr.monto_linea_oc_usd) AS TOTAL_USD,
                          MAX(sr.fecha) AS ULTIMA_COMPRA,
                          MIN(sr.matricula_link) AS MATRICULA_LINK,
                          mc.id_categoria AS CAT_ID,
                          cg.nombre AS CAT_NOMBRE,
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
                   WHERE $where_sql
                   GROUP BY sr.matricula, ig.codigo, ig.nombre, ig.id, mc.id_categoria, cg.nombre
                   ORDER BY ig.codigo, sr.matricula;";

$result_list = sql_do_query($sql_matriculas, $db, $usu);

// Totales
$total_matriculas = 0;
$total_sr = 0;
$total_usd = 0.0;
$total_sin_cat = 0;

// Almacenar resultados para contar y mostrar
$filas = [];
while ($row = sql_do_array($result_list)) {
    $filas[] = $row;
    $total_matriculas++;
    $total_sr += (int)($row['CANT_SR'] ?? 0);
    $total_usd += (float)($row['TOTAL_USD'] ?? 0);
    if (empty($row['CAT_ID'])) $total_sin_cat++;
}

// Contar matrículas sin EETT completados
$sql_sin_eett = "SELECT COUNT(DISTINCT sr.matricula) AS TOTAL
                 FROM pres_k2b_sr_logs sr
                 LEFT JOIN pres_k2b_matricula_categoria mc ON mc.matricula = sr.matricula
                 WHERE sr.matricula IS NOT NULL AND sr.matricula != ''
                   AND (mc.eett_denominacion IS NULL OR mc.eett_denominacion = '')";
$res_sin_eett = sql_do_query($sql_sin_eett, $db, $usu);
$row_sin_eett = sql_do_array($res_sin_eett);
$total_sin_eett = (int)($row_sin_eett['TOTAL'] ?? 0);

// Cargar categorías agrupadas por ítem (para los selects)
$sql_cats = "SELECT cg.id, cg.id_item, cg.codigo, cg.nombre, ig.codigo AS item_codigo
             FROM ciclo_categoria_gasto cg
             JOIN ciclo_item_gasto ig ON cg.id_item = ig.id
             WHERE cg.activo = 1
             ORDER BY ig.codigo, cg.codigo;";
$res_cats = sql_do_query($sql_cats, $db, $usu);
$categorias_por_item = [];
while ($cat = sql_do_array($res_cats)) {
    $categorias_por_item[$cat['ITEM_CODIGO']][] = $cat;
}

// Combo de ítems para filtro
$sql_combo_items = "SELECT CAST(codigo AS CHAR) AS ID, CONCAT(codigo, ' - ', nombre) AS NOMBRE
                    FROM ciclo_item_gasto ORDER BY codigo;";
?>

<?php echo generarTituloSeccion('Matrículas K2B', 'bi bi-box-seam'); ?>

<!-- Filtros -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold small mb-1">Filtrar por Ítem de Gasto</label>
                <?php echo imp_combo_w_oc('filtro_item', $sql_combo_items, $db, $usu, $filtro_item); ?>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold small mb-1">Buscar matrícula / descripción</label>
                <input type="text" class="form-control form-control-sm" name="buscar"
                       value="<?php echo htmlspecialchars($buscar); ?>"
                       placeholder="Código o descripción..."
                       onchange="this.form.submit();">
            </div>
            <div class="col-md-4 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-pade btn-sm"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                <a href="index.php?estado=<?php echo $estado; ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg me-1"></i>Limpiar</a>
                <?php if ($total_sin_cat > 0): ?>
                <button type="button" class="btn btn-warning btn-sm" id="btnIACategorizar" onclick="iaCategorizar()">
                    <i class="bi bi-robot me-1"></i>Auto-categorizar con IA <span class="badge bg-dark"><?php echo $total_sin_cat; ?></span>
                </button>
                <?php endif; ?>
                <?php if ($total_sin_eett > 0): ?>
                <button type="button" class="btn btn-info btn-sm" id="btnIAEettMasivo" onclick="iaEettMasivo()">
                    <i class="bi bi-robot me-1"></i>Auto-completar EETT con IA <span class="badge bg-dark"><?php echo $total_sin_eett; ?></span>
                </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Resumen rápido -->
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-primary border-4">
            <div class="card-body py-2">
                <div class="text-muted small">Matrículas</div>
                <div class="fs-4 fw-bold text-primary"><?php echo number_format($total_matriculas, 0, ',', '.'); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-warning border-4">
            <div class="card-body py-2">
                <div class="text-muted small">Sin categoría</div>
                <div class="fs-4 fw-bold text-warning"><?php echo number_format($total_sin_cat, 0, ',', '.'); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-info border-4">
            <div class="card-body py-2">
                <div class="text-muted small">SR vinculadas</div>
                <div class="fs-4 fw-bold text-info"><?php echo number_format($total_sr, 0, ',', '.'); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de matrículas -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Matrícula</th>
                        <th>Descripción</th>
                        <th class="text-center">Ítem</th>
                        <th class="text-center">SR</th>
                        <th class="text-end">P.Unit. (USD)</th>
                        <th class="text-center">Última Compra</th>
                        <th>Categoría</th>
                        <th style="width:40px;"></th>
                    </tr>
                </thead>
                <tbody>
<?php
$n = 0;
$item_actual = '';
foreach ($filas as $row):
    $n++;
    $mat_esc = htmlspecialchars($row['MATRICULA']);
    $item_cod = $row['ITEM_CODIGO'];
    $cat_id_actual = $row['CAT_ID'] ?? '';
    $cats_item = $categorias_por_item[$item_cod] ?? [];

    // Separador visual cuando cambia el ítem
    if ($item_cod !== $item_actual):
        $item_actual = $item_cod;
?>
                    <tr class="table-primary">
                        <td colspan="8" class="fw-bold small py-1">
                            <i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($item_cod . ' - ' . $row['ITEM_NOMBRE']); ?>
                        </td>
                    </tr>
<?php endif; ?>
                    <tr class="<?php echo claseFilaAlternada($n); ?><?php echo empty($cat_id_actual) ? ' border-start border-3 border-warning' : ''; ?>">
                        <td class="fw-semibold text-nowrap">
                            <?php if (!empty($row['MATRICULA_LINK']) && $row['MATRICULA_LINK'] !== 'NULL'): ?>
                                <a href="<?php echo htmlspecialchars($row['MATRICULA_LINK']); ?>" target="_blank" class="text-decoration-none">
                                    <?php echo $mat_esc; ?>
                                    <i class="bi bi-box-arrow-up-right" style="font-size:0.7rem;"></i>
                                </a>
                            <?php else: ?>
                                <?php echo $mat_esc; ?>
                            <?php endif; ?>
                        </td>
                        <td class="small" title="<?php echo htmlspecialchars($row['DESCRIPCION'] ?? ''); ?>">
                            <?php echo htmlspecialchars(mb_substr($row['DESCRIPCION'] ?? '', 0, 70)); ?><?php echo (mb_strlen($row['DESCRIPCION'] ?? '') > 70) ? '...' : ''; ?>
                        </td>
                        <td class="text-center"><span class="badge bg-info text-dark"><?php echo htmlspecialchars($item_cod); ?></span></td>
                        <td class="text-center"><span class="badge bg-primary"><?php echo (int)($row['CANT_SR'] ?? 0); ?></span></td>
                        <td class="text-end small">
                            <?php if (!empty($row['ULTIMO_PU_OC']) && (float)$row['ULTIMO_PU_OC'] > 0): ?>
                                <span class="fw-semibold">U$D <?php echo number_format((float)$row['ULTIMO_PU_OC'], 2, ',', '.'); ?></span>
                            <?php elseif (!empty($row['ULTIMO_PU_SOL']) && (float)$row['ULTIMO_PU_SOL'] > 0): ?>
                                <span class="fw-semibold text-warning" title="Valor solicitado (sin OC)">U$D <?php echo number_format((float)$row['ULTIMO_PU_SOL'], 2, ',', '.'); ?></span>
                                <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size:0.65rem;" title="Precio solicitado, no OC"></i>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center small"><?php echo !empty($row['ULTIMA_COMPRA']) ? htmlspecialchars($row['ULTIMA_COMPRA']) : '—'; ?></td>
                        <td>
                            <form method="post" action="index.php?estado=<?php echo $estado; ?>" class="d-flex gap-1 align-items-center">
                                <input type="hidden" name="accion_post" value="asignar_categoria">
                                <input type="hidden" name="matricula" value="<?php echo $mat_esc; ?>">
                                <?php if (!empty($filtro_item) && $filtro_item !== 'null'): ?>
                                    <input type="hidden" name="filtro_item" value="<?php echo htmlspecialchars($filtro_item); ?>">
                                <?php endif; ?>
                                <?php if (!empty($buscar)): ?>
                                    <input type="hidden" name="buscar" value="<?php echo htmlspecialchars($buscar); ?>">
                                <?php endif; ?>
                                <select name="id_categoria" class="form-select form-select-sm <?php echo empty($cat_id_actual) ? 'border-warning' : 'border-success'; ?>"
                                        style="min-width:180px;font-size:0.78rem;" onchange="this.form.submit();">
                                    <option value="null">— Sin categoría —</option>
                                    <?php foreach ($cats_item as $cat): ?>
                                        <option value="<?php echo $cat['ID']; ?>" <?php echo ($cat['ID'] == $cat_id_actual) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['CODIGO'] . ' - ' . $cat['NOMBRE']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td class="text-center">
                            <a href="index.php?estado=<?php echo $estado; ?>&accion=detalle_matricula&mat=<?php echo urlencode($row['MATRICULA']); ?>" class="btn btn-sm btn-outline-primary py-0 px-1" title="Ver detalle">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
<?php endforeach; ?>
<?php if ($n === 0): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No se encontraron matrículas<?php echo (!empty($filtro_item) && $filtro_item !== 'null') ? ' para este ítem' : ''; ?><?php echo !empty($buscar) ? " con el criterio '$buscar'" : ''; ?>.</td></tr>
<?php endif; ?>
<?php if ($n > 0): ?>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Panel de resultados IA Categorización (oculto por defecto) -->
<div id="iaResultado" class="card shadow-sm mt-3 d-none">
    <div class="card-header py-2" id="iaResultadoHeader">
        <h6 class="mb-0"><i class="bi bi-robot me-2"></i>Resultado de Auto-categorización IA</h6>
    </div>
    <div class="card-body p-0" id="iaResultadoBody"></div>
</div>

<!-- Panel de resultados IA EETT (oculto por defecto) -->
<div id="iaResultadoEett" class="card shadow-sm mt-3 d-none">
    <div class="card-header py-2" id="iaResultadoEettHeader">
        <h6 class="mb-0"><i class="bi bi-robot me-2"></i>Resultado de Auto-completar EETT con IA</h6>
    </div>
    <div class="card-body p-0" id="iaResultadoEettBody"></div>
</div>

<script>
function iaCategorizar() {
    var btn = document.getElementById('btnIACategorizar');
    var panel = document.getElementById('iaResultado');
    var header = document.getElementById('iaResultadoHeader');
    var body = document.getElementById('iaResultadoBody');

    // Deshabilitar botón y mostrar spinner
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Procesando con IA...';

    // Mostrar panel con indicador de progreso
    panel.classList.remove('d-none');
    header.className = 'card-header py-2 bg-info text-dark';
    body.innerHTML = '<div class="p-3 text-center"><div class="spinner-border text-primary mb-2"></div>' +
        '<p class="mb-0 text-muted">Consultando al modelo <strong><?php echo defined("OLLAMA_MODEL") ? OLLAMA_MODEL : "phi3:14b"; ?></strong>...<br>' +
        '<small>Esto puede tardar varios segundos según la cantidad de matrículas.</small></p></div>';

    // Preparar datos
    var formData = new FormData();
    var filtroItem = '<?php echo addslashes($filtro_item ?? ''); ?>';
    if (filtroItem && filtroItem !== 'null') {
        formData.append('filtro_item', filtroItem);
    }

    // AJAX call
    fetch('web/mod/configuracion/matriculas_ia_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(function(resp) { return resp.json(); })
    .then(function(data) {
        if (!data.ok) {
            header.className = 'card-header py-2 bg-danger text-white';
            body.innerHTML = '<div class="p-3"><i class="bi bi-x-circle me-2"></i>' + (data.error || data.msg || 'Error desconocido') + '</div>';
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-robot me-1"></i>Reintentar IA';
            return;
        }

        // Resumen
        var html = '<div class="p-3">';
        html += '<div class="row g-2 mb-3">';
        html += '<div class="col-auto"><span class="badge bg-primary fs-6">' + data.total + ' procesadas</span></div>';
        html += '<div class="col-auto"><span class="badge bg-success fs-6">' + data.asignadas + ' asignadas</span></div>';
        html += '<div class="col-auto"><span class="badge bg-danger fs-6">' + data.errores + ' errores</span></div>';
        html += '</div>';

        if (data.detalle && data.detalle.length > 0) {
            html += '<div class="table-responsive"><table class="table table-sm table-bordered mb-0">';
            html += '<thead class="table-light"><tr><th>Matrícula</th><th>Resultado</th><th>Detalle</th></tr></thead><tbody>';
            data.detalle.forEach(function(d) {
                var badge = d.status === 'ok' ? 'bg-success' : (d.status === 'skip' ? 'bg-secondary' : 'bg-danger');
                var label = d.status === 'ok' ? 'Asignada' : (d.status === 'skip' ? 'Omitida' : 'Error');
                var info = d.status === 'ok' ? d.cat_nombre : (d.msg || '');
                html += '<tr><td class="small fw-semibold">' + d.matricula + '</td>';
                html += '<td><span class="badge ' + badge + '">' + label + '</span></td>';
                html += '<td class="small">' + info + '</td></tr>';
            });
            html += '</tbody></table></div>';
        }

        if (data.msg) {
            html += '<p class="text-muted mt-2 mb-0"><i class="bi bi-info-circle me-1"></i>' + data.msg + '</p>';
        }

        html += '</div>';

        header.className = 'card-header py-2 bg-success text-white';
        body.innerHTML = html;

        // Restaurar botón con opción de recargar
        btn.disabled = false;
        if (data.asignadas > 0) {
            btn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i>Recargar página';
            btn.onclick = function() { location.reload(); };
        } else {
            btn.innerHTML = '<i class="bi bi-robot me-1"></i>Auto-categorizar con IA';
        }
    })
    .catch(function(err) {
        header.className = 'card-header py-2 bg-danger text-white';
        body.innerHTML = '<div class="p-3"><i class="bi bi-x-circle me-2"></i>Error de conexión: ' + err.message + '<br><small>Verifique que Ollama esté corriendo en <?php echo defined("OLLAMA_HOST") ? OLLAMA_HOST : "http://localhost:11434"; ?></small></div>';
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-robot me-1"></i>Reintentar IA';
    });
}

function iaEettMasivo() {
    var btn = document.getElementById('btnIAEettMasivo');
    var panel = document.getElementById('iaResultadoEett');
    var header = document.getElementById('iaResultadoEettHeader');
    var body = document.getElementById('iaResultadoEettBody');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Obteniendo lista...';

    panel.classList.remove('d-none');
    header.className = 'card-header py-2 bg-info text-dark';
    body.innerHTML = '<div class="p-3 text-center"><div class="spinner-border text-primary mb-2"></div>' +
        '<p class="mb-0 text-muted">Buscando matrículas con EETT pendientes...</p></div>';

    // Paso 1: Obtener lista de matrículas pendientes
    var formData = new FormData();
    var filtroItem = '<?php echo addslashes($filtro_item ?? ''); ?>';
    if (filtroItem && filtroItem !== 'null') {
        formData.append('filtro_item', filtroItem);
    }

    fetch('web/mod/configuracion/matricula_eett_ia_masivo_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(function(resp) { return resp.json(); })
    .then(function(data) {
        if (!data.ok) {
            header.className = 'card-header py-2 bg-danger text-white';
            body.innerHTML = '<div class="p-3"><i class="bi bi-x-circle me-2"></i>' + (data.error || 'Error desconocido') + '</div>';
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-robot me-1"></i>Reintentar EETT IA';
            return;
        }

        if (data.total === 0) {
            header.className = 'card-header py-2 bg-success text-white';
            body.innerHTML = '<div class="p-3"><i class="bi bi-check-circle me-2"></i>No hay matrículas con EETT pendientes.</div>';
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-robot me-1"></i>Auto-completar EETT con IA';
            return;
        }

        // Paso 2: Procesar una por una secuencialmente
        var pendientes = data.pendientes;
        var total = pendientes.length;
        var completadas = 0;
        var errores = 0;

        // Armar tabla de progreso
        var html = '<div class="p-3">';
        html += '<div class="row g-2 mb-3" id="eettResumen">';
        html += '<div class="col-auto"><span class="badge bg-primary fs-6" id="eettBadgeTotal">' + total + ' pendientes</span></div>';
        html += '<div class="col-auto"><span class="badge bg-success fs-6" id="eettBadgeOk">0 completadas</span></div>';
        html += '<div class="col-auto"><span class="badge bg-danger fs-6" id="eettBadgeErr">0 errores</span></div>';
        html += '</div>';
        html += '<div class="progress mb-3" style="height:20px;"><div class="progress-bar bg-info progress-bar-striped progress-bar-animated" id="eettProgress" style="width:0%">0/' + total + '</div></div>';
        html += '<div class="table-responsive"><table class="table table-sm table-bordered mb-0">';
        html += '<thead class="table-light"><tr><th>Matrícula</th><th>Descripción</th><th class="text-center">Estado</th><th>Detalle</th></tr></thead>';
        html += '<tbody id="eettTablaBody">';
        pendientes.forEach(function(p) {
            html += '<tr id="eettRow_' + p.matricula.replace(/[^a-zA-Z0-9]/g,'_') + '">';
            html += '<td class="small fw-semibold">' + p.matricula + '</td>';
            html += '<td class="small">' + (p.descripcion || '') + '</td>';
            html += '<td class="text-center"><span class="badge bg-secondary">Pendiente</span></td>';
            html += '<td class="small">—</td></tr>';
        });
        html += '</tbody></table></div></div>';
        body.innerHTML = html;

        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Procesando 0/' + total + '...';

        // Función para procesar una matrícula
        function procesarUna(idx) {
            if (idx >= total) {
                // Fin
                header.className = 'card-header py-2 bg-success text-white';
                btn.disabled = false;
                if (completadas > 0) {
                    btn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i>Recargar página';
                    btn.onclick = function() { location.reload(); };
                } else {
                    btn.innerHTML = '<i class="bi bi-robot me-1"></i>Auto-completar EETT con IA';
                }
                return;
            }

            var mat = pendientes[idx].matricula;
            var rowId = 'eettRow_' + mat.replace(/[^a-zA-Z0-9]/g,'_');
            var row = document.getElementById(rowId);
            if (row) {
                row.cells[2].innerHTML = '<span class="spinner-border spinner-border-sm text-info"></span>';
                row.cells[3].textContent = 'Consultando IA...';
            }

            var fd = new FormData();
            fd.append('matricula', mat);
            fd.append('guardar', '1');

            fetch('web/mod/configuracion/matricula_eett_ia_ajax.php', {
                method: 'POST',
                body: fd
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.ok) {
                    completadas++;
                    if (row) {
                        row.cells[2].innerHTML = '<span class="badge bg-success">OK</span>';
                        row.cells[3].textContent = res.campos && res.campos.eett_denominacion ? res.campos.eett_denominacion.substring(0, 60) : 'Completado';
                        row.className = 'table-success';
                    }
                } else {
                    errores++;
                    if (row) {
                        row.cells[2].innerHTML = '<span class="badge bg-danger">Error</span>';
                        row.cells[3].textContent = res.error || 'Error desconocido';
                        row.className = 'table-danger';
                    }
                }
            })
            .catch(function(e) {
                errores++;
                if (row) {
                    row.cells[2].innerHTML = '<span class="badge bg-danger">Error</span>';
                    row.cells[3].textContent = e.message;
                    row.className = 'table-danger';
                }
            })
            .finally(function() {
                // Actualizar progreso
                var procesadas = completadas + errores;
                var pct = Math.round((procesadas / total) * 100);
                document.getElementById('eettProgress').style.width = pct + '%';
                document.getElementById('eettProgress').textContent = procesadas + '/' + total;
                document.getElementById('eettBadgeOk').textContent = completadas + ' completadas';
                document.getElementById('eettBadgeErr').textContent = errores + ' errores';
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Procesando ' + procesadas + '/' + total + '...';

                // Siguiente matrícula
                procesarUna(idx + 1);
            });
        }

        // Iniciar procesamiento secuencial
        procesarUna(0);
    })
    .catch(function(err) {
        header.className = 'card-header py-2 bg-danger text-white';
        body.innerHTML = '<div class="p-3"><i class="bi bi-x-circle me-2"></i>Error de conexión: ' + err.message + '<br><small>Verifique que Ollama esté corriendo en <?php echo defined("OLLAMA_HOST") ? OLLAMA_HOST : "http://localhost:11434"; ?></small></div>';
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-robot me-1"></i>Reintentar EETT IA';
    });
}
</script>
