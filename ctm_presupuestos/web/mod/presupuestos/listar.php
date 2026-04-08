<?php
/**
 * ============================================================================
 * Listar Presupuestos
 * ============================================================================
 * ID menú: 202 | Tablas: ciclo_presupuesto, ciclo_periodo_fiscal, ciclo_unidad_organizativa
 * Tabla filtrable con todos los presupuestos. Punto de entrada para
 * acceder al detalle, comparativo o acciones de cada presupuesto.
 * Filtro por rol: operador/jefe ve su unidad, gerente su área, director/admin todo.
 * ============================================================================
 */


$db  = '1';
$msg = '';

// ── Detectar datos K2B disponibles para importar ────────────────────────────
// Solo último registro por campo0 (evita duplicados por re-ejecución del scraper)
// Solo filas formato directo YY-CC-CODE-DESC, match exacto del 3er segmento
$sql_k2b_disponible = "SELECT
        CONCAT('20', SUBSTRING(k.campo0, 1, 2)) AS K2B_ANIO,
        CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(k.campo0, '-', 2), '-', -1) AS UNSIGNED) AS K2B_CC,
        COUNT(*) AS CANT_ITEMS,
        SUM(k.importe_planificado) AS TOTAL_PLANIFICADO,
        COUNT(DISTINCT ig.codigo) AS CANT_CODIGOS
    FROM pres_k2b_presupuestos k
    JOIN (
        SELECT MAX(id) AS max_id FROM pres_k2b_presupuestos
        WHERE campo0 REGEXP '^[0-9]{2}-[0-9]+-[0-9]+-'
        GROUP BY campo0
    ) ult ON k.id = ult.max_id
    JOIN ciclo_item_gasto ig
        ON SUBSTRING_INDEX(SUBSTRING_INDEX(k.campo0, '-', 3), '-', -1) = ig.codigo
    GROUP BY K2B_ANIO, K2B_CC
    ORDER BY K2B_ANIO DESC, K2B_CC;";
$result_k2b = sql_do_query($sql_k2b_disponible, $db, $usu);
$k2b_disponibles = [];
while ($rk = sql_do_array($result_k2b)) {
    $k2b_disponibles[] = $rk;
}

// ── Filtros GET/POST ─────────────────────────────────────────────────────────
$filtro_periodo = Enmascarar_get('periodo');
$filtro_unidad  = Enmascarar_get('unidad');
$filtro_estado  = Enmascarar_get('filtro_estado');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['filtro_periodo']))  $filtro_periodo = Enmascarar_post('filtro_periodo');
    if (!empty($_POST['filtro_unidad']))   $filtro_unidad  = Enmascarar_post('filtro_unidad');
    if (!empty($_POST['filtro_estado']))   $filtro_estado  = Enmascarar_post('filtro_estado');
}

// ── Restricción por rol ──────────────────────────────────────────────────────
$rol_usuario    = $_SESSION['ciclo_rol_efectivo'] ?? '';
$unidad_usuario = $_SESSION['id_unidad'] ?? '';

$where_unidad = '';
if (in_array($rol_usuario, ['operador', 'jefe_unidad']) && !empty($unidad_usuario)) {
    $where_unidad = "AND pr.id_unidad = '$unidad_usuario'";
    $filtro_unidad = $unidad_usuario;
} elseif ($rol_usuario === 'gerente' && !empty($unidad_usuario)) {
    // Gerente ve su unidad y las hijas directas
    $where_unidad = "AND (pr.id_unidad = '$unidad_usuario'
                     OR pr.id_unidad IN (SELECT id FROM ciclo_unidad_organizativa WHERE id_padre = '$unidad_usuario'))";
}

// ── Construir filtros SQL ────────────────────────────────────────────────────
$where_extra = '';
if (!empty($filtro_periodo) && $filtro_periodo !== 'null') {
    $where_extra .= " AND pr.id_periodo = '$filtro_periodo'";
}
if (!empty($filtro_unidad) && $filtro_unidad !== 'null' && empty($where_unidad)) {
    $where_extra .= " AND pr.id_unidad = '$filtro_unidad'";
}
if (!empty($filtro_estado) && $filtro_estado !== 'null') {
    $where_extra .= " AND pr.estado = '$filtro_estado'";
}

$sql_list = "SELECT pr.*, pf.anio, uo.nombre AS UNIDAD
             FROM ciclo_presupuesto pr
             JOIN ciclo_periodo_fiscal pf ON pr.id_periodo = pf.id
             JOIN ciclo_unidad_organizativa uo ON pr.id_unidad = uo.id
             WHERE 1=1 $where_unidad $where_extra
             ORDER BY pf.anio DESC, uo.nombre;";
$result_list = sql_do_query($sql_list, $db, $usu);

// Combos para filtros
$sql_combo_per = "SELECT id AS ID, CONCAT(anio, ' (', estado, ')') AS NOMBRE FROM ciclo_periodo_fiscal ORDER BY anio DESC;";
$sql_combo_uni = "SELECT id AS ID, nombre AS NOMBRE FROM ciclo_unidad_organizativa WHERE 1=1 ORDER BY nombre;";

?>

<?php echo generarTituloSeccion('Presupuestos', 'bi bi-journal-text'); ?>

<!-- FILTROS -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="post" action="index.php?estado=<?php echo $estado; ?>" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Período</label>
                <?php echo imp_combo_w_oc('filtro_periodo', $sql_combo_per, $db, $usu, $filtro_periodo); ?>
            </div>
            <?php if (empty($where_unidad)): ?>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Unidad</label>
                <?php echo imp_combo_w_oc('filtro_unidad', $sql_combo_uni, $db, $usu, $filtro_unidad); ?>
            </div>
            <?php endif; ?>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Estado</label>
                <select class="form-select" name="filtro_estado" onchange="this.form.submit()">
                    <option value="null">Todos</option>
                    <option value="borrador" <?php echo ($filtro_estado === 'borrador') ? 'selected' : ''; ?>>Borrador</option>
                    <option value="en_revision" <?php echo ($filtro_estado === 'en_revision') ? 'selected' : ''; ?>>En Revisión</option>
                    <option value="aprobado" <?php echo ($filtro_estado === 'aprobado') ? 'selected' : ''; ?>>Aprobado</option>
                    <option value="rechazado" <?php echo ($filtro_estado === 'rechazado') ? 'selected' : ''; ?>>Rechazado</option>
                </select>
            </div>
            <div class="col-md-3">
                <a href="index.php?estado=<?php echo $estado; ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-circle me-1"></i>Limpiar filtros
                </a>
            </div>
        </form>
    </div>
</div>

<!-- IMPORTAR K2B -->
<?php if (!empty($k2b_disponibles)): ?>
<div class="card shadow-sm mb-3 border-warning">
    <div class="card-header bg-warning bg-opacity-25 py-2">
        <h6 class="mb-0"><i class="bi bi-cloud-arrow-down me-2"></i>Importar presupuestos desde K2B</h6>
    </div>
    <div class="card-body py-2">
        <p class="small text-muted mb-2">
            Se detectaron datos K2B que coinciden con códigos de <code>ciclo_item_gasto</code>.
            Al importar se agrupan por item y se crean como presupuesto aprobado.
        </p>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center">Año</th>
                        <th class="text-center">CC</th>
                        <th class="text-center">Filas K2B</th>
                        <th class="text-center">Códigos</th>
                        <th class="text-end">Total Planificado</th>
                        <th class="text-center">Acción</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($k2b_disponibles as $k2b_row): ?>
                    <tr>
                        <td class="text-center fw-bold"><?php echo htmlspecialchars($k2b_row['K2B_ANIO']); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($k2b_row['K2B_CC']); ?></td>
                        <td class="text-center"><?php echo (int)$k2b_row['CANT_ITEMS']; ?></td>
                        <td class="text-center"><span class="badge bg-info"><?php echo (int)$k2b_row['CANT_CODIGOS']; ?> items</span></td>
                        <td class="text-end">$ <?php echo number_format((float)$k2b_row['TOTAL_PLANIFICADO'], 2, ',', '.'); ?></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-warning btn-sm btn-import-k2b"
                                    data-anio="<?php echo htmlspecialchars($k2b_row['K2B_ANIO']); ?>"
                                    data-cc="<?php echo htmlspecialchars($k2b_row['K2B_CC']); ?>">
                                <i class="bi bi-download me-1"></i>Importar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
document.querySelectorAll('.btn-import-k2b').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var anio = this.dataset.anio;
        var cc   = this.dataset.cc;
        var btnEl = this;
        Swal.fire({
            title: '¿Importar presupuesto K2B?',
            html: 'Se creará o actualizará el presupuesto aprobado para <b>Año ' + anio + ' / CC ' + cc + '</b>.<br>' +
                  '<small class="text-muted">Si ya existe un presupuesto para ese período/unidad, será reemplazado.</small>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, importar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#418CBA',
            cancelButtonColor: '#dc3545',
            reverseButtons: true
        }).then(function(result) {
            if (!result.isConfirmed) return;
            btnEl.disabled = true;
            btnEl.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Importando...';
            fetch('web/mod/presupuestos/ajax_importar_k2b.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'anio=' + encodeURIComponent(anio) + '&cc=' + encodeURIComponent(cc)
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.ok) {
                    var tipo = data.es_nuevo ? 'creado' : 'actualizado';
                    Swal.fire({
                        title: 'Importación exitosa',
                        html: 'Presupuesto <b>' + tipo + '</b> para Año ' + anio + ' / CC ' + cc + '.<br>' +
                              'Partidas: <b>' + data.cant_partidas + '</b><br>' +
                              'Monto total: <b>$ ' + Number(data.monto_total).toLocaleString('es-AR', {minimumFractionDigits:2}) + '</b>',
                        icon: 'success'
                    }).then(function() { location.reload(); });
                } else {
                    Swal.fire('Error', data.error || 'Error desconocido', 'error');
                    btnEl.disabled = false;
                    btnEl.innerHTML = '<i class="bi bi-download me-1"></i>Importar';
                }
            })
            .catch(function(err) {
                Swal.fire('Error de conexión', err.message, 'error');
                btnEl.disabled = false;
                btnEl.innerHTML = '<i class="bi bi-download me-1"></i>Importar';
            });
        });
    });
});
</script>
<?php endif; ?>

<!-- TABLA -->
<div class="table-responsive">
    <table class="table table-hover table-pade">
        <?php echo generarTablaHeader(['Período', 'Unidad', 'Monto Total', 'Estado', 'Creación', 'Acciones']); ?>
        <tbody>
<?php
$n = 0;
while ($row = sql_do_array($result_list)):
    $n++;
    // Badge de estado
    $estado_badge = '';
    switch ($row['ESTADO']) {
        case 'borrador':    $estado_badge = '<span class="badge bg-secondary">Borrador</span>'; break;
        case 'en_revision': $estado_badge = '<span class="badge bg-warning text-dark">En Revisión</span>'; break;
        case 'aprobado':    $estado_badge = '<span class="badge bg-success">Aprobado</span>'; break;
        case 'rechazado':   $estado_badge = '<span class="badge bg-danger">Rechazado</span>'; break;
        default:            $estado_badge = '<span class="badge bg-light text-dark">' . htmlspecialchars($row['ESTADO']) . '</span>';
    }
?>
            <tr class="<?php echo claseFilaAlternada($n); ?>">
                <td class="text-center fw-bold"><?php echo htmlspecialchars($row['ANIO']); ?></td>
                <td><?php echo htmlspecialchars($row['UNIDAD']); ?></td>
                <td class="text-end">$ <?php echo number_format((float)$row['MONTO_TOTAL'], 2, ',', '.'); ?></td>
                <td class="text-center"><?php echo $estado_badge; ?></td>
                <td class="text-center small"><?php echo enmascararfecha($row['AUDITORIA_FECHA'] ?? ''); ?></td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                        <a href="index.php?estado=203&id=<?php echo $row['ID']; ?>"
                           class="btn btn-pade-alt btn-sm btn-accion" title="Ver detalle de partidas">
                            <i class="bi bi-eye me-1"></i>Detalle
                        </a>
                        <a href="index.php?estado=204&id=<?php echo $row['ID']; ?>"
                           class="btn btn-outline-info btn-sm" title="Comparativo año anterior">
                            <i class="bi bi-arrow-left-right me-1"></i>Comparar
                        </a>
                        <a href="index.php?estado=205&periodo=<?php echo $row['ID_PERIODO']; ?>&unidad=<?php echo $row['ID_UNIDAD']; ?>"
                           class="btn btn-outline-warning btn-sm" title="SR de K2B vinculadas">
                            <i class="bi bi-box-arrow-in-down-right me-1"></i>K2B
                        </a>
                    </div>
                </td>
            </tr>
<?php endwhile; ?>
<?php if ($n === 0): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No se encontraron presupuestos con los filtros seleccionados.</td></tr>
<?php endif; ?>
        </tbody>
    </table>
</div>